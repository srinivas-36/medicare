@extends('layouts.dashboard')

@section('title', 'Doctor approvals')

@section('content')
    <x-ui.page-header title="Doctors" description="Pending doctors stay out of the public directory until you approve them." />

    @if (session('status'))
        <p class="alert-success">{{ session('status') }}</p>
    @endif

    <section class="panel">
        <div class="overflow-x-auto">
            <table class="data-table min-w-[860px]">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Specialty</th>
                        <th>Status</th>
                        <th>Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($doctors as $doctor)
                        @php
                            $statusClass = match ($doctor->approval_status) {
                                'approved' => 'badge-success',
                                'cancelled' => 'badge-danger',
                                default => 'badge-warning',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$doctor->user?->name ?? 'Doctor'" :photo="$doctor->user?->photo" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-heading">{{ $doctor->user?->name }}</p>
                                        <p class="truncate text-xs text-muted">{{ $doctor->user?->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-body">{{ $doctor->specialization ?: 'Specialization not set' }}</td>
                            <td>
                                <span class="{{ $statusClass }}">{{ ucfirst($doctor->approval_status) }}</span>
                                @if ($doctor->rejection_reason)
                                    <p class="mt-1 max-w-xs text-xs text-body">{{ $doctor->rejection_reason }}</p>
                                @endif
                            </td>
                            <td>
                                @if ($doctor->approval_status === 'pending')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <form method="POST" action="{{ route('admin.doctors.approve', $doctor) }}">
                                            @csrf
                                            <button class="btn-primary h-9 px-3" type="submit">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.doctors.reject', $doctor) }}" class="flex flex-wrap gap-2">
                                            @csrf
                                            <label class="sr-only" for="rejection-{{ $doctor->id }}">Rejection reason</label>
                                            <input id="rejection-{{ $doctor->id }}" name="rejection_reason" required placeholder="Reason" class="field h-9 w-40">
                                            <button class="btn-danger h-9 px-3" type="submit">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-muted">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-sm text-body">No doctor profiles yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $doctors->links() }}</div>
@endsection
