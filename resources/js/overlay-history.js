/**
 * Mobile/browser Back closes one overlay at a time instead of leaving the page.
 *
 * Vanilla: OverlayHistory.push(id, closeSilent) on open, OverlayHistory.dismiss(id) on UI close.
 * Alpine: OverlayHistory.bindWatch(this, 'open', 'my-overlay')
 */
(function () {
    var stack = [];
    var skipPops = 0;

    function findIndex(id) {
        var i;
        for (i = 0; i < stack.length; i += 1) {
            if (stack[i].id === id) return i;
        }
        return -1;
    }

    function onPopState() {
        if (skipPops > 0) {
            skipPops -= 1;
            return;
        }
        if (!stack.length) return;
        var top = stack.pop();
        try {
            top.close();
        } catch (err) {}
    }

    window.addEventListener('popstate', onPopState);

    window.OverlayHistory = {
        push: function (id, closeFn) {
            if (!id || typeof closeFn !== 'function') return;
            var idx = findIndex(id);
            if (idx >= 0) {
                stack[idx].close = closeFn;
                return;
            }
            stack.push({ id: id, close: closeFn });
            try {
                history.pushState({ overlay: id }, '', location.href);
            } catch (err) {}
        },

        dismiss: function (id) {
            var idx = findIndex(id);
            if (idx < 0) return;
            if (idx !== stack.length - 1) {
                stack.splice(idx, 1);
                return;
            }
            stack.pop();
            skipPops += 1;
            try {
                history.back();
            } catch (err) {
                skipPops -= 1;
            }
        },

        bindWatch: function (component, prop, id) {
            if (!component || !component.$watch) return;
            component.$watch(prop, function (on) {
                if (on) {
                    window.OverlayHistory.push(id, function () {
                        component[prop] = false;
                    });
                } else {
                    window.OverlayHistory.dismiss(id);
                }
            });
            if (component[prop]) {
                window.OverlayHistory.push(id, function () {
                    component[prop] = false;
                });
            }
        },

        isOpen: function (id) {
            return findIndex(id) >= 0;
        },
    };
})();
