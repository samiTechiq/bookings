<?php

use App\Http\Controllers\DailyReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->group(function () {
    Route::get('/admin/reports/daily', DailyReportController::class)->name('reports.daily');
});
