<?php

require_once __DIR__.'/../vendor/autoload.php';

Dotenv::load(__DIR__.'/../');

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

class_alias('Illuminate\Support\Facades\Response', 'Response');
class_alias('Maatwebsite\Excel\Facades\Excel', 'Excel');

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->register('App\Providers\AppServiceProvider');
$app->withFacades();
$app->configure('jwt');
class_alias('Tymon\JWTAuth\Facades\JWTAuth', 'JWTAuth');
class_alias('Intervention\Image\Facades\Image', 'Image');
// class_alias('I')

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     // Illuminate\Cookie\Middleware\EncryptCookies::class,
//     // Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
//     // Illuminate\Session\Middleware\StartSession::class,
//     // Illuminate\View\Middleware\ShareErrorsFromSession::class,
//     // Laravel\Lumen\Http\Middleware\VerifyCsrfToken::class,
// ]);



 $app->routeMiddleware([
     'jwt.auth' => Tymon\JWTAuth\Middleware\GetUserFromToken::class,
     'jwt.refresh' => Tymon\JWTAuth\Middleware\RefreshToken::class,
     'authorization.admin' => App\Http\Middleware\Admin::class,
     'authorization.inspector' => App\Http\Middleware\Inspector::class
 ]);

$app->register('App\Providers\CatchAllOptionsRequestsProvider');
$app->middleware([
    '\App\Http\Middleware\CorsMiddleware',
    Illuminate\Session\Middleware\StartSession::class,
    Illuminate\Cookie\Middleware\EncryptCookies::class,
    Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    Illuminate\View\Middleware\ShareErrorsFromSession::class,
    // Laravel\Lumen\Http\Middleware\VerifyCsrfToken::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->register(\Barryvdh\DomPDF\ServiceProvider::class);

// Laravel Excel
$app->register('Maatwebsite\Excel\ExcelServiceProvider');


$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require __DIR__.'/../app/Http/routes.php';
});

$app->register('Tymon\JWTAuth\Providers\JWTAuthServiceProvider');
$app->register('Intervention\Image\ImageServiceProvider');

return $app;
