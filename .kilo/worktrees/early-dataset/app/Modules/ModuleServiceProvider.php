<?php

namespace App\Modules;

use App\Modules\Admin\AdminServiceProvider;
use App\Modules\Analitik\AnalitikServiceProvider;
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Crm\CrmServiceProvider;
use App\Modules\Emlak\EmlakServiceProvider;
use App\Modules\Finans\FinansServiceProvider;
use App\Modules\TakimYonetimi\TakimYonetimiServiceProvider;
// use App\Modules\Location\LocationServiceProvider; // Bu satırı kaldır
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Tüm uygulama servislerini kaydet
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(AdminServiceProvider::class);
        $this->app->register(EmlakServiceProvider::class);
        $this->app->register(CrmServiceProvider::class);
        $this->app->register(TakimYonetimiServiceProvider::class);
        $this->app->register(AnalitikServiceProvider::class);
        $this->app->register(FinansServiceProvider::class);
        // Talep + TalepAnaliz: config/app.php L177-178'de zaten kayıtlı — çift kayıt önlendi
        // Market modülü: ServiceProvider yok — #59 izleniyor
        // $this->app->register(LocationServiceProvider::class); // Bu satırı kaldır
    }

    /**
     * Bootstrap modül servisleri.
     *
     * @return void
     */
    public function boot()
    {
        // View dizinlerini ve route dosyalarını kaydediyoruz
        $modules = File::directories(__DIR__);

        foreach ($modules as $module) {
            $moduleName = basename($module);

            // Views dizinini kaydet
            $viewsPath = $module.'/Views';
            if (File::isDirectory($viewsPath)) {
                $this->loadViewsFrom($viewsPath, strtolower($moduleName));
            }

            // Routes dizinini kaydet
            $routesPath = $module.'/routes';
            if (File::isDirectory($routesPath)) {
                $webRoutePath = $routesPath.'/web.php';
                $apiRoutePath = $routesPath.'/api.php';

                if (File::exists($webRoutePath)) {
                    $this->loadRoutesFrom($webRoutePath);
                }

                if (File::exists($apiRoutePath)) {
                    $this->loadRoutesFrom($apiRoutePath);
                }
            }
        }
    }
}
