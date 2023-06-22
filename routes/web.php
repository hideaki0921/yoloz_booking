<?php

use Illuminate\Support\Facades\Route;

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

Route::any('/', [App\Http\Controllers\LoginController::class, 'lineLogin'])->name('login');

Route::any('/lineCallback', [App\Http\Controllers\LoginController::class, 'lineCallback'])->name('lineCallback');

Route::any('/calendar', [App\Http\Controllers\CalendarController::class, 'index'])->name('calendar.index');

Route::any('/calendar/inputUserInfo', [App\Http\Controllers\CalendarController::class, 'inputUserInfo'])->name('calendar.inputUserInfo');

Route::any('/calendar/registerBooking', [App\Http\Controllers\CalendarController::class, 'registerBooking'])->name('calendar.registerBooking');

Route::any('/calendar/registerGoogleCalendar', [App\Http\Controllers\CalendarController::class, 'registerGoogleCalendar'])->name('calendar.registerGoogleCalendar');

Route::any('/calendar/registerOutlookCalendar', [App\Http\Controllers\CalendarController::class, 'registerOutlookCalendar'])->name('calendar.registerOutlookCalendar');
