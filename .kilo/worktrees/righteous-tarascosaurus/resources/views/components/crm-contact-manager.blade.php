{{-- CRM İletişim Yönetimi Component --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-slate-900 dark:border-slate-700 dark:shadow-none" x-data="crmContactManager()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">İletişim Yönetimi</h3>
        <button type="button" @click="showAddContact = true"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            <i class="fas fa-plus mr-2"></i>Yeni Kişi
        </button>
    </div>

    {{-- Arama ve Filtreleme --}}
    <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Arama</label>
                <input type="text" x-model="searchQuery" @input="filterContacts()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="İsim, telefon veya email ara...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Kategori</label>
                <select x-model="selectedCategory" @change="filterContacts()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tümü</option>
                    <option value="musteri">Müşteri</option>
                    <option value="sahip">Sahip</option>
                    <option value="danisman">Danışman</option>
                    <option value="potansiyel">Potansiyel</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Sıralama</label>
                <select x-model="sortBy" @change="sortContacts()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="name">İsme Göre</option>
                    <option value="date">Tarihe Göre</option>
                    <option value="last_contact">Son İletişim</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Kişi Listesi --}}
    <div class="space-y-3 mb-6">
        <template x-for="(contact, index) in filteredContacts" :key="contact.id">
            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-text="contact.name"></h4>
                            <p class="text-sm text-gray-500" x-text="contact.phone"></p>
                            <p class="text-sm text-gray-500" x-text="contact.email"></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs rounded-full" :class="getCategoryClass(contact.category)"
                            x-text="getCategoryLabel(contact.category)"></span>
                        <div class="flex space-x-1">
                            <button type="button" @click="editContact(contact)"
                                class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                            <button type="button" @click="deleteContact(contact.id)"
                                class="p-2 text-red-600 hover:bg-red-100 rounded-lg">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div x-show="contact.notes" class="mt-2 text-sm text-gray-600">
                    <i class="fas fa-sticky-note mr-1"></i>
                    <span x-text="contact.notes"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Kişi Ekleme/Düzenleme Modal --}}
    <div x-show="showAddContact || showEditContact"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 dark:bg-slate-900">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                    <span x-text="showEditContact ? 'Kişi Düzenle' : 'Yeni Kişi'"></span>
                </h3>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveContact()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Ad Soyad *</label>
                        <input type="text" x-model="form.name" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Telefon *</label>
                        <input type="tel" x-model="form.phone" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Email</label>
                        <input type="email" x-model="form.email"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Kategori</label>
                        <select x-model="form.category"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="musteri">Müşteri</option>
                            <option value="sahip">Sahip</option>
                            <option value="danisman">Danışman</option>
                            <option value="potansiyel">Potansiyel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Notlar</label>
                        <textarea x-model="form.notes" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="closeModal()"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:text-slate-300">
                        İptal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <span x-text="showEditContact ? 'Güncelle' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hidden Inputs for Form Submission --}}
    <input type="hidden" name="selected_contacts" x-bind:value="JSON.stringify(selectedContacts)">
</div>

