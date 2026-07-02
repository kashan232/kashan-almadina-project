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
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\AccountsHeadController;
use App\Http\Controllers\SalesOfficerController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\InwardgatepassController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\StockHoldController;
use App\Http\Controllers\WarehouseStockController;
use App\Http\Controllers\SubCustomerController;
use App\Http\Controllers\StockWastageController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\CustomerClaimController;
use App\Http\Controllers\CustomerClaimReleaseController;
use App\Http\Controllers\ClaimItemReceiptController;
use App\Http\Controllers\ClaimCreditNoteController;
use App\Http\Controllers\RollbackController;
use App\Http\Controllers\GeneralLedgerController;

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

// Customer Claim Routes
Route::middleware(['auth'])->group(function () {
    // Rollback Routes
    Route::get('/rollback', [RollbackController::class, 'index'])->name('rollback.index');
    Route::post('/rollback/process', [RollbackController::class, 'process'])->name('rollback.process');

    Route::get('/customer-claims', [CustomerClaimController::class, 'index'])->name('customer-claims.index');
    Route::get('/customer-claims/add', [CustomerClaimController::class, 'create'])->name('customer-claims.create');
    Route::post('/customer-claims/ajax-save', [CustomerClaimController::class, 'ajaxSave'])->name('customer-claims.ajax-save');
    Route::post('/customer-claims/post/{id}', [CustomerClaimController::class, 'post'])->name('customer-claims.post');
    Route::get('/customer-claims/edit/{id}', [CustomerClaimController::class, 'edit'])->name('customer-claims.edit');
    Route::get('/customer-claims/search-products', [ProductController::class, 'searchProducts'])->name('customer-claims.search-products');
    
    // General Ledger Routes
    Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');
    Route::get('/general-ledger/get-accounts-by-head/{headId}', [GeneralLedgerController::class, 'getAccountsByHead']);
    Route::get('/general-ledger/search-unified', [GeneralLedgerController::class, 'searchUnified'])->name('general-ledger.search-unified');
    Route::get('/general-ledger/lookup-by-code', [GeneralLedgerController::class, 'lookupByCode'])->name('general-ledger.lookup-by-code');
    Route::get('/general-ledger/preview', [GeneralLedgerController::class, 'preview'])->name('general-ledger.preview');
    
    // Customer Claim Release Routes
    Route::get('/customer-claims-release', [CustomerClaimReleaseController::class, 'index'])->name('customer-claims.release.index');
    Route::get('/customer-claims-release/add', [CustomerClaimReleaseController::class, 'create'])->name('customer-claims.release.create');
    Route::post('/customer-claims-release/ajax-save', [CustomerClaimReleaseController::class, 'ajaxSave'])->name('customer-claims.release.ajax-save');
    Route::post('/customer-claims-release/post/{id}', [CustomerClaimReleaseController::class, 'post'])->name('customer-claims.release.post');
    Route::get('/customer-claims-release/hold-list/json', [CustomerClaimReleaseController::class, 'getHoldClaims'])->name('customer-claims.release.hold-list.json');
    Route::get('/customer-claims-release/details/{id}', [CustomerClaimReleaseController::class, 'getClaimDetails'])->name('customer-claims.release.details');

    // Claim Acceptance Routes
    Route::get('/claim-acceptance', [\App\Http\Controllers\ClaimAcceptanceController::class, 'index'])->name('claim-acceptance.index');
    Route::get('/claim-acceptance/add', [\App\Http\Controllers\ClaimAcceptanceController::class, 'create'])->name('claim-acceptance.create');
    Route::post('/claim-acceptance/ajax-save', [\App\Http\Controllers\ClaimAcceptanceController::class, 'ajaxSave'])->name('claim-acceptance.ajax-save');
    Route::post('/claim-acceptance/post/{id}', [\App\Http\Controllers\ClaimAcceptanceController::class, 'post'])->name('claim-acceptance.post');
    Route::get('/claim-acceptance/edit/{id}', [\App\Http\Controllers\ClaimAcceptanceController::class, 'edit'])->name('claim-acceptance.edit');
    Route::get('/claim-acceptance/print/{id}', [\App\Http\Controllers\ClaimAcceptanceController::class, 'print'])->name('claim-acceptance.print');
    Route::delete('/claim-acceptance/destroy/{id}', [\App\Http\Controllers\ClaimAcceptanceController::class, 'destroy'])->name('claim-acceptance.destroy');
    Route::get('/claim-acceptance/party-list', [\App\Http\Controllers\ClaimAcceptanceController::class, 'partyList'])->name('claim-acceptance.party-list');
    Route::get('/products/get-by-id/{id}', [\App\Http\Controllers\ProductController::class, 'getProductById'])->name('products.get_by_id');
});
// kashan connected
// up
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

