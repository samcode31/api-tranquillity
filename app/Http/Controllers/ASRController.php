<?php

namespace App\Http\Controllers;

use App\Http\Controllers\StudentClassRegistration as ControllersStudentClassRegistration;
use App\Models\AcademicTerm;
use App\Models\EthnicGroup;
use App\Models\FormClass;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentPersonalData;
use App\Models\Subject;
use App\Models\TeacherLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ASRController extends Controller
{
    public function show ()
    {
        $date = '2021-11-30';
        $file = '2021-2022 Secondary Schools ASR ver. 2 UPDATED.xlsx';
        $filePath = Storage::path("public/files/".$file);
        $formLevels = 5;
        $subjectRows = 116;
        $subjectRowStart = 7;

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();
        $academicYearId = null;
        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

        $enrollSheet = $spreadsheet->setActiveSheetIndexByName('Enrol Repeat Drop');
        $ageGroups = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $row = 10;
        $enrollmentData = [];

        foreach($ageGroups as $group){
            $records = $this->dataEnrollment($academicYearId, $date, $group, $group + 1);
            $enrollmentData[] = $records;
        }

        $enrollSheet->fromArray(
            $enrollmentData,
            null,
            'C'.$row,
            true
        );

        $formClassesData = $this->dataClasses($academicYearId, $formLevels);
        $row = 21;
        $enrollSheet->setCellValue('C'.$row, $formClassesData[0]);
        $enrollSheet->setCellValue('E'.$row, $formClassesData[1]);
        $enrollSheet->setCellValue('G'.$row, $formClassesData[2]);
        $enrollSheet->setCellValue('I'.$row, $formClassesData[3]);
        $enrollSheet->setCellValue('K'.$row, $formClassesData[4]);

        $religionSheet = $spreadsheet->setActiveSheetIndexByName('Religion');
        $religionData = $this->dataReligion($academicYearId);
        // return $religionData;
        $row = 7;
        foreach($religionData as $religionCount){
            $religionSheet->setCellValue('F'.$row, $religionCount[0]);
            $religionSheet->setCellValue('H'.$row, $religionCount[1]);
            $row++;
        }

        $ethnicitySheet = $spreadsheet->setActiveSheetIndexByName('Ethnicity');
        $ethnicityData = $this->dataEthnicity($academicYearId);
        $ethnicitySheet->fromArray(
            $ethnicityData,
            null,
            'C6',
            true
        );

        $subjectSheet = $spreadsheet->setActiveSheetIndexByName('Subject');
        $subjectsData = $this->dataSubjects($academicYearId, $formLevels, $subjectRows);
        // return $subjectsData;
        foreach($subjectsData as $key => $dataRecord){
            if($key > $subjectRows){
                $subjectSheet->setCellValue('B'.($subjectRowStart+$key), $dataRecord["subject"]);
            }
            $subjectSheet->setCellValue('D'.($subjectRowStart+$key), $dataRecord["form"]["1"]);
            $subjectSheet->setCellValue('E'.($subjectRowStart+$key), $dataRecord["form"]["2"]);
            $subjectSheet->setCellValue('F'.($subjectRowStart+$key), $dataRecord["form"]["3"]);
            $subjectSheet->setCellValue('G'.($subjectRowStart+$key), $dataRecord["form"]["4"]);
            $subjectSheet->setCellValue('H'.($subjectRowStart+$key), $dataRecord["form"]["5"]);
        }


        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        // $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($filePath, $file);
    }

    private function dataEnrollment ($academicYearId, $date, $ageGroupMin, $ageGroupMax)
    {
        $data = [];
        $date = date_create($date);

        $students = Student::join(
            'student_class_registrations',
            'student_class_registrations.student_id',
            'students.id'
        )
        ->select(
            'students.id',
            'students.date_of_birth',
            'student_class_registrations.form_class_id',
            'students.gender'
        )
        ->where('academic_year_id', $academicYearId)
        ->get();

        $forms = [];
        for($i = 1; $i<=5; $i++){
            $forms[$i] = 0;
            $forms[$i] = array('M' => 0, 'F' => 0);
        }
        // $ages = [];
        foreach($students as $student)
        {
            $dateOfBirth = $student->date_of_birth;
            $age = 0;
            if($dateOfBirth){
                $dateOfBirth = date_create($dateOfBirth);
                $diff = $date->diff($dateOfBirth);
                $age = $diff->y;

            }
            if($age != 0 && $age < $ageGroupMax && $age == $ageGroupMin){
                // $ages[] = $age;
                $formClassRecord = FormClass::where('id',$student->form_class_id)
                ->first();
                $formLevel = $formClassRecord->form_level;
                $formLevels[] = $formLevel;
                $forms[$formLevel] = $forms[$formLevel]++;


                if($student->gender == 'M'){
                    $males = $forms[$formLevel]['M'];
                    $males++;
                    $forms[$formLevel]['M'] = $males;
                }
                if($student->gender == 'F') {
                    $females = $forms[$formLevel]['F'];
                    $females++;
                    $forms[$formLevel]['F'] = $females;
                }
            }
        }

        foreach($forms as $formLevel){
            $data[] = $formLevel['M'];
            $data[] = $formLevel['F'];
        }

        return $data;
    }

    private function dataClasses ($academicYearId, $formLevels)
    {
        $data = [];

        for($i = 1; $i <= $formLevels; $i++){
            $formLevelClasses = StudentClassRegistration::join(
                'form_classes',
                'form_classes.id',
                'student_class_registrations.form_class_id'
            )
            ->select('form_class_id')
            ->where([
                ['academic_year_id', $academicYearId],
                ['form_level', $i]
            ])
            ->distinct()
            ->get()
            ->count();
            $data[] = $formLevelClasses;
        }

        return $data;
    }

    private function dataReligion ($academicYearId)
    {
        $religiousGroups = Religion::all();
        $data = [];
        foreach($religiousGroups as $religiousGroup)
        {
            $group = [];
            $groupId = $religiousGroup->id;
            $maleStudents = StudentPersonalData::join(
                'students',
                'students.id',
                'student_data_personal.student_id'
            )
            ->join(
                'student_class_registrations',
                'student_class_registrations.student_id',
                'student_data_personal.student_id'
            )
            ->select(
                'student_data_personal.student_id'
            )
            ->where([
                ['religion_id', $groupId],
                ['gender', 'M'],
                ['academic_year_id', $academicYearId]
            ])
            ->get()
            ->count();

            $femaleStudents = StudentPersonalData::join(
                'students',
                'students.id',
                'student_data_personal.student_id'
            )
            ->join(
                'student_class_registrations',
                'student_class_registrations.student_id',
                'student_data_personal.student_id'
            )
            ->select(
                'student_data_personal.student_id'
            )
            ->where([
                ['religion_id', $groupId],
                ['gender', 'F'],
                ['academic_year_id', $academicYearId]
            ])
            ->get()
            ->count();

            $group[] = $maleStudents;
            $group[] = $femaleStudents;
            $data[] = $group;
        }

        return $data;
    }

    private function dataEthnicity ($academicYearId)
    {
        $ethnicGroups = EthnicGroup::all();
        $data = [];
        foreach($ethnicGroups as $ethnicGroup)
        {
            $group = [];
            $groupId = $ethnicGroup->id;
            $maleStudents = StudentPersonalData::join(
                'students',
                'students.id',
                'student_data_personal.student_id'
            )
            ->join(
                'student_class_registrations',
                'student_class_registrations.student_id',
                'student_data_personal.student_id'
            )
            ->where([
                ['ethnic_group_id', $groupId],
                ['gender', 'M'],
                ['academic_year_id', $academicYearId]
            ])
            ->get()
            ->count();

            $femaleStudents = StudentPersonalData::join(
                'students',
                'students.id',
                'student_data_personal.student_id'
            )
            ->join(
                'student_class_registrations',
                'student_class_registrations.student_id',
                'student_data_personal.student_id'
            )
            ->where([
                ['ethnic_group_id', $groupId],
                ['gender', 'F'],
                ['academic_year_id', $academicYearId]
            ])
            ->get()
            ->count();

            $group[] = $maleStudents;
            $group[] = $femaleStudents;
            $data[] = $group;
        }

        return $data;
    }

    private function dataSubjects ($academicYearId, $formLevels, $subjectRows)
    {

        $subjects = Subject::orderBy(DB::raw('ISNULL(asr_code), asr_code'), 'ASC')
        ->get();
        // return $subjects;
        $data = [];

        $currSubjectId = null; $prevSubjectId = null;
        foreach($subjects as $index => $subject){
            $currSubjectId = $subject->asr_code;
            $subjectRecord = [];
            if($currSubjectId != $prevSubjectId || $currSubjectId == null ){
                $subjectFormLevelCount = [];
                for($i = 1; $i <= $formLevels; $i++){
                    $subjectFormLevelCount[$i] = 0;
                }
            }

            $teacherLessons = TeacherLesson::join(
                'form_classes',
                'form_classes.id',
                'teacher_lessons.form_class_id'
            )
            ->select(
                'subject_id',
                'form_level',
                'form_class_id'
            )
            ->distinct()
            ->where([
                ['subject_id', $subject->id],
                ['academic_year_id', $academicYearId]
            ])
            ->get();


            if($teacherLessons->count() > 0){
                foreach($teacherLessons as $lesson){
                    // get all the students in the class
                    $students = StudentClassRegistration::join(
                        'form_classes',
                        'form_classes.id',
                        'student_class_registrations.form_class_id'
                    )
                    ->select(
                        'student_id',
                        'form_level',
                    )
                    ->where([
                        ['form_class_id', $lesson->form_class_id],
                        ['academic_year_id', $academicYearId]
                    ])
                    ->count();

                    //check for student subject assignments
                    $studentSubjectAssignments = StudentClassRegistration::join(
                        'student_subject_assignments',
                        'student_subject_assignments.student_id',
                        'student_class_registrations.student_id'
                    )
                    ->join(
                        'form_classes',
                        'form_classes.id',
                        'student_class_registrations.form_class_id'
                    )
                    ->select(
                        'student_class_registration.student_id',
                        'form_level',
                    )
                    ->where([
                        ['form_class_id', $lesson->form_class_id],
                        ['student_class_registrations.academic_year_id', $academicYearId],
                        ['student_subject_assignments.academic_year_id', $academicYearId],
                        ['subject_id', $lesson->subject_id]
                    ])
                    ->count();

                    if($studentSubjectAssignments > 0){
                        $students = $studentSubjectAssignments;
                    }

                    $numberOfStudents = $subjectFormLevelCount[$lesson->form_level];
                    $numberOfStudents += $students;
                    $subjectFormLevelCount[$lesson->form_level] = $numberOfStudents;
                }

                // $subjectRecord[$subject->title] = $subjectFormLevelCount;
                $subjectRecord['subject'] = $subject->title;
                $subjectRecord['form'] = $subjectFormLevelCount;
                if($subject->asr_code){
                    $data[$subject->asr_code] = $subjectRecord;
                }
                else $data[++$subjectRows] = $subjectRecord;
            }
            $prevSubjectId = $currSubjectId;
        }

        return $data;
    }

    private function foreignStudents ($academicYearId)
    {
        $foreignStudents = StudentClassRegistration::join(
            'student_data_personal',
            'student_data_personal.student_id',
            'student_class_registrations.student_id'
        )
        ->select(
            'student_class_registrations.student_id',
            'gender',
            'date_of_birth',
            'form_class_id'
        )
        ->join(
            'students',
            'students.id',
            'student_class_registrations.student_id'
        )
        ->where([
            ['academic_year_id', $academicYearId],
            ['country_of_birth', '<>', 'Trinidad and Tobago']
        ])->get();

        return $foreignStudents;
    }


}
