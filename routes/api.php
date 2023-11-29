<?php

use App\Http\Controllers\AcademicTerm;
use App\Http\Controllers\ClassList;
use App\Http\Controllers\ClassMarkSheet;
use App\Http\Controllers\CommentTemplate;
use App\Http\Controllers\Employee;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FormClass;
use App\Http\Controllers\FormDeanAssignment;
use App\Http\Controllers\FormTeacherAssignment;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationFormController;
use App\Http\Controllers\RegistrationReportController;
use App\Http\Controllers\RegistrationSpreadSheetController;
use App\Http\Controllers\RegistrationStatus;
use App\Http\Controllers\ReportAgeStatistics;
use App\Http\Controllers\ReportHealthController;
use App\Http\Controllers\ReportForeignStudentsController;
use App\Http\Controllers\ReportCard;
use App\Http\Controllers\StudentClassRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentSubjectAssignment;
use App\Http\Controllers\StudentTermDetail;
use App\Http\Controllers\StudentTermMark;
use App\Http\Controllers\Subject;
use App\Http\Controllers\TeacherLesson;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionsAssignment;
use App\Http\Controllers\ReportDeviceAndInternet;
use App\Http\Controllers\ReportEthnicGroup;
use App\Http\Controllers\ReportReligiousGroup;
use App\Http\Controllers\ReportSchoolFeeding;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\MarkSheetSubjectChoice;
use App\Http\Controllers\SixthFormApplication;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\DbFixesController;
use App\Http\Controllers\ReportSubjectEnrollment;
use App\Models\EthnicGroup;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentTermMark as ModelsStudentTermMark;
use App\Models\Town;
use App\Models\User;
use App\Models\House;
use App\Models\RegionalCorporation;
use App\Models\LivingStatus;
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

Route::post('/reset-employee-password', [UserController::class, 'resetEmployeePassword']);

Route::post('/change-password', [UserController::class, 'changePassword']);

Route::post('/change-reset-password', [UserController::class, 'changeResetPassword']);

Route::get('/default-password/{id}', [UserController::class, 'defaultPassword']);

Route::get('/student-record/{id}', [StudentController::class, 'index']);

Route::post('/student', [StudentController::class, 'store']);

Route::get('/registration-data', [StudentController::class, 'data']);

Route::get('/towns', function(){ return Town::all();});

Route::get('/religions', function(){ return Religion::all();});

Route::get('/ethnic-groups', function(){ return EthnicGroup::all();});

Route::get('/upload-file', function () {
    return view('upload');
});

Route::post('/store-file', [FileUploadController::class, 'fileStore']);

Route::get('/registration-form/{id?}', [RegistrationFormController::class, 'createPDF']);

Route::get('/registration/{id}', [RegistrationFormController::class, 'record']);

Route::get('/registration-data-spreadsheet', [RegistrationSpreadSheetController::class, 'download']);

Route::post('/users', [UserController::class, 'register']);

Route::post('/admin-user', [UserController::class, 'registerAdmin']);

Route::middleware('auth:sanctum')->get('/user-auth', function() {
    if(Auth::check()) return "Authorized";
    else return "Not Authorized";
});

Route::post('/admin-login', [LoginController::class, 'authenticate']);

Route::post('/login-student', [LoginController::class, 'authenticateStudent']);

Route::get('/registration-report/{classId}', [RegistrationReportController::class, 'create']);

//----------------------------- Term Reports -----------------------------------

Route::post('/employee-login', [LoginController::class, 'authenticateEmployee']);

Route::post('/employee-change-password', [UserController::class, 'employeeChangePassword']);

Route::get('/current-period', [AcademicTerm::class, 'show']);

Route::get('/user-permissions/{id}', [UserPermissionsAssignment::class, 'show']);

Route::post('/user-permissions', [UserPermissionsAssignment::class, 'store']);

Route::post('/register-students', [StudentClassRegistration::class, 'register']);

