<?php

use App\Http\Controllers\LoteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use app\models\Poliza;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// --------- MODIFICAR RUTAS DESDE AQUÍ ---------
Route::get('/', function () {
    // muestro el index del frontend
    readfile("../public/index.html");
    //return view('app');
});
