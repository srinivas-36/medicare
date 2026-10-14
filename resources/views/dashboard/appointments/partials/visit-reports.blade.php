<section class="dash-panel p-4 sm:p-5">
    <div>
        <h2 class="text-base font-semibold text-heading">Reports</h2>
        <p class="mt-1 text-sm leading-6 text-body">Files shared for this visit. A new upload starts the analysis again in the background.</p>
    </div>

    @if (count($booking['reports']) === 0)
        <p class="mt-5 text-sm leading-6 text-body">No reports were attached to this booking.</p>
    @else
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach ($booking['reports'] as $group)
                <div class="rounded-2xl border border-line bg-page p-4">
                    <p class="text-sm font-bold text-navy">{{ $group['name'] }}</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($group['files'] as $file)
                            <li>
                                <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
                                    <x-ui.icon name="file" class="h-4 w-4 shrink-0" />
                                    <span class="break-all">{{ $file['name'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif

    @if ($canUpload)
        <form method="POST" action="{{ route('patient.bookings.reports.store', $appointment) }}" enctype="multipart/form-data" class="mt-6 rounded-2xl border border-dashed border-[#b9d4ff] bg-[#f7faff] p-5">
            @csrf
            <h3 class="text-sm font-bold text-navy">Add another report</h3>
            <p class="mt-1 text-sm leading-6 text-body">PDF or image, up to 5 MB each. Analysis runs in the background after the upload.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold text-navy">
                    Blood test reports
                    <input type="file" name="blood_reports[]" accept="image/*,application/pdf" multiple class="mt-2 block w-full text-sm font-normal text-body file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary">
                </label>
                <label class="block text-sm font-semibold text-navy">
                    X-ray scans
                    <input type="file" name="xray_reports[]" accept="image/*,application/pdf" multiple class="mt-2 block w-full text-sm font-normal text-body file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary">
                </label>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-end">
                <label class="block text-sm font-semibold text-navy">
                    Other report name
                    <input type="text" name="other_report_name" value="{{ old('other_report_name') }}" placeholder="MRI, ultrasound, prescription" class="{{ $inputClass }} mt-2">
                </label>
                <label class="block text-sm font-semibold text-navy">
                    Other files
                    <input type="file" name="other_reports[]" accept="image/*,application/pdf" multiple class="mt-2 block w-full text-sm font-normal text-body file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary">
                </label>
            </div>
            <x-input-error :messages="$errors->get('blood_reports')" class="mt-3" />
            <x-input-error :messages="$errors->get('blood_reports.*')" class="mt-3" />
            <x-input-error :messages="$errors->get('xray_reports')" class="mt-3" />
            <x-input-error :messages="$errors->get('xray_reports.*')" class="mt-3" />
            <x-input-error :messages="$errors->get('other_report_name')" class="mt-3" />
            <x-input-error :messages="$errors->get('other_reports')" class="mt-3" />
            <x-input-error :messages="$errors->get('other_reports.*')" class="mt-3" />
            <button type="submit" class="btn-primary mt-4">Upload reports</button>
        </form>
    @endif
</section>
