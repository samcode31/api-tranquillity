<?php

use App\Http\Controllers\AcademicTerm;
use App\Http\Controllers\CommentTemplate;
use App\Http\Controllers\Employee;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FormClass;
use App\Http\Controllers\FormTeacherAssignment;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationFormController;
use App\Http\Controllers\RegistrationReportController;
use App\Http\Controllers\RegistrationSpreadSheetController;
use App\Http\Controllers\ReportCard;
use App\Http\Controllers\StudentClassRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentTermDetail;
use App\Http\Controllers\StudentTermMark;
use App\Http\Controllers\Subject;
use App\Http\Controllers\TeacherLesson;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionsAssignment;
use App\Models\EthnicGroup;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentTermMark as ModelsStudentTermMark;
use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

//Route::middleware('auth:sanctum')->get('/student-record', [StudentController::class, 'index']);
Route::get('/user/{id}', [UserController::class, 'user']);

Route::post('/reset-password', [UserController::class, 'resetPassword']);

Route::post('/change-password', [UserController::class, 'changePassword']);

Route::post('/change-reset-password', [UserController::class, 'changeResetPassword']);

Route::get('/default-password/{id}', [UserController::class, 'defaultPassword']);

Route::get('/student-record/{id}', [StudentController::class, 'index']);

Route::post('/student', [StudentController::class, 'store']);

Route::get('/students', [StudentController::class, 'retrieve']);

Route::get('/registration-data', [StudentController::class, 'data']);

Route::get('/towns', function(){ return Town::all();});

Route::get('/religions', function(){ return Religion::all();});

Route::get('/ethnic-groups', function(){ return EthnicGroup::all();});

Route::get('/upload-file', function () {
    return view('upload');
});

Route::post('/store-file', [FileUploadController::class, 'fileStore']);

Route::get('/registration-form/{id}', [RegistrationFormController::class, 'createPDF']);

Route::get('/registration/{id}', [RegistrationFormController::class, 'record']);

Route::get('/registration-data-spreadsheet', [RegistrationSpreadSheetController::class, 'download']);

Route::post('/users', [UserController::class, 'register']);

Route::post('/admin-user', [UserController::class, 'registerAdmin']);

Route::middleware('auth:sanctum')->get('/user-auth', function() {
    if(Auth::check()) return "Authorized";
    else return "Not Authorized";    
});

Route::post('/admin-login', [LoginController::class, 'authenticate']);

Route::get('/registration-report/{classId}', [RegistrationReportController::class, 'create']);

//----------------------------- Term Reports -----------------------------------

Route::post('/employee-login', [LoginController::class, 'authenticateEmployee']);

Route::get('/user-employee/{name}', [UserController::class, 'userEmployee']);

Route::get('/employee/{id}', [Employee::class, 'index']);

Route::post('/employee-change-password', [UserController::class, 'employeeChangePassword']);

Route::get('/current-period', [AcademicTerm::class, 'show']);

Route::get('/user-permissions/{id}', [UserPermissionsAssignment::class, 'show']);

Route::post('/user-permissions', [UserPermissionsAssignment::class, 'store']);

Route::post('/register-students', [StudentClassRegistration::class, 'register']);

//---------------------Teacher Lessons ---------------------------------

Route::get('/teacher-lessons/{id}', [TeacherLessonController::class, 'show']);

Route::get('/employees', [Employee::class, 'show']);

Route::get('/form-classes-list', [FormClass::class, 'show']); 

Route::get('/subjects', [Subject::class, 'show']);

Route::get('/teacher-lessons/{id}', [TeacherLesson::class, 'show']);

Route::get('/form-teacher-class/{id}/{year}', [FormTeacherAssignment::class, 'show']);

Route::post('/form-teacher-class', [FormTeacherAssignment::class, 'store']);

Route::post('/teacher-lesson', [TeacherLesson::class, 'store']);

Route::post('/delete-teacher-lesson', [TeacherLesson::class, 'delete']);

//-----------------------------Enter Marks -----------------------------------

Route::get('/teacher-lesson-students/{class}/{termId}/{subjCode}', [StudentTermMark::class, 'show']); 

Route::get('/preset-comments', [CommentTemplate::class, 'show']);

Route::post('/term-marks', [StudentTermMark::class, 'store']);

//----------------------------Edit / View Term Reports -----------------------

Route::get('/students-registered/{term}/{class}', [StudentTermDetail::class, 'show']); 

Route::get('/student-mark-records/{studentId}/{termId}', [StudentTermMark::class, 'studentRecords']); 

Route::post('/term-details', [StudentTermDetail::class, 'store']);

//----------------------------Print / View Reports ---------------------------

Route::get('/report-cards/{termId}/{classId}', [ReportCard::class, 'show']);

//----------------------------- Students --------------------------------------

Route::get('/students', [StudentController::class, 'show']);

//-------------------------- Upload Data -------------------------------------

Route::post('/upload-employees', [Employee::class, 'upload']);

Route::post('/upload-form-classes', [FormClass::class, 'upload']);

Route::post('/upload-students', [StudentController::class, 'upload']);

Route::post('/upload-user-permissions', [UserPermissionsAssignment::class, 'upload']);


