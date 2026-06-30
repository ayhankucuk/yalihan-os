<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Enums\IlanDurumu;
use Spatie\Permission\Models\Permission;

/**
 * TestFixtureHelper
 * 🛡️ Phase T3: Fixture Canonicalization
 *
 * Canonical factory methods for test fixtures.
 * Eliminates inline Role::create / Permission::create hacks from individual tests.
 */
trait TestFixtureHelper
{
    /**
     * Create a canonical Admin user with required roles and permissions.
     *
     * Uses the same pattern that was proven in ListingLifecycleFinalSealTest:
     * - App\Modules\Auth\Models\Role (custom model, no $fillable for 'name')
     * - Spatie Permission for givePermissionTo
     */
    protected function createAdminUser(array $attributes = []): User
    {
        // 🛡️ Role: Use property assignment (not mass-assign) because
        // App\Modules\Auth\Models\Role does not have 'name' in $fillable.
        $role = \App\Modules\Auth\Models\Role::where('name', 'admin')->first();
        if (!$role) {
            $role = new \App\Modules\Auth\Models\Role();
            $role->name = 'admin';
            $role->save();
        }

        $user = User::factory()->create(array_merge([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ], $attributes));

        // 🛡️ Spatie Permissions
        $this->ensurePermissions(['edit-ilanlar', 'manage-ilanlar', 'access-admin']);
        $user->givePermissionTo(['edit-ilanlar', 'manage-ilanlar', 'access-admin']);

        return $user;
    }

    /**
     * Ensure Spatie permissions exist.
     */
    protected function ensurePermissions(array $permissions, string $guard = 'web'): void
    {
        foreach ($permissions as $p) {
            if (!Permission::where('name', $p)->where('guard_name', $guard)->exists()) {
                Permission::create(['name' => $p, 'guard_name' => $guard]);
            }
        }
    }

    /**
     * Create a publishable listing (ready for transition to YAYINDA).
     * Includes valid category, template, completion & quality scores.
     */
    protected function createPublishableListing(User $owner, array $attributes = []): Ilan
    {
        $kategori = IlanKategori::factory()->create();
        $sablon = YayinTipiSablonu::factory()->create(['kategori_id' => $kategori->id]);

        return Ilan::factory()->create(array_merge([
            'danisman_id' => $owner->id,
            'yayin_durumu' => IlanDurumu::BEKLEMEDE,
            'completion_score' => 100,
            'quality_score' => 85,
            'ana_kategori_id' => $kategori->id,
            'yayin_tipi_id' => $sablon->id,
        ], $attributes));
    }

    /**
     * Ensure a specific Il exists (handles soft-deletes and fixed IDs).
     */
    protected function ensureIl(int $id, array $attributes = []): \App\Models\Il
    {
        $il = \App\Models\Il::withTrashed()->find($id);

        if ($il) {
            if ($il->trashed()) {
                $il->restore();
            }
            $il->update($attributes);
            return $il;
        }

        return \App\Models\Il::forceCreate(array_merge([
            'id' => $id,
            'il_adi' => 'Test Il ' . $id,
            'plaka_kodu' => $id,
            'aktiflik_durumu' => true,
        ], $attributes));
    }

    /**
     * Ensure a specific Ilce exists.
     */
    protected function ensureIlce(int $id, int $ilId, array $attributes = []): \App\Models\Ilce
    {
        $ilce = \App\Models\Ilce::withTrashed()->find($id);

        if ($ilce) {
            if ($ilce->trashed()) {
                $ilce->restore();
            }
            $ilce->update(array_merge(['il_id' => $ilId], $attributes));
            return $ilce;
        }

        return \App\Models\Ilce::forceCreate(array_merge([
            'id' => $id,
            'il_id' => $ilId,
            'ilce_adi' => 'Test Ilce ' . $id,
            'aktiflik_durumu' => true,
        ], $attributes));
    }

    /**
     * Ensure a specific IlanKategori exists.
     */
    protected function ensureKategori(string $slug, array $attributes = []): IlanKategori
    {
        $kategori = IlanKategori::withTrashed()->where('slug', $slug)->first();

        if ($kategori) {
            if ($kategori->trashed()) {
                $kategori->restore();
            }
            $kategori->update($attributes);
            return $kategori;
        }

        return IlanKategori::factory()->create(array_merge([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'aktiflik_durumu' => true,
        ], $attributes));
    }