Route::delete('/student-term-mark', [StudentTermMark::class, 'delete']);

Route::post('/student-term-mark-update', [StudentTermMark::class, 'update']);

//---------------------Teacher Lessons ---------------------------------

Route::get('/teacher-lessons/{id}', [TeacherLessonController::class, 'show']);

Route::get('/form-classes-list', [FormClass::class, 'show']);

Route::get('/subjects', [Subject::class, 'show']);

//-----------------------------Employee -------------------------------------

Route::get('/employees', [Employee::class, 'show']);

Route::delete('/employee', [Employee::class, 'delete']);

Route::post('/employee', [Employee::class, 'store']);

Route::get('/employee-statuses', [Employee::class, 'status']);

Route::get('/user-employee/{name}', [UserController::class, 'userEmployee']);

Route::get('/employee/{id}', [Employee::class, 'index']);

Route::get('/teacher-lessons/{id}', [TeacherLesson::class, 'show']);

Route::get('/form-teacher-class/{id}/{year}', [FormTeacherAssignment::class, 'show']);

Route::post('/form-teacher-class', [FormTeacherAssignment::class, 'store']);

Route::get('/form-dean-assignments/{id}', [FormDeanAssignment::class, 'show']);

Route::post('/form-dean-assignments', [FormDeanAssignment::class, 'store']);

Route::post('/teacher-lesson', [TeacherLesson::class, 'store']);

Route::post('/delete-teacher-lesson', [TeacherLesson::class, 'delete']);

//-----------------------------Enter Marks -----------------------------------

Route::get('/teacher-lesson-students/{class}/{termId}/{subjCode}', [StudentTermMark::class, 'show']);

Route::get('/preset-comments', [CommentTemplate::class, 'show']);

Route::post('/term-marks', [StudentTermMark::class, 'store']);

Route::get('/term-configuration/{formLevel}', [AcademicTerm::class, 'termConfiguration']);

//----------------------------Edit / View Term Reports -----------------------

Route::get('/students-registered/{term}/{class}', [StudentTermDetail::class, 'show']);

Route::get('/student-mark-records/{studentId}/{termId}', [StudentTermMark::class, 'studentRecords']);

Route::post('/term-details', [StudentTermDetail::class, 'store']);

//----------------------------Print / View Reports ---------------------------

Route::get('/report-card/{termId}/{classId?}/{studentId?}', [ReportCard::class, 'show']);

Route::get('/class-list/{class_id}/{yearId}', [ClassList::class, 'show']);

Route::get('/mark-sheet/{term_id}/{class_id}', [ClassMarkSheet::class, 'show']);

Route::get('/mark-sheet-terms', [ClassMarkSheet::class, 'terms']);

Route::get('/mark-sheet-subject-choice/{form_class_id}/{student_id?}', [MarkSheetSubjectChoice::class, 'show']);

Route::get('/report-card-terms', [ReportCard::class, 'terms']);

Route::get('/class-list-years', [ClassList::class, 'academicYears']);

Route::get('/registration-status/{formLevel?}', [RegistrationStatus::class, 'show']);

Route::get('/student-contact/{classId}', [RegistrationReportController::class, 'show']);

Route::get('/school-feeding', [ReportSchoolFeeding::class, 'show']);

Route::get('/device-internet/{formLevel?}/{formClass?}', [ReportDeviceAndInternet::class, 'show']);

Route::get('/ethnic-group-statistics', [ReportEthnicGroup::class, 'show']);

Route::get('/religious-group-statistics', [ReportReligiousGroup::class, 'show']);

Route::get('/student-age-statistics/{date?}', [ReportAgeStatistics::class, 'show']);

Route::get('/student-subject-enrollment', [ReportSubjectEnrollment::class, 'show']);

Route::get('/asr', [ASRController::class, 'show']);

Route::get('/student-health', [ReportHealthController::class, 'show']);

Route::get('/foreign-students', [ReportForeignStudentsController::class, 'show']);

//----------------------------- Students --------------------------------------

