<script>
function surgeryBookingForm(cfg) {
    const MONTH_NAMES = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    const DAY_NAMES = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
    const WEEK_DAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function toJalali(gy, gm, gd) {
        const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        const gy2 = gm > 2 ? gy + 1 : gy;
        let days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
        let jy = -1595 + (33 * Math.floor(days / 12053));
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
        let jm, jd;
        if (days < 186) { jm = 1 + Math.floor(days / 31); jd = 1 + (days % 31); }
        else { jm = 7 + Math.floor((days - 186) / 30); jd = 1 + ((days - 186) % 30); }
        return { y: jy, m: jm, d: jd };
    }

    function isLeap(jy) {
        const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        let jp = breaks[0], jump = 0;
        for (let i = 1; i < breaks.length; i++) {
            const jm = breaks[i]; jump = jm - jp; if (jy < jm) break; jp = jm;
        }
        let n = jy - jp;
        if (jump - n < 6) n = n - jump + Math.floor((jump + 4) / 33) * 33;
        let leap = (((n + 1) % 33) - 1) % 4;
        if (leap === -1) leap = 4;
        return leap === 0;
    }

    function daysInMonth(jy, jm) {
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        return isLeap(jy) ? 30 : 29;
    }

    function jalaliToGregorian(jy, jm, jd) {
        jy += 1595;
        let days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        let gy = 400 * Math.floor(days / 146097);
        days %= 146097;
        if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
        gy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
        let gd = days + 1;
        const sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        let gm = 1;
        for (; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
        return { y: gy, m: gm, d: gd };
    }

    function weekdayIndex(jy, jm, jd) {
        const g = jalaliToGregorian(jy, jm, jd);
        return (new Date(g.y, g.m - 1, g.d).getDay() + 1) % 7;
    }

    function toEnDigits(raw) {
        return String(raw || '')
            .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
            .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
    }

    function onlyDigits(raw) {
        return toEnDigits(raw).replace(/\D+/g, '');
    }

    function isValidNationalCode(raw) {
        // فقط ۱۰ رقم — بدون checksum
        return /^\d{10}$/.test(onlyDigits(raw));
    }

    function isValidMobile(raw) {
        let digits = onlyDigits(raw);
        if (digits.indexOf('98') === 0 && digits.length === 12) digits = '0' + digits.slice(2);
        if (digits.indexOf('9') === 0 && digits.length === 10) digits = '0' + digits;
        return /^09\d{9}$/.test(digits);
    }

    const now = new Date();
    const today = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());

    return {
        optionsUrl: cfg.optionsUrl,
        slotsUrl: cfg.slotsUrl,
        excludeId: cfg.excludeId || '',
        cooldownUrl: cfg.cooldownUrl || '',
        patientId: cfg.patientId || '',
        eyeSide: cfg.initialEyeSide || '',
        cooldownConflict: false,
        cooldownAcked: false,
        cooldownChecked: false,
        cooldownMessage: '',
        cooldownConfirm: '',
        cooldownPrevious: null,
        cooldownTimer: null,
        submitGuardBusy: false,
        allHospitals: cfg.hospitals || [],
        dateKey: cfg.initialDate || '',
        timeValue: cfg.initialTime || '',
        hospitalId: cfg.initialHospitalId ? String(cfg.initialHospitalId) : '',
        typeId: cfg.initialTypeId ? String(cfg.initialTypeId) : '',
        subtypeId: cfg.initialSubtypeId != null && cfg.initialSubtypeId !== '' ? String(cfg.initialSubtypeId) : '',
        initialLabel: cfg.initialTypeLabel || '',
        preserveTime: !!cfg.initialTime,
        hydrating: !!(cfg.initialHospitalId || cfg.initialTypeId || cfg.initialDate),
        loadedHospitalId: cfg.initialHospitalId ? String(cfg.initialHospitalId) : '',
        loadedTypeId: cfg.initialTypeId ? String(cfg.initialTypeId) : '',
        loadedSubtypeId: cfg.initialSubtypeId != null && cfg.initialSubtypeId !== '' ? String(cfg.initialSubtypeId) : '',
        restoreTypeId: cfg.initialTypeId ? String(cfg.initialTypeId) : '',
        restoreSubtypeId: cfg.initialSubtypeId != null && cfg.initialSubtypeId !== '' ? String(cfg.initialSubtypeId) : '',
        restoreDateKey: cfg.initialDate || '',
        restoreTimeValue: cfg.initialTime || '',
        isException: !!cfg.initialException,
        showExceptionTimeInput: false,
        exceptionTimeDraft: '',
        catalogTypes: [],
        dayMap: {},
        loadingCatalog: false,
        loadingCalendar: false,
        loadingSlots: false,
        slotsMessage: '',
        capacityMeta: null,
        capacityAcked: false,
        rawSlots: [],
        slotMode: 'time',
        showBooked: false,
        bookedModalOpen: false,
        activeBooking: null,
        activeSlotLabel: '',
        openCalendar: false,
        viewYear: today.y,
        viewMonth: today.m,
        dayNames: DAY_NAMES,
        subtypeReady: !!(cfg.initialTypeId || cfg.initialTypeLabel || (cfg.initialSubtypeId != null && cfg.initialSubtypeId !== '')),

        get hospitals() {
            return this.allHospitals;
        },
        get selectedType() {
            return this.catalogTypes.find((x) => String(x.id) === String(this.typeId)) || null;
        },
        get selectedTypeHasGeneral() {
            return !!(this.selectedType && this.selectedType.has_general);
        },
        get selectedTypeSubtypes() {
            return (this.selectedType && this.selectedType.subtypes) ? this.selectedType.subtypes : [];
        },
        get selectedTypeName() {
            const t = this.selectedType;
            if (!t) return '';
            if (!this.subtypeId) return t.name;
            const s = this.selectedTypeSubtypes.find((x) => String(x.id) === String(this.subtypeId));
            return s ? (t.name + ' — ' + s.name) : t.name;
        },
        get canLoadSlots() {
            return !!(this.dateKey && this.hospitalId && this.typeId && this.subtypeReady);
        },
        get slotList() {
            return this.rawSlots.filter((s) => !s.booked || this.showBooked);
        },
        get hasBookedSlots() {
            return this.rawSlots.some((s) => s.booked);
        },
        get hasFreeSlots() {
            return this.rawSlots.some((s) => !s.booked && s.bookable !== false);
        },
        needsCapacityNotice() {
            if (!this.capacityMeta) return !!this.isException;
            const cost = Number(this.capacityMeta.subtype_cost || 1);
            const remaining = Number(this.capacityMeta.remaining_units || 0);
            const over = this.capacityMeta.subtype_allowed === false || remaining < cost;
            return !!this.isException || cost > 1 || over;
        },
        capacityNoticeMessage() {
            const cost = Number((this.capacityMeta && this.capacityMeta.subtype_cost) || 1);
            const remaining = Number((this.capacityMeta && this.capacityMeta.remaining_units) || 0);
            const remove = Math.max(0, cost - 1);
            const over = !this.capacityMeta
                || this.capacityMeta.subtype_allowed === false
                || remaining < cost
                || this.isException;

            if (over) {
                let msg = 'این ثبت مازاد بر ظرفیت برنامه‌شده است';
                if (this.capacityMeta && this.capacityMeta.program_group_name) {
                    msg += ' (گروه «' + this.capacityMeta.program_group_name + '»)';
                }
                if (cost > 1) {
                    msg += '.\nهر نوبت این زیرگروه ' + cost + ' واحد ظرفیت مصرف می‌کند';
                    if (remove > 0) msg += ' و ' + remove + ' نوبت از آخر روز حذف می‌شود';
                }
                msg += '.\nظرفیت باقی‌مانده: ' + remaining + '\nآیا تأیید می‌کنید؟';
                return msg;
            }

            let msg = 'این زیرگروه محدودیت ظرفیت دارد: هر انتخاب ' + cost + ' واحد مصرف می‌کند';
            if (remove > 0) msg += ' و ' + remove + ' نوبت از انتهای روز حذف می‌شود';
            msg += '.\nظرفیت باقی‌مانده: ' + remaining + '\nآیا ادامه می‌دهید؟';
            return msg;
        },
        confirmCapacityIfNeeded() {
            if (this.capacityAcked || !this.needsCapacityNotice()) {
                return true;
            }
            if (!confirm(this.capacityNoticeMessage())) {
                return false;
            }
            this.capacityAcked = true;
            if (this.capacityMeta && this.capacityMeta.subtype_allowed === false) {
                this.isException = true;
            }
            return true;
        },
        get hasFullDays() {
            return (this.dateOptions || []).some((o) => o.full);
        },
        get dateOptions() {
            const todayKey = today.y + '/' + pad(today.m) + '/' + pad(today.d);
            return Object.keys(this.dayMap || {})
                .sort()
                .filter((key) => key >= todayKey || key === this.dateKey)
                .map((key) => {
                    const info = this.dayMap[key] || {};
                    const free = Math.max(0, Number(info.free || 0));
                    const full = info.status === 'full' || free <= 0;
                    const parts = key.split('/');
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    const d = parseInt(parts[2], 10);
                    const weekday = WEEK_DAYS[weekdayIndex(y, m, d)] || '';
                    const labelDate = d + ' ' + (MONTH_NAMES[m] || m) + (weekday ? ' ' + weekday : '') + '، ' + y;
                    return {
                        value: key,
                        full,
                        disabled: full && !this.showBooked,
                        label: full
                            ? (this.showBooked
                                ? (labelDate + ' (تکمیل — قابل انتخاب / استثنا)')
                                : (labelDate + ' (تکمیل)'))
                            : (labelDate + ' (🟢 ' + free + ' خالی)'),
                    };
                });
        },
        get monthLabel() {
            return MONTH_NAMES[this.viewMonth] + ' ' + this.viewYear;
        },
        get calendarCells() {
            const cells = [];
            const first = weekdayIndex(this.viewYear, this.viewMonth, 1);
            const dim = daysInMonth(this.viewYear, this.viewMonth);
            for (let i = 0; i < first; i++) cells.push({ day: '', disabled: true });
            for (let d = 1; d <= dim; d++) {
                const date = this.viewYear + '/' + pad(this.viewMonth) + '/' + pad(d);
                const isToday = this.viewYear === today.y && this.viewMonth === today.m && d === today.d;
                const isPast = (this.viewYear < today.y) || (this.viewYear === today.y && this.viewMonth < today.m) || (this.viewYear === today.y && this.viewMonth === today.m && d < today.d);
                const wd = weekdayIndex(this.viewYear, this.viewMonth, d);
                const info = this.dayMap[date] || null;
                const hasSchedule = !!info;
                const isFull = !!(info && (info.status === 'full' || Number(info.free || 0) <= 0));
                cells.push({
                    day: d,
                    date,
                    disabled: (isPast && date !== this.dateKey) || wd === 6 || !this.hospitalId || !this.typeId || !this.subtypeReady || (!hasSchedule && date !== this.dateKey) || (isFull && !this.showBooked && date !== this.dateKey),
                    today: isToday,
                    available: hasSchedule && !isFull,
                    full: isFull,
                    free: info ? info.free : 0,
                });
            }
            return cells;
        },

        async init() {
            this.$watch('openCalendar', (open) => {
                document.documentElement.style.overflow = open ? 'hidden' : '';
                document.body.style.overflow = open ? 'hidden' : '';
            });
            this.$watch('showBooked', (on) => {
                // اگر تیک برداشته شد و تاریخ فعلی روز پر است، تاریخ را پاک کن
                // در حالت ویرایش، تاریخ فعلی نوبت را نگه می‌داریم.
                if (this.hydrating || (this.excludeId && this.dateKey === this.restoreDateKey)) return;
                if (!on && this.dateKey) {
                    const info = this.dayMap[this.dateKey] || {};
                    const free = Math.max(0, Number(info.free || 0));
                    const full = info.status === 'full' || free <= 0;
                    if (full) {
                        this.dateKey = '';
                        this.timeValue = '';
                        this.rawSlots = [];
                        this.isException = false;
                        this.showExceptionTimeInput = false;
                        this.slotsMessage = '';
                    }
                } else if (on && this.dateKey) {
                    this.loadSlots({ keepTime: true });
                }
            });
            this.$watch('eyeSide', () => this.scheduleCooldownCheck());
            this.$watch('typeId', () => this.scheduleCooldownCheck());
            this.$watch('subtypeId', () => this.scheduleCooldownCheck());
            this.$watch('dateKey', () => this.scheduleCooldownCheck());
            const national = document.getElementById('national_code');
            if (national) {
                national.addEventListener('input', () => this.scheduleCooldownCheck());
                national.addEventListener('change', () => this.scheduleCooldownCheck());
            }
            if (this.dateKey) {
                const parts = (this.dateKey || '').split('/');
                if (parts.length === 3) {
                    this.viewYear = parseInt(parts[0], 10) || today.y;
                    this.viewMonth = parseInt(parts[1], 10) || today.m;
                }
            }
            if (this.hospitalId) {
                this.loadedHospitalId = String(this.hospitalId);
                await this.fetchCatalog({ restore: true });
            }
            this.$nextTick(() => { this.hydrating = false; });
        },

        shiftMonth(delta) {
            this.viewMonth += delta;
            if (this.viewMonth < 1) { this.viewMonth = 12; this.viewYear--; }
            if (this.viewMonth > 12) { this.viewMonth = 1; this.viewYear++; }
        },

        resetAfterHospital() {
            this.typeId = '';
            this.subtypeId = '';
            this.subtypeReady = false;
            this.dateKey = '';
            this.timeValue = '';
            this.rawSlots = [];
            this.dayMap = {};
            this.catalogTypes = [];
            this.slotsMessage = '';
            this.preserveTime = false;
            this.initialLabel = '';
            this.loadedTypeId = '';
            this.loadedSubtypeId = '';
            this.isException = false;
            this.showExceptionTimeInput = false;
            this.capacityAcked = false;
            this.capacityMeta = null;
            this.resetCooldown();
        },

        async onHospitalChange() {
            const next = String(this.hospitalId || '');
            if (this.hydrating || next === this.loadedHospitalId) return;
            this.loadedHospitalId = next;
            this.resetAfterHospital();
            await this.fetchCatalog();
        },

        ensureRestoredTypeInCatalog() {
            if (!this.typeId) return;
            if (this.catalogTypes.some((t) => String(t.id) === String(this.typeId))) return;
            const label = String(this.initialLabel || '').trim();
            const parts = label.split(' — ');
            const subtypes = this.subtypeId
                ? [{ id: Number(this.subtypeId) || this.subtypeId, name: parts[1] || 'زیرگروه فعلی' }]
                : [];
            this.catalogTypes = this.catalogTypes.concat([{
                id: Number(this.typeId) || this.typeId,
                name: parts[0] || 'نوع عمل فعلی',
                has_general: !this.subtypeId,
                subtypes,
            }]);
        },

        matchFromLabel() {
            if (!this.initialLabel || !this.catalogTypes.length) return;
            const label = String(this.initialLabel).trim();
            let matchedType = null;
            let matchedSubtype = '';
            this.catalogTypes.forEach((t) => {
                if (matchedType) return;
                if (t.name === label) {
                    matchedType = t;
                    matchedSubtype = '';
                    return;
                }
                (t.subtypes || []).forEach((s) => {
                    if (matchedType) return;
                    if ((t.name + ' — ' + s.name) === label) {
                        matchedType = t;
                        matchedSubtype = String(s.id);
                    }
                });
            });
            if (!matchedType) {
                matchedType = this.catalogTypes.find((t) => label.indexOf(t.name) === 0) || null;
            }
            if (!matchedType) return;
            this.typeId = String(matchedType.id);
            this.subtypeId = matchedSubtype;
            this.subtypeReady = true;
        },

        async fetchCatalog(opts = {}) {
            this.catalogTypes = [];
            if (!this.hospitalId) return;
            this.loadingCatalog = true;
            try {
                const res = await fetch(this.optionsUrl + '?hospital_id=' + encodeURIComponent(this.hospitalId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                this.catalogTypes = data.types || [];
                this.ensureRestoredTypeInCatalog();

                if (opts.restore) {
                    if (this.restoreTypeId) this.typeId = this.restoreTypeId;
                    if (this.restoreSubtypeId !== undefined && this.restoreSubtypeId !== null) this.subtypeId = this.restoreSubtypeId;
                    if (this.restoreDateKey) this.dateKey = this.restoreDateKey;
                    if (this.restoreTimeValue) this.timeValue = this.restoreTimeValue;
                    if (!this.typeId) this.matchFromLabel();
                    if (this.typeId) {
                        this.loadedTypeId = String(this.typeId);
                        this.loadedSubtypeId = String(this.subtypeId || '');
                        this.ensureSubtypeReady();
                        await this.$nextTick();
                        this.typeId = String(this.loadedTypeId);
                        this.subtypeId = this.loadedSubtypeId;
                        this.dateKey = this.restoreDateKey || this.dateKey;
                        this.timeValue = this.restoreTimeValue || this.timeValue;
                        this.ensureSubtypeReady();
                        await this.fetchCalendar();
                        if (this.dateKey) await this.loadSlots({ keepTime: true });
                        this.scheduleCooldownCheck();
                        return;
                    }
                }

                if (this.catalogTypes.length === 1) {
                    this.typeId = String(this.catalogTypes[0].id);
                    this.onTypeChange();
                }
            } catch (e) {
                this.catalogTypes = [];
            } finally {
                this.loadingCatalog = false;
            }
        },

        ensureSubtypeReady() {
            const t = this.selectedType;
            if (!t) {
                this.subtypeReady = false;
                return;
            }
            const specifics = t.subtypes || [];
            if (!t.has_general && specifics.length === 1 && !this.subtypeId) {
                this.subtypeId = String(specifics[0].id);
            }
            if (!t.has_general && !this.subtypeId && specifics.length > 0) {
                this.subtypeReady = false;
                return;
            }
            this.subtypeReady = true;
        },

        async onTypeChange() {
            const next = String(this.typeId || '');
            if (this.hydrating || next === this.loadedTypeId) return;
            this.loadedTypeId = next;
            this.subtypeId = '';
            this.loadedSubtypeId = '';
            this.subtypeReady = false;
            this.dateKey = '';
            this.timeValue = '';
            this.rawSlots = [];
            this.dayMap = {};
            this.preserveTime = false;
            this.isException = false;
            this.showExceptionTimeInput = false;
            this.capacityAcked = false;
            this.capacityMeta = null;
            this.resetCooldown();

            const t = this.selectedType;
            if (!t) return;
            const specifics = t.subtypes || [];
            if (!t.has_general && specifics.length === 1) {
                this.subtypeId = String(specifics[0].id);
            }
            if (t.has_general || this.subtypeId || specifics.length === 0) {
                this.subtypeReady = true;
                await this.fetchCalendar();
            }
        },

        async onSubtypeChange() {
            const next = String(this.subtypeId || '');
            if (this.hydrating || next === this.loadedSubtypeId) return;
            this.loadedSubtypeId = next;
            this.dateKey = '';
            this.timeValue = '';
            this.rawSlots = [];
            this.dayMap = {};
            this.preserveTime = false;
            this.isException = false;
            this.showExceptionTimeInput = false;
            this.capacityAcked = false;
            this.capacityMeta = null;
            this.resetCooldown();
            this.ensureSubtypeReady();
            if (this.subtypeReady) await this.fetchCalendar();
            this.scheduleCooldownCheck();
        },

        async fetchCalendar() {
            this.dayMap = {};
            if (!this.hospitalId || !this.typeId || !this.subtypeReady) return;
            this.loadingCalendar = true;
            try {
                let url = this.optionsUrl
                    + '?hospital_id=' + encodeURIComponent(this.hospitalId)
                    + '&surgery_type_id=' + encodeURIComponent(this.typeId)
                    + '&surgery_subtype_id=' + encodeURIComponent(this.subtypeId || '');
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                this.dayMap = data.days || {};
            } catch (e) {
                this.dayMap = {};
            } finally {
                this.loadingCalendar = false;
            }
        },

        selectDate(cell) {
            if (!cell.day || cell.disabled) return;
            this.dateKey = cell.date;
            this.openCalendar = false;
            this.timeValue = '';
            this.rawSlots = [];
            this.preserveTime = false;
            this.isException = false;
            this.showExceptionTimeInput = false;
            this.capacityAcked = false;
            this.loadSlots();
            this.scheduleCooldownCheck();
        },

        onDateSelectChange() {
            if (this.hydrating) return;
            this.timeValue = '';
            this.rawSlots = [];
            this.preserveTime = false;
            this.isException = false;
            this.showExceptionTimeInput = false;
            this.capacityAcked = false;
            if (this.dateKey) this.loadSlots();
            else this.slotsMessage = '';
            this.scheduleCooldownCheck();
        },

        async loadSlots(opts = {}) {
            const keepTime = !!opts.keepTime;
            const previousTime = keepTime ? this.timeValue : '';
            if (!keepTime) this.timeValue = '';
            this.rawSlots = [];
            this.slotsMessage = '';
            this.capacityMeta = null;
            if (!this.canLoadSlots) return;
            this.loadingSlots = true;
            try {
                let url = this.slotsUrl
                    + '?date=' + encodeURIComponent(this.dateKey)
                    + '&kind=surgery'
                    + '&hospital_id=' + encodeURIComponent(this.hospitalId)
                    + '&surgery_type_id=' + encodeURIComponent(this.typeId);
                if (this.subtypeId) url += '&surgery_subtype_id=' + encodeURIComponent(this.subtypeId);
                if (this.excludeId) url += '&exclude_id=' + encodeURIComponent(this.excludeId);

                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                this.slotMode = data.slot_mode || 'time';
                this.capacityMeta = data.capacity || null;
                if (Array.isArray(data.slots) && data.slots.length) {
                    this.rawSlots = data.slots.map((s) => ({
                        time: s.value,
                        label: s.label || s.value,
                        booked: !!s.booked,
                        bookable: s.bookable !== false,
                        removed: !!s.removed,
                        exception: !!s.exception,
                        booking: s.booking || null,
                    }));
                } else {
                    const times = data.available_times || [];
                    const booked = data.booked || {};
                    this.rawSlots = times.map((t) => ({
                        time: t,
                        label: t,
                        booked: !!booked[t],
                        bookable: !booked[t],
                        removed: false,
                        exception: false,
                        booking: booked[t] && typeof booked[t] === 'object' ? booked[t] : null,
                    }));
                }
                if (keepTime && previousTime) {
                    const sameSlot = (a, b) => {
                        a = String(a || '');
                        b = String(b || '');
                        if (a === b) return true;
                        const ah = a.slice(0, 5);
                        const bh = b.slice(0, 5);
                        return /^\d{2}:\d{2}$/.test(ah) && ah === bh;
                    };
                    const match = this.rawSlots.find((s) => sameSlot(s.time, previousTime));
                    this.timeValue = match ? match.time : previousTime;
                    const exists = !!match;
                    if (!exists && previousTime) {
                        this.rawSlots.push({
                            time: previousTime,
                            label: previousTime.startsWith('Q') ? ('نوبت ' + parseInt(previousTime.replace(/\D/g, ''), 10)) : previousTime,
                            booked: false,
                            exception: true,
                        });
                        this.isException = true;
                    }
                }
                if (!this.rawSlots.length) {
                    this.slotsMessage = data.message || 'برای این ترکیب نوبت/ساعتی تعریف نشده است.';
                } else if (!this.hasFreeSlots && !this.showBooked) {
                    this.slotsMessage = data.message || 'نوبت آزادی نیست. «نمایش تایم‌های پر شده» را فعال کنید یا از «نوبت استثنا» استفاده کنید.';
                } else if (data.message) {
                    this.slotsMessage = data.message;
                }
            } catch (e) {
                this.slotsMessage = 'خطا در بارگذاری نوبت‌ها.';
            } finally {
                this.loadingSlots = false;
                this.preserveTime = false;
            }
        },

        selectSlot(slot) {
            if (slot.booked) {
                this.activeBooking = slot.booking || null;
                this.activeSlotLabel = slot.label || slot.time;
                this.bookedModalOpen = true;
                return;
            }
            if (slot.bookable === false) return;
            this.timeValue = slot.time;
            this.isException = !!slot.exception;
            this.showExceptionTimeInput = false;
        },

        resetCooldown() {
            this.cooldownConflict = false;
            this.cooldownAcked = false;
            this.cooldownChecked = false;
            this.cooldownMessage = '';
            this.cooldownConfirm = '';
            this.cooldownPrevious = null;
        },
        scheduleCooldownCheck() {
            this.cooldownAcked = false;
            this.cooldownChecked = false;
            clearTimeout(this.cooldownTimer);
            this.cooldownTimer = setTimeout(() => this.checkCooldown(), 280);
        },
        async checkCooldown() {
            if (!this.cooldownUrl || !this.dateKey || !this.eyeSide || (!this.subtypeId && !this.typeId)) {
                this.cooldownConflict = false;
                this.cooldownMessage = '';
                this.cooldownConfirm = '';
                this.cooldownPrevious = null;
                this.cooldownChecked = true;
                return;
            }
            const national = document.getElementById('national_code');
            const nc = national ? onlyDigits(national.value) : '';
            if (!this.patientId && nc.length !== 10) {
                this.cooldownConflict = false;
                this.cooldownMessage = '';
                this.cooldownConfirm = '';
                this.cooldownPrevious = null;
                this.cooldownChecked = true;
                return;
            }
            try {
                let url = this.cooldownUrl
                    + '?surgery_type_id=' + encodeURIComponent(this.typeId || '')
                    + '&surgery_subtype_id=' + encodeURIComponent(this.subtypeId || '')
                    + '&eye_side=' + encodeURIComponent(this.eyeSide)
                    + '&date=' + encodeURIComponent(this.dateKey);
                if (this.patientId) url += '&patient_id=' + encodeURIComponent(this.patientId);
                if (nc) url += '&national_code=' + encodeURIComponent(nc);
                if (this.excludeId) url += '&exclude_id=' + encodeURIComponent(this.excludeId);
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                this.cooldownConflict = !!data.conflict;
                this.cooldownMessage = data.message || '';
                this.cooldownConfirm = data.confirm_message || data.message || '';
                this.cooldownPrevious = data.previous || null;
            } catch (e) {
                this.cooldownConflict = false;
                this.cooldownMessage = '';
                this.cooldownConfirm = '';
                this.cooldownPrevious = null;
            } finally {
                this.cooldownChecked = true;
            }
        },
        confirmCooldownIfNeeded() {
            if (this.cooldownAcked || !this.cooldownConflict) {
                return true;
            }
            if (!confirm(this.cooldownConfirm || this.cooldownMessage)) {
                return false;
            }
            this.cooldownAcked = true;
            return true;
        },
        async finishGuardedSubmit(form) {
            if (this.submitGuardBusy) return;
            this.submitGuardBusy = true;
            try {
                await this.checkCooldown();
                if (!this.confirmCooldownIfNeeded()) return;
                if (!this.confirmCapacityIfNeeded()) return;
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            } finally {
                this.submitGuardBusy = false;
            }
        },

        closeBookedModal() {
            this.bookedModalOpen = false;
            this.activeBooking = null;
            this.activeSlotLabel = '';
        },

        addExceptionSlot() {
            if (!this.dateKey || !this.hospitalId) {
                alert('ابتدا بیمارستان، عمل و تاریخ را انتخاب کنید');
                return;
            }
            if (!this.confirmCapacityIfNeeded()) {
                return;
            }
            if (this.slotMode === 'queue') {
                const used = {};
                this.rawSlots.forEach((s) => {
                    const m = String(s.time || '').match(/Q0*(\d+)/i) || String(s.label || '').match(/(\d+)/);
                    if (m) used[parseInt(m[1], 10)] = true;
                });
                let next = 1;
                while (used[next] && next < 99) next++;
                if (next > 99) {
                    alert('ظرفیت نوبت استثنا تکمیل است');
                    return;
                }
                const token = 'Q' + String(next).padStart(2, '0');
                const exists = this.rawSlots.find((s) => s.time === token);
                if (!exists) {
                    this.rawSlots.push({
                        time: token,
                        label: 'نوبت ' + next + ' (استثنا)',
                        booked: false,
                        exception: true,
                    });
                } else {
                    exists.exception = true;
                    exists.label = (exists.label || ('نوبت ' + next)) + ' (استثنا)';
                }
                this.timeValue = token;
                this.isException = true;
                this.showExceptionTimeInput = false;
                this.slotsMessage = '';
                return;
            }

            this.showExceptionTimeInput = true;
            this.exceptionTimeDraft = '';
        },

        confirmExceptionTime() {
            const raw = (this.exceptionTimeDraft || '').trim();
            if (!/^\d{1,2}:\d{2}$/.test(raw)) {
                alert('ساعت معتبر وارد کنید');
                return;
            }
            if (!this.confirmCapacityIfNeeded()) {
                return;
            }
            const parts = raw.split(':');
            const hh = String(Math.min(23, parseInt(parts[0], 10))).padStart(2, '0');
            const mm = String(Math.min(59, parseInt(parts[1], 10))).padStart(2, '0');
            const token = hh + ':' + mm;
            const exists = this.rawSlots.find((s) => String(s.time).slice(0, 5) === token || s.time === token);
            if (exists) {
                if (exists.booked) {
                    alert('این ساعت قبلاً رزرو شده است');
                    return;
                }
                this.timeValue = exists.time;
                this.isException = true;
                exists.exception = true;
            } else {
                this.rawSlots.push({
                    time: token,
                    label: token + ' (استثنا)',
                    booked: false,
                    exception: true,
                });
                this.timeValue = token;
                this.isException = true;
            }
            this.showExceptionTimeInput = false;
            this.slotsMessage = '';
        },

        prepareSubmit(e) {
            const national = document.getElementById('national_code');
            const noNational = document.querySelector('input[name="no_national_code"]');
            // Only .checked — checkbox.value is always "1" even when unchecked.
            const skipNational = !!(noNational && noNational.checked);
            const mobile = document.getElementById('mobile');
            const mobile2 = document.getElementById('mobile_secondary');
            if (national && !skipNational && !national.disabled) {
                national.value = onlyDigits(national.value).slice(0, 10);
            }
            if (mobile) mobile.value = onlyDigits(mobile.value).slice(0, 11);
            if (mobile2 && mobile2.value) mobile2.value = onlyDigits(mobile2.value).slice(0, 11);
            if (!skipNational && national && !isValidNationalCode(national.value)) {
                e.preventDefault();
                alert('کد ملی باید دقیقاً ۱۰ رقم باشد');
                national.focus();
                return;
            }
            if (skipNational && national) national.value = '';
            if (mobile && !isValidMobile(mobile.value)) {
                e.preventDefault();
                alert('شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود');
                mobile.focus();
                return;
            }
            if (mobile2 && mobile2.value && !isValidMobile(mobile2.value)) {
                e.preventDefault();
                alert('شماره تماس دوم معتبر نیست');
                mobile2.focus();
                return;
            }
            if (!this.hospitalId) { e.preventDefault(); alert('بیمارستان را انتخاب کنید'); return; }
            if (!this.typeId) { e.preventDefault(); alert('نوع عمل را انتخاب کنید'); return; }
            if (!this.subtypeReady) { e.preventDefault(); alert('زیرگروه را انتخاب کنید'); return; }
            if (!this.dateKey) { e.preventDefault(); alert('تاریخ را انتخاب کنید'); return; }
            if (!this.timeValue) { e.preventDefault(); alert('نوبت یا ساعت را انتخاب کنید'); return; }

            const eye = document.getElementById('eye_side');
            if (eye && !String(eye.value || '').trim()) {
                e.preventDefault();
                alert('انتخاب چشم (راست / چپ / هر دو) الزامی است');
                eye.focus();
                return;
            }

            if ((this.needsCapacityNotice() && !this.capacityAcked) || !this.cooldownChecked || (this.cooldownConflict && !this.cooldownAcked)) {
                e.preventDefault();
                this.finishGuardedSubmit(e.target);
                return;
            }

            // اگر ظرفیت کافی نبود ولی کاربر تأیید کرده، به‌صورت خودکار استثنا ثبت شود
            if (
                this.capacityMeta
                && this.capacityMeta.subtype_allowed === false
                && this.capacityAcked
            ) {
                this.isException = true;
            }
        },
    };
}

function clinicAttachPicker() {
    return {
        previews: [],
        count: 0,
        label: '',
        error: '',
        pick(event, source) {
            const picked = Array.from(event.target.files || []);
            event.target.value = '';
            this.addFiles(picked, source);
        },
        onPaste(event) {
            const bag = event.clipboardData;
            if (!bag) return;
            const files = [];
            const fromList = bag.files && bag.files.length ? Array.from(bag.files) : [];
            fromList.forEach(function (file) {
                if (file && file.type && file.type.indexOf('image/') === 0) files.push(file);
            });
            if (!files.length && bag.items) {
                Array.from(bag.items).forEach(function (item) {
                    if (item.kind === 'file' && item.type && item.type.indexOf('image/') === 0) {
                        const file = item.getAsFile();
                        if (file) files.push(file);
                    }
                });
            }
            if (!files.length) return;
            event.preventDefault();
            this.addFiles(files, 'paste');
        },
        addFiles(picked, source) {
            picked = Array.from(picked || []);
            this.error = '';
            if (!picked.length) return;
            try {
                const dt = new DataTransfer();
                const existing = Array.from((this.$refs.files && this.$refs.files.files) ? this.$refs.files.files : []);
                const merged = existing.concat(picked);
                const maxBytes = 10 * 1024 * 1024;
                for (const file of merged) {
                    if (!file.type || !file.type.startsWith('image/')) {
                        this.error = 'فقط فایل تصویری مجاز است.';
                        return;
                    }
                    if (file.size > maxBytes) {
                        this.error = 'حجم هر تصویر نباید بیشتر از ۱۰ مگابایت باشد.';
                        return;
                    }
                    dt.items.add(file);
                }
                this.$refs.files.files = dt.files;
                this.count = dt.files.length;
            } catch (e) {
                this.error = 'مرورگر از انتخاب این تصویر پشتیبانی نمی‌کند.';
                return;
            }
            this.previews.forEach(function (item) { URL.revokeObjectURL(item.url); });
            this.previews = Array.from(this.$refs.files.files).slice(0, 4).map(function (file) {
                return { url: URL.createObjectURL(file), name: file.name };
            });
            this.label = source === 'camera' ? 'گرفته‌شده با دوربین' : (source === 'paste' ? 'چسبانده‌شده از کلیپ‌بورد' : 'انتخاب از گالری');
        },
        clearFiles() {
            this.previews.forEach(function (item) { URL.revokeObjectURL(item.url); });
            this.previews = [];
            this.count = 0;
            this.label = '';
            this.error = '';
            if (this.$refs.files) this.$refs.files.value = '';
        },
    };
}
</script>

<style>
    .cooldown-warn {
        border: 1px solid color-mix(in srgb, #dc2626 35%, var(--line));
        background: color-mix(in srgb, #fef2f2 88%, var(--panel));
        border-radius: 1rem;
        padding: 0.85rem 1rem;
    }
    .cooldown-warn__title {
        margin: 0 0 0.35rem;
        font-size: 0.82rem;
        font-weight: 800;
        color: #991b1b;
    }
    .cooldown-warn__msg {
        margin: 0;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.7;
    }
    .cooldown-warn__grid {
        display: grid;
        gap: 0.45rem;
        margin: 0.7rem 0 0;
        font-size: 0.78rem;
    }
    .cooldown-warn__grid dt {
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--muted);
    }
    .cooldown-warn__grid dd {
        margin: 0;
        font-weight: 800;
        color: var(--ink);
    }
    .cooldown-warn__hint {
        margin: 0.7rem 0 0;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--muted);
    }
    .booking-slot.is-selected {
        background: var(--brand-dark) !important;
        color: #fff !important;
        box-shadow: inset 0 0 0 2px color-mix(in srgb, #fff 35%, transparent);
    }
    .booking-slot.is-exception-slot {
        box-shadow: inset 0 0 0 1.5px #7c3aed;
        background: color-mix(in srgb, #7c3aed 12%, var(--panel));
        color: #5b21b6;
        font-weight: 800;
    }
    .booking-slot.is-exception-slot.is-selected {
        background: #6d28d9 !important;
    }
    .exception-btn-wrapper {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
    }
    .btn-exception {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.5rem;
        padding: 0.55rem 1rem;
        border: 0;
        border-radius: 0.9rem;
        background: linear-gradient(145deg, #a78bfa 0%, #7c3aed 55%, #5b21b6 100%);
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 8px 18px -10px rgba(91, 33, 182, 0.75);
    }
    .btn-exception:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .exception-hint {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--muted);
    }
    .booking-day.is-available {
        background: color-mix(in srgb, #16a34a 18%, var(--panel));
        color: #14532d;
        font-weight: 800;
        box-shadow: inset 0 0 0 1px color-mix(in srgb, #16a34a 35%, transparent);
    }
    .booking-day.is-full {
        background: color-mix(in srgb, #dc2626 16%, var(--panel));
        color: #7f1d1d;
        font-weight: 800;
        box-shadow: inset 0 0 0 1px color-mix(in srgb, #dc2626 35%, transparent);
    }
    .booking-day.is-available.is-active,
    .booking-day.is-full.is-active {
        outline: 2px solid var(--brand-dark);
        outline-offset: 1px;
    }
    .cal-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem 1rem;
        margin-top: .85rem;
        font-size: .72rem;
        color: var(--muted);
        font-weight: 700;
    }
    .cal-legend span {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .cal-legend i {
        width: .75rem;
        height: .75rem;
        border-radius: .25rem;
        display: inline-block;
    }
    .cal-legend .lg-available { background: color-mix(in srgb, #16a34a 45%, #fff); }
    .cal-legend .lg-full { background: color-mix(in srgb, #dc2626 45%, #fff); }
    .cal-legend .lg-off { background: var(--panel-soft); box-shadow: inset 0 0 0 1px var(--line); }
    html.dark .booking-day.is-available { color: #bbf7d0; }
    html.dark .booking-day.is-full { color: #fecaca; }
    html.dark .booking-slot.is-exception-slot { color: #ddd6fe; }
    .booking-slot.is-booked-clickable {
        cursor: pointer;
        opacity: 1;
    }
    .booking-slot.is-booked-clickable:hover {
        filter: brightness(0.97);
        box-shadow: inset 0 0 0 2px color-mix(in srgb, #dc2626 45%, transparent);
    }
    .booking-slot.is-removed {
        opacity: 0.45;
        text-decoration: line-through;
    }
    .booked-slot-modal {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .booked-slot-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
    }
    .booked-slot-modal__panel {
        position: relative;
        z-index: 1;
        width: min(100%, 24rem);
        max-height: min(90vh, 32rem);
        overflow: auto;
        padding: 1rem 1.1rem;
    }
    .booked-slot-modal__close {
        width: 2rem;
        height: 2rem;
        border: 0;
        border-radius: 999px;
        background: var(--panel-soft);
        color: var(--muted);
        font-size: 1.25rem;
        line-height: 1;
        cursor: pointer;
    }
    .booked-slot-modal__grid {
        display: grid;
        gap: 0.65rem;
        font-size: 0.8125rem;
    }
    .booked-slot-modal__grid dt {
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--muted);
    }
    .booked-slot-modal__grid dd {
        margin: 0;
        font-weight: 700;
        color: var(--ink);
    }
    .doc-upload-sources {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .doc-upload-source {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 6.5rem;
        padding: 1rem 0.75rem;
        border-radius: 1rem;
        border: 1px dashed color-mix(in srgb, var(--brand) 35%, var(--line));
        background: color-mix(in srgb, var(--panel-soft) 80%, transparent);
        color: var(--ink);
        cursor: pointer;
        text-align: center;
    }
    .doc-upload-source:hover {
        border-color: var(--brand);
        background: color-mix(in srgb, var(--brand) 8%, var(--panel));
    }
    .doc-upload-source svg { width: 1.75rem; height: 1.75rem; color: var(--brand-dark); }
    .doc-upload-source__title { font-size: 0.875rem; font-weight: 800; }
    .doc-upload-source__hint { font-size: 0.7rem; color: var(--muted); font-weight: 600; }
    .doc-upload-preview {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
        padding: 0.75rem;
        border-radius: 1rem;
        border: 1px solid var(--line);
        background: var(--panel);
    }
    .doc-upload-preview img,
    .doc-upload-preview__img {
        width: auto;
        height: auto;
        max-width: 5.5rem;
        max-height: 5.5rem;
        object-fit: contain;
        border-radius: 0.75rem;
        background: var(--panel-soft);
    }
    .doc-upload-preview__meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        font-size: 0.75rem;
        color: var(--muted);
    }
    .doc-upload-preview__meta strong { color: var(--ink); font-size: 0.8rem; }
    .doc-upload-preview__clear {
        align-self: flex-start;
        margin-top: 0.25rem;
        color: var(--danger, #b42318);
        font-weight: 700;
        font-size: 0.75rem;
        background: none;
        border: 0;
        padding: 0;
        cursor: pointer;
    }
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>
