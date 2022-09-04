<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SixthFormApplication;
use Illuminate\Support\Facades\URL;
use Throwable;

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

     public function storeSixthFormApplicationFile (Request $request)
   {
      // return $request->all();
      $applicationId = $request->input('applicationId');
      $year = $request->input('year');
      $file_type = $request->input('file_type'); 
      //$upload_path = public_path('upload');
      $file_name = $request->file->getClientOriginalName();
      $time = time();
      //$generated_new_name = time() . '.' . $request->file->getClientOriginalExtension();
      $generated_new_name = $applicationId.'_'.$year.'_' . $file_type . '_' . $time . '.' . $request->file->getClientOriginalExtension();
      $picture_name = $applicationId.'_'.$year.'.' . $request->file->getClientOriginalExtension();

      if($file_type == 'picture')
      $request->file->storeAs('public', $picture_name);
      else
      $request->file->storeAs('public', $generated_new_name);

      // return $applicationId;
      $application = SixthFormApplication::where([
         ['application_id', $applicationId],
         ['year', $year]
      ])->first();

      // return $application;

      switch ($file_type){
         case 'birth_certificate';
            $application->birth_certificate = $generated_new_name;
            break;

         case 'results_slip';
            $application->results_slip = $generated_new_name;
            break;

         case 'transfer_form';
            $application->transfer_form = $generated_new_name;
            break;

         case 'picture';
            $application->picture = $picture_name;
            break;

         case 'recommendation_1';
            $application->recommendation_1 = $generated_new_name;
            break;
      }

      $application->save();
      

      return response()->json(['success' => 'You have successfully uploaded "' . $file_name . '"', "file" => $generated_new_name]);
   }

   public function getSixthFormApplicationFiles ($applicationId, $year)
   {
      $data = [];
         
         // $student = Student::where('id', $id)->first();
         $application = SixthFormApplication::where([
            ['application_id', $applicationId],
            ['year', $year]
         ])->first();

         // return $application;

         if($application && $application->birth_certificate){             
            array_push($data, 
               array(
                  'type' => 'birth_certificate', 
                  'url' => URL::asset('storage/'.$application->birth_certificate), 
                  'name' => $application->birth_certificate
               )
            );           
         }
         
         if($application && $application->results_slip){            
            array_push($data, 
               array(
                  'type' => 'results_slip', 
                  'url' => URL::asset('storage/'.$application->results_slip), 
                  'name' => $application->results_slip
               )
            );             
         }
         
         if($application && $application->transfer_form){           
            array_push($data, 
               array(
                  'type' => 'transfer_form', 
                  'url' => URL::asset('storage/'.$application->transfer_form), 
                  'name' => $application->transfer_form
               )
            );   
         }

         if($application && $application->picture){           
            array_push($data, 
               array(
                  'type' => 'picture', 
                  'url' => URL::asset('storage/'.$application->picture), 
                  'name' => $application->picture
               )
            );   
         }

         if($application->recommendation_1){           
            array_push($data, 
               array(
                  'type' => 'recommendation_1', 
                  'url' => URL::asset('storage/'.$application->recommendation_1), 
                  'name' => $application->recommendation_1
               )
            );   
         }

      return $data;
   }
}
