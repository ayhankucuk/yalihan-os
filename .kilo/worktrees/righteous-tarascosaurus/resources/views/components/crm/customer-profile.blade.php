@props(['customer'])
<div class="bg-white rounded-2xl shadow-lg p-8 max-w-4xl mx-auto mt-8 dark:bg-slate-900">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-6">
        <!-- Avatar & Status -->
        <div class="flex items-center gap-4">
            <div class="relative">
                <div
                    class="w-20 h-20 rounded-full bg-gradient-to-tr from-blue-500 via-purple-500 to-pink-400 flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <!-- Status Indicator -->
                <span class="absolute bottom-2 right-2 w-5 h-5 rounded-full border-2 border-white bg-green-400 shadow-md dark:shadow-none"
                    title="Aktif"></span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $customer->name }}</span>
                    <!-- Loyalty Badge -->
                    <span
                        class="ml-2 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-yellow-400 to-yellow-600 text-white shadow dark:shadow-none"
                        title="Sadakat Seviyesi">
                        {{ $customer->loyalty_tier ?? 'Gold' }}
                    </span>
                </div>
                <div class="text-gray-500 text-sm flex items-center gap-2">
                    <span>{{ $customer->email }}</span>
                    <a href="{{ $customer->linkedin_url }}" target="_blank" class="hover:text-blue-600"><i
                            class="fab fa-linkedin"></i></a>
                    <a href="{{ $customer->instagram_url }}" target="_blank" class="hover:text-pink-500"><i
                            class="fab fa-instagram"></i></a>
                    <a href="{{ $customer->facebook_url }}" target="_blank" class="hover:text-blue-700"><i
                            class="fab fa-facebook"></i></a>
                </div>
            </div>
        </div>
        <!-- Quick Actions -->
        <div class="flex gap-2">
            <button
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition shadow dark:text-slate-300 dark:shadow-none dark:bg-slate-900">Düzenle</button>
            <button
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition shadow dark:shadow-none">Mesaj
                Gönder</button>
            <button
                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition shadow dark:shadow-none">Sil</button>
        </div>
    </div>
    <!-- Tabs -->
    <div x-data="{ tab: 'genel' }">
        <div class="flex gap-2 border-b mb-6">
            <button @click="tab = 'genel'"
                :class="tab === 'genel' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                class="px-4 py-2 border-b-2 font-semibold transition">Genel</button>
            <button @click="tab = 'emlak'"
                :class="tab === 'emlak' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                class="px-4 py-2 border-b-2 font-semibold transition">Emlak</button>
            <button @click="tab = 'mali'"
                :class="tab === 'mali' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                class="px-4 py-2 border-b-2 font-semibold transition">Mali</button>
            <button @click="tab = 'etkilesim'"
                :class="tab === 'etkilesim' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                class="px-4 py-2 border-b-2 font-semibold transition">Etkileşim</button>
            <button @click="tab = 'belgeler'"
                :class="tab === 'belgeler' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                class="px-4 py-2 border-b-2 font-semibold transition">Belgeler</button>
            <button @click="tab = 'notlar'"
                :class="tab === 'notlar' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                class="px-4 py-2 border-b-2 font-semibold transition">Notlar</button>
        </div>
        <div>
            <div x-show="tab === 'genel'">
                <x-crm.customer-profile-general :customer="$customer" />
            </div>
            <div x-show="tab === 'emlak'">
                <x-crm.customer-profile-property :customer="$customer" />
            </div>
            <div x-show="tab === 'mali'">
                <x-crm.customer-profile-finance :customer="$customer" />
            </div>
            <div x-show="tab === 'etkilesim'">
                <x-crm.customer-profile-interaction :customer="$customer" />
            </div>
            <div x-show="tab === 'belgeler'">
                <x-crm.customer-profile-documents :customer="$customer" />
            </div>
            <div x-show="tab === 'notlar'">
                <x-crm.customer-profile-notes :customer="$customer" />
            </div>
        </div>
    </div>
</div>
