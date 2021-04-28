<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm as ModelsAcademicTerm;
use App\Models\AcademicYear;
use App\Models\StudentTermDetail;
use Illuminate\Http\Request;


class AcademicTerm extends Controller
{
    
    public function show()
    {
        return ModelsAcademicTerm::whereIsCurrent(1)->first();        
    }

    public function showNextTerm()
    {
        $currentTerm = ModelsAcademicTerm::whereIsCurrent(1)->first();
        $term = $currentTerm->term;
        $nextTerm = $term == 3 ? 1 : $term + 1;
        $academic_year_id = $currentTerm->academic_year_id;
        if($nextTerm == 1){
            $term_year = date_format(date_create($currentTerm->term_start), 'Y');
            $next_term_year = $term_year + 1;
            $academic_year_id = $term_year.$next_term_year;
        }
        
        return ModelsAcademicTerm::where([
            ['academic_year_id', $academic_year_id],
            ['term', $nextTerm]
        ])->first();
    }

    public function store(Request $request)
    {
        $data = [];
        
        ModelsAcademicTerm::where('id', '>', 1)->update(['is_current' => 0]);

        $term = $request->term;

        $term_start = $request->term_start;

        $term_end = $request->term_end;

        $total_sessions = $request->total_sessions;

        $next_term_start = $request->next_term_start;

        $term_year = date_format(date_create($term_start), 'Y');

        $academic_term_id = ($term == 1) ? $term_year.'0'.$term : ($term_year - 1).'0'.$term;

        $academic_year_id = ($term == 1) ? $term_year.($term_year + 1) : ($term_year - 1).$term_year;        

        $academic_year = AcademicYear::where('id', $academic_year_id)->first();        

        if($academic_year && $term == 3) $academic_year->end = $term_end;

        elseif($academic_year && $term == 1) $academic_year->start = $term_start;

        elseif(!$academic_year && $term == 1){
            AcademicYear::updateOrCreate(
                [ 'id' => $academic_year_id ],
                [ 'start' => $term_start ]
            );
        }

        elseif(!$academic_year && $term == 3){
            AcademicYear::updateOrCreate(
                ['id' => $academic_year_id ],
                ['end' => $term_end ]
            );
        }

        elseif(!$academic_year){
            AcademicYear::updateOrCreate([
                'id' => $academic_year_id                
            ]);
        }

        $current_term = ModelsAcademicTerm::updateOrCreate(
            ['id' => $academic_term_id],
            [
                'id' => $academic_term_id,
                'academic_year_id' => $academic_year_id,
                'term' => $term,
                'term_start' => $term_start,
                'term_end' => $term_end,
                'total_sessions' => $total_sessions,
                'is_current' => 1
            ]
        );

        $data['current_term'] = $current_term;

        $next_academic_year_id = $term == 3 ? $term_year.($term_year + 1) : $academic_year_id;

        $next_academic_term_id = $term == 3 ? $term_year.'01' : $academic_term_id + 1;       
        
        if($term == 3){
            AcademicYear::updateOrCreate(
                ['id' => $next_academic_year_id],
                ['start' => $next_term_start]
            );
        }

        $term = $term == 3 ? 1 : ($term + 1);

        $next_term = ModelsAcademicTerm::updateOrCreate(
            ['id' => $next_academic_term_id],
            [
                'id' => $next_academic_term_id,
                'academic_year_id' => $next_academic_year_id,
                'term' => $term,
                'term_start' => $next_term_start,
            ]
        );

        $data['next_term'] = $next_term;

        return $data;
    }

    public function termConfiguration(){
        $current_academic_term_id = ModelsAcademicTerm::where('is_current', 1)
        ->first()
        ->id;

        return $current_academic_term_id;

    }

    public function showHistory()
    {       
        $data = [];

        $academic_terms = StudentTermDetail::select('academic_term_id')
        ->distinct()
        ->orderBy('academic_term_id', 'desc')
        ->get();        

        foreach($academic_terms as $term){
            $academic_term = ModelsAcademicTerm::join('academic_years', 'academic_years.id', 'academic_terms.academic_year_id')
            ->select('academic_terms.*', 'academic_years.start', 'academic_years.end')
            ->where('academic_terms.id', $term->academic_term_id)
            ->first();

            $termRecord = [];
            $termRecord["id"] = $academic_term->id;
            $termRecord["term"] = $academic_term->term;
            $termRecord["period"] = date_format(date_create($academic_term->term_start), "j M Y").' - '.date_format(date_create($academic_term->term_end), "j M Y");
            $termRecord["current"] = $academic_term->is_current;
            $termRecord["year"] = date_format(date_create($academic_term->start), "Y").'-'.date_format(date_create($academic_term->end), "Y");            
            array_push($data, $termRecord);
        }        
       
        return $data;
    }

    public function backdateTerm(Request $request)
    {
        ModelsAcademicTerm::where('id', '>', 1)->update(['is_current' => 0]);

        $academic_term = ModelsAcademicTerm::where('id', $request->academic_term_id)
        ->first();

        if($academic_term){
            $academic_term->update(['is_current' => 1]);
            return $academic_term;
        }

        return abort(500);
       
    }
}
