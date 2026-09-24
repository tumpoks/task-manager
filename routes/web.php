<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::inertia('/tasks', 'Tasks')->name('tasks');