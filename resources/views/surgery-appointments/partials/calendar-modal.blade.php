<template x-teleport="body">
    <div
        class="booking-calendar-modal"
        :class="{ 'is-open': openCalendar }"
        x-cloak
        @click.self="openCalendar = false"
        @keydown.escape.window="if (openCalendar) openCalendar = false"
    >
        <div class="booking-calendar-content" @click.stop>
            <div class="booking-calendar-header">
                <button type="button" @click.prevent="shiftMonth(1)" aria-label="ماه بعد">‹</button>
                <strong x-text="monthLabel"></strong>
                <button type="button" @click.prevent="shiftMonth(-1)" aria-label="ماه قبل">›</button>
            </div>
            <div class="booking-calendar-grid">
                <template x-for="name in dayNames" :key="name"><div class="booking-day-name" x-text="name"></div></template>
                <template x-for="(cell, idx) in calendarCells" :key="idx">
                    <button type="button" class="booking-day"
                            :class="{ 'is-disabled': cell.disabled, 'is-today': cell.today, 'is-active': cell.date === dateKey, 'is-available': cell.available, 'is-full': cell.full }"
                            :disabled="cell.disabled || !cell.day"
                            x-text="cell.day || ''"
                            @click.prevent="selectDate(cell)"></button>
                </template>
            </div>
            <div class="cal-legend px-1">
                <span><i class="lg-available"></i> دارای نوبت آزاد</span>
                <span><i class="lg-full"></i> تکمیل‌شده (با تیک قابل انتخاب)</span>
                <span><i class="lg-off"></i> بدون برنامه / غیرفعال</span>
            </div>
        </div>
    </div>
</template>
