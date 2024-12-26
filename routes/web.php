<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;


// Route halaman utama
Route::get('/', function () {
    return view('welcome');
});

// Route untuk dashboard, memerlukan autentikasi dan verifikasi email
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Route untuk profil pengguna (autentikasi wajib)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/folders', [FileController::class, 'listFolders'])->name('folders.index');
    
    
});

// Rute untuk fitur file, dengan middleware autentikasi dan role
Route::middleware(['auth'])->group(function () {
    // Rute khusus untuk admin
    Route::middleware([RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/admin/files', [FileController::class, 'index'])->name('admin.files.index');
        Route::post('/admin/files', [FileController::class, 'store'])->name('admin.files.store');
        Route::get('/admin/files/upload', [FileController::class, 'uploadForm'])->name('admin.files.upload');
        Route::get('/admin/files/{file}/download', [FileController::class, 'download'])->name('admin.files.download');
        Route::delete('/admin/files/{file}', [FileController::class, 'destroy'])->name('admin.files.destroy');
        Route::get('/admin/files/{file}/contents', [FileController::class, 'showContents'])->name('admin.files.showContents');
        
        Route::post('/admin/folder', [FileController::class, 'createFolder'])->name('admin.folder.create');
        Route::post('/admin/folder/delete', [FileController::class, 'deleteFolder'])->name('admin.folder.delete');

       
    });

    // Rute khusus untuk user
    Route::middleware([RoleMiddleware::class . ':user'])->group(function () {
        Route::get('/files', [FileController::class, 'index'])->name('user.files.index'); // Daftar file untuk user
        Route::post('/files', [FileController::class, 'store'])->name('user.files.store');
        Route::get('/files/upload', [FileController::class, 'uploadForm'])->name('user.files.upload'); // Halaman upload file untuk user
        Route::get('/files/download/{file}', [FileController::class, 'download'])->name('user.files.download');
        Route::get('/files/{file}/contents', [FileController::class, 'showContents'])->name('user.files.showContents');
       
    });
});



// Rute autentikasi dari Laravel
require __DIR__.'/auth.php';
