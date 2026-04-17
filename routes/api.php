<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gunakan semua controller
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\UserDeviceController;

use App\Http\Controllers\Api\LaundryPackageController;
use App\Http\Controllers\Api\LaundryItemController;
use App\Http\Controllers\Api\LaundryAddonController;
use App\Http\Controllers\Api\LaundryOrderController;
use App\Http\Controllers\Api\LaundryOrderItemController;
use App\Http\Controllers\Api\LaundryOrderAddonController;
use App\Http\Controllers\Api\LaundryStatusHistoryController;

use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductStockController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\MarketOrderController;
use App\Http\Controllers\Api\MarketOrderItemController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseItemController;

use App\Http\Controllers\Api\DeliveryRequestController;
use App\Http\Controllers\Api\DeliveryStatusHistoryController;

use App\Http\Controllers\Api\PosOrderController;
use App\Http\Controllers\Api\PosOrderItemController;

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InvoiceController;

use App\Http\Controllers\Api\DailyReportController;
use App\Http\Controllers\Api\StockMovementController;

use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\AppSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| AUTH (system security)
|--------------------------------------------------------------------------
*/

Route::post('sign_in_identifier', [AuthController::class, 'login']);
Route::post('sign_up_verifier', [AuthController::class, 'register']);
Route::post('send_reset_code', [AuthController::class, 'sendResetCode']);
Route::post('verify_reset_code', [AuthController::class, 'verifyResetCode']);
Route::post('change_password/reset', [AuthController::class, 'changePassword']);

Route::get('/app-settings', function () {
    return App\Models\AppSetting::pluck('value', 'key');
});

/*
|--------------------------------------------------------------------------
| PROTECTED API (Semua butuh token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('get_profile', [UserController::class, 'me']);
    Route::post('/uploads/image', [AppSettingController::class, 'image']);

    // A. USERS & ROLES
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('user-addresses', UserAddressController::class);
    Route::apiResource('user-devices', UserDeviceController::class);

    // B. LAUNDRY SYSTEM
    Route::apiResource('laundry-packages', LaundryPackageController::class);
    Route::apiResource('laundry-items', LaundryItemController::class);
    Route::apiResource('laundry-addons', LaundryAddonController::class);
    Route::apiResource('laundry-orders', LaundryOrderController::class);
    Route::apiResource('laundry-order-items', LaundryOrderItemController::class);
    Route::apiResource('laundry-order-addons', LaundryOrderAddonController::class);
    Route::apiResource('laundry-status-history', LaundryStatusHistoryController::class);

    // C. MINIMARKET SYSTEM
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-stock', ProductStockController::class);
    Route::apiResource('carts', CartController::class);
    Route::apiResource('cart-items', CartItemController::class);
    Route::apiResource('market-orders', MarketOrderController::class);
    Route::apiResource('market-order-items', MarketOrderItemController::class);

    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::apiResource('purchase-items', PurchaseItemController::class);

    // D. DELIVERY SYSTEM
    Route::apiResource('delivery-requests', DeliveryRequestController::class);
    Route::apiResource('delivery-status-history', DeliveryStatusHistoryController::class);

    // E. POS SYSTEM
    Route::apiResource('pos-orders', PosOrderController::class);
    Route::apiResource('pos-order-items', PosOrderItemController::class);

    // F. PAYMENT & INVOICE
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('invoices', InvoiceController::class);

    // G. REPORTING
    Route::apiResource('daily-reports', DailyReportController::class);
    Route::apiResource('stock-movements', StockMovementController::class);
    Route::get('daily-reports/chart', [DailyReportController::class, 'chart']);

    Route::apiResource('feedbacks', FeedbackController::class);
    Route::post('feedbacks/{id}/response', [FeedbackController::class, 'respond']);
    Route::get('settings', [AppSettingController::class, 'index']);
    Route::get('settings/{key}', [AppSettingController::class, 'show']);
    Route::post('settings', [AppSettingController::class, 'store']);

    Route::post('/cart-laundry/add', [LaundryOrderController::class, 'addToCart']);
    Route::get('/cart-laundry', [LaundryOrderController::class, 'getCart']);
    Route::delete('/cart-laundry/{id}', [LaundryOrderController::class, 'removeCart']);

});
