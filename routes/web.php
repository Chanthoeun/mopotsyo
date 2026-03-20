<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/admin/backups/download', function (\Illuminate\Http\Request $request) {
    abort_if(!auth()->check() || !auth()->user()->hasRole('super_admin'), 403);
    $path = $request->query('path');
    $disk = $request->query('disk', 'local');
    return \Illuminate\Support\Facades\Storage::disk($disk)->download($path);
})->name('admin.backups.download');
