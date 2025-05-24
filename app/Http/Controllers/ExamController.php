<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class ExamController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $is_active = $request->input('is_active');

        $query = Exam::withCount('questions');

        $query->where(function ($q) use ($is_active) {
            if (!empty($is_active)) {
                $q->orWhere('is_active', 'like', '%' . $is_active . '%');
            }
        });

        $exams = $query->paginate(50);

        $date = [
            'exams' => $exams,
        ];

        return $this->apiResponse($date, MessageConstants::INDEX_SUCCESS, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'attempt_limit' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'allow_review' => 'boolean',
            'is_scheduled' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            // 'questions.*.type' => 'required|in:multiple_choice,true_false',
            'questions.*.answers' => 'required|array|min:2',
            'questions.*.answers.*.text' => 'required|string',
            'questions.*.answers.*.is_correct' => 'nullable|boolean',
            'questions.*.image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        DB::beginTransaction();

        try {
            $isScheduled = $request->is_scheduled;
            $isActive = $request->is_active;

            // إذا كان الامتحان مجدول، نحدد إن كان نشط بناء على التاريخ
            if ($isScheduled) {
                $now = now();
                $startAt = $request->start_at ? Carbon::parse($request->start_at) : null;
                $endAt = $request->end_at ? Carbon::parse($request->end_at) : null;

                if ($startAt && $endAt && $now->between($startAt, $endAt)) {
                    $isActive = true;
                    // dd($startAt);
                } else {
                    $isActive = false;
                }
            }
            // return $request;
            $exam = Exam::create([
                'title' => $request->title,
                'description' => $request->description,
                'duration_minutes' => $request->duration_minutes,
                'is_active' => $isActive,
                'allow_review' => $request->allow_review,
                'is_scheduled' => $isScheduled,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'attempt_limit' => $request->attempt_limit,
                'teacher_id' => auth('teacher')->user()->id,
            ]);
            // dd($exam);

            foreach ($request->questions as $index => $qData) {
                // رفع الصورة إن وجدت
                $imagePath = null;
                if ($request->hasFile("questions.$index.image")) {
                    $imagePath = $request->file("questions.$index.image")->store('questions_images', 'public');
                }

                $question = Question::create([
                    'text' => $qData['text'],
                    // 'type' => $qData['type'],
                    'image' => $imagePath,
                    'exam_id' => $exam->id,
                ]);



                // إضافة الإجابات
                foreach ($qData['answers'] as $answer) {
                    Answer::create([
                        'question_id' => $question->id,
                        'text' => $answer['text'],
                        'is_correct' => $answer['is_correct'],
                    ]);
                }
            }

            DB::commit();

            return $this->apiResponse($exam->load('questions.answers'), MessageConstants::STORE_SUCCESS, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse(['error' => $e->getMessage()], MessageConstants::QUERY_NOT_EXECUTED, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $student = auth('student')->user();
        $exam = Exam::with(['questions.answers'])->find($id);

        $exam['attemptsCount'] = StudentAnswer::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->distinct('created_at') // أو session id لو عندك
            ->count('created_at'); // أو use GROUP BY later
        if (!$exam) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        return $this->apiResponse($exam, MessageConstants::SHOW_SUCCESS, 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attempt_limit' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'allow_review' => 'boolean',
            'is_scheduled' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            // 'questions.*.type' => 'required|in:multiple_choice,true_false',
            'questions.*.answers' => 'required|array|min:2',
            'questions.*.answers.*.text' => 'required|string',
            'questions.*.answers.*.is_correct' => 'nullable|boolean',
            'questions.*.image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        DB::beginTransaction();

        try {
            $exam = Exam::find($id);

            if (!$exam) {
                return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
            }

            $isScheduled = $request->is_scheduled;
            $isActive = $request->is_active;

            if ($isScheduled) {
                $now = now();
                $startAt = $request->start_at ? Carbon::parse($request->start_at) : null;
                $endAt = $request->end_at ? Carbon::parse($request->end_at) : null;

                if ($startAt && $endAt && $now->between($startAt, $endAt)) {
                    $isActive = true;
                    // dd($startAt);
                } else {
                    $isActive = false;
                }
            }

            $exam->update([
                'title' => $request->title,
                'description' => $request->description,
                'duration_minutes' => $request->duration_minutes,
                'is_active' => $isActive,
                'allow_review' => $request->allow_review,
                'is_scheduled' => $isScheduled,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'attempt_limit' => $request->attempt_limit,

            ]);

            // حذف الأسئلة والإجابات القديمة
            foreach ($exam->questions as $question) {
                $question->answers()->delete();
                $question->delete();
            }

            // إعادة إضافة الأسئلة والإجابات
            foreach ($request->questions as $index => $qData) {
                $imagePath = null;
                if ($request->hasFile("questions.$index.image")) {
                    $imagePath = $request->file("questions.$index.image")->store('questions_images', 'public');
                }

                $question = Question::create([
                    'text' => $qData['text'],
                    'type' => $qData['type'],
                    'image' => $imagePath,
                    'exam_id' => $exam->id,
                ]);

                foreach ($qData['answers'] as $answer) {
                    Answer::create([
                        'question_id' => $question->id,
                        'text' => $answer['text'],
                        'is_correct' => $answer['is_correct'],
                    ]);
                }
            }

            DB::commit();

            return $this->apiResponse($exam->load('questions.answers'), MessageConstants::UPDATE_SUCCESS, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse(['error' => $e->getMessage()], MessageConstants::QUERY_NOT_EXECUTED, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        $exam->delete();

        return $this->apiResponse(null, MessageConstants::DELETE_SUCCESS, 200);
    }

    public function ActiveExams(Request $request)
    {

        $student=auth('student')->user();
        $titlesearch = $request->input('title');

        $query = Exam::withCount('questions');

        $query->where(function ($q) use ($titlesearch) {
            if (!empty($titlesearch)) {
                $q->orWhere('title', 'like', '%' . $titlesearch . '%');
            }
        });


        
       

        $exams = $query->paginate(50);

        foreach($exams as $exam)
        {
            $exam['attemptsCount'] = StudentAnswer::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->distinct('created_at') // أو session id لو عندك
                ->count('created_at'); // أو use GROUP BY later
        }
        $date = [
            'exams' => $exams,
        ];

        return $this->apiResponse($date, MessageConstants::INDEX_SUCCESS, 200);
    }


    public function startExam($id)
    {

        $student = auth('student')->user();
 
        $exam = Exam::with(['questions.answers'])->find($id);

        if (!$exam) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $attemptsCount = StudentAnswer::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->distinct('created_at') // أو session id لو عندك
            ->count('created_at'); // أو use GROUP BY later

        if ($exam->attempt_limit !== null && $attemptsCount >= $exam->attempt_limit) {
            return $this->apiResponse(null, 'You have reached the maximum number of attempts for this exam.', 403);
        }

        // تعديل كل إجابة: اجعل is_correct = false مؤقتًا
        foreach ($exam->questions as $question) {
            foreach ($question->answers as $answer) {
                $answer->is_correct = false;
            }
        }

        // ارجع الامتحان كامل مع الأسئلة والأجوبة لكن is_correct كلها false
        return $this->apiResponse($exam, MessageConstants::SHOW_SUCCESS, 200);
    }

    public function submitExam(Request $request, $id)
    {
        $student = auth('student')->user();

        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'required|integer|exists:questions,id',
            'questions.*.answers' => 'required|array|min:1',
            'questions.*.answers.*.id' => 'required|integer|exists:answers,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $exam = Exam::with('questions.answers')->find($id);

        if (!$exam) {
            return $this->apiResponse(null, 'Exam not found', 404);
        }
        $attemptsCount = StudentAnswer::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->distinct('created_at') // أو session id لو عندك
            ->count('created_at'); // أو use GROUP BY later

        if ($exam->attempt_limit !== null && $attemptsCount >= $exam->attempt_limit) {
            return $this->apiResponse(null, 'You have reached the maximum number of attempts for this exam.', 403);
        }




        $submittedQuestions = collect($request->input('questions'));
        $result = [];
        $score = 0;

        foreach ($exam->questions as $question) {
            // جلب الإجابة التي اختارها الطالب لهذا السؤال
            $submittedQuestion = $submittedQuestions->firstWhere('id', $question->id);
            $selectedAnswerId = $submittedQuestion['answers'][0]['id'] ?? null;

            // جلب الجواب الصحيح من قاعدة البيانات
            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            $isCorrect = $selectedAnswerId == $correctAnswer->id;

            if ($isCorrect) {
                $score++;
            }

            // حفظ إجابة الطالب
            StudentAnswer::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'question_id' => $question->id,
                'answer_id' => $selectedAnswerId,
                'score' => $score,
                'total_questions' =>$exam->questions->count(),
            ]);

            $result[] = [
                'question_id' => $question->id,
                'question_text' => $question->text,
                'selected_answer_id' => $selectedAnswerId,
                'is_correct' => $isCorrect,
                'correct_answer_id' => $correctAnswer->id,
                'answers' => $question->answers->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'text' => $a->text,
                        'is_correct' => $a->is_correct,
                    ];
                })
            ];
        }

        return $this->apiResponse([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'score' => $score,
            'total_questions' => $exam->questions->count(),
            'details' => $result
        ], 'Exam submitted and corrected successfully', 200);
    }


    public function reviewExam($examId)
    {
        $student = auth('student')->user();

        $exam = Exam::with('questions.answers')->find($examId);
        if (!$exam) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        if(!$exam->allow_review)
        {
            return $this->apiResponse(null, 'this exams does not allow to review it', 403);
        }

        $result = [];

        foreach ($exam->questions as $question) {
            $studentAnswer = StudentAnswer::where('student_id', $student->id)
                ->where('exam_id', $examId)
                ->where('question_id', $question->id)
                ->first();

            $selectedAnswerId = $studentAnswer->answer_id ?? null;
            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            $isCorrect = $selectedAnswerId == $correctAnswer->id;

            $result[] = [
                'question_id' => $question->id,
                'question_text' => $question->text,
                'answers' => $question->answers->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'text' => $a->text,
                        'is_correct' => $a->is_correct
                    ];
                }),
                'selected_answer_id' => $selectedAnswerId,
                'correct_answer_id' => $correctAnswer->id,
                'is_correct' => $isCorrect
            ];
        }

        return $this->apiResponse([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'review' => $result
        ], 'Review loaded successfully',200);
    }

    public function getStudentAnswers()
    {
        $student = auth('student')->user();
        // dd($student);

        $answerStudent= StudentAnswer::with([
            'student',
            'exam.questions.answers',
            // 'question',
            'answer',
        ])->where('student_id', $student->id)->get();

        return $this->apiResponse($answerStudent, MessageConstants::INDEX_SUCCESS, 200);
    }


    public function getStudentAnswerById($id)
    {
        $student = auth('student')->user();
        // dd($student);

        $answerStudent = StudentAnswer::with([
            'student',
            'exam.questions.answers',
            // 'question',
            'answer',
        ])->where('student_id', $student->id)->where('id',$id)->first();

        return $this->apiResponse($answerStudent, MessageConstants::INDEX_SUCCESS, 200);
    }

    
}