    /**
     * Ensure a specific YayinTipiSablonu exists.
     */
    protected function ensureYayinTipi(string $slug, array $attributes = []): YayinTipiSablonu
    {
        $tip = YayinTipiSablonu::withTrashed()->where('slug', $slug)->first();

        if ($tip) {
            if ($tip->trashed()) {
                $tip->restore();
            }
            $tip->update($attributes);
            return $tip;
        }

        return YayinTipiSablonu::factory()->create(array_merge([
            'slug' => $slug,
            'ad' => ucfirst(str_replace('-', ' ', $slug)),
            'aktiflik_durumu' => \App\Enums\AktiflikDurumu::AKTIF,
        ], $attributes));
    }

    /**
     * Ensure a specific Mahalle exists.
     */
    protected function ensureMahalle(int $id, int $ilceId, array $attributes = []): \App\Models\Mahalle
    {
        $mahalle = \App\Models\Mahalle::withTrashed()->find($id);

        if ($mahalle) {
            if ($mahalle->trashed()) {
                $mahalle->restore();
            }
            $mahalle->update(array_merge(['ilce_id' => $ilceId], $attributes));
            return $mahalle;
        }

        return \App\Models\Mahalle::forceCreate(array_merge([
            'id' => $id,
            'ilce_id' => $ilceId,
            'mahalle_adi' => 'Test Mahalle ' . $id,
            'aktiflik_durumu' => true,
        ], $attributes));
    }

    /**
     * Ensure a specific User exists.
     */
    protected function ensureUser(string $email, array $attributes = []): User
    {
        $user = User::withTrashed()->where('email', $email)->first();

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->update($attributes);
            return $user;
        }

        return User::factory()->create(array_merge([
            'email' => $email,
        ], $attributes));
    }

    /**
     * Ensure a specific Kisi exists.
     */
    protected function ensureKisi(string $emailOrTckn, array $attributes = []): \App\Models\Kisi
    {
        $query = \App\Models\Kisi::withTrashed();
        
        if (filter_var($emailOrTckn, FILTER_VALIDATE_EMAIL)) {
            $query->where('email', $emailOrTckn);
        } else {
            $query->where('tckn', $emailOrTckn);
        }

        $kisi = $query->first();

        if ($kisi) {
            if ($kisi->trashed()) {
                $kisi->restore();
            }
            $kisi->update($attributes);
            return $kisi;
        }

        return \App\Models\Kisi::withoutEvents(function() use ($emailOrTckn, $attributes) {
            return \App\Models\Kisi::factory()->create(array_merge([
                str_contains($emailOrTckn, '@') ? 'email' : 'tckn' => $emailOrTckn,
            ], $attributes));
        });
    }

    /**
     * Ensure a specific Proje exists.
     */
    protected function ensureProje(string $slug, array $attributes = []): \App\Models\Proje
    {
        $proje = \App\Models\Proje::withTrashed()->where('slug', $slug)->first();

        if ($proje) {
            if ($proje->trashed()) {
                $proje->restore();
            }
            $proje->update($attributes);
            return $proje;
        }

        return \App\Models\Proje::factory()->create(array_merge([
            'slug' => $slug,
            'proje_adi' => ucfirst($slug),
        ], $attributes));
    }

    /**
     * Ensure a specific Feature exists.
     */
    protected function ensureFeature(int $id, array $attributes = []): \App\Models\Feature
    {
        $feature = \App\Models\Feature::withTrashed()->find($id);

        if ($feature) {
            if ($feature->trashed()) {
                $feature->restore();
            }
            $feature->update($attributes);
            return $feature;
        }

        return \App\Models\Feature::forceCreate(array_merge([
            'id' => $id,
            'name' => 'Test Feature ' . $id,
            'slug' => 'test-feature-' . $id,
            'type' => 'text',
            'aktiflik_durumu' => true,
        ], $attributes));
    }

    /**
     * Ensure a specific FeatureCategory exists.
     */
    protected function ensureFeatureCategory(int $id, array $attributes = []): \App\Models\FeatureCategory
    {
        $cat = \App\Models\FeatureCategory::withTrashed()->find($id);

        if ($cat) {
            if ($cat->trashed()) {
                $cat->restore();
            }
            $cat->update($attributes);
            return $cat;
        }

        return \App\Models\FeatureCategory::forceCreate(array_merge([
            'id' => $id,
            'name' => 'Test Feature Category ' . $id,
            'slug' => 'test-feature-cat-' . $id,
            'aktiflik_durumu' => true,
        ], $attributes));
    }

    /**
     * Ensure a specific FeatureAssignment exists.
     */
    protected function ensureFeatureAssignment(array $keys, array $attributes = []): \App\Models\FeatureAssignment
    {
        $assignment = \App\Models\FeatureAssignment::where($keys)->first();

        if ($assignment) {
            $assignment->update($attributes);
            return $assignment;
        }

        return \App\Models\FeatureAssignment::create(array_merge($keys, $attributes));
    }
}
