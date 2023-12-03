<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentPicture;
use App\Models\StudentDataFile;
use App\Models\StudentFamilyData;
use App\Models\StudentMedicalData;
use App\Models\StudentOtherData;
use App\Models\StudentPersonalData;
use App\Models\StudentRegistration;
use App\Models\StudentStatus;
use App\Models\StudentStatusAssignment;
use App\Models\UserStudent;
use App\Models\StudentHouseAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Exception;
use Illuminate\Support\Facades\DB;


class StudentController extends Controller
{
    public function index($id){
        return Student::whereId($id)->first();
    }

    public function showData($id = null)
    {
        if($id){
            $student = Student::where('id', $id)->first();
            $pictureFileName = $student ? $student->picture : null;
            $pictureFile = null;
            if($pictureFileName){
                $pictureFile =  Storage::exists('public/pics/'.$pictureFileName);
            }
            $studentPersonalData = StudentPersonalData::where('student_id', $id)->first();
            $studentPersonalData->picture = $pictureFile ?
            URL::asset('storage/pics/'.$pictureFileName) : null;
            return $studentPersonalData;
        }


        $studentPersonalData = new StudentPersonalData;
        $columns =  Schema::getColumnListing('student_data_personal');
        foreach($columns as $column){
            $studentPersonalData[$column] = null;
        }
        return $studentPersonalData;
    }

    public function store(Request $request)
    {
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();

        $academicYearId = $academicTerm ? $academicTerm->academic_year_id : null;

        $id = $request->input('student_id');

        $classId = $request->input('form_class_id');

        if(!$id){
            //new student without id
            $form_level = FormClass::whereId($request->form_class_id)
            ->first()
            ->form_level;
            $max = StudentClassRegistration::withTrashed()
            ->where('form_class_id', 'like', $form_level.'%')
            ->max('student_id');
            $id = $max + 1;
            //check if id was assigned to student removed 
            $student = Student::withTrashed()
            ->where('id', $id)->first();
            while($student){
                $id++;
                $student = Student::withTrashed()
                ->where('id', $id)->first();
            }
        }
        
        //check if record was deleted
        $student = Student::withTrashed()
        ->where('id', $id)
        ->first();

        if($id && $classId && $student->trashed())
        {
            //restore student
            $student->restore();

            StudentPersonalData::withTrashed()
            ->where('student_id', $id)
            ->restore();

            StudentMedicalData::withTrashed()
            ->where('student_id', $id)
            ->restore();

            StudentDataFile::withTrashed()
            ->where('student_id', $id)
            ->restore();

            StudentClassRegistration::withTrashed()
            ->where('student_id', $id)
            ->restore();

        }


        $student = Student::withTrashed()
        ->updateOrCreate(
            ['id' => $id],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'birth_certificate_pin' => $request->birth_certificate_pin
            ]
        );


        $studentPicture = StudentPicture::where('student_id', $student->id)
        ->orderBy('created_at', 'desc')
        ->first();

        $pictureFile = null;

        if($studentPicture && File::exists( public_path('storage/pics/'.$studentPicture->file))){
            $pictureFile = URL::asset('storage/pics/'.$studentPicture->file);
        }

        $student->picture = $pictureFile;

        $data['student'] = $student;

        $data['studentDataPersonal'] = StudentPersonalData::withTrashed()
        ->firstOrCreate(
            ['student_id' => $id]
        );

        $data['studentDataMedical'] = StudentMedicalData::withTrashed()
        ->firstOrCreate(
            ['student_id' => $id]
        );

        $data['studentDataFile'] = StudentDataFile::withTrashed()
        ->firstOrCreate(
            ['student_id' => $id]
        );

        if($request->filled('house_id')){
            StudentHouseAssignment::updateOrCreate(
                ['student_id' => $id],
                ['house_id'=> $request->input('house_id')]
            );
        }

        if($classId)
        {
            $studentClassRegistration = StudentClassRegistration::updateOrCreate(
                [
                    'student_id' => $id, 
                    'academic_year_id' => $academicYearId
                ],
                ['form_class_id' => $classId, ]
            );
        }


        $birthPin = $request->input('birth_certificate_pin');

        $user = UserStudent::updateOrCreate(
            [
                'student_id' => $id
            ],
            [
                'name' => $request->input('first_name').' '.$request->input('last_name'),
                'password_reset'=> 0,
                'password' => Hash::make($birthPin),
            ]
        );

       
        $data['student'] = $student;
        $data['classRegistration'] = $studentClassRegistration;
        $data['user'] = $user;

