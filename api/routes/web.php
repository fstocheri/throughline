<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Throughline API',
        'docs' => 'https://github.com/fstocheri/throughline',
    ]);
});
