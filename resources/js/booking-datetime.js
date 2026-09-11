(function () {
    const MONTH_NAMES = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    const DAY_NAMES = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
    const WEEK_DAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function toJalali(gy, gm, gd) {
        var g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        var gy2 = gm > 2 ? gy + 1 : gy;
        var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
        var jy = -1595 + (33 * Math.floor(days / 12053));
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) {
            jy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        var jm, jd;
        if (days < 186) {
            jm = 1 + Math.floor(days / 31);
            jd = 1 + (days % 31);
        } else {
            jm = 7 + Math.floor((days - 186) / 30);
            jd = 1 + ((days - 186) % 30);
        }
        return { y: jy, m: jm, d: jd };
    }

    function isLeapJalali(jy) {
        var breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        var bl = breaks.length;
        var jp = breaks[0];
        var jump = 0;
        for (var i = 1; i < bl; i++) {
            var jm = breaks[i];
            jump = jm - jp;
            if (jy < jm) break;
            jp = jm;
        }
        var n = jy - jp;
        if (jump - n < 6) n = n - jump + Math.floor((jump + 4) / 33) * 33;
        var leap = (((n + 1) % 33) - 1) % 4;
        if (leap === -1) leap = 4;
        return leap === 0;
    }

    function daysInJalaliMonth(jy, jm) {
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        return isLeapJalali(jy) ? 30 : 29;
    }

    // Approximate weekday for Jalali first of month via Gregorian conversion
    function jalaliToGregorian(jy, jm, jd) {
        jy += 1595;
        var days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        var gy = 400 * Math.floor(days / 146097);
        days %= 146097;
        if (days > 36524) {
            gy += 100 * Math.floor(--days / 36524);
            days %= 36524;
            if (days >= 365) days++;
        }
        gy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) {
            gy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        var gd = days + 1;
        var sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        var gm;
        for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
        return { y: gy, m: gm, d: gd };
    }

    function jalaliWeekday(jy, jm, jd) {
        var g = jalaliToGregorian(jy, jm, jd);
        var date = new Date(g.y, g.m - 1, g.d);
        // JS: 0=Sun ... convert to Persian week starting Saturday=0
        return (date.getDay() + 1) % 7;
    }

    function initPicker(root) {
        var dateInput = root.querySelector('[data-date-input]');
        var dateSelect = root.querySelector('[data-date-select]');
        var timeInput = root.querySelector('[data-time-input]');
        var timeSection = root.querySelector('[data-time-section]');
        var timeGrid = root.querySelector('[data-time-grid]');
        var dateText = root.querySelector('[data-selected-date-text]');
        var hideBooked = root.querySelector('[data-hide-booked]');
        var mode = root.getAttribute('data-mode') || 'calendar';
        var kind = root.getAttribute('data-kind') || 'visit';
        var slotsUrl = root.getAttribute('data-slots-url');
        var calendarUrl = root.getAttribute('data-calendar-url') || '';
        var excludeId = root.getAttribute('data-exclude-id') || '';
        var hospitalSelect = document.querySelector('[data-hospital-select]');

        var today = toJalali(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
        var todayKey = today.y + '/' + pad(today.m) + '/' + pad(today.d);
        var viewYear = today.y;
        var viewMonth = today.m;
        var dayMap = {};
        var calendarLoaded = kind !== 'visit';
        var calendarMessage = '';

        function currentHospitalId() {
            if (kind !== 'surgery' || !hospitalSelect) return '';
            return hospitalSelect.value || '';
        }

        // Select mode for register forms — populate date dropdown, skip calendar modal.
        if (mode === 'select' && dateSelect) {
            function loadSlotsSelect(dateStr) {
                timeSection.style.display = '';
                if (dateText) dateText.textContent = dateStr;
                timeGrid.innerHTML = '<div class="booking-slot-loading">در حال بارگذاری تایم‌ها...</div>';
                timeInput.value = '';
                var url = slotsUrl + '?date=' + encodeURIComponent(dateStr) + '&kind=' + encodeURIComponent(kind);
                if (excludeId) url += '&exclude_id=' + encodeURIComponent(excludeId);
                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var slots = Array.isArray(data.slots) && data.slots.length
                            ? data.slots
                            : (data.available_times || []).map(function (t) {
                                return { value: t, label: t, booked: !!(data.booked || {})[t] };
                            });
                        var booked = data.booked || {};
                        timeGrid.innerHTML = '';
                        if (!slots.length) {
                            timeGrid.innerHTML = '<div class="booking-slot-empty">' + (data.message || 'برای این تاریخ برنامه‌ای تعریف نشده است.') + '</div>';
                            return;
                        }
                        slots.forEach(function (slot) {
                            var timeVal = slot.value;
                            var label = slot.label || timeVal;
                            var isBooked = !!slot.booked || !!booked[timeVal];
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'booking-slot';
                            btn.setAttribute('data-time', timeVal);
                            if (isBooked) {
                                btn.classList.add('is-booked');
                                btn.innerHTML = label + '<small>پر شده</small>';
                            } else {
                                btn.textContent = label;
                                btn.addEventListener('click', function () {
                                    timeGrid.querySelectorAll('.booking-slot').forEach(function (el) { el.classList.remove('is-selected'); });
                                    btn.classList.add('is-selected');
                                    timeInput.value = timeVal;
                                });
                            }
                            timeGrid.appendChild(btn);
                        });
                        if (hideBooked && hideBooked.checked) {
                            timeGrid.querySelectorAll('.booking-slot.is-booked').forEach(function (el) { el.style.display = 'none'; });
                        }
                    })
                    .catch(function () {
                        timeGrid.innerHTML = '<div class="booking-slot-empty">خطا در دریافت تایم‌ها</div>';
                    });
            }

            function fillDateOptions(days) {
                var prefer = dateSelect.getAttribute('data-initial') || '';
                dateSelect.innerHTML = '<option value="">-- انتخاب تاریخ --</option>';
                Object.keys(days || {}).sort().filter(function (key) { return key >= todayKey; }).forEach(function (key) {
                    var info = days[key] || {};
                    var free = Math.max(0, Number(info.free || 0));
                    var full = info.status === 'full' || free <= 0;
                    var parts = key.split('/');
                    var y = parseInt(parts[0], 10);
                    var m = parseInt(parts[1], 10);
                    var d = parseInt(parts[2], 10);
                    var weekday = WEEK_DAYS[jalaliWeekday(y, m, d)] || '';
                    var labelDate = d + ' ' + (MONTH_NAMES[m] || parts[1]) + (weekday ? ' ' + weekday : '') + '، ' + y;
                    var opt = document.createElement('option');
                    opt.value = key;
                    opt.disabled = full;
                    opt.textContent = full ? (labelDate + ' (تکمیل)') : (labelDate + ' (🟢 ' + free + ' خالی)');
                    dateSelect.appendChild(opt);
                });
                if (prefer && dateSelect.querySelector('option[value="' + prefer + '"]')) {
                    dateSelect.value = prefer;
                    loadSlotsSelect(prefer);
                }
            }

            dateSelect.addEventListener('change', function () {
                timeInput.value = '';
                if (dateSelect.value) loadSlotsSelect(dateSelect.value);
                else {
                    timeSection.style.display = 'none';
                    timeGrid.innerHTML = '';
                }
            });
            if (hideBooked) {
                hideBooked.addEventListener('change', function () {
                    timeGrid.querySelectorAll('.booking-slot.is-booked').forEach(function (el) {
                        el.style.display = hideBooked.checked ? 'none' : '';
                    });
                });
            }
            if (calendarUrl) {
                dateSelect.innerHTML = '<option value="">در حال بارگذاری...</option>';
                fetch(calendarUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var days = data.days || {};
                        if (!Object.keys(days).length) {
                            dateSelect.innerHTML = '<option value="">' + (data.message || 'تاریخی ثبت نشده') + '</option>';
                            return;
                        }
                        fillDateOptions(days);
                    })
                    .catch(function () {
                        dateSelect.innerHTML = '<option value="">خطا در دریافت تاریخ‌ها</option>';
                    });
            }
            return;
        }

        var modal = root.parentElement.querySelector('[data-calendar-modal]') || document.querySelector('[data-calendar-modal]');
        // Prefer modal next to this picker
        var siblingModal = root.nextElementSibling;
        if (siblingModal && siblingModal.matches('[data-calendar-modal]')) {
            modal = siblingModal;
        }
        if (!modal || !dateInput) return;
        mountModalOnBody(modal);
        var calHistId = 'booking-cal-' + (root.getAttribute('data-booking-picker') || Math.random().toString(36).slice(2, 8));

        var monthLabel = modal.querySelector('[data-month-label]');
        var calendarGrid = modal.querySelector('[data-calendar-grid]');

        function loadVisitCalendar(thenRender) {
            if (kind !== 'visit' || !calendarUrl) {
                calendarLoaded = true;
                if (thenRender) renderCalendar();
                return;
            }
            calendarGrid.innerHTML = '<div class="booking-slot-loading" style="grid-column:1/-1;padding:1rem;text-align:center">در حال بارگذاری روزهای ویزیت...</div>';
            fetch(calendarUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    dayMap = data.days || {};
                    calendarMessage = data.message || '';
                    calendarLoaded = true;
                    if (thenRender) renderCalendar();
                })
                .catch(function () {
                    dayMap = {};
                    calendarMessage = 'خطا در دریافت روزهای ویزیت';
                    calendarLoaded = true;
                    if (thenRender) renderCalendar();
                });
        }

        function openModal(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (kind === 'surgery' && hospitalSelect && !hospitalSelect.value) {
                alert('ابتدا بیمارستان را انتخاب کنید');
                hospitalSelect.focus();
                return;
            }
            var already = modal.classList.contains('is-open');
            modal.classList.add('is-open');
            lockBodyScroll(true);
            if (kind === 'visit' && !calendarLoaded) {
                loadVisitCalendar(true);
            } else {
                renderCalendar();
            }
            if (!already && window.OverlayHistory) {
                window.OverlayHistory.push(calHistId, function () { closeModal(true); });
            }
        }

        function closeModal(fromHistory) {
            if (!modal.classList.contains('is-open')) return;
            modal.classList.remove('is-open');
            lockBodyScroll(false);
            if (!fromHistory && window.OverlayHistory) {
                window.OverlayHistory.dismiss(calHistId);
            }
        }

        function renderCalendar() {
            monthLabel.textContent = MONTH_NAMES[viewMonth] + ' ' + viewYear;
            var html = '';
            DAY_NAMES.forEach(function (n) {
                html += '<div class="booking-day-name">' + n + '</div>';
            });

            if (kind === 'visit' && calendarLoaded && Object.keys(dayMap).length === 0) {
                html += '<div class="booking-slot-empty" style="grid-column:1/-1;padding:1rem;text-align:center">'
                    + (calendarMessage || 'هنوز هیچ روزی برای ویزیت در تنظیمات تایم ثبت نشده است.')
                    + '</div>';
                calendarGrid.innerHTML = html;
                return;
            }

            var firstWeekday = jalaliWeekday(viewYear, viewMonth, 1);
            var dim = daysInJalaliMonth(viewYear, viewMonth);
            var i;
            for (i = 0; i < firstWeekday; i++) {
                html += '<div class="booking-day is-disabled"></div>';
            }

            for (i = 1; i <= dim; i++) {
                var dateStr = viewYear + '/' + pad(viewMonth) + '/' + pad(i);
                var isToday = viewYear === today.y && viewMonth === today.m && i === today.d;
                var isPast = (viewYear < today.y) || (viewYear === today.y && viewMonth < today.m) || (viewYear === today.y && viewMonth === today.m && i < today.d);
                var weekday = jalaliWeekday(viewYear, viewMonth, i);
                var isWeekend = weekday === 6; // Friday
                var info = dayMap[dateStr] || null;
                var hasSchedule = kind !== 'visit' || !!info;
                var isFull = !!(info && info.status === 'full');
                var disabled = isPast || isWeekend || (kind === 'visit' && !hasSchedule);
                var classes = 'booking-day';
                if (disabled) classes += ' is-disabled';
                if (isToday) classes += ' is-today';
                if (dateInput.value === dateStr) classes += ' is-active';
                if (kind === 'visit' && hasSchedule && !isFull) classes += ' is-available';
                if (kind === 'visit' && isFull) classes += ' is-full';
                var title = '';
                if (kind === 'visit' && info) {
                    title = isFull ? 'تکمیل' : (info.free + ' نوبت خالی');
                }
                html += '<button type="button" class="' + classes + '" data-date="' + dateStr + '"'
                    + (disabled ? ' disabled' : '')
                    + (title ? ' title="' + title + '"' : '')
                    + '>' + i + '</button>';
            }

            calendarGrid.innerHTML = html;
            calendarGrid.querySelectorAll('.booking-day:not(.is-disabled)').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectDate(btn.getAttribute('data-date'));
                });
            });
        }

        function selectDate(dateStr) {
            dateInput.value = dateStr;
            dateText.textContent = dateStr;
            closeModal();
            loadSlots(dateStr);
        }

        function loadSlots(dateStr) {
            timeSection.style.display = '';
            timeGrid.innerHTML = '<div class="booking-slot-loading">در حال بارگذاری تایم‌ها...</div>';
            timeInput.value = '';

            if (kind === 'surgery' && !currentHospitalId()) {
                timeGrid.innerHTML = '<div class="booking-slot-empty">ابتدا بیمارستان را انتخاب کنید.</div>';
                return;
            }

            var url = slotsUrl + '?date=' + encodeURIComponent(dateStr) + '&kind=' + encodeURIComponent(kind);
            var hospitalId = currentHospitalId();
            if (hospitalId) {
                url += '&hospital_id=' + encodeURIComponent(hospitalId);
            }
            if (excludeId) {
                url += '&exclude_id=' + encodeURIComponent(excludeId);
            }

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var slots = Array.isArray(data.slots) && data.slots.length
                        ? data.slots
                        : (data.available_times || []).map(function (t) {
                            return { value: t, label: t, booked: !!(data.booked || {})[t] };
                        });
                    var booked = data.booked || {};
                    timeGrid.innerHTML = '';

                    if (!slots.length) {
                        timeGrid.innerHTML = '<div class="booking-slot-empty">' + (data.message || 'برای این تاریخ برنامه‌ای تعریف نشده است.') + '</div>';
                        return;
                    }

                    slots.forEach(function (slot) {
                        var timeVal = slot.value;
                        var label = slot.label || timeVal;
                        var isBooked = !!slot.booked || !!booked[timeVal];
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'booking-slot';
                        btn.setAttribute('data-time', timeVal);

                        if (isBooked) {
                            btn.classList.add('is-booked');
                            btn.innerHTML = label + '<small>پر شده</small>';
                            var info = booked[timeVal] || {};
                            btn.title = info.patient_name ? ('رزرو: ' + info.patient_name) : 'پر شده';
                            btn.addEventListener('click', function () {
                                alert('این نوبت پر است.\nبیمار: ' + (info.patient_name || '—') + '\nموبایل: ' + (info.mobile || '—'));
                            });
                        } else {
                            btn.textContent = label;
                            btn.addEventListener('click', function () {
                                timeGrid.querySelectorAll('.booking-slot').forEach(function (el) { el.classList.remove('is-selected'); });
                                btn.classList.add('is-selected');
                                timeInput.value = timeVal;
                            });
                        }
                        timeGrid.appendChild(btn);
                    });

                    if (hideBooked && hideBooked.checked) {
                        timeGrid.querySelectorAll('.booking-slot.is-booked').forEach(function (el) { el.style.display = 'none'; });
                    }
                })
                .catch(function () {
                    timeGrid.innerHTML = '<div class="booking-slot-empty">خطا در دریافت تایم‌ها</div>';
                });
        }

        dateInput.addEventListener('click', openModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
        modal.querySelector('[data-prev-month]').addEventListener('click', function () {
            viewMonth--;
            if (viewMonth < 1) { viewMonth = 12; viewYear--; }
            renderCalendar();
        });
        modal.querySelector('[data-next-month]').addEventListener('click', function () {
            viewMonth++;
            if (viewMonth > 12) { viewMonth = 1; viewYear++; }
            renderCalendar();
        });

        if (hideBooked) {
            hideBooked.addEventListener('change', function () {
                timeGrid.querySelectorAll('.booking-slot.is-booked').forEach(function (el) {
                    el.style.display = hideBooked.checked ? 'none' : '';
                });
            });
        }

        if (hospitalSelect && kind === 'surgery') {
            hospitalSelect.addEventListener('change', function () {
                timeInput.value = '';
                if (dateInput.value) {
                    loadSlots(dateInput.value);
                } else {
                    timeSection.style.display = 'none';
                    timeGrid.innerHTML = '';
                }
            });
        }

        if (kind === 'visit') {
            loadVisitCalendar(false);
        }

        if (dateInput.value) {
            dateText.textContent = dateInput.value;
            loadSlots(dateInput.value);
        }
    }

    function lockBodyScroll(lock) {
        if (lock) {
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        } else if (!document.querySelector('.booking-calendar-modal.is-open') && !document.querySelector('.row-toolbox-overlay[style*="display: flex"]')) {
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }
    }

    function mountModalOnBody(modal) {
        if (!modal || modal.parentElement === document.body) return;
        document.body.appendChild(modal);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-booking-picker]').forEach(initPicker);
        document.querySelectorAll('[data-jalali-date]').forEach(initJalaliDateField);
    });

    function initJalaliDateField(root) {
        var input = root.querySelector('[data-jalali-date-input]');
        var modal = root.querySelector('[data-jalali-date-modal]');
        if (!input || !modal) return;

        mountModalOnBody(modal);
        var jalaliHistId = 'jalali-cal-' + Math.random().toString(36).slice(2, 10);

        var monthLabel = modal.querySelector('[data-month-label]');
        var calendarGrid = modal.querySelector('[data-calendar-grid]');
        var allowPast = root.getAttribute('data-allow-past') !== '0';
        var allowFriday = root.getAttribute('data-allow-friday') !== '0';
        var ignoreDocClose = false;

        var today = toJalali(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
        var viewYear = today.y;
        var viewMonth = today.m;

        if (input.value && /^\d{4}\/\d{2}\/\d{2}$/.test(input.value)) {
            var parts = input.value.split('/');
            viewYear = parseInt(parts[0], 10);
            viewMonth = parseInt(parts[1], 10);
        }

        function openModal(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            ignoreDocClose = true;
            var already = modal.classList.contains('is-open');
            modal.classList.add('is-open');
            lockBodyScroll(true);
            renderCalendar();
            window.setTimeout(function () { ignoreDocClose = false; }, 50);
            if (!already && window.OverlayHistory) {
                window.OverlayHistory.push(jalaliHistId, function () { closeModal(true); });
            }
        }

        function closeModal(fromHistory) {
            if (!modal.classList.contains('is-open')) return;
            modal.classList.remove('is-open');
            lockBodyScroll(false);
            if (!fromHistory && window.OverlayHistory) {
                window.OverlayHistory.dismiss(jalaliHistId);
            }
        }

        function renderCalendar() {
            monthLabel.textContent = MONTH_NAMES[viewMonth] + ' ' + viewYear;
            var html = '';
            DAY_NAMES.forEach(function (n) {
                html += '<div class="booking-day-name">' + n + '</div>';
            });

            var firstWeekday = jalaliWeekday(viewYear, viewMonth, 1);
            var dim = daysInJalaliMonth(viewYear, viewMonth);
            var i;
            for (i = 0; i < firstWeekday; i++) {
                html += '<div class="booking-day is-disabled"></div>';
            }

            for (i = 1; i <= dim; i++) {
                var dateStr = viewYear + '/' + pad(viewMonth) + '/' + pad(i);
                var isToday = viewYear === today.y && viewMonth === today.m && i === today.d;
                var isPast = (viewYear < today.y) || (viewYear === today.y && viewMonth < today.m) || (viewYear === today.y && viewMonth === today.m && i < today.d);
                var weekday = jalaliWeekday(viewYear, viewMonth, i);
                var isWeekend = weekday === 6;
                var disabled = (!allowPast && isPast) || (!allowFriday && isWeekend);
                var classes = 'booking-day';
                if (disabled) classes += ' is-disabled';
                if (isToday) classes += ' is-today';
                if (input.value === dateStr) classes += ' is-active';
                html += '<button type="button" class="' + classes + '" data-date="' + dateStr + '"' + (disabled ? ' disabled' : '') + '>' + i + '</button>';
            }

            calendarGrid.innerHTML = html;
            calendarGrid.querySelectorAll('.booking-day:not(.is-disabled)').forEach(function (btn) {
                btn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    input.value = btn.getAttribute('data-date');
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    closeModal();
                });
            });
        }

        input.addEventListener('click', openModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
        var content = modal.querySelector('.booking-calendar-content');
        if (content) {
            content.addEventListener('click', function (e) { e.stopPropagation(); });
        }
        document.addEventListener('pointerdown', function (e) {
            if (ignoreDocClose || !modal.classList.contains('is-open')) return;
            if (root.contains(e.target) || modal.contains(e.target)) return;
            closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });
        modal.querySelector('[data-prev-month]').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            viewMonth--;
            if (viewMonth < 1) { viewMonth = 12; viewYear--; }
            renderCalendar();
        });
        modal.querySelector('[data-next-month]').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            viewMonth++;
            if (viewMonth > 12) { viewMonth = 1; viewYear++; }
            renderCalendar();
        });
    }
})();
