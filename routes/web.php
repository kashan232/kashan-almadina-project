<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\IncentiveController;
use App\Http\Controllers\NarrationController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\AccountsHeadController;
use App\Http\Controllers\SalesOfficerController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\InwardgatepassController;
use App\Http\Controllers\WarehouseStockController;
    use App\Http\Controllers\SubCustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

// Route::get('/adminpage', [HomeController::class, 'adminpage'])->middleware(['auth','admin'])->name('adminpage');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/get-customers-by-type', [CustomerController::class, 'getByType']);
Route::resource('narrations', NarrationController::class)->only(['index','store', 'destroy']);
Route::get('vouchers/{type}', [VoucherController::class, 'index'])->name('vouchers.index');
Route::post('vouchers/store', [VoucherController::class, 'store'])->name('vouchers.store');

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::prefix('accounts')->group(function () {
    Route::get('/expenses', [ExpensesController::class, 'index'])->name('expenses.index');
    Route::get('/income', [IncentiveController::class, 'index'])->name('incomes.index'); 
  
    Route::get('/charts', function () {
    return view('admin_panel.chart_of_accounts');
    });
   
});

    // Chart Of accounts

    Route::get('/view_all', [AccountsHeadController::class, 'index'])->name('view_all');
    // Route::get('/narration', [AccountsHeadController::class, 'narration'])->name('narration');
// Route::get('/expense-heads', [AccountsHeadController::class, 'index'])->name('expense.heads.index');
// Route::post('/expense-heads/store', [AccountsHeadController::class, 'store'])->name('expense.heads.store');
// Route::get('/expense-heads/delete/{id}', [AccountsHeadController::class, 'destroy'])->name('expense.heads.delete');



// narration 
    // Route::get('/narration', [AccountsHeadController::class, 'narration'])->name('narration');
    Route::get('/reciepts_vouchers', [AccountsHeadController::class, 'reciepts_vouchers'])->name('reciepts_vouchers');

    route::get('/category',[CategoryController::class,'index'])->name('Category.home');
    Route::get('/category/delete/{id}', [CategoryController::class, 'delete'])->name('delete.category');
    route::post('/category/stote',[CategoryController::class,'store'])->name("store.category");

    route::get('/Brand',[BrandController::class,'index'])->name('Brand.home');
    Route::get('/Brand/delete/{id}', [BrandController::class, 'delete'])->name('delete.Brand');
    route::post('/Brand/stote',[BrandController::class,'store'])->name("store.Brand");

    route::get('/Unit',[UnitController::class,'index'])->name('Unit.home');
    Route::get('/Unit/delete/{id}', [UnitController::class, 'delete'])->name('delete.Unit');
    route::post('/Unit/stote',[UnitController::class,'store'])->name("store.Unit");

    route::get('/subcategory',[SubcategoryController::class,'index'])->name('subcategory.home');
    Route::get('/subcategory/delete/{id}', [SubcategoryController::class, 'delete'])->name('delete.subcategory');
    route::post('/subcategory/stote',[SubcategoryController::class,'store'])->name("store.subcategory");

    // Route::get('/Product', [ProductController::class, 'product'])->name('product')->middleware('permission:View Product');  
    // Route::post('/store-product', [ProductController::class, 'store_product'])->name('store-product');
    // Route::put('/product/update/{id}', [ProductController::class, 'update'])->name('product.update');
    // Route::get('/fetch-subcategories', [ProductController::class,'fetchSubCategories'])->name('fetch-subcategories');
    
    // Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');

    // Route::get('/barcode/{id}', [ProductController::class, 'barcode'])->name('product.barcode');

    // Product