<script>
    function crmContactManager() {
        return {
            // Veri
            contacts: [],
            filteredContacts: [],
            selectedContacts: [],

            // Form statusu
            showAddContact: false,
            showEditContact: false,
            editingContact: null,

            // Arama ve filtreleme
            searchQuery: '',
            selectedCategory: '',
            sortBy: 'name',

            // Form verileri
            form: {
                name: '',
                phone: '',
                email: '',
                category: 'musteri',
                notes: ''
            },

            init() {
                this.loadContacts();
                this.filterContacts();
            },

            loadContacts() {
                // Örnek veri - gerçek uygulamada API'den alınmalı
                this.contacts = [{
                        id: 1,
                        name: 'Ahmet Yılmaz',
                        phone: '+90 532 123 45 67',
                        email: 'ahmet@example.com',
                        category: 'musteri',
                        notes: 'Satın alma potansiyeli yüksek',
                        created_at: '2024-01-15',
                        last_contact: '2024-01-20'
                    },
                    {
                        id: 2,
                        name: 'Fatma Demir',
                        phone: '+90 533 987 65 43',
                        email: 'fatma@example.com',
                        category: 'sahip',
                        notes: 'Villa sahibi',
                        created_at: '2024-01-10',
                        last_contact: '2024-01-18'
                    },
                    {
                        id: 3,
                        name: 'Mehmet Kaya',
                        phone: '+90 534 555 44 33',
                        email: 'mehmet@example.com',
                        category: 'danisman',
                        notes: 'Deneyimli danışman',
                        created_at: '2024-01-05',
                        last_contact: '2024-01-22'
                    }
                ];
            },

            filterContacts() {
                let filtered = [...this.contacts];

                // Arama filtresi
                if (this.searchQuery) {
                    const query = this.searchQuery.toLowerCase();
                    filtered = filtered.filter(contact =>
                        contact.name.toLowerCase().includes(query) ||
                        contact.phone.includes(query) ||
                        contact.email.toLowerCase().includes(query)
                    );
                }

                // Kategori filtresi
                if (this.selectedCategory) {
                    filtered = filtered.filter(contact => contact.category === this.selectedCategory);
                }

                // Sıralama
                this.sortContacts(filtered);
            },

            sortContacts(contacts = null) {
                const targetContacts = contacts || this.filteredContacts;

                targetContacts.sort((a, b) => {
                    switch (this.sortBy) {
                        case 'name':
                            return a.name.localeCompare(b.name);
                        case 'date':
                            return new Date(b.created_at) - new Date(a.created_at);
                        case 'last_contact':
                            return new Date(b.last_contact) - new Date(a.last_contact);
                        default:
                            return 0;
                    }
                });

                if (!contacts) {
                    this.filteredContacts = targetContacts;
                }
            },

            editContact(contact) {
                this.editingContact = contact;
                this.form = {
                    ...contact
                };
                this.showEditContact = true;
            },

            deleteContact(contactId) {
                if (confirm('Bu kişiyi silmek istediğinizden emin misiniz?')) {
                    this.contacts = this.contacts.filter(contact => contact.id !== contactId);
                    this.filterContacts();
                }
            },

            saveContact() {
                if (this.showEditContact) {
                    // Düzenleme
                    const index = this.contacts.findIndex(contact => contact.id === this.editingContact.id);
                    if (index !== -1) {
                        this.contacts[index] = {
                            ...this.contacts[index],
                            ...this.form,
                            updated_at: new Date().toISOString().split('T')[0]
                        };
                    }
                } else {
                    // Yeni ekleme
                    const newContact = {
                        ...this.form,
                        id: Date.now(),
                        created_at: new Date().toISOString().split('T')[0],
                        last_contact: new Date().toISOString().split('T')[0]
                    };
                    this.contacts.push(newContact);
                }

                this.filterContacts();
                this.closeModal();
            },

            closeModal() {
                this.showAddContact = false;
                this.showEditContact = false;
                this.editingContact = null;
                this.form = {
                    name: '',
                    phone: '',
                    email: '',
                    category: 'musteri',
                    notes: ''
                };
            },

            getCategoryClass(category) {
                const classes = {
                    'musteri': 'bg-green-100 text-green-800',
                    'sahip': 'bg-blue-100 text-blue-800',
                    'danisman': 'bg-purple-100 text-purple-800',
                    'potansiyel': 'bg-yellow-100 text-yellow-800'
                };
                return classes[category] || 'bg-gray-100 text-gray-800';
            },

            getCategoryLabel(category) {
                const labels = {
                    'musteri': 'Müşteri',
                    'sahip': 'Sahip',
                    'danisman': 'Danışman',
                    'potansiyel': 'Potansiyel'
                };
                return labels[category] || 'Bilinmiyor';
            }
        }
    }
</script>