// Route::get('/adminpage', [HomeController::class, 'adminpage'])->middleware(['auth','admin'])->name('adminpage');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/get-customers-by-type', [CustomerController::class, 'getByType']);
Route::resource('narrations', NarrationController::class)->only(['index', 'store', 'destroy'])->names([
    'index' => 'coa.narration',
]);
Route::get('/narrations/receipts/json', [NarrationController::class, 'getForReceipts'])->name('narrations.receipts');
Route::get('vouchers/{type}', [VoucherController::class, 'index'])->name('vouchers.index');
Route::post('vouchers/store', [VoucherController::class, 'store'])->name('vouchers.store');

Route::get('/recepit-vochers/{id?}', [VoucherController::class, 'recepit_vochers'])->name('recepit-vochers');
Route::post('/recepit/vochers/store', [VoucherController::class, 'store_rec_vochers'])->name('recepit.vochers.store');
Route::post('/recepit/vochers/ajax-save', [VoucherController::class, 'ajax_save_receipt'])->name('recepit.vochers.ajax-save');
Route::post('/recepit/vochers/post/{id}', [VoucherController::class, 'post_receipt'])->name('recepit.vochers.post');
Route::post('/recepit/vochers/unpost/{id}', [VoucherController::class, 'unpost_receipt'])->name('recepit.vochers.unpost');
Route::delete('/recepit/vochers/cancel/{id}', [VoucherController::class, 'cancel_receipt'])->name('recepit.vochers.cancel');

Route::get('/all-recepit-vochers', [VoucherController::class, 'all_recepit_vochers'])->name('all-recepit-vochers');
Route::get('/receipt-voucher/print/{id}', [VoucherController::class, 'print'])->name('receiptVoucher.print');


Route::get('/Payment-vochers/{id?}', [VoucherController::class, 'Payment_vochers'])->name('Payment-vochers');
Route::post('/Payment/vochers/store', [VoucherController::class, 'store_Pay_vochers'])->name('Payment.vochers.store');
Route::post('/Payment/vochers/ajax-save', [VoucherController::class, 'ajax_save_payment'])->name('Payment.vochers.ajax-save');
Route::post('/Payment/vochers/post/{id}', [VoucherController::class, 'post_payment'])->name('Payment.vochers.post');
Route::post('/Payment/vochers/unpost/{id}', [VoucherController::class, 'unpost_payment'])->name('Payment.vochers.unpost');
Route::delete('/Payment/vochers/cancel/{id}', [VoucherController::class, 'cancel_payment'])->name('Payment.vochers.cancel');

Route::get('/all-Payment-vochers', [VoucherController::class, 'all_Payment_vochers'])->name('all-Payment-vochers');
Route::get('/Payment-voucher/print/{id}', [VoucherController::class, 'Paymentprint'])->name('PaymentVoucher.print');

Route::get('/expense-vochers/{id?}', [VoucherController::class, 'expense_vochers'])->name('expense-vochers');
Route::post('/expense/vochers/store', [VoucherController::class, 'store_expense_vochers'])->name('expense.vochers.store');
Route::post('/expense/vochers/ajax-save', [VoucherController::class, 'ajax_save_expense'])->name('Expense.vochers.ajax-save');
Route::post('/expense/vochers/post/{id}', [VoucherController::class, 'post_expense'])->name('Expense.vochers.post');
Route::post('/expense/vochers/unpost/{id}', [VoucherController::class, 'unpost_expense'])->name('Expense.vochers.unpost');
Route::delete('/expense/vochers/cancel/{id}', [VoucherController::class, 'cancel_expense'])->name('Expense.vochers.cancel');

Route::get('/all-expense-vochers', [VoucherController::class, 'all_expense_vochers'])->name('all-expense-vochers');
Route::get('/expense-voucher/print/{id}', [VoucherController::class, 'expenseprint'])->name('ExpenseVoucher.print');

