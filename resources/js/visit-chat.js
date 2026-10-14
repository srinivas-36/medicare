export function registerVisitChat(Alpine) {
    Alpine.data('visitChat', (config) => ({
        messages: Array.isArray(config.messages) ? config.messages : [],
        body: typeof config.draft === 'string' ? config.draft : '',
        error: '',
        sending: false,
        pending: false,
        pollUrl: config.pollUrl,
        sendUrl: config.sendUrl,
        timer: null,
        polling: false,

        init() {
            this.onVisible = () => {
                if (!document.hidden) {
                    this.poll();
                }
            };
            this.$nextTick(() => this.scrollToEnd());
            this.timer = window.setInterval(() => this.poll(), 2500);
            document.addEventListener('visibilitychange', this.onVisible);
        },

        destroy() {
            window.clearInterval(this.timer);
            document.removeEventListener('visibilitychange', this.onVisible);
        },

        rows() {
            let previous = null;

            return this.messages.map((message, index) => {
                const next = this.messages[index + 1];
                const showDay = previous?.day !== message.day;
                const grouped = Boolean(previous) && !showDay && previous.mine === message.mine && previous.name === message.name;
                const lastInGroup = !next || next.day !== message.day || next.mine !== message.mine || next.name !== message.name;
                previous = message;

                return { ...message, showDay, grouped, lastInGroup };
            });
        },

        async send() {
            const text = this.body.trim();

            if (text === '' || this.sending) {
                return;
            }

            this.sending = true;
            this.error = '';

            try {
                const response = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({ body: text }),
                });

                if (response.status === 422) {
                    const payload = await response.json();
                    this.error = payload?.errors?.body?.[0] || 'Write a message before sending.';
                    return;
                }

                if (!response.ok) {
                    this.error = 'Could not send that message. Try again.';
                    return;
                }

                const payload = await response.json();
                this.push(payload.message);
                this.body = '';
                this.$nextTick(() => {
                    this.resize();
                    this.scrollToEnd();
                });
            } catch {
                this.error = 'Could not send that message. Try again.';
            } finally {
                this.sending = false;
            }
        },

        async poll() {
            if (this.polling || document.hidden) {
                return;
            }

            this.polling = true;

            try {
                const response = await fetch(this.pollUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const incoming = Array.isArray(payload.messages) ? payload.messages : [];
                const near = this.nearBottom();
                const grew = this.merge(incoming);

                if (grew && near) {
                    this.pending = false;
                    this.$nextTick(() => this.scrollToEnd());
                } else if (grew) {
                    this.pending = true;
                }
            } catch {
                // The next poll retries.
            } finally {
                this.polling = false;
            }
        },

        push(message) {
            if (!message || this.messages.some((current) => current.id === message.id)) {
                return;
            }

            this.messages.push(message);
            this.pending = false;
        },

        onKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                this.send();
            }
        },

        merge(incoming) {
            const byId = new Map(this.messages.map((message) => [message.id, message]));
            let added = false;

            incoming.forEach((message) => {
                if (!byId.has(message.id)) {
                    added = true;
                }

                byId.set(message.id, message);
            });

            this.messages = Array.from(byId.values()).sort((left, right) => left.id - right.id);

            return added;
        },

        resize() {
            const field = this.$refs.composer;

            if (!field) {
                return;
            }

            field.style.height = 'auto';
            field.style.height = `${Math.min(field.scrollHeight, 128)}px`;
        },

        nearBottom() {
            const thread = this.$refs.thread;

            if (!thread) {
                return true;
            }

            return thread.scrollHeight - thread.scrollTop - thread.clientHeight < 96;
        },

        scrollToEnd() {
            const thread = this.$refs.thread;

            if (!thread) {
                return;
            }

            thread.scrollTop = thread.scrollHeight;
            this.pending = false;
        },

        headers() {
            return {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            };
        },
    }));
}
