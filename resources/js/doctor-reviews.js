export function registerDoctorReviews(Alpine) {
    Alpine.data('doctorReviews', (config) => ({
        reviews: Array.isArray(config.reviews) ? config.reviews : [],
        average: String(config.average ?? '0.0'),
        total: Number(config.total ?? 0),
        canWrite: Boolean(config.canWrite),
        rating: Number(config.mine?.rating ?? 0),
        hover: Number(config.mine?.rating ?? 0),
        body: typeof config.mine?.body === 'string' ? config.mine.body : '',
        mineId: config.mine?.id ?? null,
        error: '',
        saved: '',
        hint: '',
        sending: false,
        formOpen: Boolean(config.formOpen),
        page: Number(config.page ?? 1),
        lastPage: Number(config.lastPage ?? 1),
        perPage: Number(config.perPage ?? 10),
        pollUrl: config.pollUrl,
        sendUrl: config.sendUrl,
        loginUrl: config.loginUrl,
        draftKey: `doctor-review:${config.doctorId}`,
        starScale: [1, 2, 3, 4, 5],
        timer: null,
        requestId: 0,

        init() {
            this.restoreDraft();
            if (this.formOpen) {
                document.body.style.overflow = 'hidden';
            }
            this.onVisible = () => {
                if (!document.hidden) {
                    this.poll();
                }
            };
            this.timer = window.setInterval(() => this.poll(), 3000);
            document.addEventListener('visibilitychange', this.onVisible);
        },

        destroy() {
            window.clearInterval(this.timer);
            document.removeEventListener('visibilitychange', this.onVisible);
            document.body.style.overflow = '';
        },

        restoreDraft() {
            if (!this.canWrite || this.mineId !== null) {
                return;
            }

            try {
                const saved = window.sessionStorage.getItem(this.draftKey);

                if (!saved) {
                    return;
                }

                const draft = JSON.parse(saved);
                window.sessionStorage.removeItem(this.draftKey);

                if (typeof draft.body === 'string' && draft.body.trim() !== '') {
                    this.body = draft.body;
                }

                const rating = Number(draft.rating);

                if (rating >= 1 && rating <= 5) {
                    this.rating = rating;
                    this.hover = rating;
                }

                if (this.body.trim() !== '') {
                    this.hint = 'Sign-in saved your draft. Submit it to publish the review.';
                }
            } catch {
                // A blocked or full session store just leaves the form empty.
            }
        },

        async send() {
            const text = this.body.trim();

            if (this.sending) {
                return;
            }

            if (!this.canWrite) {
                this.keepDraft(text);
                window.location.assign(this.loginUrl);
                return;
            }

            if (this.rating < 1) {
                this.error = 'Choose a star rating.';
                this.saved = '';
                this.hint = '';
                return;
            }

            if (text === '') {
                this.error = 'Write your review before submitting.';
                this.saved = '';
                this.hint = '';
                return;
            }

            this.sending = true;
            this.error = '';
            this.saved = '';
            this.hint = '';

            try {
                const response = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        rating: this.rating,
                        reviewText: text,
                    }),
                });

                if (response.status === 422) {
                    const payload = await response.json();
                    this.error = payload?.errors?.rating?.[0]
                        || payload?.errors?.reviewText?.[0]
                        || 'Check the rating and review, then try again.';
                    return;
                }

                if (response.status === 401 || response.status === 419) {
                    this.keepDraft(text);
                    window.location.assign(this.loginUrl);
                    return;
                }

                if (!response.ok) {
                    this.error = 'Could not save that review. Try again.';
                    return;
                }

                const payload = await response.json();
                this.applySummary(payload);
                this.mineId = payload.review?.id ?? this.mineId;
                this.saved = payload.updated
                    ? 'Your review was updated.'
                    : 'Your review is on this profile.';
                window.sessionStorage.removeItem(this.draftKey);
                this.closeForm(true);

                if (!payload.updated) {
                    this.page = 1;
                }

                await this.load();
            } catch {
                this.error = 'Could not save that review. Try again.';
            } finally {
                this.sending = false;
            }
        },

        openForm() {
            this.error = '';
            this.formOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.reviewDialog?.focus());
        },

        closeForm(force = false) {
            if (this.sending && !force) {
                return;
            }

            this.formOpen = false;
            document.body.style.overflow = '';
        },

        async goTo(number) {
            const last = Math.max(1, this.lastPage);
            const next = Math.min(last, Math.max(1, Number(number) || 1));

            if (next === this.page) {
                return;
            }

            this.page = next;
            await this.load();
        },

        pageRange() {
            if (this.total < 1 || this.reviews.length < 1) {
                return '';
            }

            const start = (this.page - 1) * this.perPage + 1;
            const end = start + this.reviews.length - 1;

            return `Showing ${start}–${end} of ${this.total}`;
        },

        async poll() {
            if (document.hidden) {
                return;
            }

            await this.load();
        },

        async load() {
            if (!this.pollUrl) {
                return;
            }

            const requestId = ++this.requestId;
            const requested = this.page;

            try {
                const url = new URL(this.pollUrl, window.location.origin);
                url.searchParams.set('page', String(requested));

                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok || requestId !== this.requestId || this.page !== requested) {
                    return;
                }

                const payload = await response.json();

                if (requestId !== this.requestId || this.page !== requested) {
                    return;
                }

                this.applyPage(payload);
            } catch {
                // The next poll retries.
            }
        },

        applySummary(payload) {
            if (typeof payload.average === 'string' || typeof payload.average === 'number') {
                this.average = Number(payload.average).toFixed(1);
            }

            if (payload.total !== undefined && payload.total !== null) {
                this.total = Number(payload.total);
            }
        },

        applyPage(payload) {
            this.applySummary(payload);

            if (Array.isArray(payload.reviews)) {
                this.reviews = payload.reviews;
            }

            const last = Number(payload.lastPage ?? this.lastPage ?? 1);
            this.lastPage = last > 0 ? last : 1;

            if (payload.perPage) {
                this.perPage = Number(payload.perPage);
            }

            if (payload.page !== undefined && payload.page !== null) {
                this.page = Number(payload.page);
            }

            if (this.page > this.lastPage) {
                this.page = this.lastPage;
            }
        },

        keepDraft(text) {
            try {
                window.sessionStorage.setItem(this.draftKey, JSON.stringify({
                    rating: this.rating,
                    body: text,
                }));
            } catch {
                // The login page still brings them back to this profile.
            }
        },

        headers() {
            return {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            };
        },
    }));
}
