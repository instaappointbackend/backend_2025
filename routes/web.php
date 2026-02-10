<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WebBlogController;

/*
|--------------------------------------------------------------------------
| Utility & Maintenance Routes
|--------------------------------------------------------------------------
*/

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return 'Cache cleared successfully.';
});

Route::get('/run-migration', function (Request $request) {

    if ($request->query('key') !== env('MIGRATE_KEY')) {
        abort(403, 'Unauthorized.');
    }

    Artisan::call('migrate', ['--force' => true]);

    return 'Migration executed successfully.';
});

Route::get('/maintenance/dump-autoload', [
    App\Http\Controllers\MaintenanceController::class,
    'dumpAutoload'
]);

Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
    return 'Storage link created.';
});

/*
|--------------------------------------------------------------------------
| Public Website Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/download-mobile', [HomeController::class, 'downloadApk'])->name('downloadApk');

/*
|--------------------------------------------------------------------------
| Blog Routes
|--------------------------------------------------------------------------
*/

Route::get('/blogs', [WebBlogController::class, 'blogs'])->name('blogs');
Route::get('/blog/{slug}', [WebBlogController::class, 'blog'])->name('blog');
Route::post('/blog/read/{slug}', [WebBlogController::class, 'incrementReadCount'])
    ->name('blog.read');

/*
|--------------------------------------------------------------------------
| Contact & Newsletter
|--------------------------------------------------------------------------
*/

Route::post('contact/submit', [
    App\Http\Controllers\ContactController::class,
    'submit'
])->name('contact.submit');

Route::post('newsletter/subscribe', [
    App\Http\Controllers\NewsletterController::class,
    'subscribe'
])->name('newsletter.subscribe');

Route::get('newsletter/unsubscribe/{email?}', [
    App\Http\Controllers\NewsletterController::class,
    'unsubscribe'
])->name('newsletter.unsubscribe');

/*
|--------------------------------------------------------------------------
| Admin & Vendor Routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/payments.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/vendor.php';

/*
|--------------------------------------------------------------------------
| CMS / Dynamic Pages (⚠️ ALWAYS LAST)
|--------------------------------------------------------------------------
*/

Route::get('{page:slug}', [HomeController::class, 'show'])
    ->where('page', '(?!admin|login|register|password|home|dashboard).*')
    ->name('page.show');