Route::get('/students', [StudentController::class, 'show']);

Route::post('/student', [StudentController::class, 'store']);

Route::delete('/student', [StudentController::class, 'delete']);

Route::post('/student-subject-assignment', [StudentSubjectAssignment::class, 'store']);

Route::get('/subject-students/{subjectId}', [StudentSubjectAssignment::class, 'show']);

Route::post('/delete-student-subject-assignment', [StudentSubjectAssignment::class, 'delete']);

Route::get('/student-status', [StudentController::class, 'status']);

Route::get('/student-reports/{studentId}', [StudentTermMark::class, 'showReportTerms']);

//-------------------------- Upload Data -------------------------------------

Route::post('/upload-employees', [Employee::class, 'upload']);

Route::post('/upload-form-classes', [FormClass::class, 'upload']);

Route::post('/upload-students', [StudentController::class, 'upload']);

Route::post('/upload-user-permissions', [UserPermissionsAssignment::class, 'upload']);

Route::post('/upload-student-subject-assignment', [StudentSubjectAssignment::class, 'upload']);

Route::post('/upload-student-class-registration', [StudentClassRegistration::class, 'upload']);

Route::post('/upload-teacher-lessons', [TeacherLesson::class, 'upload']);

//--------------------- Settings ---------------------------------

Route::get('/current-term', [AcademicTerm::class, 'show']);

Route::get('/next-term', [AcademicTerm::class, 'showNextTerm']);

Route::post('/current-term', [AcademicTerm::class, 'store']);

Route::post('/term-registration', [StudentTermDetail::class, 'register']);

Route::get('/term-history', [AcademicTerm::class, 'showHistory']);

Route::post('/backdate-term', [AcademicTerm::class, 'backdateTerm']);

Route::post('/subjects', [Subject::class, 'store']);

Route::delete('/subject', [Subject::class, 'delete']);

Route::get('/term-registration', [StudentTermDetail::class, 'showAll']);

Route::post('/promote', [StudentClassRegistration::class, 'promote']);

//--------------------- Student Attendance ---------------------------------

Route::post('/student-attendance', [StudentAttendanceController::class, 'store']);

Route::post('/student-file', [FileUploadController::class, 'storeFile']);

Route::get('/student-picture/{studentId}', [FileUploadController::class, 'getPicture']);

Route::get('/student-population', [StudentController::class, 'currentPopulation']);

/*
|--------------------------------------------------------------------------
| Student Registration Routes
|--------------------------------------------------------------------------
*/

Route::get('/query-id', [StudentController::class, 'queryId']);

Route::get('/user/{id}', [UserController::class, 'user']);

Route::get('/student/{id}', [StudentController::class, 'index']);

Route::post('/student-registration', [StudentController::class, 'storeRegistration']);

Route::post('/student', [StudentController::class, 'store']);

Route::delete('/student', [StudentController::class, 'delete']);

Route::get('/student-data-personal/{id?}', [StudentController::class, 'showData']);

Route::post('/student-data-personal', [StudentController::class, 'storePersonalData']);

Route::get('/student-data-family/{id?}', [StudentController::class, 'showDataFamily']);

Route::post('/student-data-family', [StudentController::class, 'storeDataFamily']);

Route::get('/student-data-medical/{id?}', [StudentController::class, 'showDataMedical']);

Route::post('/student-data-medical', [StudentController::class, 'storeDataMedical']);

Route::get('/student-data-files/{id}', [StudentController::class, 'showDataFiles']);

Route::post('/student-data-files', [StudentController::class, 'storeDataFiles']);

Route::get('/student-data-house/{id?}', [StudentController::class, 'showDataHouse']);

Route::get('/students', [StudentController::class, 'show']);

Route::get('/registration-data', [StudentController::class, 'data']);

Route::get('/towns', function(){ return Town::all();});

Route::get('/religions', function(){ return Religion::all();});

Route::get('/ethnic-groups', function(){ return EthnicGroup::all();});

