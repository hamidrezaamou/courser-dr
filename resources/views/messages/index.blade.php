<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="page-title">پیام‌های پرونده</h2>
            @if(($unread ?? 0) > 0)
                <form method="POST" action="{{ route('staff-messages.read-all') }}">
                    @csrf
                    <button type="submit" class="btn-ghost !py-1.5 !text-xs">همه را خواندم</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="report-chips">
                <a href="{{ route('staff-messages.index', ['filter' => 'unread']) }}" class="report-chip {{ ($filter ?? 'unread') === 'unread' ? 'is-active' : '' }}">خوانده‌نشده {{ fa_digits($unread ?? 0) }}</a>
                <a href="{{ route('staff-messages.index', ['filter' => 'all']) }}" class="report-chip {{ ($filter ?? '') === 'all' ? 'is-active' : '' }}">همه</a>
            </div>

            <div class="space-y-2">
                @forelse($items as $item)
                    @php
                        $note = $item->note;
                        $patient = $item->patient;
                        $actor = $item->actor;
                        $href = $patient && $note
                            ? route('patients.show', ['patient' => $patient, 'note' => $note->id])
                            : '#';
                    @endphp
                    <a href="{{ $href }}" class="msg-row {{ $item->isUnread() ? 'is-unread' : '' }}">
                        <div class="msg-row__top">
                            <strong>{{ $patient?->name ?? 'بیمار حذف‌شده' }}</strong>
                            @if($item->isUnread())
                                <span class="msg-row__dot">جدید</span>
                            @endif
                            <span class="msg-row__time" dir="ltr">{{ jalali($item->created_at, 'Y/m/d H:i') }}</span>
                        </div>
                        <div class="msg-row__meta">{{ $actor?->name ?? 'همکار' }} پیام گذاشت</div>
                        <p class="msg-row__preview">{{ \App\Support\StaffNoteAlerts::preview((string) ($note?->note ?? 'این پیام حذف شده است.')) }}</p>
                    </a>
                @empty
                    <div class="panel p-6 text-sm" style="color: var(--muted);">
                        @if(($filter ?? 'unread') === 'unread')
                            پیام خوانده‌نشده‌ای نیست.
                        @else
                            هنوز پیامی در پرونده‌ها برای شما ثبت نشده.
                        @endif
                    </div>
                @endforelse
            </div>

            <x-list-pager :paginator="$items" label="پیام" />
        </div>
    </div>
</x-app-layout>
