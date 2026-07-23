<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ViewServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ViewServiceProvider::class,
    VoltServiceProvider::class,
];
