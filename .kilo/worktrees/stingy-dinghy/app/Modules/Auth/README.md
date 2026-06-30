# 🔐 Auth Modülü

**Versiyon:** 1.0.0  
**Context7 Standardı:** C7-AUTH-MODULE-2025-12-01  
**Durum:** ✅ Aktif

---

## 📋 Genel Bakış

Auth modülü, kullanıcı kimlik doğrulama, yetkilendirme, rol yönetimi ve kullanıcı yönetimi sağlar.

## 🎯 Sorumluluklar

- **Kimlik Doğrulama:** Login, logout, password reset
- **Yetkilendirme:** Rol tabanlı erişim kontrolü (RBAC)
- **Kullanıcı Yönetimi:** Kullanıcı CRUD işlemleri
- **Rol Yönetimi:** Rol tanımları, yetki atamaları

## 📁 Yapı

```
Auth/
├── Controllers/
│   └── AuthController.php            # Kimlik doğrulama controller'ı
├── Models/
│   ├── User.php                      # Kullanıcı modeli
│   └── Role.php                      # Rol modeli
├── Traits/
│   └── HasRoles.php                  # Rol trait'i
├── Database/
│   ├── Migrations/                   # Veritabanı migration'ları
│   └── Seeders/                      # Seed data
└── routes/
    └── web.php                       # Auth route'ları
```

## 🔗 Bağımlılıklar

- **Laravel Sanctum:** API authentication için
- **Laravel Breeze/Jetstream:** UI scaffolding (opsiyonel)

## 🚀 Kullanım

### Kullanıcı Oluşturma

```php
use App\Modules\Auth\Models\User;

$user = User::create([
    'name' => 'Ahmet Yılmaz',
    'email' => 'ahmet@example.com',
    'password' => Hash::make('password'),
    'role_id' => 1,
    // ...
]);
```

### Rol Kontrolü

```php
use App\Modules\Auth\Traits\HasRoles;

$user = User::find(1);
if ($user->hasRole('admin')) {
    // Admin işlemleri
}
```

## 📊 Route'lar

- `GET /login` - Login sayfası
- `POST /login` - Login işlemi
- `POST /logout` - Logout işlemi
- `GET /register` - Kayıt sayfası
- `POST /register` - Kayıt işlemi
- `GET /password/reset` - Şifre sıfırlama

## 🔧 Yapılandırma

Modül, `AuthServiceProvider` üzerinden yüklenir ve `ModuleServiceProvider` tarafından kaydedilir.

## 📝 Notlar

- **Context7 Uyumluluk:** `status` field kullanılır (status değil)
- **Rol Sistemi:** Laravel'in built-in yetkilendirme sistemi kullanılır
- **Telegram Entegrasyonu:** Kullanıcılar Telegram ile eşleştirilebilir
- **Soft Deletes:** Kullanıcılar soft delete ile silinir

---

**Son Güncelleme:** 01 Aralık 2025
