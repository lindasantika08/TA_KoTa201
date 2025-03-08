<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('emails.credentials', function ($view) {
            Log::info('Data yang diterima di view credentials:', [
                'nama' => $view->getData()['namaMahasiswa'] ?? 'tidak ada',
                'email' => $view->getData()['email'] ?? 'tidak ada',
                'password' => $view->getData()['password'] ?? 'tidak ada',
                'semua_data' => $view->getData()
            ]);
        });
    }
}