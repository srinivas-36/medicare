<div
    x-show="reportsOpen"
    x-cloak
    class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="chart-reports-title"
>
    <div class="absolute inset-0 bg-[#0B1F3A]/50" @click="reportsOpen = false"></div>
    <div class="relative flex max-h-[min(720px,92dvh)] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-[0_24px_80px_rgba(11,31,58,0.28)] sm:rounded-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-[#E2E8F0] px-5 py-4">
            <div>
                <h2 id="chart-reports-title" class="text-lg font-semibold text-[#181A1E]" x-text="reportHeading"></h2>
                <p class="mt-0.5 text-sm text-[#64748B]">Open a file in the browser, or save a copy.</p>
            </div>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-[#181A1E] hover:bg-[#F4F7FB]" @click="reportsOpen = false" aria-label="Close reports">
                <x-ui.icon name="close" class="h-4 w-4" />
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            <p x-show="reportFiles.length === 0" class="text-sm text-[#64748B]">No reports are attached to this visit.</p>
            <ul x-show="reportFiles.length > 0" class="divide-y divide-[#F1F5F9]">
                <template x-for="file in reportFiles" :key="file.id">
                    <li class="flex items-center gap-3 py-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#F4F7FB] text-[#64748B]">
                            <x-ui.icon name="file" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-[#181A1E]" x-text="file.name"></span>
                            <span class="block text-xs text-[#64748B]" x-show="file.date" x-text="file.date"></span>
                        </span>
                        <span class="flex shrink-0 items-center gap-1.5">
                            <a :href="file.url" target="_blank" rel="noopener" class="inline-flex h-8 items-center rounded-lg border border-[#D6E4FF] bg-[#F4F8FF] px-2.5 text-xs font-semibold text-[#0067FF] no-underline hover:bg-[#E8F2FF]">View</a>
                            <a :href="file.download_url || file.url" class="inline-flex h-8 items-center rounded-lg border border-[#E2E8F0] bg-white px-2.5 text-xs font-semibold text-[#181A1E] no-underline hover:bg-[#F8FAFC]">Download</a>
                        </span>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>

<div
    x-show="action === 'note' || action === 'follow'"
    x-cloak
    class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="chart-action-title"
>
    <div class="absolute inset-0 bg-[#0B1F3A]/50" @click="closeAction()"></div>
    <div class="relative flex max-h-[min(760px,92dvh)] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-[0_24px_80px_rgba(11,31,58,0.28)] sm:rounded-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-[#E2E8F0] px-5 py-4">
            <div>
                <h2 id="chart-action-title" class="text-lg font-semibold text-[#181A1E]" x-text="action === 'follow' ? 'Schedule follow-up' : 'Add note'"></h2>
                <p class="mt-0.5 text-sm text-[#64748B]">Saved on the visit you choose. The next upcoming visit is selected first.</p>
            </div>
            <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[#181A1E] hover:bg-[#F4F7FB]" @click="closeAction()" aria-label="Close">
                <x-ui.icon name="close" class="h-4 w-4" />
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            <label class="block text-sm font-semibold text-[#181A1E]">
                Visit
                <select class="field mt-1.5" x-model="visitId">
                    <template x-for="item in visits" :key="item.id">
                        <option :value="String(item.id)" x-text="item.label"></option>
                    </template>
                </select>
            </label>

            <template x-if="visit && action === 'note'">
                <form method="POST" :action="visit.consult_url" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-semibold text-[#181A1E]">
                        Symptoms and examination
                        <textarea name="clinical_notes" rows="4" class="field mt-1.5" x-model="visit.clinical_notes"></textarea>
                    </label>
                    <label class="block text-sm font-semibold text-[#181A1E]">
                        Diagnosis
                        <input type="text" name="diagnosis" class="field mt-1.5" x-model="visit.diagnosis">
                    </label>
                    <label class="block text-sm font-semibold text-[#181A1E]">
                        Treatment plan
                        <textarea name="plan" rows="3" class="field mt-1.5" x-model="visit.plan"></textarea>
                    </label>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="btn-secondary h-9" @click="closeAction()">Cancel</button>
                        <button type="submit" class="btn-primary h-9">Save note</button>
                    </div>
                </form>
            </template>

            <template x-if="visit && action === 'follow'">
                <form method="POST" :action="visit.consult_url" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-semibold text-[#181A1E]">
                        Follow-up instructions
                        <textarea name="follow_up_instructions" rows="3" class="field mt-1.5" x-model="visit.follow_up_instructions"></textarea>
                    </label>
                    <label class="block text-sm font-semibold text-[#181A1E]">
                        Next review date
                        <input type="date" name="follow_up_on" class="field mt-1.5" x-model="visit.follow_up_on">
                    </label>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="btn-secondary h-9" @click="closeAction()">Cancel</button>
                        <button type="submit" class="btn-primary h-9">Save follow-up</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