//     Route::get('/products', [ProductController::class, 'index'])->name('products.index');
//     Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
//     Route::post('/products', [ProductController::class, 'store'])->name('products.store');
//     Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
//     Route::post('/products/{product}/update-price', [ProductController::class, 'updatePrice'])->name('products.updatePrice');
//     Route::get('/products/{product}/prices', [ProductController::class, 'showPrices']);
//     Route::get('/get-subcategories/{category_id}', [ProductController::class, 'getSubcategories']);
//     Route::get('/products/bulk-set-price', [ProductController::class, 'bulkSetPrice'])->name('products.bulkSetPrice');
//     Route::post('/products/bulk-set-price', [ProductController::class, 'bulkSetPriceUpdate'])->name('products.bulkUpdatePrices.update');

Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulkAction');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('/products/{product}/update-price', [ProductController::class, 'updatePrice'])->name('products.updatePrice');
    Route::get('/products/{product}/prices', [ProductController::class, 'showPrices']);
    Route::get('/get-subcategories/{category_id}', [ProductController::class, 'getSubcategories']);
    Route::get('/products/bulk-set-price', [ProductController::class, 'bulkSetPrice'])->name('products.bulkSetPrice');
    Route::post('/products/bulk-set-price', [ProductController::class, 'bulkSetPriceUpdate'])->name('products.bulkUpdatePrices.update');



    // Customer Routes


    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers/store', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/edit/{id}', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::post('/customers/update/{id}', [CustomerController::class, 'update'])->name('customers.update');
    Route::get('/customers/delete/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('/customers/ledger', [CustomerController::class, 'customer_ledger'])->name('customers.ledger');
    Route::get('/customer/payments', [CustomerController::class, 'customer_payments'])->name('customer.payments');
    Route::post('/customer/payments', [CustomerController::class, 'store_customer_payment'])->name('customer.payments.store');


    // New
    Route::get('/customers/inactive', [CustomerController::class, 'inactiveCustomers'])->name('customers.inactive');
    Route::get('/customers/inactive/{id}', [CustomerController::class, 'markInactive'])->name('customers.markInactive');
    Route::get('customers/toggle-status/{id}', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus');
    Route::get('/outstanding-losses',[CustomerController::class,'outstandingLosses'])->name('outstanding.losses');
    // Vendor Routes
    Route::get('/vendor', [VendorController::class, 'index']);
    Route::post('/vendor/store', [VendorController::class, 'store']);
    Route::get('/vendor/delete/{id}', [VendorController::class, 'delete']);
    Route::get('vendor/ledger', [VendorController::class, 'allLedgers'])->name('vendor.ledger');
    Route::get('vendor/payments', [VendorController::class, 'payments_index'])->name('vendor.payments.index');
    Route::post('vendor/payments/store', [VendorController::class, 'payments_store'])->name('vendor.payments.store');

    // Warehouse Routes
    Route::get('/warehouse', [WarehouseController::class, 'index']);
    Route::post('/warehouse/store', [WarehouseController::class, 'store']);
    Route::get('/warehouse/delete/{id}', [WarehouseController::class, 'delete']);
    Route::resource('warehouse_stocks', WarehouseStockController::class);
    Route::resource('stock_transfers', StockTransferController::class);
    Route::get('/warehouse-stock-quantity', [StockTransferController::class, 'getStockQuantity'])->name('warehouse.stock.quantity');

    // Branches
    Route::resource('branch', BranchController::class)->names('branch')->only(['index', 'store']);
    Route::get('/branch/delete/{id}', [BranchController::class, 'delete'])->name('branch.delete');

    // Roles
    Route::resource('roles', RoleController::class)->names('roles')->only(['index', 'store']);
    Route::get('/roles/delete/{id}', [RoleController::class, 'delete'])->name('roles.delete');
    Route::post('/admin/roles/update-permission', [RoleController::class, 'updatePermissions'])->name('roles.update.permission');

    // Permissions
    Route::resource('permissions', PermissionController::class)->names('permissions')->only(['index', 'store']);
    Route::get('/permissions/delete/{id}', [PermissionController::class, 'delete'])->name('permission.delete');

    // Users
    Route::resource('users', UserController::class)->names('users')->only(['index', 'store']);
    Route::get('/users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');
    Route::post('/admin/users/update-roles', [UserController::class, 'updateRoles'])->name('users.update.roles');
    // Route::put('/users/{id}/roles', [UserController::class, 'updateRoles'])->name('users.update.roles');

    // Zone
    Route::get('zone', [ZoneController::class, 'index'])->name('zone.index');
    Route::post('zones/store', [ZoneController::class, 'store'])->name('zone.store');
    Route::get('zones/edit/{id}', [ZoneController::class, 'edit'])->name('zone.edit');
    Route::get('zones/delete/{id}', [ZoneController::class, 'destroy'])->name('zone.delete');


    //Sales Officer
    Route::get('sales-officers', [SalesOfficerController::class, 'index'])->name('sales.officer.index');
    Route::post('sales-officers/store', [SalesOfficerController::class, 'store'])->name('sales-officer.store');
    Route::get('sales-officers/edit/{id}', [SalesOfficerController::class, 'edit'])->name('sales.officer.edit');
    Route::delete('sales-officers/{id}', [SalesOfficerController::class, 'destroy'])->name('sales-officer.delete');

    // products

    route::get('/Purchase',[PurchaseController::class,'index'])->name('Purchase.home');
    route::get('/add/Purchase',[PurchaseController::class,'add_purchase'])->name('add_purchase');
    route::post('/Purchase/stote',[PurchaseController::class,'store'])->name("store.Purchase");
    Route::get('/purchase/{id}/edit', [PurchaseController::class, 'edit'])->name('purchase.edit');
    Route::put('/purchase/{id}', [PurchaseController::class, 'update'])->name('purchase.update');
    Route::delete('/purchase/{id}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');


    // Route::get('/fetch-product', [PurchaseController::class, 'fetchProduct'])->name('item.search');

    // Route::post('/fetch-item-details', [PurchaseController::class, 'fetchItemDetails']);
    Route::get('/search-products', [ProductController::class, 'searchProducts'])->name('search-products');
    // Route::get('/Purchase/create', function () {
    //     return view('admin_panel.purchase.add_purchase');
    // });
    // Route::get('/get-items-by-category/{categoryId}', [PurchaseController::class, 'getItemsByCategory'])->name('get-items-by-category');
    // Route::get('/get-product-details/{productName}', [ProductController::class, 'getProductDetails'])->name('get-product-details');

    route::get('/sale/add',[SaleController::class,'add_sale'])->name('sale.add');
    route::get('/sale',[SaleController::class,'index'])->name('sale.index');
    route::get('/Booking',[SaleController::class,'Booking'])->name('Booking.index');
    route::get('/Booking/edit/{id}',[SaleController::class,'editBooking'])->name('editBooking.index');

    // routes/web.php
    Route::get('/get-products-by-warehouse/{warehouseId}', [App\Http\Controllers\SaleController::class, 'getProductsByWarehouse']);
    // Route::get('/get-stock/{warehouseId}/{productId}', [App\Http\Controllers\SaleController::class, 'getStock']);
    Route::get('/get-stock/{productId}', [App\Http\Controllers\SaleController::class, 'getStock']);
    // web.php
    Route::get('/get-customer/{id}', [App\Http\Controllers\SaleController::class, 'getCustomerData']);
    route::post('/sale/data',[SaleController::class,'store'])->name('sale.store');
    Route::get('/sale/edit/{id}', [SaleController::class, 'edit'])->name('sale.edit');
    Route::post('/sale/update/{id}', [SaleController::class, 'update'])->name('sale.update');



Route::get('/sub-customers', [SubCustomerController::class, 'index'])->name('sub_customers.index');
Route::get('/sub-customers/create', [SubCustomerController::class, 'create'])->name('sub_customers.create');
Route::post('/sub-customers/store', [SubCustomerController::class, 'store'])->name('sub_customers.store');
Route::get('/sub-customers/edit/{id}', [SubCustomerController::class, 'edit'])->name('sub_customers.edit');
Route::post('/sub-customers/update/{id}', [SubCustomerController::class, 'update'])->name('sub_customers.update');
Route::get('/sub-customers/delete/{id}', [SubCustomerController::class, 'destroy'])->name('sub_customers.destroy');
Route::get('/sub-customers/toggle-status/{id}', [SubCustomerController::class, 'toggleStatus'])->name('sub_customers.toggleStatus');
Route::get('/sub-customers/ledger', [SubCustomerController::class, 'getLedger'])->name('sub_customers.ledger');
Route::get('/sub-customers/by-type', [SubCustomerController::class, 'getByType']);
// SubCustomer inactive list
Route::get('/sub_customers/inactive', [SubCustomerController::class, 'inactive'])->name('sub_customers.inactive');

});Route::get('sale/invoice/{id}', [SaleController::class, 'invoice'])->name('sale.invoice');
// SubCustomer Payments
Route::get('/sub_customers/payments', [SubCustomerController::class, 'payments'])->name('sub_customers.payments');
Route::post('/sub_customers/payments/store', [SubCustomerController::class, 'storePayment'])->name('sub_customers.payments.store');
Route::get('vouchers/{id}/receipt', [VoucherController::class, 'receipt'])->name('vouchers.receipt');


