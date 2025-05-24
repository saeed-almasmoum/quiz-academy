<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
use App\Models\Student;
use App\Models\Teacher;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;



class TeacherController extends Controller
{
    use ApiResponseTrait;
    public function register(Request $request)
    {

        // if (!(auth()->user() instanceof \App\Models\User)) {
        //     return $this->apiResponse( 'Unauthorized: Only users can add teachers', MessageConstants::QUERY_NOT_EXECUTED, 403);
        // }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:teachers',
            'password' => 'required|string|min:6|confirmed',
            'is_active' => 'required|boolean',

        ]);

        if ($validator->fails()) {
            // return response()->json($validator->errors()->toJson(), 400);
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $Teacher = Teacher::create([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'password' => Hash::make($request->get('password')),
            'is_active' => $request->get('is_active'),
        ]);

        $token = JWTAuth::fromUser($Teacher);

        return $this->apiResponse(['Teacher' => $Teacher], MessageConstants::STORE_SUCCESS, 201);
    }


    public function updatePassword($id, Request $request)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $teacher->password = Hash::make($request->new_password);
        $teacher->save();
        return response()->json(['message' => 'Teacher password updated successfully.']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $searcUserhName = $request->input('username');
        $searchName = $request->input('name');

        $totalTeachers = Teacher::count();
        $query = Teacher::with('students')->withCount('students');

        $query->where(function ($q) use ($searcUserhName, $searchName) {
            if (!empty($searcUserhName)) {
                $q->orWhere('username', 'like', '%' . $searcUserhName . '%');
            }
            if (!empty($searchName)) {
                $q->orWhere('name', 'like', '%' . $searchName . '%');
            }
        });


        $teachers = $query->paginate(50);

        $date = [
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers
        ];

        return $this->apiResponse($date, MessageConstants::INDEX_SUCCESS, 200);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        return $this->apiResponse($teacher, MessageConstants::SHOW_SUCCESS, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teachers')->ignore($id)
            ],
        ]);

        if ($validator->fails()) {
            // return response()->json($validator->errors()->toJson(), 400);
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $teacher = Teacher::find($id);

        if (!$teacher) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $teacher->username = $request->username;
        $teacher->name = $request->name;
        $teacher->save();
        return $this->apiResponse($teacher, MessageConstants::UPDATE_SUCCESS, 200);
    }

    public function updateIsActive(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ],);

        if ($validator->fails()) {
            // return response()->json($validator->errors()->toJson(), 400);
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $teacher = Teacher::find($id);

        if (!$teacher) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $teacher->is_active = $request->is_active;
        $teacher->save();
        return $this->apiResponse($teacher, MessageConstants::UPDATE_SUCCESS, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        $teacher->delete();

        return $this->apiResponse(null, MessageConstants::DELETE_SUCCESS, 200);
    }

    public function dashboard()
    {
        $auth = auth('teacher')->user();
        $studentCount = $auth->students()->count(); // عدد الطلاب
        $students = $auth->students()->latest()->take(5)->get(); // جميع الطلاب المرتبطين

        $examCount = $auth->exams()->count();
        $latestExams = $auth->exams()->latest()->take(5)->get();

        $date = [
            'studentCount' => $studentCount,
            'students' => $students,
            'examCount' => $examCount,
            'exams' => $latestExams
        ];
        return $this->apiResponse($date, MessageConstants::INDEX_SUCCESS, 200);
    }
}