// Income Voucher Routes
Route::get('/income-vochers/{id?}', [VoucherController::class, 'income_vochers'])->name('income-vochers');
Route::post('/income/vochers/ajax-save', [VoucherController::class, 'ajax_save_income'])->name('income.vochers.ajax-save');
Route::post('/income/vochers/post/{id}', [VoucherController::class, 'post_income'])->name('income.vochers.post');
Route::post('/income/vochers/unpost/{id}', [VoucherController::class, 'unpost_income'])->name('income.vochers.unpost');
Route::delete('/income/vochers/cancel/{id}', [VoucherController::class, 'cancel_income'])->name('income.vochers.cancel');
Route::get('/all-income-vochers', [VoucherController::class, 'all_income_vochers'])->name('all-income-vochers');
Route::get('/income-voucher/print/{id}', [VoucherController::class, 'incomeprint'])->name('incomeVoucher.print');

// Adjustment Voucher Routes
Route::get('/adjustment-vochers/{id?}', [VoucherController::class, 'adjustment_vochers'])->name('adjustment-vochers');
Route::post('/adjustment/vochers/ajax-save', [VoucherController::class, 'ajax_save_adjustment'])->name('adjustment.vochers.ajax-save');
Route::post('/adjustment/vochers/post/{id}', [VoucherController::class, 'post_adjustment'])->name('adjustment.vochers.post');
Route::delete('/adjustment/vochers/cancel/{id}', [VoucherController::class, 'cancel_adjustment'])->name('adjustment.vochers.cancel');
Route::get('/all-adjustment-vochers', [VoucherController::class, 'all_adjustment_vochers'])->name('all-adjustment-vochers');
Route::get('/adjustment-voucher/print/{id}', [VoucherController::class, 'adjustmentprint'])->name('adjustmentVoucher.print');