// Inward Gatepass Routes
Route::get('/InwardGatepass', [InwardgatepassController::class, 'index'])->name('InwardGatepass.home');
Route::get('/add/InwardGatepass', [InwardgatepassController::class, 'create'])->name('add_inwardgatepass');
Route::post('/InwardGatepass/store', [InwardgatepassController::class, 'store'])->name("store.InwardGatepass");
Route::get('/InwardGatepass/{id}', [InwardgatepassController::class, 'show'])->name('InwardGatepass.show');

// edit/update/delete abhi comment kiye hue hain
Route::get('/InwardGatepass/{id}/edit', [InwardgatepassController::class, 'edit'])->name('InwardGatepass.edit');
Route::put('/InwardGatepass/{id}', [InwardgatepassController::class, 'update'])->name('InwardGatepass.update');
Route::get('/inward-gatepass/{id}/pdf', [InwardgatepassController::class, 'pdf'])->name('InwardGatepass.pdf');


Route::delete('/InwardGatepass/{id}', [InwardgatepassController::class, 'destroy'])->name('InwardGatepass.destroy');
// Products search
Route::get('/search-productsinwar', [InwardgatepassController::class, 'searchProducts'])->name('search-productsinwar');


// Show Add Bill Form
Route::get('inward-gatepass/{id}/add-bill', [PurchaseController::class, 'addBill'])->name('add_bill');
// Store Bill
Route::post('inward-gatepass/{id}/store-bill', [PurchaseController::class, 'store'])->name('store.bill');

Route::prefix('coa')->group(function() {
    Route::get('/', [AccountsHeadController::class,'index'])->name('coa.index');
    Route::post('/head', [AccountsHeadController::class,'storeHead'])->name('coa.head.store');
    Route::post('/account', [AccountsHeadController::class,'storeAccount'])->name('coa.account.store');
});

require __DIR__.'/auth.php';
