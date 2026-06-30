<?php

namespace Tests\Feature;

use App\Models\IlanKategori;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{

    public function test_yazlik_kiralama_subcategories_exclude_publication_types(): void
    {
        $yazlik = IlanKategori::where('slug', 'like', '%yazlik%')
            ->orWhere('name', 'like', '%Yazlık%')
            ->where('seviye', 0)
            ->first();

        if (!$yazlik) {
            $this->markTestSkipped('Yazlık Kiralama kategori bulunamadı');
        }

        $subCategories = IlanKategori::where('parent_id', $yazlik->id)
            ->where('seviye', 1)
            ->where('aktiflik_durumu', true)
            ->where('seviye', '!=', 2)
            ->get();

        $publicationTypeNames = ['Günlük Kiralama', 'Haftalık Kiralama', 'Aylık Kiralama', 'Sezonluk Kiralama'];
        
        foreach ($subCategories as $category) {
            $this->assertNotContains(
                $category->name,
                $publicationTypeNames,
                "Alt kategori listesinde yayın tipi bulunmamalı: {$category->name}"
            );
        }

        $this->assertTrue(
            $subCategories->count() > 0,
            'Yazlık Kiralama için en az bir alt kategori olmalı'
        );
    }

    public function test_subcategories_api_excludes_seviye_2(): void
    {
        $yazlik = IlanKategori::where('slug', 'like', '%yazlik%')
            ->orWhere('name', 'like', '%Yazlık%')
            ->where('seviye', 0)
            ->first();

        if (!$yazlik) {
            $this->markTestSkipped('Yazlık Kiralama kategori bulunamadı');
        }

        $response = $this->getJson("/api/v1/categories/sub/{$yazlik->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'subcategories' => [
                    '*' => ['id', 'name', 'slug']
                ]
            ]
        ]);

        $subcategories = $response->json('data.subcategories');
        
        foreach ($subcategories as $category) {
            $dbCategory = IlanKategori::find($category['id']);
            if ($dbCategory) {
                $this->assertNotEquals(
                    2,
                    $dbCategory->seviye,
                    "API response'unda seviye=2 kategori bulunmamalı: {$category['name']}"
                );
            }
        }
    }

    public function test_konut_subcategories_exclude_publication_types(): void
    {
        $konut = IlanKategori::where('slug', 'konut')
            ->where('seviye', 0)
            ->first();

        if (!$konut) {
            $this->markTestSkipped('Konut kategori bulunamadı');
        }

        $subCategories = IlanKategori::where('parent_id', $konut->id)
            ->where('seviye', 1)
            ->where('aktiflik_durumu', true)
            ->where('seviye', '!=', 2)
            ->get();

        $publicationTypeNames = ['Satılık', 'Kiralık', 'Günlük Kiralama', 'Haftalık Kiralama', 'Aylık Kiralama', 'Sezonluk Kiralama'];
        
        foreach ($subCategories as $category) {
            $this->assertNotContains(
                $category->name,
                $publicationTypeNames,
                "Konut alt kategori listesinde yayın tipi bulunmamalı: {$category->name}"
            );
        }

        $this->assertTrue(
            $subCategories->count() > 0,
            'Konut için en az bir alt kategori olmalı'
        );
    }

    public function test_arsa_subcategories_exclude_publication_types(): void
    {
        $arsa = IlanKategori::where('slug', 'arsa-arazi')
            ->where('seviye', 0)
            ->first();

        if (!$arsa) {
            $this->markTestSkipped('Arsa & Arazi kategori bulunamadı');
        }

        $subCategories = IlanKategori::where('parent_id', $arsa->id)
            ->where('seviye', 1)
            ->where('aktiflik_durumu', true)
            ->where('seviye', '!=', 2)
            ->get();

        $publicationTypeNames = ['Satılık', 'Kiralık', 'Günlük Kiralama', 'Haftalık Kiralama', 'Aylık Kiralama', 'Sezonluk Kiralama'];
        
        foreach ($subCategories as $category) {
            $this->assertNotContains(
                $category->name,
                $publicationTypeNames,
                "Arsa alt kategori listesinde yayın tipi bulunmamalı: {$category->name}"
            );
        }

        $this->assertTrue(
            $subCategories->count() > 0,
            'Arsa & Arazi için en az bir alt kategori olmalı'
        );
    }

    public function test_konut_subcategories_api_excludes_seviye_2(): void
    {
        $konut = IlanKategori::where('slug', 'konut')
            ->where('seviye', 0)
            ->first();

        if (!$konut) {
            $this->markTestSkipped('Konut kategori bulunamadı');
        }

        $response = $this->getJson("/api/v1/categories/sub/{$konut->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'subcategories' => [
                    '*' => ['id', 'name', 'slug']
                ]
            ]
        ]);

        $subcategories = $response->json('data.subcategories');
        
        foreach ($subcategories as $category) {
            $dbCategory = IlanKategori::find($category['id']);
            if ($dbCategory) {
                $this->assertNotEquals(
                    2,
                    $dbCategory->seviye,
                    "Konut API response'unda seviye=2 kategori bulunmamalı: {$category['name']}"
                );
            }
        }
    }

    public function test_arsa_subcategories_api_excludes_seviye_2(): void
    {
        $arsa = IlanKategori::where('slug', 'arsa-arazi')
            ->where('seviye', 0)
            ->first();

        if (!$arsa) {
            $this->markTestSkipped('Arsa & Arazi kategori bulunamadı');
        }

        $response = $this->getJson("/api/v1/categories/sub/{$arsa->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'subcategories' => [
                    '*' => ['id', 'name', 'slug']
                ]
            ]
        ]);

        $subcategories = $response->json('data.subcategories');
        
        foreach ($subcategories as $category) {
            $dbCategory = IlanKategori::find($category['id']);
            if ($dbCategory) {
                $this->assertNotEquals(
                    2,
                    $dbCategory->seviye,
                    "Arsa API response'unda seviye=2 kategori bulunmamalı: {$category['name']}"
                );
            }
        }
    }
}
