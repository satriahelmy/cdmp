<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamAnswerController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.device')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{attempt}', [ExamController::class, 'show'])->whereNumber('attempt')->name('exams.show');
    Route::put('/exams/{attempt}/questions/{position}', [ExamAnswerController::class, 'update'])->whereNumber(['attempt', 'position'])->name('exams.answers.update');
    Route::post('/exams/{attempt}/submit', [ExamController::class, 'submit'])->whereNumber('attempt')->name('exams.submit');
    Route::get('/exams/{attempt}/result', [ExamResultController::class, 'show'])->whereNumber('attempt')->name('exams.result');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
});
