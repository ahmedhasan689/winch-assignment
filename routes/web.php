<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('dispatcher::dispatcher'));
Route::get('/dispatcher', fn () => view('dispatcher::dispatcher'));
