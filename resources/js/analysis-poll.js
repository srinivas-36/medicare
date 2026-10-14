export function registerAnalysisPoll(Alpine) {
    Alpine.data('analysisPoll', (config) => ({
        timer: null,
        start() {
            if (!config.active) {
                return;
            }

            this.timer = window.setInterval(() => this.tick(), 3000);
        },
        async tick() {
            try {
                const response = await fetch(config.url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                if (data.terminal || data.status !== config.status || data.step !== config.step) {
                    window.location.reload();
                }
            } catch {
                // The next poll retries. The page stays usable.
            }
        },
        destroy() {
            if (this.timer) {
                window.clearInterval(this.timer);
            }
        },
    }));
}
