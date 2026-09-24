<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\ExternalProductController;
use App\Http\Controllers\Admin\NumberOverrideController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\LogAdminController;
use App\Http\Controllers\Admin\LogCategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogStoreController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NumberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/stop-impersonating', [ImpersonationController::class, 'stop'])->middleware('auth')->name('impersonate.stop');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Settings (self-contained; profile.* aliases kept for old links)
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.index');
    Route::get('/profile', [SettingsController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    /*
    |--------------------------------------------------------------------------
    | Virtual numbers
    |--------------------------------------------------------------------------
    | Paths are deliberately generic — no segment names the upstream supplier,
    | and orders are addressed by our own reference, never an activation ID.
    |
    | No per-route throttles. The upstream gateway enforces its own limits
    | (10 purchases, 30 price checks and 60 status checks per minute) and the
    | controller translates a 429 from it into plain wording. Ours were
    | duplicating that and firing first, which is where Laravel's raw
    | "Too Many Attempts." was coming from.
    */
    Route::prefix('numbers')->name('numbers.')->group(function () {

        Route::get('/', [NumberController::class, 'index'])->name('index');

        // Catalogue
        Route::get('/catalogue', [NumberController::class, 'usaServices'])
            ->name('usa.services');

        Route::get('/services/{country}', [NumberController::class, 'globalServices'])
            ->whereNumber('country')
            ->name('services');

        Route::post('/prices', [NumberController::class, 'globalPrices'])
            ->name('prices');

        // Orders
        Route::get('/orders', [NumberController::class, 'orders'])->name('orders');

        Route::post('/purchase', [NumberController::class, 'purchase'])->name('store');

        Route::get('/order/{ref}', [NumberController::class, 'check'])->name('order.check');

        Route::post('/order/{ref}/cancel', [NumberController::class, 'cancel'])->name('order.cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Account store (pre-made accounts sold from stock)
    |--------------------------------------------------------------------------
    */
    Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliates.index');
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/deposit', [WalletController::class, 'deposit'])->name('deposit');
        Route::get('/callback', [WalletController::class, 'callback'])->name('callback');
        Route::get('/recheck/{reference}', [WalletController::class, 'recheck'])->name('recheck');
        Route::get('/status/{reference}', [WalletController::class, 'status'])->name('status');
    Route::match(['get', 'post'], 'virtual-account', [WalletController::class, 'virtualAccount'])->name('virtual-account');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{ref}', [OrderController::class, 'show'])->name('show');
    });

        Route::prefix('services')->name('logs.')->group(function () {
        Route::get('/', [LogStoreController::class, 'index'])->name('index');
        Route::get('/item/{slug}', [LogStoreController::class, 'show'])->name('show');
        Route::get('/item/{slug}/list', [LogStoreController::class, 'items'])->name('items');
        Route::post('/purchase', [LogStoreController::class, 'purchase'])->name('store');
        Route::get('/download/{ref}', [LogStoreController::class, 'download'])->name('download');
    });

});

/*
|--------------------------------------------------------------------------
| Admin panel — behind the 'admin' guard, never the customer one
|--------------------------------------------------------------------------
*/
Route::middleware('admin')->prefix('admin/account')->name('admin.account.')->group(function () {
    Route::get('/', [AdminAccountController::class, 'edit'])->name('index');
    Route::put('/profile', [AdminAccountController::class, 'updateProfile'])->name('profile');
    Route::put('/password', [AdminAccountController::class, 'updatePassword'])->name('password');
});

Route::middleware('admin')->get('admin/dashboard', AdminDashboardController::class)->name('admin.dashboard');

Route::middleware('admin')->prefix('admin/customers')->name('admin.customers.')->group(function () {
    Route::get('/', [AdminCustomerController::class, 'index'])->name('index');
    Route::get('/{id}', [AdminCustomerController::class, 'show'])->name('show');
    Route::post('/{id}/adjust', [AdminCustomerController::class, 'adjust'])->name('adjust');
    Route::post('/{id}/suspend', [AdminCustomerController::class, 'suspend'])->name('suspend');
    Route::put('/{id}/profile', [AdminCustomerController::class, 'updateProfile'])->name('profile');
    Route::post('/{id}/password', [AdminCustomerController::class, 'resetPassword'])->name('password');
    Route::post('/{id}/login-as', [AdminCustomerController::class, 'loginAs'])->name('login-as');
});

Route::middleware('admin')->prefix('admin/transactions')->name('admin.transactions.')->group(function () {
    Route::get('/', [AdminTransactionController::class, 'index'])->name('index');
    Route::get('/{ref}', [AdminTransactionController::class, 'show'])->name('show');
});

Route::middleware('admin')->get('admin/accounts-debug', function () {
    $key    = \App\Support\Gateway::shopviaKey();
    $base   = \App\Support\Gateway::shopviaBase();
    $raw    = \Illuminate\Support\Facades\Http::acceptJson()->timeout(20)
                ->get(rtrim($base,'/').'/products.php', ['api_key' => $key]);
    $mapped = \App\Support\ShopVia::products(true);   // fresh
    return response()->json([
        'key_set'      => $key !== '',
        'key_preview'  => $key ? substr($key,0,6).'…'.substr($key,-4) : null,
        'base'         => $base,
        'http_status'  => $raw->status(),
        'raw_response' => $raw->json() ?? $raw->body(),
        'mapped_count' => count($mapped),
        'mapped_sample'=> array_slice($mapped, 0, 3),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->name('admin.accounts-debug');

Route::middleware('admin')->prefix('admin/numbers')->name('admin.numbers.')->group(function () {
    Route::get('/', [NumberOverrideController::class, 'index'])->name('index');
    Route::post('/', [NumberOverrideController::class, 'store'])->name('store');
    Route::post('/toggle-block', [NumberOverrideController::class, 'toggleBlock'])->name('toggle-block');
    Route::get('/country/{country}/services', [NumberOverrideController::class, 'countryServices'])->name('country-services');
    Route::get('/country/{country}/service/{service}/tiers', [NumberOverrideController::class, 'serviceTiers'])->name('service-tiers');
    Route::post('/toggle-tier', [NumberOverrideController::class, 'toggleTier'])->name('toggle-tier');
    Route::post('/{id}/toggle', [NumberOverrideController::class, 'toggle'])->name('toggle');
    Route::post('/{id}/destroy', [NumberOverrideController::class, 'destroy'])->name('destroy');
});

Route::middleware('admin')->prefix('admin/external')->name('admin.external.')->group(function () {
    Route::get('/', [ExternalProductController::class, 'index'])->name('index');
    Route::post('/{extId}', [ExternalProductController::class, 'save'])->name('save');
});

Route::middleware('admin')->prefix('admin/system-logs')->name('admin.system-logs.')->group(function () {
    Route::get('/', [SystemLogController::class, 'index'])->name('index');
    Route::post('/clear', [SystemLogController::class, 'clear'])->name('clear');
});

Route::middleware('admin')->prefix('admin/settings')->name('admin.settings.')->group(function () {
    Route::get('/', [AdminSettingsController::class, 'edit'])->name('index');
    Route::put('/', [AdminSettingsController::class, 'update'])->name('update');
    Route::get('/reveal', [AdminSettingsController::class, 'reveal'])->name('reveal');
});

Route::middleware('admin')->prefix('admin/orders')->name('admin.orders.')->group(function () {
    Route::get('/', [AdminOrderController::class, 'index'])->name('index');
    Route::get('/{ref}', [AdminOrderController::class, 'show'])->name('show');
    Route::post('/{ref}/refund', [AdminOrderController::class, 'refund'])->name('refund');
});

Route::middleware('admin')->prefix('admin/categories')->name('admin.categories.')->group(function () {
    Route::get('/', [LogCategoryController::class, 'index'])->name('index');
    Route::post('/', [LogCategoryController::class, 'store'])->name('store');
    Route::put('/{category}', [LogCategoryController::class, 'update'])->name('update');
    Route::post('/{category}/destroy', [LogCategoryController::class, 'destroy'])->name('destroy');
});

Route::middleware('admin')->prefix('admin/accounts')->name('admin.logs.')->group(function () {
    Route::get('/', [LogAdminController::class, 'index'])->name('index');
    Route::post('/', [LogAdminController::class, 'store'])->name('store');
    Route::put('/{product}', [LogAdminController::class, 'update'])->name('update');
    Route::post('/{product}/stock', [LogAdminController::class, 'addStock'])->name('stock');
    Route::get('/{product}/items', [LogAdminController::class, 'items'])->name('items');
    Route::post('/{product}/destroy', [LogAdminController::class, 'destroy'])->name('destroy');
    Route::post('/{product}/toggle', [LogAdminController::class, 'toggle'])->name('toggle');

    // Individual stock items
    Route::put('/item/{item}', [LogAdminController::class, 'updateItem'])->name('item.update');
    Route::post('/item/{item}/destroy', [LogAdminController::class, 'destroyItem'])->name('item.destroy');
});


/*
|--------------------------------------------------------------------------
| Admin authentication (separate guard, its own login)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminLoginController::class, 'show'])->name('admin.login');
    Route::post('login', [AdminLoginController::class, 'login'])->name('admin.login.attempt');
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
});

// Serve uploaded media from storage/app/public without the symlink
Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')->name('media.show');

// Email verification by 6-digit code (signed-in but unverified users)
Route::middleware('auth')->group(function () {
    Route::get('/verify-code', [EmailCodeController::class, 'show'])->name('verification.code');
    Route::post('/verify-code', [EmailCodeController::class, 'verify'])->name('verification.verify-code');
    Route::post('/verify-code/resend', [EmailCodeController::class, 'resend'])->name('verification.resend-code');
});

require __DIR__.'/auth.php';