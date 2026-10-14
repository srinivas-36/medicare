export function registerReferralPreview(Alpine) {
    Alpine.data('referralDesk', (config) => ({
        preview: Boolean(config.preview),
        doctors: Array.isArray(config.doctors) ? config.doctors : [],
        draft: typeof config.draft === 'string' ? config.draft : '',
        reopen: Boolean(config.reopen),
        open: false,
        view: 'list',
        query: '',
        specialty: 'suggested',
        doctorId: config.doctorId ? String(config.doctorId) : '',
        reason: typeof config.reason === 'string' ? config.reason : '',
        question: typeof config.question === 'string' ? config.question : '',
        urgency: config.urgency || 'routine',
        previewUrl: config.previewUrl || '',
        visits: Array.isArray(config.visits) ? config.visits : [],
        visitId: config.visitId ? String(config.visitId) : '',
        reasonTouched: false,
        error: '',
        sent: Array.isArray(config.sent) ? config.sent : [],
        saving: false,
        banner: '',

        init() {
            if (!this.doctors.some((doctor) => doctor.matched)) {
                this.specialty = 'all';
            }

            if (this.currentVisit) {
                this.previewUrl = this.currentVisit.preview_url || this.previewUrl;

                if (!this.reason) {
                    this.reason = this.currentVisit.draft || this.reason;
                }
            }

            if (this.reopen) {
                this.open = true;
                this.view = 'note';
            }
        },

        get currentVisit() {
            if (!this.visits.length) {
                return null;
            }

            return this.visits.find((visit) => String(visit.id) === String(this.visitId)) ?? this.visits[0];
        },

        chooseVisit(id) {
            this.visitId = String(id);
            const visit = this.currentVisit;

            if (!visit) {
                return;
            }

            this.previewUrl = visit.preview_url || this.previewUrl;

            if (!this.reasonTouched) {
                this.reason = visit.draft || '';
            }
        },

        get doctor() {
            return this.doctors.find((doctor) => String(doctor.id) === String(this.doctorId)) ?? null;
        },

        get hasMatches() {
            return this.doctors.some((doctor) => doctor.matched);
        },

        get specialtyNames() {
            return [...new Set(this.doctors.map((doctor) => doctor.specialization).filter(Boolean))].sort((left, right) => left.localeCompare(right));
        },

        openList() {
            this.open = true;
            this.view = 'list';
            this.error = '';
        },

        close() {
            if (this.view === 'sent') {
                this.banner = this.successLine();
            }

            this.open = false;
        },

        urgencyLabel() {
            return { routine: 'Routine', soon: 'Soon', urgent: 'Urgent' }[this.urgency] || 'Routine';
        },

        successLine() {
            if (!this.doctor) {
                return 'Referral sent.';
            }

            return `Referral sent to ${this.doctor.name}. ${this.urgencyLabel()} · ${this.doctor.specialization}. They can accept or decline, then book the visit.`;
        },

        suggestedIds() {
            const matched = this.doctors.filter((doctor) => doctor.matched);
            const pool = matched.length >= 3
                ? matched
                : [...matched, ...this.doctors.filter((doctor) => !doctor.matched)].slice(0, 3);

            return new Set(pool.map((doctor) => String(doctor.id)));
        },

        visible(id) {
            const doctor = this.doctors.find((item) => String(item.id) === String(id));

            if (!doctor) {
                return false;
            }

            if (this.specialty === 'suggested' && !this.suggestedIds().has(String(doctor.id))) {
                return false;
            }

            if (this.specialty !== 'suggested' && this.specialty !== 'all' && doctor.specialization !== this.specialty) {
                return false;
            }

            const query = this.query.trim().toLowerCase();

            if (query === '') {
                return true;
            }

            return `${doctor.name} ${doctor.specialization} ${doctor.degree || ''} ${doctor.clinic || ''}`.toLowerCase().includes(query);
        },

        visibleCount() {
            return this.doctors.filter((doctor) => this.visible(doctor.id)).length;
        },

        showProfile(id) {
            this.doctorId = String(id);
            this.view = 'profile';
            this.error = '';
        },

        startReferral(id) {
            this.doctorId = String(id);
            this.view = 'note';
            this.error = '';

            if (this.reason.trim() === '') {
                this.reason = this.draft;
            }
        },

        async sendPreview() {
            if (!this.doctor) {
                this.error = 'Choose a doctor first.';

                return;
            }

            if (this.reason.trim() === '') {
                this.error = 'Add the reason for this referral.';

                return;
            }

            this.saving = true;
            this.error = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        to_doctor_id: this.doctor.id,
                        reason: this.reason.trim(),
                        clinical_question: this.question,
                        urgency: this.urgency,
                        report_ids: [...document.querySelectorAll('#refer input[name="report_ids[]"]:checked')]
                            .map((input) => Number(input.value))
                            .filter((id) => Number.isInteger(id)),
                    }),
                });

                if (!response.ok) {
                    this.error = 'The referral could not be saved. Try again.';

                    return;
                }

                const payload = await response.json();
                this.sent.unshift(payload.referral);
                this.view = 'sent';
            } catch (error) {
                this.error = 'The referral could not be saved. Try again.';
            } finally {
                this.saving = false;
            }
        },

        referAnother() {
            this.view = 'list';
            this.doctorId = '';
            this.reason = this.draft;
            this.question = '';
            this.urgency = 'routine';
            this.query = '';
            this.error = '';
        },
    }));
}
