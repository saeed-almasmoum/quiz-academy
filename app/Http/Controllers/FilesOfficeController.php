<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
use App\Models\filesCategory;
use App\Models\FilesOffice;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilesOfficeController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $student = auth('student')->user();
        $categorySearch = $request->input('categoryName');
        $teacherSearch = $request->input('teacherName');

        // 1. الحصول على معرفات المعلمين المرتبطين بالطالب
        $teacherIds = $student->teachers()->pluck('teachers.id')->toArray();

        // 2. إنشاء استعلام الملفات
        $query = FilesOffice::with(['filesCategory', 'teacher']);

        // 3. تصفية حسب المعلمين المرتبطين
        $query->whereIn('teacher_id', $teacherIds);

        // 4. تصفية حسب البحث (تصنيف أو اسم المعلم)
        $query->where(function ($q) use ($categorySearch, $teacherSearch) {
            if (!empty($categorySearch)) {
                $q->whereHas('filesCategory', function ($q) use ($categorySearch) {
                    $q->where('name', 'like', '%' . $categorySearch . '%');
                });
            }

            if (!empty($teacherSearch)) {
                $q->whereHas('teacher', function ($q) use ($teacherSearch) {
                    $q->where('name', 'like', '%' . $teacherSearch . '%');
                });
            }
        });

        $files = $query->paginate(50);

        return $this->apiResponse($files, MessageConstants::INDEX_SUCCESS, 201);
    }

    public function indexTeacher(Request $request)
    {
        $teacher = auth('teacher')->user();
        $categorySearch = $request->input('categoryName');
        $teacherSearch = $request->input('teacherName');

        // 1. الحصول على معرفات المعلمين المرتبطين بالطالب
        // $teacherIds = $student->teachers()->pluck('teachers.id')->toArray();

        // 2. إنشاء استعلام الملفات
        $query = FilesOffice::with(['filesCategory', 'teacher']);
        // dd()
        // 3. تصفية حسب المعلمين المرتبطين
        $query->where('teacher_id', $teacher->id);

        // 4. تصفية حسب البحث (تصنيف أو اسم المعلم)
        $query->where(function ($q) use ($categorySearch, $teacherSearch) {
            if (!empty($categorySearch)) {
                $q->whereHas('filesCategory', function ($q) use ($categorySearch) {
                    $q->where('name', 'like', '%' . $categorySearch . '%');
                });
            }

            if (!empty($teacherSearch)) {
                $q->whereHas('teacher', function ($q) use ($teacherSearch) {
                    $q->where('name', 'like', '%' . $teacherSearch . '%');
                });
            }
        });

        $files = $query->paginate(50);

        return $this->apiResponse($files, MessageConstants::INDEX_SUCCESS, 201);
    }


    public function resourceData()
    {
        $student = auth('student')->user();

        $category = FilesCategory::get();
        $teachers=null ; 
        if($student)
        // جلب المعلمين المرتبطين بهذا الطالب
        $teachers = $student->teachers;

        return response()->json([
            'categories' => $category,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:files_categories,id',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        // $uploadedFile = $request->file('file');
        // $newName = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
        // $uploadedFile->move(public_path('files_office'), $newName);
        // $filePath = 'files_office/' . $newName;

        $uploadedFile = $request->file('file');

        // استخراج الاسم الأصلي بدون الامتداد
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        // تنسيق الوقت الحالي
        $timestamp = now()->format('Y-m-d_H-i-s');

        // استخراج الامتداد
        $extension = $uploadedFile->getClientOriginalExtension();

        // إنشاء الاسم الجديد
        $newName = $originalName . '_' . $timestamp . '.' . $extension;

        // نقل الملف
        $uploadedFile->move(public_path('files_office'), $newName);

        // حفظ المسار
        $filePath = 'files_office/' . $newName;

        // إنشاء السجل
        $file = FilesOffice::create([
            'category_id' => $request->category_id,
            'file' => $filePath,
            'teacher_id' =>auth('teacher')->user()->id,
            // $teacher = auth('teacher')->user();

        ]);

        return $this->apiResponse($file, MessageConstants::STORE_SUCCESS, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $FilesOffice = FilesOffice::with(['filesCategory'])->find($id);

        if (!$FilesOffice) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        return $this->apiResponse($FilesOffice, MessageConstants::SHOW_SUCCESS, 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:files_categories,id',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $fileRecord = FilesOffice::find($id);

        if (!$fileRecord) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        $data = [
            'category_id' => $request->category_id,
        ];

        if ($request->hasFile('file')) {
            // حذف الملف القديم إذا كان موجودًا
            $oldPath = public_path($fileRecord->file);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }

            // $uploadedFile = $request->file('file');
            // $newName = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
            // $uploadedFile->move(public_path('files_office'), $newName);
            // $filePath = 'files_office/' . $newName;

            $uploadedFile = $request->file('file');

            // استخراج الاسم الأصلي بدون الامتداد
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

            // تنسيق الوقت الحالي
            $timestamp = now()->format('Y-m-d_H-i-s');

            // استخراج الامتداد
            $extension = $uploadedFile->getClientOriginalExtension();

            // إنشاء الاسم الجديد
            $newName = $originalName . '_' . $timestamp . '.' . $extension;

            // نقل الملف
            $uploadedFile->move(public_path('files_office'), $newName);

            // حفظ المسار
            $filePath = 'files_office/' . $newName;

            // تحديث مسار الملف الجديد
            $data['file'] = $filePath;
        }

        $fileRecord->update($data);

        return $this->apiResponse($fileRecord, MessageConstants::UPDATE_SUCCESS, 200);
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $fileRecord = FilesOffice::find($id);

        if (!$fileRecord) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }

        // حذف الملف من المجلد إن وُجد
        $filePath = public_path($fileRecord->file);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // حذف السجل من قاعدة البيانات
        $fileRecord->delete();

        return $this->apiResponse(null, MessageConstants::DELETE_SUCCESS, 200);
    }

    public function indexToStudent(Request $request)
    {
        $categorySearch = $request->input('namecategory');
        $categorySearch = $request->input('namecategory');

        $query = FilesOffice::with('filesCategory');

        $query->where(function ($q) use ($categorySearch) {
            if (!empty($categorySearch)) {
                $q->whereHas('filesCategory', function ($q) use ($categorySearch) {
                    $q->where('name', 'like', '%' . $categorySearch . '%');
                });
            }
        });

        $exams = $query->paginate(50);
        return $this->apiResponse($exams, MessageConstants::INDEX_SUCCESS, 201);
    }
}