        return $data;
    }

    public function data()
    {
        //return Student::all();
        return Student::whereNotNull('date_of_birth')->get();
    }

    public function updateData()
    {
        $file = 'ARIMA_NORTH_SECONDARY(4445)_EDITED.xlsx';
        $filePath = './files/'.$file;
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        //return $worksheet->getCell('A1')->getCalculatedValue();
        $recordsUpdated = 0;
        $rows = $worksheet->getHighestRow();
        for($row = 1; $row <= $rows; $row++){
            $id = $worksheet->getCell('A'.$row)->getCalculatedValue();
            $house_code = $worksheet->getCell('B'.$row)->getCalculatedValue();
            $class_id = $worksheet->getCell('C'.$row)->getCalculatedValue();
            $student = Student::whereId($id)->first();
            $student->house_code = $house_code;
            $student->class_id = $class_id;
            $student->save();
            if($student->wasChanged()) $recordsUpdated++;
        }
        return 'Records Updated: '.$recordsUpdated;
    }

    public function upload()
    {
        $file = './files/students.xlsx';
        $reader = new XlsxReader();
        $spreadsheet = $reader->load($file);
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academicYearId = $academicTerm->academic_year_id;
        // return $academicYearId;
        //return $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,2)->getValue();
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        // return $rows;
        //return $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,2)->getValue();
        $records = [];
        $studentRecords = 0;
        $classRegistrations = 0;
        $userAccounts = 0;
        $errors = array();
        for($i = 2; $i <= $rows; $i++){
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $lastName = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue());
            $firstName = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue());

            $gender = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
            $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
            $birthPin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
            $dateOfBirth = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();

            try{
                $student = Student::updateOrCreate(
                    ['id' => $id],
                    [
                        'last_name' => mb_convert_encoding(ucwords(strtolower($lastName)), 'UTF-8', 'UTF-8'),
                        'first_name' => mb_convert_encoding(ucwords(strtolower($firstName)), 'UTF-8', 'UTF-8'),
                        'gender' => $gender,
                        'birth_certificate_pin' => $birthPin,
                        'date_of_birth' => $dateOfBirth,
                        'sea_number' => $birthPin,
                    ]
                );
            } catch (Exception $e){
                $errorMsg = array();
                $errorMsg['id'] = $id;
                $errorMsg['error'] = $e;
                $errors[] = $errorMsg;
            }
            

            StudentPersonalData::firstOrCreate(
                ['student_id' => $id]
            );

            StudentMedicalData::firstOrCreate(
                ['student_id' => $id]
            );

            StudentDataFile::firstOrCreate(
                ['student_id' => $id]
            );

            $studentClassRegistration = StudentClassRegistration::updateOrCreate(
                ['student_id' => $id, 'academic_year_id' => $academicYearId],
                ['student_id' => $id, 'form_class_id' => $classId, 'academic_year_id' => $academicYearId]
            );

            if($studentClassRegistration->exists()){
                $classRegistrations++;
            }

            $user = UserStudent::updateOrCreate(
                [
                    'student_id' => $id
                ],
                [
                    'student_id'=> $id,
                    'name' => $firstName.' '.$lastName,
                    'password_reset'=> 0,
                    'password' => Hash::make($birthPin),
                ]
            );


            if($user->exists()){
                $userAccounts++;
            }
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        $records['Students'] = $studentRecords;
        $records['ClassRegistration'] = $classRegistrations;
        $records['UserAccounts'] = $userAccounts;
        $records['errors'] = $errors;
        return $records;
    }

    public function show()
    {
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academicTerm->academic_year_id;
        $date = date_create();
        //$studentsRegistered = StudentClassRegistration::where('academic_year_id', $academic_year_id)->get();
        $currentStudents = Student::join(
            'student_class_registrations', 
            'students.id', 
            'student_class_registrations.student_id'
        )
        ->select(
            'student_class_registrations.student_id',
            'students.first_name',
            'students.last_name',
            'students.gender',
            'students.date_of_birth',
            'students.birth_certificate_pin',
            'student_class_registrations.form_class_id'
        )
        ->where('student_class_registrations.academic_year_id', $academic_year_id)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        foreach($currentStudents as $student){
            $studentPicture = StudentPicture::where('student_id', $student->student_id)
            ->orderBy('created_at', 'desc')
            ->first();

            $pictureFile = null;

            $age = null;
            $dateOfBirth = $student->date_of_birth;
            if($dateOfBirth){
                $dateOfBirth = date_create($dateOfBirth);
                $diff = $date->diff($dateOfBirth);
                $age = $diff->y;
            }

            $student->age = $age;

            if($studentPicture && File::exists( public_path('storage/pics/'.$studentPicture->file))){
                $pictureFile = URL::asset('storage/pics/'.$studentPicture->file);
            }

            $student->picture = $pictureFile;

            $studentHouse = StudentHouseAssignment::where('student_id', $student->student_id)
            ->first();

            $student->house_id = null;

            if($studentHouse)
            $student->house_id = $studentHouse->house_id;
        }

        return $currentStudents;
    }

    public function showAll()
    {
        $data = array();
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academicYearId = $academicTerm ? $academicTerm->academic_year_id : null;
        $date = date_create();

        $studentsCurrent =  DB::table('students')
        ->join(
            'student_class_registrations',
            'students.id',
            'student_class_registrations.student_id'
        )
        ->select(
            'students.id as student_id',
            'students.first_name',
            'students.last_name',
            'students.gender',
            'students.date_of_birth',
            'students.birth_certificate_pin',
            'student_class_registrations.form_class_id'
        )
        ->where('student_class_registrations.academic_year_id', $academicYearId)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        foreach($studentsCurrent as $student){
            $studentPicture = StudentPicture::where('student_id', $student->student_id)
            ->orderBy('created_at', 'desc')
            ->first();

            $pictureFile = null;

            $age = null;
            $dateOfBirth = $student->date_of_birth;
            if($dateOfBirth){
                $dateOfBirth = date_create($dateOfBirth);
                $diff = $date->diff($dateOfBirth);
                $age = $diff->y;
            }

            $student->age = $age;

            if($studentPicture && File::exists( public_path('storage/pics/'.$studentPicture->file))){
                $pictureFile = URL::asset('storage/pics/'.$studentPicture->file);
            }

            $student->picture = $pictureFile;

            $studentHouse = StudentHouseAssignment::where('student_id', $student->student_id)
            ->first();

            $student->house_id = null;

            if($studentHouse)
            $student->house_id = $studentHouse->house_id;
        }

        $data['current_students'] = $studentsCurrent;
        $data['current_students_count'] = sizeof($studentsCurrent);

        $studentsArchived =  DB::table('students')
        ->leftJoin(
            'student_class_registrations',
            function ($join) use ($academicYearId) {
                $join->on('students.id', '=', 'student_class_registrations.student_id')
                    ->where('student_class_registrations.academic_year_id', $academicYearId);
        })
        ->select(
            'students.id as student_id',
            'students.first_name',
            'students.last_name',
            'students.gender',
            'students.date_of_birth',
            'students.birth_certificate_pin',
            'student_class_registrations.form_class_id'
        )
        ->whereNull('student_class_registrations.form_class_id')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        foreach($studentsArchived as $student){
            $studentPicture = StudentPicture::where('student_id', $student->student_id)
            ->orderBy('created_at', 'desc')
            ->first();

            $pictureFile = null;

            $age = null;
            $dateOfBirth = $student->date_of_birth;
            if($dateOfBirth){
                $dateOfBirth = date_create($dateOfBirth);
                $diff = $date->diff($dateOfBirth);
                $age = $diff->y;
            }

            $student->age = $age;

            if($studentPicture && File::exists( public_path('storage/pics/'.$studentPicture->file))){
                $pictureFile = URL::asset('storage/pics/'.$studentPicture->file);
            }

            $student->picture = $pictureFile;

            $studentHouse = StudentHouseAssignment::where('student_id', $student->student_id)
            ->first();

            $student->house_id = null;

            if($studentHouse)
            $student->house_id = $studentHouse->house_id;
        }

        $data['archived_students'] = $studentsArchived;
        $data['archived_students_count'] = sizeof($studentsArchived);

        

        return $data;
    }

    public function delete(Request $request)
    {
        $data = [];
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academicTerm->academic_year_id;
        //return $academic_year_id;
        $student_class_registration = StudentClassRegistration::where([
            ['academic_year_id', $academic_year_id],
            ['student_id', $request->student_id]
        ])
        ->first();
        if($student_class_registration) $student_class_registration->delete();

        if($student_class_registration && $student_class_registration->trashed()){
            $data['student_class_registration'] = $student_class_registration;
            //return 'class registration deleted';
            $student = Student::whereId($request->student_id)->first();
            $student->student_status_id = $request->student_status_id;
            $student->save();
            if($student->wasChanged('student_status_id')){
                $student->delete();
            }

            if($student->trashed()) $data['student'] = $student;

            return $data;
        }

        abort(500);
    }

    public function status()
    {
        return StudentStatus::all();
    }

    public function showDataFamily($id = null)
    {
        $data = [];
        $studentFamilyData = StudentFamilyData::withTrashed()
        ->where('student_id', $id)->get();
        foreach($studentFamilyData as $record){
            $data[$record->relationship] = $record;
        }
        return $data;
    }

    public function showDataMedical($id = null)
    {
        if($id) return StudentMedicalData::withTrashed()
        ->where('student_id', $id)->first();

        $studentMedicalData = new StudentMedicalData;
        $columns =  Schema::getColumnListing('student_data_medical');
        foreach($columns as $column){
            $studentMedicalData[$column] = null;
        }
        return $studentMedicalData;
    }

    public function showDataFiles($id){
        return StudentOtherData::withTrashed()
        ->where('student_id', $id)->first();
    }

    public function storePersonalData (Request $request)
    {
        $student_id= $request->student_id;

        $studentPersonalData = StudentPersonalData::withTrashed()
        ->updateOrCreate(
            ['student_id' => $student_id],
            $request->except(['picture'])
        );

        return $studentPersonalData;
    }

    public function storeDataMedical (Request $request)
    {
        $student_id = $request->student_id;

        $studentMedicalData = StudentMedicalData::withTrashed()
        ->updateOrCreate(
            ['student_id' => $student_id],
            $request->all()
        );

        return $studentMedicalData;
    }

    public function storeDataFamily (Request $request)
    {
        $studentFamilyData = StudentFamilyData::withTrashed()
        ->updateOrCreate(
            [
                'student_id' => $request->student_id,
                'relationship' => $request->relationship
            ],
            $request->all()
        );

        return $studentFamilyData;
    }

    public function storeDataFiles (Request $request)
    {
        $student_id = $request->student_id;

        $studentOtherData = StudentOtherData::withTrashed()
        ->updateOrCreate(
            ['student_id' => $student_id],
            $request->all()
        );

        return $studentOtherData;
    }

    public function showDataHouse ($id = null)
    {
        return StudentHouseAssignment::where('student_id', $id)->first();
    }

    public function queryId ()
    {
        return Student::select(
            'id as student_id',
            'first_name',
            'last_name',
            'gender',
            'date_of_birth',
            'birth_certificate_pin',
        )
        ->addSelect([
            'form_class_id' => StudentClassRegistration::select('form_class_id')
            ->whereColumn('student_id', 'students.id')
            ->limit(1)
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();
    }

    public function postHealth ()
    {
        $students = StudentRegistration::select(
            'id',
            'hepatitis',
            'polio',
            'diphtheria',
            'tetanus',
            'yellow_fever',
            'measles',
            'tb',
            'chicken_pox',
            'typhoid',
            'rheumatic_fever',
            'poor_eyesight',
            'poor_hearing',
            'diabetes',
            'asthma',
            'epilepsy',
            'allergy',
            'other_illness'
        )->get();

        foreach($students as $student){
            StudentMedicalData::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'student_id' => $student->id,
                    'hepatitis' => $student->hepatitis,
                    'polio' => $student->polio,
                    'diphtheria' => $student->diphtheria,
                    'tetanus' => $student->tetanus,
                    'yellow_fever' => $student->yellow_fever,
                    'measles' => $student->measles,
                    'tb' => $student->tb,
                    'chicken_pox' => $student->chicken_pox,
                    'typhoid' => $student->typhoid,
                    'rheumatic_fever' => $student->rheumatic_fever,
                    'poor_eyesight' => $student->poor_eyesight,
                    'poor_hearing' => $student->poor_hearing,
                    'diabetes' => $student->diabetes,
                    'asthma' => $student->asthma,
                    'epilepsy' => $student->epilepsy,
                    'allergies' => $student->allergy,
                    'other' => $student->other_illness
                ]
            );
        }
    }

    public function postOtherData ()
    {
        $records = StudentRegistration::select(
            'id',
            'file_birth_certificate',
            'file_sea_slip',
            'file_immunization_card',
            'agree_terms_conditions',
            'request_transfer'
        )->get();

        foreach($records as $record){
            StudentOtherData::updateOrCreate(
                ['student_id'=> $record->id],
                [
                    'student_id'=> $record->id,
                    'file_birth_certificate' => $record->file_birth_certificate,
                    'file_sea_slip' => $record->file_sea_slip,
                    'file_immunization_card' => $record->file_immunization_card,
                    'agree_terms_conditions' => $record->agree_terms_conditions,
                    'request_transfer'=> $record->request_transfer
                ]
            );
        }
    }

    public function storeRegistration(Request $request)
    {
        $id = $request->input('id');
        $student_data = $request->except(['form_class_id', 'name']);
        $data = [];

        $studentRecord = Student::updateOrCreate(
            ['id' => $id],
            $student_data
        );

        if($studentRecord->exists()){

            $birthPin = $request->input('birth_certificate_pin');

            $user = UserStudent::updateOrCreate(
                [
                    'student_id' => $id
                ],
                [
                    'name' => $request->input('first_name').' '.$request->input('last_name'),
                    'password_reset'=> 0,
                    'password' => Hash::make($birthPin),
                    'remember_token' => $birthPin
                ]
            );

        }

        $data['Student'] = $studentRecord;
        $data['User'] = $user;

        return $data;
    }

}
