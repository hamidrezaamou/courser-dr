/**
 * Live clinic floor: polls snapshot and swaps board HTML without full page reload.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('clinicFloorLive', (config = {}) => ({
        url: config.url || '',
        version: config.version || '',
        interval: Number(config.interval) || 2000,
        connected: true,
        refreshing: false,
        timer: null,
        statusText: 'زنده',
        lastReadyCount: null,

        init() {
            this.poll();
            this.timer = setInterval(() => this.poll(), this.interval);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.poll(true);
                }
            });
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
            }
        },

        async refresh(force = false) {
            await this.poll(force);
        },

        async poll(force = false) {
            if (!this.url || this.refreshing) {
                return;
            }

            this.refreshing = true;

            try {
                const res = await window.axios.get(this.url, {
                    headers: { Accept: 'application/json' },
                    params: { _: Date.now() },
                });
                const data = res.data || {};
                this.connected = true;
                this.statusText = 'زنده · بدون رفرش';

                if (force || (data.version && data.version !== this.version)) {
                    this.version = data.version;
                    this.applyHtml(data.html || '');
                    this.maybeNotifyRefer(data.html || '');
                }
            } catch (e) {
                this.connected = false;
                this.statusText = 'قطع ارتباط · تلاش مجدد…';
            } finally {
                this.refreshing = false;
            }
        },

        applyHtml(html) {
            const root = this.$refs.board;
            if (!root || typeof html !== 'string') {
                return;
            }

            if (window.Alpine?.destroyTree) {
                window.Alpine.destroyTree(root);
            }
            root.innerHTML = html;
            if (window.Alpine?.initTree) {
                window.Alpine.initTree(root);
            }
        },

        maybeNotifyRefer(html) {
            try {
                const match = html.match(/floor-col--ready[\s\S]*?floor-col__count[^>]*>(\d+)/);
                const count = match ? Number(match[1]) : null;
                if (this.lastReadyCount !== null && count !== null && count > this.lastReadyCount) {
                    this.flashTitle();
                }
                if (count !== null) {
                    this.lastReadyCount = count;
                }
            } catch (_) {
                // ignore
            }
        },

        flashTitle() {
            const base = document.title.replace(/^\(\*?\)\s*/, '');
            document.title = '(*) ارجاع جدید — ' + base;
            setTimeout(() => {
                document.title = base;
            }, 4000);
        },
    }));
});
