<div class="cooldown-warn mt-3" x-show="cooldownConflict" x-cloak>
    <p class="cooldown-warn__title" x-text="(cooldownPrevious && cooldownPrevious.scope === 'type') ? 'محدودیت کل نوع عمل' : 'محدودیت فاصله زمانی'"></p>
    <p class="cooldown-warn__msg" x-text="cooldownMessage"></p>
    <dl class="cooldown-warn__grid" x-show="cooldownPrevious">
        <div>
            <dt>نوبت تداخلی</dt>
            <dd>
                <span x-text="cooldownPrevious && cooldownPrevious.date"></span>
                <span> · </span>
                <span x-text="cooldownPrevious && cooldownPrevious.time"></span>
                <span x-show="cooldownPrevious && cooldownPrevious.when"> (</span><span x-text="cooldownPrevious && cooldownPrevious.when"></span><span x-show="cooldownPrevious && cooldownPrevious.when">)</span>
            </dd>
        </div>
        <div>
            <dt>مرکز</dt>
            <dd x-text="cooldownPrevious && cooldownPrevious.hospital"></dd>
        </div>
        <div>
            <dt>عمل</dt>
            <dd>
                <span x-text="cooldownPrevious && cooldownPrevious.type"></span>
                <span x-show="cooldownPrevious && cooldownPrevious.subtype"> · </span>
                <span x-text="cooldownPrevious && cooldownPrevious.subtype"></span>
            </dd>
        </div>
        <div>
            <dt>چشم / وضعیت</dt>
            <dd>
                <span x-text="cooldownPrevious && cooldownPrevious.eye"></span>
                <span> · </span>
                <span x-text="cooldownPrevious && cooldownPrevious.status"></span>
            </dd>
        </div>
    </dl>
    <p class="cooldown-warn__hint">ثبت مسدود نمی‌شود؛ اگر ادامه دهید باید این هشدار را تأیید کنید.</p>
</div>
