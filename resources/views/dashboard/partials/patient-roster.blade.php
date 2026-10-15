<div x-show="patientMode === 'existing'" x-cloak class="mt-5">
    <label for="patient-roster-search" class="label">Patient list</label>
    <p class="mb-3 text-sm text-text">The list opens with patients already on file. Search by name, email, or phone, then choose one.</p>

    <div class="overflow-hidden rounded-2xl border border-line bg-white">
        <div class="flex items-center gap-2 border-b border-line bg-page px-3">
            <x-ui.icon name="search" class="h-4 w-4 shrink-0 text-muted" />
            <input
                id="patient-roster-search"
                type="search"
                x-model="query"
                @input="scheduleSearch()"
                @keydown.enter.prevent="lookup()"
                @keydown.escape="query = ''; scheduleSearch()"
                placeholder="Search name, email, or phone"
                autocomplete="off"
                role="combobox"
                aria-controls="patient-roster"
                aria-autocomplete="list"
                :aria-expanded="'true'"
                class="h-12 w-full border-0 bg-transparent text-sm text-ink outline-none placeholder:text-muted"
            >
            <span class="inline-flex h-4 w-4 shrink-0 text-primary" x-show="searching" role="status" aria-label="Searching patients">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
            </span>
            <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-navy ring-1 ring-line" x-text="listLabel()"></span>
        </div>

        <div id="patient-roster" role="listbox" aria-label="Patients" class="max-h-72 overflow-auto">
            <p x-show="searching" class="border-b border-line px-4 py-3 text-sm text-text">Searching patients…</p>
            <p x-show="!searching && searchError !== ''" x-text="searchError" class="px-4 py-3 text-sm text-red-600"></p>
            <p x-show="!searching && searchError === '' && visiblePatients().length === 0" class="px-4 py-10 text-center text-sm leading-6 text-text" x-text="emptyRoster()"></p>
            <template x-for="patient in visiblePatients()" :key="patient.id">
                <button
                    type="button"
                    role="option"
                    @click="choose(patient)"
                    class="flex w-full items-center gap-3 border-b border-line px-4 py-3 text-left last:border-b-0"
                    :class="String(patientId) === String(patient.id) ? 'bg-light' : 'hover:bg-page'"
                    :aria-selected="String(patientId) === String(patient.id) ? 'true' : 'false'"
                >
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-light text-xs font-semibold text-primary" x-text="initials(patient.name)"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-navy" x-text="patient.name"></span>
                        <span class="mt-0.5 block truncate text-xs text-text" x-text="[patient.email, patient.phone].filter(Boolean).join(' · ') || 'No contact details'"></span>
                    </span>
                    <span x-show="String(patientId) === String(patient.id)" class="shrink-0 text-primary">
                        <x-ui.icon name="check" class="h-5 w-5" />
                    </span>
                </button>
            </template>
        </div>
    </div>

    <p x-show="chosen" class="mt-3 rounded-xl bg-light px-4 py-3 text-sm text-navy">
        Selected patient:
        <span class="font-semibold" x-text="chosen ? chosen.name : ''"></span>
        <span class="text-text" x-text="chosen && [chosen.email, chosen.phone].filter(Boolean).length ? ' · ' + [chosen.email, chosen.phone].filter(Boolean).join(' · ') : ''"></span>
    </p>
</div>
