@extends('layouts.dashboard')

@section('title', 'Notifications')

@section('content')
    <x-ui.page-header title="Notifications" description="{{ $unread }} unread">
        @if ($unread > 0)
            <form method="POST" action="{{ route('account.notifications.read-all') }}">
                @csrf
                <button class="btn-secondary" type="submit">Mark all read</button>
            </form>
        @endif
    </x-ui.page-header>

    <ul class="panel">
        @forelse ($notifications as $notification)
            <li class="border-b border-line last:border-b-0 {{ $notification->read_at ? '' : 'bg-[#F7FAFF]' }}">
                <a href="{{ route('account.notifications.read', $notification->id) }}" class="flex items-start gap-3 px-4 py-3 no-underline">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-[#F8FAFC] text-muted' : 'bg-[#E8F2FF] text-primary' }}">
                        <x-ui.icon name="bell" class="h-4 w-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-sm font-semibold text-heading">{{ $notification->data['title'] ?? 'Update' }}</span>
                            @unless ($notification->read_at)
                                <span class="h-2 w-2 shrink-0 rounded-full bg-[#0067FF]" aria-label="Unread"></span>
                            @endunless
                        </span>
                        <span class="mt-0.5 block text-sm text-body">{{ $notification->data['body'] ?? '' }}</span>
                        <span class="mt-1 block text-xs text-muted">{{ $notification->created_at?->diffForHumans() }}</span>
                    </span>
                </a>
            </li>
        @empty
            <li class="px-4 py-6 text-sm text-body">No notifications yet. Booking and referral updates will appear here.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
