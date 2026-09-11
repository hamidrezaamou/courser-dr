/**
 * Progressive patient identity fields for visit/surgery registration.
 * National code is the unique key; existing files fill the form and lock name.
 */
window.patientIdentityLookup = function patientIdentityLookup(config) {
    const initial = config.initial || {};
    const hasOld = Boolean(initial.national_code || initial.no_national_code || initial.name);

    return {
        lookupUrl: config.lookupUrl,
        noNationalCode: Boolean(initial.no_national_code),
        nationalCode: initial.national_code || '',
        name: initial.name || '',
        mobile: initial.mobile || '',
        age: initial.age || '',
        mobileSecondary: initial.mobile_secondary || '',
        found: false,
        looking: false,
        status: '',
        lookupSeq: 0,
        debounceTimer: null,

        get detailsOpen() {
            return this.noNationalCode || this.found || this.status === 'new' || this.status === 'manual';
        },

        get nameLocked() {
            return this.found && !this.noNationalCode;
        },

        get nameDisabled() {
            if (this.noNationalCode) {
                return false;
            }
            if (this.found || this.status === 'new' || this.status === 'manual') {
                return false;
            }
            return true;
        },

        get extrasLocked() {
            return this.found && !this.noNationalCode;
        },

        init() {
            if (this.noNationalCode) {
                this.status = 'manual';
                return;
            }
            if (String(this.nationalCode).replace(/\D/g, '').length === 10) {
                this.lookupNow(false);
            } else if (hasOld && this.name) {
                this.status = 'manual';
            }
        },

        onNoCodeChange() {
            if (this.noNationalCode) {
                this.nationalCode = '';
                this.found = false;
                this.status = 'manual';
                this.looking = false;
                return;
            }
            this.status = '';
            this.found = false;
            this.name = '';
            this.mobile = '';
            this.age = '';
            this.mobileSecondary = '';
        },

        normalizeDigits(value, max) {
            return String(value || '')
                .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
                .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
                .replace(/\D/g, '')
                .slice(0, max);
        },

        onNationalInput(event) {
            this.nationalCode = this.normalizeDigits(event.target.value, 10);
            event.target.value = this.nationalCode;
            this.scheduleLookup();
        },

        scheduleLookup() {
            clearTimeout(this.debounceTimer);
            const digits = this.normalizeDigits(this.nationalCode, 10);
            this.nationalCode = digits;

            if (this.noNationalCode) {
                return;
            }

            if (digits.length < 10) {
                this.found = false;
                this.status = '';
                this.looking = false;
                this.name = '';
                this.mobile = '';
                this.age = '';
                this.mobileSecondary = '';
                return;
            }

            this.debounceTimer = setTimeout(() => this.lookupNow(true), 280);
        },

        lookupNow(clearOnMiss) {
            const digits = this.normalizeDigits(this.nationalCode, 10);
            if (digits.length !== 10 || this.noNationalCode) {
                return;
            }

            const seq = ++this.lookupSeq;
            this.looking = true;
            this.status = 'looking';

            const url = this.lookupUrl + (this.lookupUrl.includes('?') ? '&' : '?') + 'national_code=' + encodeURIComponent(digits);

            fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then((res) => res.json())
                .then((data) => {
                    if (seq !== this.lookupSeq) {
                        return;
                    }
                    this.looking = false;
                    if (data.found && data.patient) {
                        this.found = true;
                        this.status = 'found';
                        this.nationalCode = data.patient.national_code || digits;
                        this.name = data.patient.name || '';
                        this.mobile = data.patient.mobile || '';
                        this.mobileSecondary = data.patient.mobile_secondary || '';
                        this.age = data.patient.age || '';
                        return;
                    }
                    this.found = false;
                    this.status = 'new';
                    if (clearOnMiss) {
                        // Keep typed code; unlock name/mobile for a new file.
                        if (!this.name) {
                            this.name = '';
                        }
                    }
                })
                .catch(() => {
                    if (seq !== this.lookupSeq) {
                        return;
                    }
                    this.looking = false;
                    this.found = false;
                    this.status = 'error';
                });
        },
    };
};
