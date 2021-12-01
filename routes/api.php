<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Product\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'store'])->name('auth.store');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware(['auth:sanctum', 'isAPIAdmin'])->group(function () {
    Route::get('/checkAuthenticated', function () {
        return response()->json(['message' => 'You are in', 'status' => 200], 200);
    });

    //routes category
    Route::post('view-category', [CategoryController::class, 'index'])->name('category.index');
    Route::post('store-category', [CategoryController::class, 'store'])->name('category.store');
    Route::post('edit-category/{id}', [CategoryController::class, 'edit'])->name('category.edit');
    Route::put('update-category/{id}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('delete-category/{id}', [CategoryController::class, 'destroy'])->name('category.destroy');
    Route::get('all-category', [CategoryController::class, 'allCategories'])->name('category.allCategories');

    //routes product
    Route::post('view-product', [ProductController::class, 'index'])->name('product.index');
    Route::post('store-product', [ProductController::class, 'store'])->name('product.store');
    Route::post('edit-product/{id}', [ProductController::class, 'edit'])->name('product.edit');
    Route::put('update-product/{id}', [ProductController::class, 'update'])->name('product.update');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});
