@extends('layouts.dashboard')

@php
    $receptionists = $receptionists ?? collect();
@endphp

@section('title', 'Receptionists')

@section('content')
    <x-ui.page-header title="Your receptionists" description="People who can view the desk and update visits on your open hours.">
        <a href="{{ route('doctor.receptionists.create') }}" class="btn-primary">
            <x-ui.icon name="plus" class="h-4 w-4" />
            Add a receptionist
        </a>
    </x-ui.page-header>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <section class="dash-panel mt-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-page text-text">
                    <tr>
                        <th class="px-6 py-3 font-semibold">#</th>
                        <th class="px-6 py-3 font-semibold">Receptionist</th>
                        <th class="px-6 py-3 font-semibold">Phone</th>
                        <th class="px-6 py-3 font-semibold">Added</th>
                        <th class="px-6 py-3 font-semibold">Access</th>
                        <th class="px-6 py-3 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($receptionists as $receptionist)
                        @php($account = $receptionist->user)
                        <tr class="align-top hover:bg-page">
                            <td class="px-6 py-4 text-muted">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$account?->name ?? 'Receptionist'" size="sm" />
                                    <span>
                                        <span class="block font-semibold text-navy">{{ $account?->name }}</span>
                                        <span class="mt-0.5 block text-xs text-text">{{ $account?->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-navy">{{ $account?->phone ?: '—' }}</td>
                            <td class="px-6 py-4 text-navy">{{ $receptionist->created_at?->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="badge-success">Can book your hours</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div x-data="{ confirm: false }">
                                    <button type="button" class="text-sm font-semibold text-danger" @click="confirm = true">Remove</button>
                                    <form x-show="confirm" x-cloak method="POST" action="{{ route('doctor.receptionists.destroy', $account) }}" class="mt-3 rounded-xl border border-line bg-white p-3 text-left shadow-card">
                                        @csrf
                                        @method('DELETE')
                                        <p class="text-sm leading-5 text-navy">Remove {{ $account?->name }}? They will lose sign-in access immediately.</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="submit" class="btn-danger h-10 px-4 text-sm">Remove access</button>
                                            <button type="button" class="btn-ghost h-10 px-4 text-sm" @click="confirm = false">Keep</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10">
                                <p class="font-semibold text-navy">No receptionists yet</p>
                                <p class="mt-1 max-w-lg text-sm leading-6 text-text">Add someone at the front desk so they can check your open hours and book patients for you.</p>
                                <a href="{{ route('doctor.receptionists.create') }}" class="btn-primary mt-4">Add a receptionist</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
