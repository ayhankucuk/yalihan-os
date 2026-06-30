<?php

namespace Tests\Architecture;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ReleaseGuardTest extends TestCase
{
    /**
     * 🛡️ SAB v6.x Zero-Tolerance Layer Isolation
     * Controllers MUST NOT access DB directly.
     */
    public function test_controllers_do_not_access_db_directly()
    {
        $controllerPath = base_path("app/Http/Controllers");
        $files = File::allFiles($controllerPath);

        $violations = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== "php") continue;

            $content = File::get($file->getRealPath());
            if (preg_match("/DB::table|->from\(|\\DB::table/", $content)) {
                // Skips traits and base controllers if necessary
                if (str_contains($file->getFilename(), "Base") || str_contains($file->getFilename(), "Traits")) continue;

                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertEmpty($violations, "SAB Violation: Controllers accessed DB directly: " . implode(", ", $violations));
    }

    /**
     * 🛡️ SAB v6.x Forbidden Field Access
     * Models MUST NOT have legacy durum accessors.
     */
    public function test_models_do_not_have_legacy_accessors()
    {
        $modelPath = base_path("app/Models");
        $files = File::allFiles($modelPath);

        $forbiddenAccessors = ["getDurumAttribute", "setDurumAttribute", "getAktifAttribute", "setAktifAttribute"];
        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== "php") continue;

            $content = File::get($file->getRealPath());
            foreach ($forbiddenAccessors as $accessor) {
                if (str_contains($content, "function $accessor")) {
                    $violations[] = $file->getFilename() . " ($accessor)";
                }
            }
        }

        $this->assertEmpty($violations, "SAB Violation: Models found with legacy accessors: " . implode(", ", $violations));
    }
}
