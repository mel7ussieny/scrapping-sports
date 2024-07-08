<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapperController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::get('/ebay-scrapper', [ScrapperController::class, 'scrapEbay']);

Route::get('/sport-scrapper', [ScrapperController::class, 'scrapSportCard']);

Route::get('/improve-scrapping', [ScrapperController::class, 'improveScrapping']);

Route::get('/scraping', [ScrapperController::class, 'showScrapingLog']);
