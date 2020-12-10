<?php

namespace App\Http\Controllers;

use App\Models\FormClass as ModelsFormClass;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class FormClass extends Controller
{
    public function upload(){
        $file = './files/classes.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        $formClasses = 0;
        
        for($i = 2; $i <= $rows; $i++){
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $formLevel = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            
            $formClass = ModelsFormClass::updateOrCreate(
                ['id' => $id ],
                ['id' => $id, 'form_level' => $formLevel]
            );

            if($formClass->exists) $formClasses++;
        }
        return $formClasses;
    }

    public function show()
    {
        $formClasses = ModelsFormClass::all();
        return $formClasses;
    }
}
