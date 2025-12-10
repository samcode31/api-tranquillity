<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\StudentClassRegistration;
use App\Models\StudentTermDetail;

class DbFixesController extends Controller
{
    public function fixStudentTermDetails ()
    {
        $academicYearId = null;
        $academicTermId = null;
        $academicTerm = AcademicTerm::where('is_current',1)
        ->first();
        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
            $academicTermId = $academicTerm->id;
        }
        // return $academicYearId;

        // $studentClassRegistrations = StudentClassRegistration::where([
        //     ['academic_year_id', $academicYearId],
        //     // ['form_class_id', 'like', '4%']
        // ])->get();

        // return $studentClassRegistrations;


        $studentTermDetails = StudentTermDetail::where([
            ['academic_term_id', $academicTermId],
            // ['form_class_id', 'like', '4%']
        ])->get();

        $updates = 0;

        foreach($studentTermDetails as $studentTermDetail){
            $studentClassRegistration = StudentClassRegistration::where([
                ['academic_year_id', $academicYearId],
                ['student_id', $studentTermDetail->student_id]
            ])->first();

            if($studentClassRegistration){
                $studentTermDetail->form_class_id = $studentClassRegistration->form_class_id;
                $studentTermDetail->save();
                if($studentTermDetail->wasChanged()) $updates++;
            }
        }

        return $updates;
    }
}