Route::get('/living-status', function(){ return LivingStatus::all();});

Route::get('/regional-corporations', function () { return RegionalCorporation::all();});

Route::get('/houses', function () { return House::all();});

Route::get('/upload-file', function () {
    return view('upload');
});

Route::post('/store-file', [FileUploadController::class, 'fileStore']);

Route::get('/get-files/{id}', [FileUploadController::class, 'getFiles']);

// Route::get('/registration-form/{id}', [RegistrationFormController::class, 'show']);

Route::get('/registration/{id}', [RegistrationFormController::class, 'record']);

Route::get('/registration-data-spreadsheet', [RegistrationSpreadSheetController::class, 'download']);

Route::post('/users', [UserController::class, 'register']);

Route::get('/student-status', [StudentController::class, 'status']);


Route::get('/student-reports/{studentId}', [StudentTermMark::class, 'showReportTerms']);

Route::post('/student-class-registration', [StudentClassRegistration::class, 'store']);

/*
|--------------------------------------------------------------------------
| Sixth Form Application Routes
|--------------------------------------------------------------------------
*/

Route::post('/sixth-form-application', [SixthFormApplication::class, 'store']);

Route::post('/sixth-form-application-grade', [SixthFormApplication::class, 'storeGrade']);

Route::post(
    '/sixth-form-application-subject-choice', 
    [SixthFormApplication::class, 'storeSubjectChoice']
);

Route::get('/sixth-form-application/{applicationId}/{year}', [SixthFormApplication::class, 'show']);

Route::get(
    '/sixth-form-application-grades/{applicationId}/{year}', 
    [SixthFormApplication::class, 'showGrades']
);

Route::get(
    '/sixth-form-application-subject-choices/{applicationId}/{year}',
    [SixthFormApplication::class, 'showSubjectChoices']
);

Route::get('/sixth-form-application-subject-lines', [SixthFormApplication::class, 'showSubjectLines']);

Route::get('/csec-subjects', [SubjectController::class, 'showCSECSubjects']);

Route::get(
    '/sixth-form-application-form/{applicationId}/{year}', 
    [SixthFormApplication::class, 'applicationForm']
);

Route::get(
    '/sixth-form-application-check/{applicationId}/{birthPin}',
    [SixthFormApplication::class, 'checkApplication'] 
);

Route::get(
    '/sixth-form-applications',
    [SixthFormApplication::class, 'showAll']
);

Route::delete(
    '/sixth-form-application',
    [SixthFormApplication::class, 'delete']
);

Route::get(
    '/sixth-form-application-instructions',
    [SixthFormApplication::class, 'instructions']
);

Route::get(
    '/sixth-form-application-accepted-pdf/{year}',
    [SixthFormApplication::class, 'acceptedPDF']
);

Route::get(
    '/sixth-form-application-accepted-excel/{year}',
    [SixthFormApplication::class, 'acceptedSpreadsheet']
);

Route::post(
    '/sixth-form-application-store-file',
    [FileUploadController::class, 'storeSixthFormApplicationFile']
);

Route::get(
    '/sixth-form-application-get-files/{applicationId}/{year}',
    [FileUploadController::class, 'getSixthFormApplicationFiles']
);

Route::post(
    '/sixth-form-application-lock-status',
    [SixthFormApplication::class, 'applicationsLock']
);

Route::get(
    '/sixth-form-application-lock-status/{year?}',
    [SixthFormApplication::class, 'applicationsLockStatus']
);

Route::get(
    '/sixth-form-application-periods',
    [SixthFormApplication::class, 'applicationPeriods']
);

Route::get(
    '/sixth-form-application-current-period',
    [SixthFormApplication::class, 'currentPeriod']
);


/*
|--------------------------------------------------------------------------
| DB Fixes  Routes
|--------------------------------------------------------------------------
*/

Route::post(
    '/fix-student-term-details',
    [DbFixesController::class, 'fixStudentTermDetails']
);