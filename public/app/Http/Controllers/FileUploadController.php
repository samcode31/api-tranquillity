<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
     // function to store file in 'upload' folder
     public function fileStore(Request $request)
     {
        $id = $request->input('id');
        $file_type = $request->input('doc_name'); 
        $upload_path = public_path('upload');
        $file_name = $request->file->getClientOriginalName();
        $time = time();
        //$generated_new_name = time() . '.' . $request->file->getClientOriginalExtension();
        $generated_new_name = $id . '_' . $file_type . '_' . $time . '.' . $request->file->getClientOriginalExtension();
        $request->file->move($upload_path, $generated_new_name); 
        

        return response()->json(['success' => 'You have successfully uploaded "' . $file_name . '"', "file" => $generated_new_name]);
     }
}
