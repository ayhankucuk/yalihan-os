/**
 * Sahte API (Mock API) - Seçici Bileşenler için API simülasyonu
 * Bu dosya gerçek API endpoint'leri olmadığında bile testlerin çalışmasını sağlar
 */

// Sahte API endpoint'lerini tanımla
class MockAPI {
    constructor() {
        console.log('MockAPI başlatılıyor...');
        this.setupFakeEndpoints();
    }

    setupFakeEndpoints() {
        // URL kalıpları ve karşılık gelen veri oluşturucuları
        const endpoints = [
            { pattern: /\/admin\/api\/sites\/search/, handler: this.generateSites },
            { pattern: /\/admin\/api\/sites\/\d+/, handler: this.generateSiteDetail },
            { pattern: /\/admin\/api\/sites\/create/, handler: this.createSite, method: 'POST' },
            { pattern: /\/admin\/api\/danisman\/search/, handler: this.generateDanismans },
            { pattern: /\/admin\/api\/danisman\/\d+/, handler: this.generateDanismanDetail },
            {
                pattern: /\/admin\/api\/danisman\/create/,
                handler: this.createDanisman,
                method: 'POST',
            },
            { pattern: /\/admin\/api\/kisi\/search/, handler: this.generateContacts },
            { pattern: /\/admin\/api\/kisi\/\d+/, handler: this.generateContactDetail },
            { pattern: /\/admin\/api\/kisi\/create/, handler: this.createContact, method: 'POST' },
            { pattern: /\/api\/crm\/kisi\/search/, handler: this.generateOwners },
            { pattern: /\/api\/crm\/kisi\/\d+/, handler: this.generateOwnerDetail },
            { pattern: /\/admin\/api\/kisi\/create/, handler: this.createOwner, method: 'POST' },
        ];

        // Fetch API'yi bu sahte API ile değiştir
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            // URL'i string olarak al
            const urlString = url.toString();
            const method = options.method || 'GET';

            console.log('Fetch isteği:', urlString, method);

            // URL kalıplarının aranması
            for (const endpoint of endpoints) {
                // Metot kontrolü ekle, varsayılan olarak GET
                const endpointMethod = endpoint.method || 'GET';

                if (endpoint.pattern.test(urlString) && endpointMethod === method) {
                    console.log('Sahte endpoint eşleşti:', urlString, method);

                    // İstek parametrelerini parse et
                    const urlObj = new URL(urlString, window.location.origin);
                    const query = urlObj.searchParams.get('q') || '';
                    const limit = parseInt(urlObj.searchParams.get('limit') || '10');

                    // ID çıkarma (eğer detail endpoint ise)
                    const idMatch = urlString.match(/\/(\d+)$/);
                    const id = idMatch ? parseInt(idMatch[1]) : null;

                    // POST metodu için body data'sını al
                    let bodyData = null;
                    if (method === 'POST' && options.body) {
                        try {
                            bodyData = JSON.parse(options.body);
                            console.log('POST data:', bodyData);
                        } catch (e) {
                            console.warn('POST body parse error:', e);
                        }
                    }

                    // Simüle edilmiş gecikme
                    await new Promise((resolve) => setTimeout(resolve, 300));

                    // Handler'ı çağır ve sonucu döndür - parametreleri metoda göre ayarla
                    return Promise.resolve({
                        ok: true,
                        status: 200,
                        json: async () => {
                            if (method === 'POST') {
                                return endpoint.handler(bodyData);
                            } else {
                                return endpoint.handler(query, limit, id);
                            }
                        },
                    });
                }
            }

            // Eşleşen bir sahte endpoint yoksa orijinal fetch'i çağır
            return originalFetch(url, options);
        };

