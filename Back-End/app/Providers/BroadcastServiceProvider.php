<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \Illuminate\Support\Facades\Broadcast::routes(['middleware' => ['auth:api']]);

        require base_path('routes/channels.php');
    }

}
