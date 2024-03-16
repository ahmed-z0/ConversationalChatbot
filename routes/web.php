<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WhisperController;

use App\Http\Controllers\AskController;

use App\Http\Controllers\AssistantController;





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

// Route::get('/', function () {
//     return view('welcome');
// });


// For opening the view pages
Route::get('/', [App\Http\Controllers\HomeController::class, 'home']);

// Chat bot view

// Route::get('/chatbot', [App\Http\Controllers\HomeController::class, 'chatbot']);
Route::post('/ct', [App\Http\Controllers\HomeController::class, 'ct']);

// // for chatgpt response
// Route::get('/chatd', [App\Http\Controllers\HomeController::class, 'chatd']); // For GET requests with query parameters
// // OR
// Route::post('/chatd', [App\Http\Controllers\HomeController::class, 'chatd']); // For POST requests


//for converting speech to text:

    Route::post('/transcribe', [WhisperController::class, 'transcribe']);



    // ______________TEST_______________
    Route::get('/w', function () {
        return view('welcome');
    });
    Route::get("/ask", [AskController::class, 'st']);
    // for tts
    Route::get('/text-to-speech', [HomeController::class, 'chatd']); // For GET requests with query parameters



    // Assistant Api

    Route::get('/assistants', [AssistantController::class, 'assistants']); 


    // create assistant
    Route::get('/create-assistant', [AssistantController::class, 'createassistant'])->name('create.assistant');
 // edit assistant
 Route::get('/edit-assistant', [AssistantController::class, 'createassistant'])->name('assistant.edit');

 // edit assistant
 Route::get('/delete-assistant', [AssistantController::class, 'delete'])->name('assistant.delete');

    // Handle the submission of the form to create a new assistant
// Assuming you already have this route defined for the POST request
Route::post('/assistant/create', [AssistantController::class, 'store'])->name('assistant.store');

// use assistant
// Route::get('/chatbot', [HomeController::class, 'chatbot'])->name('assistant.use');

Route::get('/chatbot', [HomeController::class, 'chatbot'])->name('assistant.use');