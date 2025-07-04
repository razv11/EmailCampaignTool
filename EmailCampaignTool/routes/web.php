<?php

use App\Http\Controllers\EmailTrackingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/track/open/{id}', [EmailTrackingController::class, 'trackOpen'])->name('email.track.open');

