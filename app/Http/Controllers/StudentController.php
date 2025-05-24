<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\Teacher;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;


class StudentController extends Controller
{

    use ApiResponseTrait;


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:students',
            'password' => 'required|string|min:6|confirmed',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $teacher = auth('teacher')->user(); // يفترض أنك متأكد أنه من نوع Teacher


        // إنشاء الطالب
        $student = Student::create([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'password' => Hash::make($request->get('password')),
            'is_active' => $request->get('is_active'),
        ]);

        // ربط الطالب بالمعلم المسجل دخوله

        // dd($student);
        // العلاقة many-to-many
        $teacher->students()->attach($student->id);

        $token = JWTAuth::fromUser($student);

        return $this->apiResponse([
            'student' => $student,
            'token' => $token
        ], MessageConstants::STORE_SUCCESS, 201);
    }

    public function updatePassword($id, Request $request)
    {
        $student = student::find($id);

        if (!$student) {
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

        $student->password = Hash::make($request->new_password);
        $student->save();
        return response()->json(['message' => 'student password updated successfully.']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $searcUserhName = $request->input('username');
        $searchName = $request->input('name');

        $totalStudents = Student::count();
        $query = Student::query();

        $query->where(function ($q) use ($searcUserhName, $searchName) {
            if (!empty($searcUserhName)) {
                $q->orWhere('username', 'like', '%' . $searcUserhName . '%');
            }
            if (!empty($searchName)) {
                $q->orWhere('name', 'like', '%' . $searchName . '%');
            }
        });


        $Students = $query->paginate(50);

        $date = [
            'students' => $Students,
            'totalStudents' => $totalStudents
        ];

        return $this->apiResponse($date, MessageConstants::INDEX_SUCCESS, 200);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        return $this->apiResponse($student, MessageConstants::SHOW_SUCCESS, 200);
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
                Rule::unique('students')->ignore($id)
            ],
        ]);

        if ($validator->fails()) {
            // return response()->json($validator->errors()->toJson(), 400);
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $Student = Student::find($id);

        if (!$Student) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $Student->username = $request->username;
        $Student->name = $request->name;
        $Student->save();
        return $this->apiResponse($Student, MessageConstants::UPDATE_SUCCESS, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        $student->delete();

        return $this->apiResponse(null, MessageConstants::DELETE_SUCCESS, 200);
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

        $student = Student::find($id);

        if (!$student) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $student->is_active = $request->is_active;
        $student->save();
        return $this->apiResponse($student, MessageConstants::UPDATE_SUCCESS, 200);
    }


    public function assignStudentToTeacher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|exists:students,username',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        // البحث عن الطالب
        $student = Student::where('username', $request->username)->first();

        // المعلم الحالي (المصادق عليه)
        $teacher = auth('teacher')->user();

        // التأكد أنه لم يُربط من قبل مع هذا المعلم
        if ($teacher->students()->where('student_id', $student->id)->exists()) {
            return $this->apiResponse(
                'This student is already assigned to you.',
                MessageConstants::QUERY_NOT_EXECUTED,
                409
            );
        }

        // الربط في جدول الوسيط
        $teacher->students()->attach($student->id);

        return $this->apiResponse(
            ['student' => $student],
            MessageConstants::STORE_SUCCESS,
            200
        );
    }

    public function Subscriptions()
    {
        $auth= auth('student')->user();
        $StudentAnswerCount = StudentAnswer::where('student_id', $auth->id)->count();

        $pastResults = StudentAnswer::where('student_id', $auth->id)->with('exam')->get();

        $data=[
            'StudentAnswerCount' =>  $StudentAnswerCount,
            'pastResults' =>  $pastResults,
        ];

        return $this->apiResponse($data, MessageConstants::INDEX_SUCCESS, 200);
    }

    public function dashboard()
    {
        $auth = auth('student')->user();

        $StudentAnswerCount = StudentAnswer::where('student_id', $auth->id)->count();
        $pastResults = StudentAnswer::where('student_id', $auth->id)->latest()->take(5)->get();

        $teachers = $auth->teachers;

        // جلب كل الامتحانات التي أجاب عنها الطالب
        $answeredExamIds = StudentAnswer::where('student_id', $auth->id)
            ->pluck('exam_id')
            ->toArray();

        $exams = collect();

        foreach ($teachers as $teacher) {
            // جلب الامتحانات الخاصة بالمعلم والتي لم يتم تقديمها بعد
            $teacherExams = Exam::where('teacher_id', $teacher->id)
                ->whereNotIn('id', $answeredExamIds)
                ->get();

            $exams = $exams->merge($teacherExams);
        }


        $data = [
            'StudentAnswerCount' =>    $StudentAnswerCount,
            'pastResults' =>    $pastResults,
            'exams' =>    $exams
        ];

        return $this->apiResponse($data, MessageConstants::UPDATE_SUCCESS, 200);
    }
}
