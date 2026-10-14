@php
    $chatWith = $booking['person']['name'] ?: ($role === 'doctor' ? 'the patient' : 'your doctor');
    $chat = [
        'messages' => $booking['messages'],
        'pollUrl' => $role === 'doctor'
            ? route('doctor.appointments.messages.index', $appointment)
            : route('patient.bookings.messages.index', $appointment),
        'sendUrl' => $messageUrl,
        'draft' => old('body', ''),
    ];
@endphp

<section
    id="messages"
    class="dash-panel flex h-[min(40rem,calc(100dvh-8rem))] min-h-[24rem] flex-col overflow-hidden p-0"
    x-data="visitChat(@js($chat))"
>
    <div class="flex items-center gap-3 border-b border-line px-4 py-3">
        <span class="relative flex h-10 w-10 items-center justify-center rounded-full bg-light text-primary">
            <x-ui.icon name="message" class="h-5 w-5" />
            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500" title="Live"></span>
        </span>
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-heading">Messages</h2>
            <p class="truncate text-sm text-body">Chat with {{ $chatWith }}</p>
        </div>
    </div>

    <div class="relative min-h-0 flex-1 bg-[#F4F7FB]">
        <div x-ref="thread" class="h-full space-y-1 overflow-y-auto px-3 py-4">
            <p x-show="messages.length === 0" x-cloak class="px-2 py-8 text-center text-sm leading-6 text-body">
                No messages yet. Send the first one below.
            </p>

            <template x-for="message in rows()" :key="message.id">
                <div>
                    <div x-show="message.showDay" class="my-3 flex justify-center">
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-muted" x-text="message.day"></span>
                    </div>

                    <div class="flex items-end gap-2" :class="message.mine ? 'justify-end' : 'justify-start'">
                        <template x-if="!message.mine">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-[10px] font-bold text-primary" :class="message.lastInGroup ? '' : 'invisible'" x-text="message.initials"></span>
                        </template>

                        <div class="max-w-[78%]" :class="message.lastInGroup ? 'mb-2' : 'mb-0.5'">
                            <p x-show="!message.mine && !message.grouped" class="mb-1 px-1 text-[11px] font-semibold text-muted" x-text="message.name"></p>
                            <div
                                class="w-fit max-w-full whitespace-pre-line break-words px-3 py-2 text-sm leading-5"
                                :class="message.mine
                                    ? 'ml-auto rounded-2xl rounded-br-md bg-primary text-white'
                                    : 'rounded-2xl rounded-bl-md bg-white text-ink shadow-sm'"
                                x-text="message.body"
                            ></div>
                            <p x-show="message.lastInGroup" class="mt-1 px-1 text-[11px] text-muted" :class="message.mine ? 'text-right' : ''">
                                <span x-text="message.time"></span>
                                <span x-show="message.mine && message.read"> · Seen</span>
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button
            type="button"
            x-show="pending"
            x-cloak
            @click="scrollToEnd()"
            class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-navy px-3 py-1.5 text-xs font-semibold text-white shadow-card"
        >
            New messages
        </button>
    </div>

    <form method="POST" action="{{ $messageUrl }}" data-no-lock="true" class="border-t border-line bg-white px-3 py-3" @submit.prevent="send()">
        @csrf
        <div class="flex items-end gap-2">
            <label for="appointment-message" class="sr-only">Message</label>
            <textarea
                id="appointment-message"
                name="body"
                x-ref="composer"
                x-model="body"
                rows="1"
                maxlength="2000"
                placeholder="Type a message"
                @input="resize()"
                @keydown="onKeydown($event)"
                class="max-h-32 min-h-11 flex-1 resize-none rounded-2xl border border-line bg-page px-4 py-2.5 text-sm leading-5 text-ink outline-none placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/15"
            ></textarea>
            <button
                type="submit"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="sending || body.trim() === ''"
                aria-label="Send message"
            >
                <x-ui.icon name="send" class="h-5 w-5" />
            </button>
        </div>
        <p x-show="error !== ''" x-cloak x-text="error" class="mt-2 text-sm text-danger"></p>
        <x-input-error :messages="$errors->get('body')" class="mt-2" />
    </form>
</section>
