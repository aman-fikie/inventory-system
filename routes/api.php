<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\StockLocationController;
use \App\Http\Controllers\Api\StockBalanceController;
use \App\Http\Controllers\Api\StoreController;

// Category routes
Route::post('/categories', [CategoryController::class, 'store']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);
Route::patch('/categories/{id}', [CategoryController::class, 'update']); // same as put
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/me', [AuthController::class, 'me']);
Route::post('/logout', [AuthController::class, 'logout']);

// Public API routes without middleware
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::patch('/products/{id}', [ProductController::class, 'update']); // same as put
Route::delete('/products/{id}', [ProductController::class, 'destroy']);


// Catalog API Routes
Route::apiResource('categories', CategoryController::class);
Route::apiResource('brands', BrandController::class);
Route::apiResource('units', UnitController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('product-variants', ProductVariantController::class);

// Inventory API Routes
Route::apiResource('suppliers', SupplierController::class);
Route::apiResource('purchase-orders', PurchaseOrderController::class);
Route::apiResource('stock-locations', StockLocationController::class);
Route::apiResource('stock-balances', StockBalanceController::class);
Route::apiResource('stores', StoreController::class);
