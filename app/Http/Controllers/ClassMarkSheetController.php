<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentTermMark;
use App\Models\StudentTermDetail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\AcademicTerm;
use App\Models\TermConfiguration;
use App\Models\FormClass;

class ClassMarkSheetController extends Controller
{
    public function download(Request $request){
        $formClassId = $request->form_class_id;
        $academicTermId = $request->academic_term_id;
        $academicYearId = $request->academic_year_id;

        $academicTermRecords = [];

        if($academicTermId)
        {
            $academicTermRecords[] = AcademicTerm::find($academicTermId);
        }

        if($academicYearId)
        {
            $academicTermRecords = AcademicTerm::where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->get();
        }

        $first = true;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach($academicTermRecords as $academicTermRecord)
        {
            $academicYearId = $academicTermRecord->academic_year_id;
            $academicYear = substr($academicYearId, 0, 4).'-'.substr($academicYearId, 4);
            $term = $academicTermRecord->term;
            $data = $this->data($formClassId, $academicTermRecord->id);
            // return $data;
            if(sizeof($data['subjects']) == 0) continue;

            if(!$first)
            {
                $sheet = $spreadsheet->createSheet();
                $index = $spreadsheet->getIndex($sheet);
                $spreadsheet->setActiveSheetIndex($index);
            }
            else $first = false;

            $sheet->setTitle("{$academicYear} Term {$term}");

            $this->generateSpreadsheet($sheet, $data);
        }


        $timestamp = date('Ymd_Hi');
        $file = "{$academicYear}_ClassMarkSheet_{$formClassId}__{$timestamp}.xlsx";
        $filePath = storage_path('app/public/' . $file);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath)->deleteFileAfterSend(true);

    }

    private function generateSpreadsheet($sheet, $data)
    {
        $headers = [
            "Student ID",
            "Last Name",
            "First Name",
        ];

        $subjectsArray = [];

        foreach($data['subjects'] as $subject){
            $headers[] = $subject->abbr ;
            $subjectsArray[] = $subject->abbr;
        }

        $headers[] = "Avg";
        $headers[] = "Marks";
        $headers[] = "Rank";
        $headers[] = "Abs";
        $headers[] = "Late";


        $subjectMarksColStart = 4;

        $colIndex = $subjectMarksColStart;


        foreach($headers as $index => $header)
        {

            if(in_array($header, $subjectsArray)){
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $nextCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->mergeCells("{$col}1:{$nextCol}1");
                $sheet->setCellValue("{$col}1", $header);
                $colIndex += 2;
                $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                continue;
            }

            if($header === "Avg" || $header === "Marks" || $header === "Rank" || $header === "Abs" || $header === "Late"){
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->mergeCells("{$col}1:{$col}2");
                $sheet->setCellValue("{$col}1", $header);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $sheet->getStyle("{$col}1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $colIndex++;
                continue;
            }

            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index+1);
            $sheet->mergeCells("{$col}1:{$col}2");
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        $colIndex = $subjectMarksColStart;
        foreach($subjectsArray as $subject){
            $sheet->setCellValue([$colIndex, 2], "CW");
            $sheet->setCellValue([++$colIndex, 2], "EX");
            $colIndex++;
        }

        $rowIndex=3;
        foreach($data['students'] as $index => $student){
            $colIndex = 1;
            $sheet->setCellValue([$colIndex, $rowIndex], $student['student_id']);
            $sheet->setCellValue([++$colIndex, $rowIndex], $student['last_name']);
            $sheet->setCellValue([++$colIndex, $rowIndex], $student['first_name']);

            foreach($student['marks'] as $marks)
            {
                $sheet->setCellValue([++$colIndex, $rowIndex], $marks['course_mark']);
                $sheet->setCellValue([++$colIndex, $rowIndex], $marks['exam_mark']);
            }

            $sheet->setCellValue([++$colIndex, $rowIndex], $student['average']);
            $sheet->setCellValue([++$colIndex, $rowIndex], $student['total_marks']);
            $sheet->setCellValue([++$colIndex, $rowIndex], $this->rank($student['average'], $data['averages']));
            $sheet->setCellValue([++$colIndex, $rowIndex], $student['sessions_absent']);
            $sheet->setCellValue([++$colIndex, $rowIndex], $student['sessions_late']);

            $rowIndex++;
        }

        $leftAlignFields = [
            "Last Name",
            "First Name",
        ];

        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

         $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => [
                    'argb' => 'FFBFBFBF'
                ]
            ]
        ];

        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray($styleArray);
        $sheet->getStyle('A2:'.$highestColumn.'2')->applyFromArray($styleArray);

        for($col = 1; $col < $subjectMarksColStart; ++$col){
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        for($col = $subjectMarksColStart; $col <= $highestColumnIndex; $col++){
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($column)->setWidth(5);
        }

        for($row = 1; $row <= $highestRow; ++$row){
            $colIndexSubject = $subjectMarksColStart;
            for($col = 1; $col <= $highestColumnIndex; ++$col)
            {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    if(!isset($headers[$col-1]) || !in_array($headers[$col-1], $leftAlignFields))
                    {
                        // Center align all other fields
                        $sheet->getStyle($column.$row)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    if(isset($headers[$col-1]) && in_array($headers[$col-1], $subjectsArray))
                    {
                        $colIndexSubject += 2;
                    }

                    if(isset($headers[$col-1]) && $headers[$col-1] === "Avg")
                    {
                        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndexSubject);
                        $sheet->getStyle($column.$row)
                        ->getNumberFormat()->setFormatCode('#0.0');
                    }
            }
        }
    }

    public function data ($formClassId, $academicTermId)
    {
        $data = [];

        $formClassRecord = FormClass::find($formClassId);
        $formLevel = $formClassRecord ? $formClassRecord->form_level : null;

        $termConfiguration = TErmConfiguration::where('academic_term_id', $academicTermId)
        ->whereNull('form_level')
        ->first();

        $termConfigurationFormLevel = TermConfiguration::where([
            ['academic_term_id', '=', $academicTermId],
            ['form_level', '=', $formLevel]
        ])
        ->first();

        $termConfiguration = $termConfigurationFormLevel ?: $termConfiguration;

        $courseMarkOnly = ($termConfiguration->exam_mark === 0) ? true : false;

        $subjects = StudentTermMark::join(
            'subjects',
            'subjects.id',
            'student_term_marks.subject_id'
        )
        ->join(
            'student_term_details',
            'student_term_details.student_id',
            'student_term_marks.student_id'
        )
        ->select(
            'student_term_marks.subject_id',
            'subjects.title',
            'subjects.abbr'
        )
        ->where([
            ['student_term_details.form_class_id', '=', $formClassId],
            ['student_term_details.academic_term_id', '=', $academicTermId],
            ['student_term_marks.academic_term_id', '=', $academicTermId]
        ])
        ->distinct()
        ->orderBy('subjects.title')
        ->get();

        $students = StudentTermDetail::join(
            'students',
            'students.id',
            'student_term_details.student_id'
        )
        ->select(
            'student_term_details.student_id',
            'students.first_name',
            'students.last_name',
            'student_term_details.sessions_absent',
            'student_term_details.sessions_late'
        )
        ->where([
            ['student_term_details.form_class_id', '=', $formClassId],
            ['student_term_details.academic_term_id', '=', $academicTermId]
        ])
        ->orderBy('students.last_name')
        ->orderBy('students.first_name')
        ->get();

        $studentMarkRecords = [];
        $averages = [];

        foreach($students as $student)
        {
            $studentRecord = [];
            $studentMarks = [];
            $totalMarks = 0;
            $totalSubjects = 0;

            $studentId = $student->student_id;
            $studentRecord['student_id'] = $studentId;
            $studentRecord['last_name'] = $student->last_name;
            $studentRecord['first_name'] = $student->first_name;
            $studentRecord['sessions_absent'] = $student->sessions_absent;
            $studentRecord['sessions_late'] = $student->sessions_late;

            $assesmentAttendanceMap = [ 2 => 'Abs', 3 => 'NW'];

            foreach($subjects as $subject)
            {
                $markRecord = [];
                $subjectId = $subject->subject_id;
                $subjectTitle = $subject->title;

                $markRecord['course_mark'] = "--";
                $markRecord['exam_mark'] = "--";

                $courseMark = StudentTermMark::where([
                    ['student_id', '=', $studentId],
                    ['subject_id', '=', $subjectId],
                    ['academic_term_id', '=', $academicTermId],
                    ['test_id', '=', 2]
                ])->first();

                if($courseMark){
                    $markRecord['course_mark'] = $assesmentAttendanceMap[$courseMark->assesment_attendance_id] ?? $courseMark->mark;
                    if($courseMarkOnly){
                        $totalMarks += $courseMark->mark;
                        $totalSubjects++;
                    }
                }

                $examMark = StudentTermMark::where([
                    ['student_id', '=', $studentId],
                    ['subject_id', '=', $subjectId],
                    ['academic_term_id', '=', $academicTermId],
                    ['test_id', '=', 1]
                ])
                ->first();

                if($examMark){
                    $markRecord['exam_mark'] = $assesmentAttendanceMap[$examMark->assesment_attendance_id] ?? $examMark->mark;
                    if(!$courseMarkOnly)
                    {
                        $totalMarks += $examMark->mark;
                        $totalSubjects++;
                    }
                }

                $studentMarks[] = $markRecord;
            }

            $average = ($totalSubjects > 0) ? number_format($totalMarks / $totalSubjects, 1) : null;
            $averages[] = $average;
            $studentRecord['average'] = $average;
            $studentRecord['total_marks'] = $totalMarks;
            $studentRecord['marks'] = $studentMarks;
            array_push($studentMarkRecords, $studentRecord);
        }
        rsort($averages);
        $data['subjects'] = $subjects;
        $data['students'] = $studentMarkRecords;
        $data['averages'] = $averages;

        return $data;

    }

    private function rank($average, $array){        
        foreach($array as $key => $value){
            if($average == $value){
                return $key+1;
            }
        }
        return null;
    }
}