        console.log("Sahte API endpoint'leri hazır");
    }

    // Sahte veri oluşturucular
    generateSites(query, limit) {
        const allSites = [
            {
                id: 1,
                name: 'Park Residence',
                location: 'Şişli, İstanbul',
                lat: '41.0660',
                lng: '28.9857',
            },
            {
                id: 2,
                name: 'Deniz Panorama Konutları',
                location: 'Beykoz, İstanbul',
                lat: '41.1277',
                lng: '29.1010',
            },
            {
                id: 3,
                name: 'Avcılar Garden',
                location: 'Avcılar, İstanbul',
                lat: '40.9782',
                lng: '28.7182',
            },
            {
                id: 4,
                name: 'Kadıköy Towers',
                location: 'Kadıköy, İstanbul',
                lat: '40.9981',
                lng: '29.0270',
            },
            {
                id: 5,
                name: 'Çankaya Prestige',
                location: 'Çankaya, Ankara',
                lat: '39.9027',
                lng: '32.8351',
            },
            {
                id: 6,
                name: 'Park Vadi Konutları',
                location: 'Keçiören, Ankara',
                lat: '39.9819',
                lng: '32.8548',
            },
            {
                id: 7,
                name: 'Kordon Residence',
                location: 'Karşıyaka, İzmir',
                lat: '38.4519',
                lng: '27.1752',
            },
            {
                id: 8,
                name: 'Bodrum Hills',
                location: 'Bitez, Bodrum',
                lat: '37.0266',
                lng: '27.3809',
            },
            {
                id: 9,
                name: 'Marina Garden',
                location: 'Turgutreis, Bodrum',
                lat: '37.0176',
                lng: '27.2557',
            },
            {
                id: 10,
                name: 'Nur Apartmanı',
                location: 'Mecidiyeköy, İstanbul',
                lat: '41.0674',
                lng: '28.9878',
            },
        ];

        // Filtreleme (basit arama)
        return allSites
            .filter(
                (site) =>
                    !query ||
                    site.name.toLowerCase().includes(query.toLowerCase()) ||
                    site.location.toLowerCase().includes(query.toLowerCase())
            )
            .slice(0, limit);
    }

    generateSiteDetail(query, limit, id) {
        const allSites = this.generateSites('', 100);
        return allSites.find((site) => site.id === id) || null;
    }

    generateDanismans(query, limit) {
        const allDanismans = [
            {
                id: 1,
                ad: 'Ahmet',
                soyad: 'Yılmaz',
                telefon: '0555 111 2233',
                email: 'ahmet.yilmaz@emlakpro.com',
            },
            {
                id: 2,
                ad: 'Ayşe',
                soyad: 'Kaya',
                telefon: '0555 222 3344',
                email: 'ayse.kaya@emlakpro.com',
            },
            {
                id: 3,
                ad: 'Mehmet',
                soyad: 'Demir',
                telefon: '0555 333 4455',
                email: 'mehmet.demir@emlakpro.com',
            },
            {
                id: 4,
                ad: 'Fatma',
                soyad: 'Çelik',
                telefon: '0555 444 5566',
                email: 'fatma.celik@emlakpro.com',
            },
            {
                id: 5,
                ad: 'Ali',
                soyad: 'Öztürk',
                telefon: '0555 555 6677',
                email: 'ali.ozturk@emlakpro.com',
            },
            {
                id: 6,
                ad: 'Zeynep',
                soyad: 'Yıldız',
                telefon: '0555 666 7788',
                email: 'zeynep.yildiz@emlakpro.com',
            },
            {
                id: 7,
                ad: 'Mustafa',
                soyad: 'Şahin',
                telefon: '0555 777 8899',
                email: 'mustafa.sahin@emlakpro.com',
            },
            {
                id: 8,
                ad: 'Elif',
                soyad: 'Arslan',
                telefon: '0555 888 9900',
                email: 'elif.arslan@emlakpro.com',
            },
        ];

        // Filtreleme (basit arama)
        return allDanismans
            .filter(
                (d) =>
                    !query ||
                    d.ad.toLowerCase().includes(query.toLowerCase()) ||
                    d.soyad.toLowerCase().includes(query.toLowerCase()) ||
                    d.email.toLowerCase().includes(query.toLowerCase())
            )
            .slice(0, limit);
    }

    generateDanismanDetail(query, limit, id) {
        const allDanismans = this.generateDanismans('', 100);
        return allDanismans.find((d) => d.id === id) || null;
    }

    generateContacts(query, limit) {
        const allContacts = [
            {
                id: 1,
                ad: 'Hasan',
                soyad: 'Aydın',
                telefon: '0532 111 2233',
                email: 'hasan.aydin@example.com',
            },
            {
                id: 2,
                ad: 'Sevgi',
                soyad: 'Kılıç',
                telefon: '0532 222 3344',
                email: 'sevgi.kilic@example.com',
            },
            {
                id: 3,
                ad: 'Okan',
                soyad: 'Güler',
                telefon: '0532 333 4455',
                email: 'okan.guler@example.com',
            },
            {
                id: 4,
                ad: 'Deniz',
                soyad: 'Yaman',
                telefon: '0532 444 5566',
                email: 'deniz.yaman@example.com',
            },
            {
                id: 5,
                ad: 'Selin',
                soyad: 'Aksu',
                telefon: '0532 555 6677',
                email: 'selin.aksu@example.com',
            },
            {
                id: 6,
                ad: 'Burak',
                soyad: 'Koç',
                telefon: '0532 666 7788',
                email: 'burak.koc@example.com',
            },
            {
                id: 7,
                ad: 'Ali',
                soyad: 'Yılmaz',
                telefon: '0532 777 8899',
                email: 'ali.yilmaz@example.com',
            },
        ];

        // Filtreleme (basit arama)
        return allContacts
            .filter(
                (c) =>
                    !query ||
                    c.ad.toLowerCase().includes(query.toLowerCase()) ||
                    c.soyad.toLowerCase().includes(query.toLowerCase()) ||
                    c.email.toLowerCase().includes(query.toLowerCase())
            )
            .slice(0, limit);
    }

    generateContactDetail(query, limit, id) {
        const allContacts = this.generateContacts('', 100);
        return allContacts.find((c) => c.id === id) || null;
    }

    generateOwners(query, limit) {
        const allOwners = [
            {
                id: 1,
                ad: 'Kemal',
                soyad: 'Sunal',
                telefon: '0533 111 2233',
                email: 'kemal.sunal@example.com',
                adres: 'Kadıköy, İstanbul',
            },
            {
                id: 2,
                ad: 'Filiz',
                soyad: 'Akın',
                telefon: '0533 222 3344',
                email: 'filiz.akin@example.com',
                adres: 'Çankaya, Ankara',
            },
            {
                id: 3,
                ad: 'Tarık',
                soyad: 'Akan',
                telefon: '0533 333 4455',
                email: 'tarik.akan@example.com',
                adres: 'Karşıyaka, İzmir',
            },
            {
                id: 4,
                ad: 'Türkan',
                soyad: 'Şoray',
                telefon: '0533 444 5566',
                email: 'turkan.soray@example.com',
                adres: 'Beşiktaş, İstanbul',
            },
            {
                id: 5,
                ad: 'Cüneyt',
                soyad: 'Arkın',
                telefon: '0533 555 6677',
                email: 'cuneyt.arkin@example.com',
                adres: 'Bodrum, Muğla',
            },
        ];

        // Filtreleme (basit arama)
        return allOwners
            .filter(
                (o) =>
                    !query ||
                    o.ad.toLowerCase().includes(query.toLowerCase()) ||
                    o.soyad.toLowerCase().includes(query.toLowerCase()) ||
                    o.email.toLowerCase().includes(query.toLowerCase())
            )
            .slice(0, limit);
    }

    generateOwnerDetail(query, limit, id) {
        const allOwners = this.generateOwners('', 100);
        return allOwners.find((o) => o.id === id) || null;
    }

    // Yeni kayıt oluşturma metodları
    createSite(data) {
        if (!data || !data.name) {
            return { error: 'Site/Apartman adı gereklidir' };
        }

        // Yeni ID oluştur (normal şartlarda backend tarafından yapılır)
        const newId = Date.now(); // Basit bir yaklaşım olarak timestamp kullanıyoruz

        // Yeni site nesnesi oluştur
        const newSite = {
            id: newId,
            name: data.name,
            location: data.location || '',
            lat: data.lat || '',
            lng: data.lng || '',
            createdAt: new Date().toISOString(),
        };

        console.log('Yeni site oluşturuldu:', newSite);

        return newSite;
    }

    createContact(data) {
        if (!data || !data.ad || !data.telefon) {
            return { error: 'Ad ve telefon gereklidir' };
        }

        // Yeni ID oluştur
        const newId = Date.now();

        // Yeni ilgili kişi nesnesi oluştur
        const newContact = {
            id: newId,
            ad: data.ad,
            soyad: data.soyad || '',
            telefon: data.telefon,
            email: data.email || '',
            notlar: data.notlar || '',
            createdAt: new Date().toISOString(),
        };

        console.log('Yeni ilgili kişi oluşturuldu:', newContact);

        return newContact;
    }

    createDanisman(data) {
        if (!data || !data.ad || !data.soyad) {
            return { error: 'Ad ve soyad gereklidir' };
        }

        // Yeni ID oluştur
        const newId = Date.now();

        // Yeni danışman nesnesi oluştur
        const newDanisman = {
            id: newId,
            ad: data.ad,
            soyad: data.soyad,
            telefon: data.telefon || '',
            email: data.email || '',
            createdAt: new Date().toISOString(),
        };

        console.log('Yeni danışman oluşturuldu:', newDanisman);

        return newDanisman;
    }

    createOwner(data) {
        if (!data || !data.ad || !data.soyad) {
            return { error: 'Ad ve soyad gereklidir' };
        }

        // Yeni ID oluştur
        const newId = Date.now();

        // Yeni mal sahibi nesnesi oluştur
        const newOwner = {
            id: newId,
            ad: data.ad,
            soyad: data.soyad,
            telefon: data.telefon || '',
            email: data.email || '',
            adres: data.adres || '',
            createdAt: new Date().toISOString(),
        };

        console.log('Yeni mal sahibi oluşturuldu:', newOwner);

        return newOwner;
    }
}

// Sahte API'yi başlat
const mockAPI = new MockAPI();
