<?php

namespace App\Http\Controllers;

use App\Http\Resources\Table1 as ResourcesTable1;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Table1;
use App\Models\FormClass;
use Illuminate\Http\Request;

class Table1Controller extends Controller
{
    public function register()
    {
        $currentPeriod = AcademicTerm::whereIsCurrent(1)->first();               
        $year = substr($currentPeriod->academic_year_id, 0, 4);
        $term = $currentPeriod->term;
        $newTermBeginning = $currentPeriod->new_term_beginning;
        $possibleAttendance = $currentPeriod->possible_attendance;        
        $students = Student::select('id', 'class_id')->get();
        $registered = 0;
        foreach($students as $student)
        {
            $studentId = $student->id;
            $classId = $student->class_id;
            $table1Record = Table1::updateOrCreate(
                ['student_id' => $studentId, 'year' => $year, 'term' => $term, ],
                [
                    'student_id' => $studentId, 
                    'year' => $year, 
                    'term' => $term, 
                    'class_id' => $classId,
                    'new_term_beginning' => $newTermBeginning,
                    'possible_attendance' => $possibleAttendance
                ]
            );
            if($table1Record->wasRecentlyCreated) $registered++;

        }
        return $registered;
    }

    public function show($year, $term, $class){
        $records = Table1::join('students', 'students.id', 'table1.student_id')
        ->select('table1.*','students.first_name', 'students.last_name', 'students.picture')
        ->where([
            ['year', $year],
            ['term', $term],
            ['table1.class_id', $class]
        ])
        ->orderBy('last_name')
        ->paginate(1);

        return ResourcesTable1::collection($records);        
    }

    public function cmp($a, $b){
        return strcmp($a->last_name, $b->last_name);
    }

    public function formClasses()
    {
        $currentPeriod = AcademicTerm::whereIsCurrent(1)->first();               
        $year = substr($currentPeriod->academic_year_id, 0, 4);
        $term = $currentPeriod->term;
        $formClasses = Table1::select('class_id')
        ->where([
            ['year', $year],
            ['term', $term],
        ])->distinct()->addSelect(['form_level' => FormClass::select('form_level')
        ->whereColumn('id', 'table1.class_id')])
        ->orderBy('form_level')
        ->get();
        
        return $formClasses;
    }

    public function store(Request $request)
    {
        $record = Table1::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
            ],
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
                'class_id' => $request->class_id,
                'new_term_beginning' => $request->new_term_beginning,
                'possible_attendance' => $request->possible_attendance,
                'times_absent' => $request->times_absent,
                'times_late' => $request->times_late,
                'comments' => $request->comments,
                'work' => $request->work,
                'conduct' => $request->conduct,
                'detentions' => $request->detentions,
            ]
        );

        return $record;
    }

    public function termRecords($year, $term){
        $records = Table1::where([
            'year' => $year,
            'term' => $term
        ])->get();

        return $records;
    }
}
