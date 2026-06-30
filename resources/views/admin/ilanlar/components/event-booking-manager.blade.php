{{-- Event/Booking Manager Component --}}
{{-- Pure Tailwind + Alpine.js, NO FULLCALENDAR! --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}
{{-- Function is defined in layout (admin.blade.php) before Alpine.js loads --}}

<div x-data="eventBookingManager({{ json_encode($ilan->id ?? null) }})" x-init="init()"
    class="rounded-xl border-2 border-gray-200 bg-white p-6 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                📅 Rezervasyon ve Etkinlik Yönetimi
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Yazlık kiralama rezervasyonları ve bloklanan tarihleri yönetin
            </p>
        </div>
        <button type="button" @click="showCreateModal = true"
            class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-purple-700 hover:shadow-xl">
            ➕ Yeni Rezervasyon
        </button>
    </div>

    {{-- Mini Calendar (Current Month) --}}
    <div class="mb-6">
        <div class="mb-4 flex items-center justify-between">
            <button @click="previousMonth()"
                class="rounded-lg bg-gray-200 px-3 py-2 text-gray-900 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-100 dark:text-white dark:hover:bg-gray-600">
                ◀ Önceki
            </button>
            <h4 class="text-lg font-bold text-gray-900 dark:text-slate-100 dark:text-white" x-text="currentMonthName">
            </h4>
            <button @click="nextMonth()"
                class="rounded-lg bg-gray-200 px-3 py-2 text-gray-900 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-100 dark:text-white dark:hover:bg-gray-600">
                Sonraki ▶
            </button>
        </div>

        {{-- Calendar Grid --}}
        <div class="grid grid-cols-7 gap-2">
            {{-- Day Headers --}}
            <template x-for="day in ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz']">
                <div class="py-2 text-center text-xs font-bold text-gray-600 dark:text-gray-400" x-text="day"></div>
            </template>

            {{-- Calendar Days --}}
            <template x-for="day in calendarDays" :key="day.date">
                <div @click="selectDate(day)"
                    :class="{
                        'bg-gray-100 dark:bg-gray-900 text-gray-400 dark:text-gray-600': !day.isCurrentMonth,
                        'bg-white dark:bg-gray-800 text-gray-900 dark:text-white hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer': day
                            .isCurrentMonth && !day.isBooked && !day.isBlocked,
                        'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300': day.isBooked,
                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300': day.isBlocked,
                        'bg-blue-500 text-white': day.isToday,
                        'ring-2 ring-blue-500': day.isSelected
                    }"
                    class="relative flex aspect-square items-center justify-center rounded-lg border border-gray-200 text-sm font-medium transition-all duration-200 dark:border-slate-700 dark:border-slate-800">
                    <span x-text="day.dayNumber"></span>
                    <span x-show="day.isBooked" class="absolute bottom-0.5 text-xs">🔒</span>
                    <span x-show="day.isBlocked" class="absolute bottom-0.5 text-xs">⛔</span>
                </div>
            </template>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mb-6 flex flex-wrap items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50 dark:bg-slate-900">
        <div class="flex items-center gap-2">
            <div class="h-4 w-4 rounded border border-red-300 bg-red-100 dark:bg-red-900/30"></div>
            <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">🔒 Rezervasyon</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-4 w-4 rounded border border-yellow-300 bg-yellow-100 dark:bg-yellow-900/30"></div>
            <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">⛔ Bloke</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-4 w-4 rounded bg-blue-500"></div>
            <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Bugün</span>
        </div>
    </div>

    {{-- Events List --}}
    <div class="mb-6 space-y-3" x-show="events.length > 0">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">📋 Yaklaşan Rezervasyonlar
        </h4>
        <template x-for="event in upcomingEvents" :key="event.id">
            <div class="rounded-xl border-2 p-4 transition-all duration-200 hover:shadow-lg"
                :class="event.booking_status === 'confirmed' ?
                    'border-green-500 dark:border-green-400 bg-green-50 dark:bg-green-900/20' : event
                    .booking_status === 'pending' ?
                    'border-yellow-500 dark:border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20' :
                    'border-red-500 dark:border-red-400 bg-red-50 dark:bg-red-900/20'">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-2 flex items-center gap-3">
                            <span class="rounded px-2 py-1 text-sm font-bold"
                                :class="event.booking_status === 'confirmed' ? 'bg-green-600 text-white' : event
                                    .booking_status === 'pending' ?
                                    'bg-yellow-600 text-white' : 'bg-red-600 text-white'"
                                x-text="event.booking_status === 'confirmed' ? '✅ Onaylandı' : event.booking_status === 'pending' ? '⏳ Beklemede' : '❌ İptal'"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400"
                                x-text="event.event_type === 'booking' ? '🏠 Rezervasyon' : '⛔ Bloke'"></span>
                        </div>
                        <div class="space-y-1">
                            <p class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                x-text="event.guest_name || 'İsimsiz'"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                📅 <span x-text="formatDate(event.check_in)"></span> → <span
                                    x-text="formatDate(event.check_out)"></span>
                                (<span x-text="event.nights"></span> gece)
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-show="event.guest_count">
                                👥 <span x-text="event.guest_count"></span> kişi
                            </p>
                            <p class="text-sm font-bold text-green-600 dark:text-green-400" x-show="event.total_price">
                                💰 <span x-text="formatPrice(event.total_price)"></span>
                            </p>
                        </div>
                    </div>
                    <div class="ml-4 flex flex-col gap-2">
                        <button @click="editEvent(event)"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs text-white transition-colors duration-200 hover:bg-blue-700">
                            ✏️ Düzenle
                        </button>
                        <button @click="deleteEvent(event.id)"
                            class="rounded-lg bg-red-600 px-3 py-1.5 text-xs text-white transition-colors duration-200 hover:bg-red-700">
                            🗑️ Sil
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="events.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
        <div class="mb-3 text-4xl">📅</div>
        <p class="text-sm">Henüz rezervasyon veya bloke tarih yok</p>
    </div>

    {{-- Create/Edit Modal --}}
    <div x-show="showCreateModal || editingEvent" @click.self="closeModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl dark:bg-slate-900"
            @click.stop>
            <div class="p-6">
                {{-- Modal Header --}}
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                        <span x-show="!editingEvent">➕ Yeni Rezervasyon/Bloke</span>
                        <span x-show="editingEvent">✏️ Rezervasyon Düzenle</span>
                    </h3>
                    <button @click="closeModal()"
                        class="text-2xl text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">✖</button>
                </div>

                {{-- Modal Form --}}
                <div class="space-y-4">
                    {{-- Event Type --}}
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Tür</label>
                        <select x-model="formData.event_type"
                            class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            <option value="booking">🏠 Rezervasyon</option>
                            <option value="blocked">⛔ Bloke (Müsait Değil)</option>
                        </select>
                    </div>

                    {{-- Guest Info (if booking) --}}
                    <div x-show="formData.event_type === 'booking'" class="space-y-4">
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Misafir
                                Adı
                                *</label>
                            <input type="text" x-model="formData.guest_name" placeholder="Ad Soyad"
                                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Telefon</label>
                                <input type="tel" x-model="formData.guest_phone" placeholder="+90 5XX XXX XX XX"
                                    class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Email</label>
                                <input type="email" x-model="formData.guest_email" placeholder="email@example.com"
                                    class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Kişi
                                Sayısı</label>
                            <input type="number" x-model.number="formData.guest_count" min="1"
                                max="20"
                                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Giriş
                                Tarihi
                                *</label>
                            <input type="date" x-model="formData.check_in" @change="calculateNights()"
                                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Çıkış
                                Tarihi
                                *</label>
                            <input type="date" x-model="formData.check_out" @change="calculateNights()"
                                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        </div>
                    </div>

                    {{-- Nights & Price (if booking) --}}
                    <div x-show="formData.event_type === 'booking' && formData.nights > 0"
                        class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                            <p class="mb-1 text-xs text-gray-600 dark:text-gray-400">Gece Sayısı</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="formData.nights">
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Toplam
                                Fiyat
                                (₺)</label>
                            <input type="number" x-model.number="formData.total_price" step="0.01"
                                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Durum</label>
                        <select x-model="formData.booking_status"
                            class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            <option value="pending">⏳ Beklemede</option>
                            <option value="confirmed">✅ Onaylandı</option>
                            <option value="cancelled">❌ İptal</option>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Notlar
                            (Opsiyonel)</label>
                        <textarea x-model="formData.notes" rows="3" placeholder="Rezervasyon notları..."
                            class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-black focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white"></textarea>
                    </div>
                </div>

                {{-- Modal Actions --}}
                <div
                    class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700 dark:border-slate-800">
                    <button @click="closeModal()"
                        class="rounded-lg bg-gray-200 px-4 py-2 text-gray-900 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-100 dark:text-white dark:hover:bg-gray-600">
                        İptal
                    </button>
                    <button @click="saveEvent()"
                        class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-2 font-semibold text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-purple-700">
                        <span x-show="!editingEvent">➕ Oluştur</span>
                        <span x-show="editingEvent">💾 Kaydet</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
