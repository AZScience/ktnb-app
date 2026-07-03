<?php

use App\Http\Controllers\Api\ExtensionApiController;
use App\Http\Controllers\Api\OnlineCheckinController;
use App\Http\Controllers\Api\ScheduleApiController;
use Illuminate\Support\Facades\Route;

$cors = fn () => response('', 204, [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, OPTIONS',
    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, x-api-key',
]);

foreach (['/v1/online-checkins', '/v1/schedules', '/v1/polls', '/v1/exams', '/v1/discussions', '/v1/ai/generate-test'] as $path) {
    Route::options($path, $cors);
    Route::options("{$path}/{any}", $cors)->where('any', '.*');
}

// Public read / vote — không cần API key
Route::get('/v1/polls/{poll}', [ExtensionApiController::class, 'showPoll']);
Route::match(['patch', 'put'], '/v1/polls/{poll}', [ExtensionApiController::class, 'votePoll']);
Route::get('/v1/exams/{exam}', [ExtensionApiController::class, 'showExam']);
Route::get('/v1/discussions', [ExtensionApiController::class, 'listDiscussions']);
Route::get('/v1/online-checkins/{onlineCheckin}', [OnlineCheckinController::class, 'show']);
Route::get('/v1/schedules/{schedule}', [ScheduleApiController::class, 'show']);

// Extension ghi dữ liệu — cần API key
Route::middleware('api.key')->group(function () {
    Route::get('/v1/schedules', [ScheduleApiController::class, 'index']);
    Route::match(['patch', 'put'], '/v1/schedules/{schedule}', [ScheduleApiController::class, 'update']);
    Route::post('/v1/online-checkins', [OnlineCheckinController::class, 'store']);
    Route::match(['patch', 'put'], '/v1/online-checkins/{onlineCheckin}', [OnlineCheckinController::class, 'update']);
    Route::post('/v1/polls', [ExtensionApiController::class, 'createPoll']);
    Route::post('/v1/exams', [ExtensionApiController::class, 'createExam']);
    Route::post('/v1/discussions', [ExtensionApiController::class, 'createDiscussion']);
    Route::post('/v1/ai/generate-test', [ExtensionApiController::class, 'generateTest']);
});
