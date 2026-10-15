<script>
    window.deskBooking = function () {
        const node = document.getElementById('desk-booking-data');
        const config = node ? JSON.parse(node.textContent || '{}') : {};
        const roster = Array.isArray(config.roster) ? config.roster : (Array.isArray(config.patients) ? config.patients : []);
        const patients = Array.isArray(config.patients) ? config.patients : roster;
        const patientId = String(config.patientId || '');
        const known = roster.concat(patients);

        return {
            searchUrl: config.searchUrl || '',
            query: config.query || '',
            roster,
            patients,
            patientId,
            patientMode: config.patientMode === 'new' ? 'new' : 'existing',
            newName: config.newName || '',
            newEmail: config.newEmail || '',
            newPhone: config.newPhone || '',
            newAge: config.newAge || '',
            newGender: config.newGender || '',
            newBlood: config.newBlood || '',
            newPassword: '',
            newPasswordConfirmation: '',
            chosen: known.find((patient) => String(patient.id) === patientId) || null,
            searched: Boolean(config.searched),
            remote: Boolean(config.searched),
            searching: false,
            searchError: '',
            date: config.date || '',
            slot: config.slot || '',
            days: config.days || {},
            minDate: config.minDate || '',
            maxDate: config.maxDate || '',
            cursor: config.month || '',
            duration: config.duration || 30,
            openGroups: {},
            controller: null,
            timer: null,
            patientReady() {
                if (this.patientMode === 'new') {
                    return this.newName.trim() !== '' && this.newEmail.trim() !== '' && this.newPhone.trim() !== '' && /^[0-9]{2}$/.test(String(this.newAge).trim());
                }
                return this.patientId !== '';
            },
            wizardStep() {
                if (!this.patientReady()) {
                    return 1;
                }
                if (this.date === '' || this.slot === '') {
                    return 2;
                }
                return 4;
            },
            avatarStyle(index) {
                const tones = [
                    ['#E8F1FF', '#2F6FED'],
                    ['#FFF4E6', '#E5922A'],
                    ['#E7F8EE', '#22A06B'],
                    ['#FFF0EA', '#E07A45'],
                ];
                const tone = tones[Number(index) % tones.length];
                return 'background-color:' + tone[0] + ';color:' + tone[1];
            },
            genderLabel(value) {
                const text = String(value || '').trim();
                if (text === '') {
                    return '—';
                }
                return text.charAt(0).toUpperCase() + text.slice(1);
            },
            blank(value) {
                const text = value === null || value === undefined ? '' : String(value).trim();
                return text === '' ? '—' : text;
            },
            slotDateLabel(iso) {
                if (!iso) {
                    return '';
                }
                const [year, month, day] = String(iso).split('-').map(Number);
                const date = new Date(year, month - 1, day);
                const weekday = date.toLocaleDateString('en-US', { weekday: 'long' });
                const mon = date.toLocaleDateString('en-US', { month: 'short' });
                return weekday + ', ' + day + ' ' + mon + ' ' + year;
            },
            periodBlocks() {
                const groups = this.days[this.date] || [];
                const order = ['Morning', 'Afternoon', 'Evening', 'Open'];
                const buckets = {};
                groups.forEach((group, index) => {
                    const period = group.period || 'Open';
                    if (!buckets[period]) {
                        buckets[period] = [];
                    }
                    buckets[period].push(Object.assign({ key: period + '-' + index }, group));
                });
                return order.filter((period) => buckets[period]).map((period) => ({
                    period,
                    rows: buckets[period],
                }));
            },
            groupRange(group) {
                if (group.range) {
                    return group.range;
                }
                const slots = group.slots || [];
                if (slots.length === 0) {
                    return 'Open';
                }
                return slots[0].label + ' – ' + slots[slots.length - 1].endLabel;
            },
            slotCountLabel(group) {
                const count = (group.slots || []).length;
                return count === 1 ? '1 slot' : count + ' slots';
            },
            groupChosen(group) {
                return (group.slots || []).some((item) => item.value === this.slot);
            },
            toggleGroup(group) {
                const slots = group.slots || [];
                if (slots.length <= 1) {
                    if (slots[0]) {
                        this.slot = slots[0].value;
                    }
                    return;
                }
                this.openGroups = Object.assign({}, this.openGroups, {
                    [group.key]: !this.openGroups[group.key],
                });
            },
            listLabel() {
                const count = this.visiblePatients().length;
                return count === 1 ? '1 patient' : count + ' patients';
            },
            emptyRoster() {
                const term = this.query.trim();
                if (term.length >= 2) {
                    return 'No patients match that search.';
                }
                if (term.length === 1) {
                    return 'Keep typing to search every patient, or clear the box to see the list.';
                }
                return 'No patients yet. Switch to New patient to add the first one.';
            },
            visiblePatients() {
                const term = this.query.trim().toLowerCase();
                if (term.length >= 2 && this.remote) {
                    return this.patients;
                }
                if (term === '') {
                    return this.roster;
                }
                return this.roster.filter((patient) => {
                    return [patient.name, patient.email, patient.phone]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase()
                        .includes(term);
                });
            },
            choose(patient) {
                this.patientId = String(patient.id);
                this.chosen = patient;
            },
            initials(name) {
                const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
                const letters = (parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '');
                return letters.toUpperCase() || 'P';
            },
            scheduleSearch() {
                window.clearTimeout(this.timer);
                const term = this.query.trim();
                if (term.length < 2) {
                    if (this.controller) {
                        this.controller.abort();
                        this.controller = null;
                    }
                    this.searching = false;
                    this.searchError = '';
                    this.remote = false;
                    this.searched = false;
                    return;
                }
                this.timer = window.setTimeout(() => this.lookup(), 300);
            },
            lookup() {
                const term = this.query.trim();
                if (this.controller) {
                    this.controller.abort();
                }
                if (term.length < 2) {
                    this.searching = false;
                    this.searchError = '';
                    this.controller = null;
                    this.remote = false;
                    return;
                }
                const controller = new AbortController();
                this.controller = controller;
                this.searching = true;
                this.searchError = '';
                this.searched = true;
                const timer = window.setTimeout(() => controller.abort(), 8000);
                fetch(this.searchUrl + '?q=' + encodeURIComponent(term), {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                }).then((response) => {
                    if (!response.ok) {
                        throw new Error('search failed');
                    }
                    return response.json();
                }).then((payload) => {
                    if (this.controller !== controller) {
                        return;
                    }
                    this.patients = payload.patients || [];
                    this.remote = true;
                }).catch((error) => {
                    if (this.controller !== controller) {
                        return;
                    }
                    this.patients = [];
                    this.remote = true;
                    this.searchError = error.name === 'AbortError'
                        ? 'Search is taking too long. Try again.'
                        : 'Search failed. Try again.';
                }).finally(() => {
                    window.clearTimeout(timer);
                    if (this.controller === controller) {
                        this.searching = false;
                        this.controller = null;
                    }
                });
            },
            timeValue() {
                return String(this.slot || '').split('|')[0] || '';
            },
            exactSlots() {
                return (this.days[this.date] || []).flatMap((group) => group.slots.map((item) => ({
                    value: item.value,
                    period: group.period,
                    range: item.label + ' – ' + item.endLabel,
                    label: item.label,
                    endLabel: item.endLabel,
                })));
            },
            selectDate(iso) {
                if (this.date !== iso) {
                    this.slot = '';
                }
                this.date = iso;
            },
            prettyDate(iso) {
                const [year, month, day] = String(iso).split('-').map(Number);
                return new Date(year, month - 1, day).toLocaleDateString(undefined, {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            },
            monthTitle() {
                const [year, month] = String(this.cursor).split('-').map(Number);
                if (!year || !month) {
                    return '';
                }
                return new Date(year, month - 1, 1).toLocaleDateString(undefined, {
                    month: 'long',
                    year: 'numeric',
                });
            },
            calendarCells() {
                const [year, month] = String(this.cursor).split('-').map(Number);
                if (!year || !month) {
                    return [];
                }
                const first = new Date(year, month - 1, 1);
                const count = new Date(year, month, 0).getDate();
                const cells = Array.from({ length: first.getDay() }, () => null);
                for (let day = 1; day <= count; day += 1) {
                    const iso = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                    const groups = this.days[iso] || [];
                    cells.push({
                        date: iso,
                        label: day,
                        open: iso >= this.minDate && iso <= this.maxDate && groups.length > 0,
                    });
                }
                return cells;
            },
            canShift(delta) {
                const [year, month] = String(this.cursor).split('-').map(Number);
                if (!year || !month) {
                    return false;
                }
                const next = new Date(year, month - 1 + delta, 1);
                const key = next.getFullYear() + '-' + String(next.getMonth() + 1).padStart(2, '0');
                const min = String(this.minDate).slice(0, 7);
                const max = String(this.maxDate).slice(0, 7);
                return key >= min && key <= max;
            },
            shiftMonth(delta) {
                if (!this.canShift(delta)) {
                    return;
                }
                const [year, month] = String(this.cursor).split('-').map(Number);
                const next = new Date(year, month - 1 + delta, 1);
                this.cursor = next.getFullYear() + '-' + String(next.getMonth() + 1).padStart(2, '0');
            },
            selectedVisit() {
                for (const group of (this.days[this.date] || [])) {
                    const match = group.slots.find((item) => item.value === this.slot);
                    if (match) {
                        return match;
                    }
                }
                return null;
            },
            askToBook(button) {
                const visit = this.selectedVisit();
                const form = button.form;
                const visitType = form ? form.querySelector('[name="visit_type"]') : null;
                const reason = form ? form.querySelector('[name="reason"]') : null;
                const who = this.patientMode === 'new'
                    ? this.newName.trim()
                    : (this.chosen ? this.chosen.name : '');
                window.appConfirm({
                    title: 'Confirm this appointment?',
                    message: 'Review the details below. This visit will be saved on the schedule.',
                    rows: [
                        { label: 'Patient', value: who },
                        { label: 'Date', value: this.date ? this.slotDateLabel(this.date) : '' },
                        { label: 'Time', value: visit ? visit.label + ' – ' + visit.endLabel : '' },
                        { label: 'Visit type', value: visitType && visitType.selectedOptions[0] ? visitType.selectedOptions[0].textContent.trim() : '' },
                        { label: 'Reason', value: reason ? reason.value.trim() : '' },
                    ],
                    confirmLabel: 'Confirm booking',
                    cancelLabel: 'Go back',
                }).then((accepted) => {
                    if (accepted && button.form) {
                        button.form.requestSubmit(button);
                    }
                });
            },
            canConfirm() {
                return this.date !== '' && this.slot !== '' && this.patientReady();
            },
            summary() {
                const visit = this.selectedVisit();
                if (!this.date || !visit) {
                    return '';
                }
                const who = this.patientMode === 'new'
                    ? (this.newName.trim() !== '' ? this.newName.trim() + ' · ' : '')
                    : (this.chosen ? this.chosen.name + ' · ' : '');
                return who + this.prettyDate(this.date) + ' · ' + visit.label + ' – ' + visit.endLabel;
            },
        };
    };
</script>
