<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
use App\Models\filesCategory;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilesCategoryController extends Controller
{
    use ApiResponseTrait;


    public function index()
    {
        $student = auth('student')->user();
        $query = filescategory::query();
        // $query->where('teacher_id', $teacher->id);

        $teacherIds = $student->teachers()->pluck('teachers.id')->toArray();

        $query->whereIn('teacher_id', $teacherIds);


        $filescategories = $query->paginate(50);
        return $this->apiResponse($filescategories, MessageConstants::INDEX_SUCCESS, 201);
    }
    /**
     * Display a listing of the resource.
     */
    public function indexTeacher()
    {
        $teacher = auth('teacher')->user();
        $query = filescategory::query();
        $query->where('teacher_id', $teacher->id);


        $filescategories=$query->paginate(50);
        return $this->apiResponse($filescategories, MessageConstants::INDEX_SUCCESS, 201);
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $teacher = auth('teacher')->user();
        // return $request;
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:files_categories,name',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $filescategory=filescategory::create([
            'name' => $request->name,
            'teacher_id' => $teacher->id,
        ]);

        return $this->apiResponse($filescategory, MessageConstants::STORE_SUCCESS, 201);
    }

    /**
     * Display the specified resource.
     */

    public function show($id)
    {
        $filescategory = filescategory::with(['filesOffice', 'teacher'])->find($id);

        if (!$filescategory) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        return $this->apiResponse($filescategory, MessageConstants::SHOW_SUCCESS, 200);
    }
    



    public function update(Request $request, $id)
    {
        $teacher = auth('teacher')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:files_categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $category = filescategory::find($id);

        if (!$category) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $category->update([
            'name' => $request->name,
            'teacher_id' => $teacher->id,

        ]);

        return $this->apiResponse($category, MessageConstants::UPDATE_SUCCESS, 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $filescategory = filescategory::find($id);

        if (!$filescategory) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        $filescategory->delete();

        return $this->apiResponse(null, MessageConstants::DELETE_SUCCESS, 200);  
      }
}
