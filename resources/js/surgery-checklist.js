/**
 * Surgery checklist popup — gallery-style modal from row toolbox.
 */
window.SurgeryChecklistPanel = (function () {
    var root = null;
    var overlay = null;
    var listEl = null;
    var titleEl = null;
    var patientEl = null;
    var savedEl = null;
    var msgEl = null;
    var csrf = '';
    var ensureUrl = '';
    var apiBase = '/surgery-checklists';
    var current = null;
    var bound = false;

    function showMsg(text) {
        if (!msgEl) return;
        msgEl.textContent = text || '';
        msgEl.style.display = text ? '' : 'none';
    }

    function resolveCsrf() {
        return csrf
            || (root && root.getAttribute('data-csrf'))
            || (document.querySelector('meta[name="csrf-token"]') || {}).content
            || '';
    }

    function bindDom() {
        root = document.getElementById('row-toolbox-root');
        overlay = document.querySelector('[data-cl-overlay]');
        if (!overlay) return false;

        listEl = overlay.querySelector('[data-cl-list]');
        titleEl = overlay.querySelector('[data-cl-title]');
        patientEl = overlay.querySelector('[data-cl-patient]');
        savedEl = overlay.querySelector('[data-cl-saved]');
        msgEl = overlay.querySelector('[data-cl-msg]');

        if (root) {
            csrf = root.getAttribute('data-csrf') || csrf;
            ensureUrl = root.getAttribute('data-checklist-ensure-base') || ensureUrl;
            apiBase = root.getAttribute('data-checklist-api-base') || apiBase;
        }

        if (bound) return true;

        overlay.querySelector('[data-cl-close]')?.addEventListener('click', close);
        overlay.querySelector('[data-cl-add]')?.addEventListener('click', addItem);
        overlay.querySelector('[data-cl-confirm]')?.addEventListener('click', confirmSave);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });
        bound = true;

        return true;
    }

    function api(method, url, body) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': resolveCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        }).then(function (res) {
            return res.text().then(function (text) {
                var data = {};
                if (text) {
                    try {
                        data = JSON.parse(text);
                    } catch (err) {
                        throw new Error(res.ok ? 'پاسخ نامعتبر از سرور' : ('خطا ' + res.status));
                    }
                }
                if (!res.ok) throw new Error(data.message || data.error || ('خطا ' + res.status));
                return data;
            });
        });
    }

    function itemUrl(itemId, suffix) {
        return apiBase + '/' + current.id + '/items/' + itemId + (suffix || '');
    }

    function fullStampTitle(row) {
        return row.checked_at_jalali
            ? (row.checked_by ? row.checked_by + ' · ' : '') + row.checked_at_jalali
            : '';
    }

    function render(data) {
        current = data;
        if (titleEl) titleEl.textContent = data.title || 'چک‌لیست عمل';
        if (savedEl) {
            if (data.saved_at_jalali) {
                savedEl.textContent = 'ثبت در پرونده: ' + data.saved_at_jalali;
                savedEl.style.display = '';
            } else {
                savedEl.textContent = '';
                savedEl.style.display = 'none';
            }
        }
        if (!listEl) return;
        listEl.innerHTML = '';

        var items = data.items || [];
        if (!items.length) {
            var empty = document.createElement('li');
            empty.className = 'scl-record__item';
            empty.innerHTML = '<span class="scl-record__label" style="color:var(--muted)">'
                + 'موردی نیست. با «+ مورد» اضافه کنید یا در تنظیماتِ انواع عمل قالب بسازید.'
                + '</span>';
            listEl.appendChild(empty);
            return;
        }

        items.forEach(function (row) {
            var li = document.createElement('li');
            li.className = 'scl-record__item' + (row.checked ? ' is-done' : '');

            var checkBtn = document.createElement('button');
            checkBtn.type = 'button';
            checkBtn.className = 'scl-record__check scl-record__check--btn';
            checkBtn.setAttribute('aria-label', row.checked ? 'برداشتن تیک' : 'تیک زدن');
            checkBtn.setAttribute('aria-pressed', row.checked ? 'true' : 'false');
            checkBtn.textContent = row.checked ? '✓' : '○';

            var text = document.createElement('button');
            text.type = 'button';
            text.className = 'scl-record__label scl-record__label--btn';
            text.textContent = row.label;
            text.title = fullStampTitle(row);

            if (row.checked && !row.checked_by_me) {
                checkBtn.disabled = true;
                text.disabled = true;
                var lockTitle = 'فقط ' + (row.checked_by || 'تیک‌زننده') + ' می‌تواند تیک، ویرایش یا حذف کند';
                checkBtn.title = lockTitle;
                text.title = lockTitle;
            } else {
                checkBtn.addEventListener('click', function () {
                    toggleItem(row);
                });
                text.addEventListener('click', function () {
                    toggleItem(row);
                });
            }

            li.appendChild(checkBtn);
            li.appendChild(text);

            if (row.checked && (row.checked_stamp || row.checked_time_short)) {
                var stamp = document.createElement('span');
                stamp.className = 'scl-record__stamp';
                stamp.textContent = row.checked_stamp || row.checked_time_short;
                stamp.title = fullStampTitle(row);
                li.appendChild(stamp);
            }

            var lockedToOther = !!(row.checked && !row.checked_by_me);
            if (!lockedToOther) {
                var actions = document.createElement('div');
                actions.className = 'scl-item__actions';

                var editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'scl-item__btn';
                editBtn.textContent = '✎';
                editBtn.title = 'ویرایش';
                editBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var next = prompt('متن مورد:', row.label);
                    if (next && next.trim()) updateLabel(row.id, next.trim());
                });

                var delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'scl-item__btn scl-item__btn--danger';
                delBtn.textContent = '×';
                delBtn.title = 'حذف';
                delBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (confirm('این مورد حذف شود؟')) removeItem(row.id);
                });

                actions.appendChild(editBtn);
                actions.appendChild(delBtn);
                li.appendChild(actions);
            }

            listEl.appendChild(li);
        });
    }

    function loadForAppointment(appointmentId) {
        if (!appointmentId) return Promise.reject(new Error('نوبت عمل مشخص نیست'));
        var url = (ensureUrl || '/surgery-appointments/__ID__/checklist/ensure').replace('__ID__', String(appointmentId));
        showMsg('در حال بارگذاری…');
        return api('POST', url).then(function (data) {
            showMsg('');
            render(data);
            return data;
        }).catch(function (e) {
            showMsg(e.message || 'خطا در بارگذاری چک‌لیست');
            throw e;
        });
    }

    function toggleItem(row) {
        if (!current || !row) return;
        if (row.checked) {
            if (!row.checked_by_me) {
                showMsg('فقط کسی که تیک زده می‌تواند آن را بردارد.');
                return;
            }
            if (!window.confirm('تیک این مورد برداشته شود؟')) {
                return;
            }
        }
        api('PATCH', itemUrl(row.id, '/toggle'))
            .then(render)
            .catch(function (e) { showMsg(e.message); });
    }

    function updateLabel(itemId, label) {
        if (!current) return;
        api('PUT', itemUrl(itemId), { label: label })
            .then(render)
            .catch(function (e) { showMsg(e.message); });
    }

    function removeItem(itemId) {
        if (!current) return;
        api('DELETE', itemUrl(itemId))
            .then(render)
            .catch(function (e) { showMsg(e.message); });
    }

    function addItem() {
        if (!current) return;
        var label = prompt('مورد جدید:');
        if (!label || !label.trim()) return;
        api('POST', apiBase + '/' + current.id + '/items', { label: label.trim() })
            .then(render)
            .catch(function (e) { showMsg(e.message); });
    }

    function confirmSave() {
        if (!current) return;
        var unchecked = Number(current.unchecked_count || 0);
        if (unchecked > 0) {
            var ok = window.confirm(
                'هنوز ' + unchecked + ' مورد از چک‌لیست تیک نخورده است.\nآیا همچنان تأیید و ثبت در پرونده انجام شود؟'
            );
            if (!ok) return;
        }
        api('POST', apiBase + '/' + current.id + '/confirm')
            .then(function (res) {
                showMsg(res.message || 'در پرونده ثبت شد');
                if (res.checklist) render(res.checklist);
            })
            .catch(function (e) { showMsg(e.message); });
    }

    function open(item) {
        if (!overlay) bindDom();
        if (!overlay) {
            window.alert('پنل چک‌لیست بارگذاری نشد. صفحه را یک‌بار کامل رفرش کنید (Ctrl+F5).');
            return;
        }

        // Escape stacking contexts (same reliability as gallery on body).
        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }

        if (patientEl) patientEl.textContent = (item && item.name) || '';
        showMsg('');
        if (listEl) listEl.innerHTML = '';
        if (titleEl) titleEl.textContent = 'چک‌لیست عمل';
        if (savedEl) {
            savedEl.textContent = '';
            savedEl.style.display = 'none';
        }

        overlay.style.display = 'flex';
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        if (window.OverlayHistory) {
            window.OverlayHistory.push('surgery-checklist', function () { close(true); });
        }

        if (item && item.surgeryAppointmentId) {
            loadForAppointment(item.surgeryAppointmentId);
        } else {
            showMsg('این نوبت عمل نیست یا شناسه عمل موجود نیست.');
        }
    }

    function close(fromHistory) {
        if (!overlay) return;
        var wasOpen = overlay.classList.contains('is-open') || overlay.style.display === 'flex';
        overlay.style.display = 'none';
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        showMsg('');
        current = null;

        var rtOpen = document.querySelector('[data-rt-overlay]')?.classList.contains('is-open');
        var bsOpen = document.querySelector('[data-bs-overlay]')?.classList.contains('is-open');
        var pwOpen = document.querySelector('[data-pw-overlay]')?.classList.contains('is-open');
        if (!rtOpen && !bsOpen && !pwOpen) {
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }
        if (wasOpen && !fromHistory && window.OverlayHistory) {
            window.OverlayHistory.dismiss('surgery-checklist');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindDom);
    } else {
        bindDom();
    }

    return { open: open, close: close, loadForAppointment: loadForAppointment };
})();