// Journal Voucher Routes
Route::get('/journal-vochers/{id?}', [VoucherController::class, 'journal_vochers'])->name('journal-vochers');
Route::post('/journal-vochers/ajax-save', [VoucherController::class, 'ajax_save_journal'])->name('journal.vochers.ajax-save');
Route::post('/journal-vochers/post/{id?}', [VoucherController::class, 'post_journal'])->name('journal.vochers.post');
Route::delete('/journal-vochers/{id}', [VoucherController::class, 'cancel_journal'])->name('journal.vochers.cancel');
Route::get('/all-journal-vochers', [VoucherController::class, 'all_journal_vochers'])->name('all-journal-vochers');
Route::get('/journalVoucher-print/{id}', [VoucherController::class, 'journalprint'])->name('journalVoucher.print');

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
    Route::get('/purcahse-account-allocation', [AccountsHeadController::class, 'purcahse_account_allocation'])->name('purcahse-account-allocation');
    // Route::get('/narration', [AccountsHeadController::class, 'narration'])->name('narration');
    // Route::get('/expense-heads', [AccountsHeadController::class, 'index'])->name('expense.heads.index');
    // Route::post('/expense-heads/store', [AccountsHeadController::class, 'store'])->name('expense.heads.store');
    // Route::get('/expense-heads/delete/{id}', [AccountsHeadController::class, 'destroy'])->name('expense.heads.delete');

    // narration
    // Route::get('/narration', [AccountsHeadController::class, 'narration'])->name('narration');
    Route::get('/reciepts_vouchers', [AccountsHeadController::class, 'reciepts_vouchers'])->name('reciepts_vouchers');

    route::get('/category', [CategoryController::class, 'index'])->name('Category.home');
    Route::get('/category/delete/{id}', [CategoryController::class, 'delete'])->name('delete.category');
    route::post('/category/stote', [CategoryController::class, 'store'])->name('store.category');

    route::get('/Brand', [BrandController::class, 'index'])->name('Brand.home');
    Route::get('/Brand/delete/{id}', [BrandController::class, 'delete'])->name('delete.Brand');
    route::post('/Brand/stote', [BrandController::class, 'store'])->name('store.Brand');

    route::get('/Unit', [UnitController::class, 'index'])->name('Unit.home');
    Route::get('/Unit/delete/{id}', [UnitController::class, 'delete'])->name('delete.Unit');
    route::post('/Unit/stote', [UnitController::class, 'store'])->name('store.Unit');

    route::get('/subcategory', [SubcategoryController::class, 'index'])->name('subcategory.home');
    Route::get('/subcategory/delete/{id}', [SubcategoryController::class, 'delete'])->name('delete.subcategory');
    route::post('/subcategory/stote', [SubcategoryController::class, 'store'])->name('store.subcategory');

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

    Route::get('admin/products/{product}/prices', [App\Http\Controllers\ProductController::class, 'prices'])
        ->name('products.prices');


    // simple & resourceful:
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');


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
    Route::get('/customer/{id}/closing-balance', [CustomerController::class, 'getClosingBalance'])->name('customer.getClosingBalance');

    // New
    Route::get('/customers/inactive', [CustomerController::class, 'inactiveCustomers'])->name('customers.inactive');
    Route::get('/customers/inactive/{id}', [CustomerController::class, 'markInactive'])->name('customers.markInactive');
    Route::get('customers/toggle-status/{id}', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus');
    Route::get('/outstanding-losses', [CustomerController::class, 'outstandingLosses'])->name('outstanding.losses');
    // Vendor Routes
    Route::get('/vendor', [VendorController::class, 'index']);
    Route::get('/vendor/create', [VendorController::class, 'create'])->name('vendor.create');
    Route::post('/vendor/store', [VendorController::class, 'store']);
    Route::get('/vendor/edit/{id}', [VendorController::class, 'edit'])->name('vendor.edit');
    Route::post('/vendor/update/{id}', [VendorController::class, 'update'])->name('vendor.update');
    Route::get('/vendor/delete/{id}', [VendorController::class, 'delete']);
    Route::get('vendor/ledger', [VendorController::class, 'allLedgers'])->name('vendor.ledger');
    Route::get('vendor/payments', [VendorController::class, 'payments_index'])->name('vendor.payments.index');
    Route::post('vendor/payments/store', [VendorController::class, 'payments_store'])->name('vendor.payments.store');
    // routes/web.php
    Route::get('/vendor/{id}/closing-balance', [VendorController::class, 'getClosingBalance'])->name('vendor.closing.balance');

    // Warehouse Routes
    Route::get('/warehouse', [WarehouseController::class, 'index']);
    Route::post('/warehouse/store', [WarehouseController::class, 'store']);
    Route::get('/warehouse/delete/{id}', [WarehouseController::class, 'delete']);
    Route::resource('warehouse_stocks', WarehouseStockController::class);
    Route::post('warehouse_stocks/{id}/post', [WarehouseStockController::class, 'post'])->name('warehouse_stocks.post');
    Route::get('warehouse_stocks/{id}/print', [WarehouseStockController::class, 'print'])->name('warehouse_stocks.print');
    Route::get('/warehouse-stock-quantity', [StockTransferController::class, 'getStockQuantity'])->name('warehouse.stock.quantity');

    Route::resource('stock_transfers', StockTransferController::class)->except(['destroy']);
    Route::post('stock_transfers/{id}/accept', [StockTransferController::class, 'accept'])->name('stock_transfers.accept');
    Route::post('stock_transfers/{id}/reject', [StockTransferController::class, 'reject'])->name('stock_transfers.reject');
    Route::post('stock_transfers/{id}/post', [StockTransferController::class, 'post'])->name('stock_transfers.post');
    Route::get('stock_transfers/{id}/print', [StockTransferController::class, 'printView'])->name('stock_transfers.print');
    // Stock Wastage
    Route::resource('stock-wastage', StockWastageController::class);
    Route::post('stock-wastage/{id}/post', [StockWastageController::class, 'post'])->name('stock-wastage.post');
    Route::get('stock-wastage/{id}/print', [StockWastageController::class, 'print'])->name('stock-wastage.print');

    // Ajax
    Route::get('warehouse-stock-quantity', [StockTransferController::class, 'warehouseStockQuantity'])->name('warehouse.stock.quantity');

    // Pending list (optional)
    Route::get('stock_transfers-pending', [StockTransferController::class, 'pending'])->name('stock_transfers.pending');

    // Branches
    Route::resource('branch', BranchController::class)
        ->names('branch')
        ->only(['index', 'store']);
    Route::get('/branch/delete/{id}', [BranchController::class, 'delete'])->name('branch.delete');

    // Roles
    Route::resource('roles', RoleController::class)
        ->names('roles')
        ->only(['index', 'store']);
    Route::get('/roles/delete/{id}', [RoleController::class, 'delete'])->name('roles.delete');
    Route::post('/admin/roles/update-permission', [RoleController::class, 'updatePermissions'])->name('roles.update.permission');

    // Permissions
    Route::resource('permissions', PermissionController::class)
        ->names('permissions')
        ->only(['index', 'store']);
    Route::get('/permissions/delete/{id}', [PermissionController::class, 'delete'])->name('permission.delete');

    // Users
    Route::resource('users', UserController::class)
        ->names('users')
        ->only(['index', 'store']);
    Route::get('/users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');
    Route::post('/admin/users/update-roles', [UserController::class, 'updateRoles'])->name('users.update.roles');
    // Route::put('/users/{id}/roles', [UserController::class, 'updateRoles'])->name('users.update.roles');

    // User Groups
    Route::resource('user-group', UserGroupController::class);

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

    route::get('/Purchase', [PurchaseController::class, 'index'])->name('Purchase.home');
    route::get('/add/Purchase', [PurchaseController::class, 'add_purchase'])->name('add_purchase');
    route::post('/Purchase/stote', [PurchaseController::class, 'store'])->name('store.Purchase');
    Route::get('/purchase/{id}/view', [PurchaseController::class, 'show'])->name('purchase.view');
    Route::get('/purchase/{id}/edit', [PurchaseController::class, 'edit'])->name('purchase.edit');
    Route::put('/purchase/{id}', [PurchaseController::class, 'update'])->name('purchase.update');
    Route::delete('/purchase/{id}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');
    Route::post('/purchase/{id}/post', [PurchaseController::class, 'post'])->name('purchase.post');
    Route::get('/purchase/{id}/invoice', [PurchaseController::class, 'Invoice'])->name('purchase.invoice');
    Route::get('/get-accounts-by-head/{headId}', [PurchaseController::class, 'getAccountsByHead']);
    Route::get('/getPartyList', [PurchaseController::class, 'getPartyList'])->name('party.list');

    // Purchase Returns
    Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase.return.home');
    Route::get('/purchase-returns/add', [PurchaseReturnController::class, 'create'])->name('purchase.return.add');
    Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase.return.store');
    Route::get('/purchase-returns/get-purchase/{invoice_no}', [PurchaseReturnController::class, 'getPurchaseDetails']);
    Route::post('/purchase-returns/post/{id}', [PurchaseReturnController::class, 'post'])->name('purchase.return.post');
    Route::get('/purchase-returns/print/{id}', [PurchaseReturnController::class, 'print'])->name('purchase.return.print');
    /* Added routes for edit and update */
    Route::get('/purchase-returns/{id}/edit', [PurchaseReturnController::class, 'edit'])->name('purchase.return.edit');
    Route::post('/purchase-returns/{id}/update', [PurchaseReturnController::class, 'update'])->name('purchase.return.update');
    Route::delete('/purchase-returns/{id}/destroy', [PurchaseReturnController::class, 'destroy'])->name('purchase.return.destroy');

    // Sale Returns
    Route::get('/sale-returns', [SaleReturnController::class, 'index'])->name('sale.return.home');
    Route::get('/sale-returns/add', [SaleReturnController::class, 'create'])->name('sale.return.add');
    Route::post('/sale-returns', [SaleReturnController::class, 'store'])->name('sale.return.store');
    Route::get('/sale-returns/get-sale/{invoice_no}', [SaleReturnController::class, 'getSaleDetails']);
    Route::post('/sale-returns/post/{id}', [SaleReturnController::class, 'post'])->name('sale.return.post');
    Route::get('/sale-returns/print/{id}', [SaleReturnController::class, 'print'])->name('sale.return.print');
    Route::get('/sale-returns/{id}/edit', [SaleReturnController::class, 'edit'])->name('sale.return.edit');
    Route::post('/sale-returns/{id}/update', [SaleReturnController::class, 'update'])->name('sale.return.update');
    Route::delete('/sale-returns/{id}/destroy', [SaleReturnController::class, 'destroy'])->name('sale.return.destroy');

    // Route::get('/fetch-product', [PurchaseController::class, 'fetchProduct'])->name('item.search');

    // Route::post('/fetch-item-details', [PurchaseController::class, 'fetchItemDetails']);
    Route::get('/search-products', [ProductController::class, 'searchProducts'])->name('search-products');
    // Route::get('/Purchase/create', function () {
    //     return view('admin_panel.purchase.add_purchase');
    // });
    // Route::get('/get-items-by-category/{categoryId}', [PurchaseController::class, 'getItemsByCategory'])->name('get-items-by-category');
    // Route::get('/get-product-details/{productName}', [ProductController::class, 'getProductDetails'])->name('get-product-details');

    //     route::get('/sale/add',[SaleController::class,'add_sale'])->name('sale.add');
    //     route::get('/sale',[SaleController::class,'index'])->name('sale.index');
    //     route::get('/Booking',[SaleController::class,'Booking'])->name('Booking.index');
    //     route::get('/Booking/edit/{id}',[SaleController::class,'editBooking'])->name('editBooking.index');

    // // sale return
    //     route::get('/sale/return',[SaleReturnController::class,'index_salereturn'])->name('sale.retrun');
    //     route::get('/sale/return/create/{id}',[SaleReturnController::class,'index_salereturn_Add'])->name('sale.retrun.add');

    //     // routes/web.php
    //     Route::get('/get-products-by-warehouse/{warehouseId}', [App\Http\Controllers\SaleController::class, 'getProductsByWarehouse']);
    //     // Route::get('/get-stock/{warehouseId}/{productId}', [App\Http\Controllers\SaleController::class, 'getStock']);
    //     Route::get('/get-stock/{productId}', [App\Http\Controllers\SaleController::class, 'getStock']);
    //     // web.php
    //     Route::get('/get-customer/{id}', [App\Http\Controllers\SaleController::class, 'getCustomerData']);
    //     route::post('/sale/data',[SaleController::class,'store'])->name('sale.store');
    //     Route::get('/sale/edit/{id}', [SaleController::class, 'edit'])->name('sale.edit');
    //     Route::post('/sale/update/{id}', [SaleController::class, 'update'])->name('sale.update');

    // routes/web.php

    // Sales list & screens
    Route::get('/sale', [SaleController::class, 'index'])->name('sale.index');
    Route::get('/sale/add', [SaleController::class, 'add_sale'])->name('sale.add');
    Route::get('/sale/edit/{id}', [SaleController::class, 'edit'])->name('sale.edit');
    Route::post('/sale/update/{id}', [SaleController::class, 'update'])->name('sale.update');

    Route::get('/create-stock-hold', [StockHoldController::class, 'create'])->name('create-stock-hold');
    Route::get('products/search', [SaleController::class, 'search'])->name('products.search');
    Route::get('stock-holds/products/search', [SaleController::class, 'search'])->name('stock-holds.products.search');
    Route::get('stock-holds/party/list', [StockHoldController::class, 'partyList'])->name('stock-holds.party.list');
    Route::get('stock-holds/party/{id}/invoices', [StockHoldController::class, 'partyInvoices'])->name('stock-holds.party.invoices');
    Route::get('stock-holds/invoice/{id}/items', [StockHoldController::class, 'invoiceItems'])->name('stock-holds.invoice.items');


    Route::post('stock-holds/store', [StockHoldController::class, 'store'])->name('stock-holds.store');
    Route::get('/stock-holds/edit/{id}', [StockHoldController::class, 'edit'])->name('stock-holds.edit');
    Route::post('/stock-holds/update/{id}', [StockHoldController::class, 'update'])->name('stock-holds.update');
    Route::post('/stock-holds/post/{id}', [StockHoldController::class, 'post'])->name('stock-holds.post');
    Route::post('stock-holds/claim/invoice/{invoice}', [StockHoldController::class, 'claimByInvoice'])->name('stock-holds.claim.invoice');
    Route::post('stock-holds/claim/item', [StockHoldController::class, 'claimItem'])->name('stock-holds.claim.item');

    Route::get('/stock-hold-list', [StockHoldController::class, 'stockholdlist'])->name('stock-hold-list');
    Route::get('/stock-holds/{id}/release', [StockHoldController::class, 'createFromHold'])->name('stock-holds.release');
    Route::post('/stock-holds/{id}/release', [StockHoldController::class, 'storeFromHold'])->name('stock-holds.release.store');
    Route::get('/stock-holds/print/{id}', [StockHoldController::class, 'print'])->name('stock-holds.print');
    // Legacy form submit (optional)
    Route::post('/sale/data', [SaleController::class, 'store'])->name('sale.store');

    Route::post('/stock-holds/release/bulk-store', [StockHoldController::class, 'storeBulkRelease'])->name('stock-holds.release.bulk_store');
    Route::get('/add-stock-release', [StockHoldController::class, 'createRelease'])->name('stock-holds.release.add');
    Route::get('/stock-release/edit/{id}', [StockHoldController::class, 'editRelease'])->name('stock-holds.release.edit');
    Route::post('/stock-release/update/{id}', [StockHoldController::class, 'updateRelease'])->name('stock-holds.release.update');
    Route::post('/stock-release/post/{id}', [StockHoldController::class, 'postRelease'])->name('stock-holds.release.post');
    Route::get('/stock-release/print/{id}', [StockHoldController::class, 'printRelease'])->name('stock-holds.release.print');
    Route::get('/stock-holds/voucher/{id}/details', [StockHoldController::class, 'voucherDetails'])->name('stock-holds.voucher.details');
    Route::get('stock-holds/list/json', [StockHoldController::class, 'holdVoucherList'])->name('stock-holds.list.json');
    Route::get('/stock-relase-list', [StockHoldController::class, 'stockrelaselist'])->name('stock-relase-list');

    // Claim Item Receipt Routes
    Route::get('/claim-item-receipt', [ClaimItemReceiptController::class, 'index'])->name('claim-item-receipt.index');
    Route::get('/claim-item-receipt/add', [ClaimItemReceiptController::class, 'create'])->name('claim-item-receipt.create');
    Route::get('/claim-item-receipt/edit/{id}', [ClaimItemReceiptController::class, 'edit'])->name('claim-item-receipt.edit');
    Route::get('/claim-item-receipt/fetch-btr', [ClaimItemReceiptController::class, 'fetchByBTR'])->name('claim-item-receipt.fetch-btr');
    Route::post('/claim-item-receipt/ajax-save', [ClaimItemReceiptController::class, 'ajaxSave'])->name('claim-item-receipt.ajax-save');
    Route::post('/claim-item-receipt/post/{id}', [ClaimItemReceiptController::class, 'post'])->name('claim-item-receipt.post');
    Route::get('/claim-item-receipt/print/{id}', [ClaimItemReceiptController::class, 'print'])->name('claim-item-receipt.print');

    // Claim Credit Note Routes
    Route::get('/claim-credit-note', [ClaimCreditNoteController::class, 'index'])->name('claim-credit-note.index');
    Route::get('/claim-credit-note/add', [ClaimCreditNoteController::class, 'create'])->name('claim-credit-note.create');
    Route::get('/claim-credit-note/edit/{id}', [ClaimCreditNoteController::class, 'edit'])->name('claim-credit-note.edit');
    Route::get('/claim-credit-note/fetch-btr', [ClaimCreditNoteController::class, 'fetchByBTR'])->name('claim-credit-note.fetch-btr');
    Route::post('/claim-credit-note/ajax-save', [ClaimCreditNoteController::class, 'ajaxSave'])->name('claim-credit-note.ajax-save');
    Route::post('/claim-credit-note/post/{id}', [ClaimCreditNoteController::class, 'post'])->name('claim-credit-note.post');
    Route::get('/claim-credit-note/print/{id}', [ClaimCreditNoteController::class, 'print'])->name('claim-credit-note.print');


    // AJAX (no refresh)
    Route::post('/sale/ajax/save', [SaleController::class, 'ajaxSave'])->name('sale.ajax.save');
    Route::post('/sale/ajax/post', [SaleController::class, 'ajaxPost'])->name('sale.ajax.post');
    Route::post('/sale/{id}/post', [SaleController::class, 'post'])->name('sale.post');
    Route::delete('/sale/{id}', [SaleController::class, 'destroy'])->name('sale.destroy');

    // Prints
    Route::get('/sale/invoice/{sale}', [SaleController::class, 'invoice'])->name('sale.invoice');
    Route::get('/sale/print2/{sale}', [SaleController::class, 'print2'])->name('sale.print2');
    Route::get('/sale/dc/{sale}', [SaleController::class, 'dc'])->name('sale.dc');

    // Booking (optional legacy UIs)
    Route::get('/Booking', [SaleController::class, 'Booking'])->name('Booking.index');
    Route::get('/Booking/edit/{id}', [SaleController::class, 'editBooking'])->name('editBooking.index');
    Route::get('/booking/print/{id}', [SaleController::class, 'bookingPrint'])->name('booking.print');
    Route::get('/booking/print2/{id}', [SaleController::class, 'bookingPrint2'])->name('booking.print2');
    Route::get('/booking/dc/{id}', [SaleController::class, 'bookingDc'])->name('booking.dc');

    // Support APIs
    Route::get('/get-products-by-warehouse/{wid}', [SaleController::class, 'getProductsByWarehouse']);
    Route::get('/get-all-sale-products', [SaleController::class, 'getAllSaleProducts']); // NEW: All products for sale
    Route::get('/get-stock/{pid}', [SaleController::class, 'getStock']);
    Route::get('/customers/filter', [SaleController::class, 'filterCustomers'])->name('customers.filter');
    Route::get('/get-customer/{id}', [SaleController::class, 'getCustomerData'])->name('customers.show');
    Route::get('/get-vendor/{id}', [SaleController::class, 'getVendorData'])->name('vendor.show');
    Route::get('/accounts/list', [SaleController::class, 'accountsList'])->name('accounts.list');
    Route::delete('/customers/{customer}', [SaleController::class, 'deleteCustomer'])->name('customers.delete');
    Route::get('/accounts/list', [SaleController::class, 'getAccountList'])->name('accounts.list');



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
    // Reports Routes
    Route::get('/reports/dashboard', [HomeController::class, 'dashboardReport'])->name('reports.dashboard');
    Route::get('/reports/sales', [\App\Http\Controllers\SalesReportController::class, 'index'])->name('reports.sales.index');
    Route::post('/reports/sales/preview', [\App\Http\Controllers\SalesReportController::class, 'preview'])->name('reports.sales.preview');
    Route::get('/reports/purchase', [\App\Http\Controllers\PurchaseReportController::class, 'index'])->name('reports.purchase.index');
    Route::post('/reports/purchase/preview', [\App\Http\Controllers\PurchaseReportController::class, 'preview'])->name('reports.purchase.preview');
    Route::get('/reports/claim', [\App\Http\Controllers\ClaimReportController::class, 'index'])->name('reports.claim.index');
    Route::post('/reports/claim/preview', [\App\Http\Controllers\ClaimReportController::class, 'preview'])->name('reports.claim.preview');


});
Route::get('sale/invoice/{id}', [SaleController::class, 'invoice'])->name('sale.invoice');
// SubCustomer Payments
Route::get('/sub_customers/payments', [SubCustomerController::class, 'payments'])->name('sub_customers.payments');
Route::post('/sub_customers/payments/store', [SubCustomerController::class, 'storePayment'])->name('sub_customers.payments.store');
Route::get('vouchers/{id}/receipt', [VoucherController::class, 'receipt'])->name('vouchers.receipt');

// Inward Gatepass Routes
Route::get('/InwardGatepass', [InwardgatepassController::class, 'index'])->name('InwardGatepass.home');
Route::get('/add/InwardGatepass', [InwardgatepassController::class, 'create'])->name('add_inwardgatepass');
Route::post('/InwardGatepass/store', [InwardgatepassController::class, 'store'])->name('store.InwardGatepass');
Route::post('/InwardGatepass/{id}/post', [InwardgatepassController::class, 'post'])->name('InwardGatepass.post');
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
Route::post('inward-gatepass/{id}/store-bill', [PurchaseController::class, 'store_inwrd_purchse'])->name('store.bill');

Route::prefix('coa')->group(function () {
    Route::get('/', [AccountsHeadController::class, 'index'])->name('coa.index');
    Route::post('/head', [AccountsHeadController::class, 'storeHead'])->name('coa.head.store');
    Route::post('/account', [AccountsHeadController::class, 'storeAccount'])->name('coa.account.store');
    Route::get('/next-account-code/{headId}', [AccountsHeadController::class, 'getNextAccountCode'])->name('coa.account.next_code');
});

require __DIR__ . '/auth.php';
