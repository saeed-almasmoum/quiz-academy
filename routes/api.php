<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FilesCategoryController;
use App\Http\Controllers\FilesOfficeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\OnlyStudentCanAccess;
use App\Http\Middleware\OnlyTeacherCanAccess;
use App\Http\Middleware\OnlyUserAndStudentCanAccess;
use App\Http\Middleware\OnlyUserCanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Route::middleware([AuthController::class])->group(function () {
    Route::get('user', [AuthController::class, 'getUser']);
    Route::post('logout', [AuthController::class, 'logout']);
    // });
});


Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyTeacherCanAccess::class],
        'prefix' => 'teachers'
    ],
    function ($router) {
        Route::post('/dashboard', [TeacherController::class, 'dashboard']);
    }
);

Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyUserCanAccess::class],
        'prefix' => 'teachers'
    ],
    function ($router) {
        Route::post('/register', [TeacherController::class, 'register']);
        Route::post('/index', [TeacherController::class, 'index']);
        Route::post('/update-password/{id}', [TeacherController::class, 'updatePassword']);
        Route::post('/update/{id}', [TeacherController::class, 'update']);
        Route::post('/updateIsActive/{id}', [TeacherController::class, 'updateIsActive']);
        Route::post('/delete/{id}', [TeacherController::class, 'destroy']);
    }
);
Route::group(
    [
        'middleware' => [JwtMiddleware::class],
        'prefix' => 'teachers'
    ],
    function ($router) {
        Route::post('/show/{id}', [TeacherController::class, 'show']);
    }
);

Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyTeacherCanAccess::class],
        'prefix' => 'students'
    ],
    function ($router) {
        Route::post('/register', [StudentController::class, 'register']);
        Route::post('/index', [StudentController::class, 'index']);
        Route::post('/show/{id}', [StudentController::class, 'show']);
        Route::post('/update-password/{id}', [StudentController::class, 'updatePassword']);
        Route::post('/update/{id}', [StudentController::class, 'update']);
        Route::post('/updateIsActive/{id}', [StudentController::class, 'updateIsActive']);
        Route::post('/delete/{id}', [StudentController::class, 'destroy']);
        Route::post('/assignStudentToTeacher', [StudentController::class, 'assignStudentToTeacher']);
    }
);

Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyStudentCanAccess::class],
        'prefix' => 'students'
    ],
    function ($router) {
        Route::post('/dashboard', [StudentController::class, 'dashboard']);
        Route::post('/Subscriptions', [StudentController::class, 'Subscriptions']);

    }
);

Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyTeacherCanAccess::class],
        'prefix' => 'exams'
    ],
    function ($router) {
        Route::post('/index', [ExamController::class, 'index']);
        Route::post('/store', [ExamController::class, 'store']);
        Route::post('/show/{id}', [ExamController::class, 'show']);
        Route::post('/delete/{id}', [ExamController::class, 'destroy']);
        Route::post('/update/{id}', [ExamController::class, 'update']);
     
    }
);
Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyStudentCanAccess::class],
        'prefix' => 'exams'
    ],
    function ($router) {

        Route::post('/startExam/{id}', [ExamController::class, 'startExam']);
        Route::post('/submitExam/{id}', [ExamController::class, 'submitExam']);
        Route::post('/reviewExam/{id}', [ExamController::class, 'reviewExam']);
        Route::post('/getStudentAnswers', [ExamController::class, 'getStudentAnswers']);
        Route::post('/getStudentAnswerById/{id}', [ExamController::class, 'getStudentAnswerById']);
        Route::post('/ActiveExams', [ExamController::class, 'ActiveExams']);
        
    }
);


Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyTeacherCanAccess::class],
        'prefix' => 'filescategory'
    ],
    function ($router) {
        Route::post('/index', [FilesCategoryController::class, 'index']);
        Route::post('/store', [FilesCategoryController::class, 'store']);
        Route::post('/show/{id}', [FilesCategoryController::class, 'show']);
        Route::post('/update/{id}', [FilesCategoryController::class, 'update']);
        Route::post('/delete/{id}', [FilesCategoryController::class, 'destroy']);
 
    }
);

Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyTeacherCanAccess::class],
        'prefix' => 'filescategory'
    ],
    function ($router) {
        Route::post('/index', [FilesCategoryController::class, 'index']);
        Route::post('/store', [FilesCategoryController::class, 'store']);
        Route::post('/show/{id}', [FilesCategoryController::class, 'show']);
        Route::post('/update/{id}', [FilesCategoryController::class, 'update']);
        Route::post('/delete/{id}', [FilesCategoryController::class, 'destroy']);
 
    }
);

Route::group(
    [
        'middleware' => [JwtMiddleware::class, OnlyTeacherCanAccess::class],
        'prefix' => 'filesoffice'
    ],
    function ($router) {
        // Route::post('/index', [FilesOfficeController::class, 'index']);
        Route::post('/store', [FilesOfficeController::class, 'store']);
        Route::post('/update/{id}', [FilesOfficeController::class, 'update']);
        Route::post('/delete/{id}', [FilesOfficeController::class, 'destroy']);
    }

);

Route::group(
    [
        'middleware' => [JwtMiddleware::class],
        'prefix' => 'filesoffice'
    ],
    function ($router) {
        Route::post('/index', [FilesOfficeController::class, 'index']);
        Route::post('/show/{id}', [FilesOfficeController::class, 'show']);
        Route::post('/resourceData', [FilesOfficeController::class, 'resourceData']);
    }

);