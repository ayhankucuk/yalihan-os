{{-- Availability Calendar Component (Public View) --}}
{{-- Pure Tailwind + Alpine.js (NO FullCalendar!) --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

@php
    $calendar = $calendar ?? [];
@endphp

<div x-data="availabilityCalendar({{ json_encode($calendar) }})" class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
        📅 Müsaitlik Takvimi
    </h3>

    {{-- Month Navigation --}}
    <div class="flex items-center justify-between">
        <button @click="previousMonth()"
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm dark:text-slate-100">
            ◀
        </button>
        <div class="font-semibold text-gray-900 dark:text-white text-sm dark:text-slate-100" x-text="currentMonthName"></div>
        <button @click="nextMonth()"
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm dark:text-slate-100">
            ▶
        </button>
    </div>

    {{-- Calendar Grid --}}
    <div class="grid grid-cols-7 gap-1">
        {{-- Day Headers --}}
        <template x-for="day in ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz']">
            <div class="text-center text-xs font-bold text-gray-600 dark:text-gray-400 py-1" x-text="day"></div>
        </template>

        {{-- Calendar Days --}}
        <template x-for="day in calendarDays" :key="day.date">
            <div :class="{
                    'bg-gray-100 dark:bg-gray-800 text-gray-400': !day.isCurrentMonth,
                    'bg-white dark:bg-gray-700 text-gray-900 dark:text-white': day.isCurrentMonth && day.isAvailable,
                    'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 cursor-not-allowed': day.isBooked,
                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300': day.isPast,
                    'ring-2 ring-blue-500': day.isToday
                 }"
                 class="aspect-square flex items-center justify-center text-xs font-medium rounded-md border border-gray-200 dark:border-gray-600 transition-all relative dark:border-slate-700">
                <span x-text="day.dayNumber"></span>
                <span x-show="day.isBooked" class="absolute bottom-0 text-[8px]">🔒</span>
            </div>
        </template>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded dark:bg-slate-900"></div>
            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Müsait</span>
        </div>
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 bg-red-100 dark:bg-red-900/30 border border-red-300 rounded"></div>
            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Dolu</span>
        </div>
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 rounded"></div>
            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">Geçmiş</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function availabilityCalendar(bookedDates) {
    return {
        currentMonth: new Date(),
        bookedDates: bookedDates,

        get currentMonthName() {
            return this.currentMonth.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);

            const startDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;

            const days = [];
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Previous month days
            for (let i = startDay - 1; i >= 0; i--) {
                const date = new Date(year, month, -i);
                days.push(this.createDayObject(date, false, today));
            }

            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                days.push(this.createDayObject(date, true, today));
            }

            // Next month days
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) {
                const date = new Date(year, month + 1, i);
                days.push(this.createDayObject(date, false, today));
            }

            return days;
        },

        createDayObject(date, isCurrentMonth, today) {
            const dateStr = date.toISOString().split('T')[0];
            date.setHours(0, 0, 0, 0);

            const isBooked = this.bookedDates.includes(dateStr);
            const isPast = date < today;
            const isToday = date.getTime() === today.getTime();
            const isAvailable = !isBooked && !isPast;

            return {
                date: dateStr,
                dayNumber: date.getDate(),
                isCurrentMonth: isCurrentMonth,
                isBooked: isBooked,
                isPast: isPast && !isToday,
                isToday: isToday,
                isAvailable: isAvailable
            };
        },

        previousMonth() {
            this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() - 1, 1);
        },

        nextMonth() {
            this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + 1, 1);
        }
    }
}
</script>
@endpush
