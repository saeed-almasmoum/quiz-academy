<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
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
        $categorySearch=$request->input('namecategory');

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



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:files_categories,id',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse($validator->errors(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $uploadedFile = $request->file('file');

        // الاسم الأصلي مع إضافة التاريخ والوقت
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $uploadedFile->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His'); // تاريخ بصيغة: 20250521_142530
        $fileName = $originalName . '_' . $timestamp . '.' . $extension;

        // حفظ الملف بالاسم الجديد
        $filePath = $uploadedFile->storeAs('files_office', $fileName, 'public');

        // إنشاء السجل
        $file = FilesOffice::create([
            'category_id' => $request->category_id,
            'file' => $filePath,
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
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
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
            if ($fileRecord->file && \Illuminate\Support\Facades\Storage::disk('public')->exists($fileRecord->file)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($fileRecord->file);
            }

            // إعداد الاسم الجديد للملف
            $uploadedFile = $request->file('file');
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $uploadedFile->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $fileName = $originalName . '_' . $timestamp . '.' . $extension;

            // حفظ الملف بالاسم الجديد
            $filePath = $uploadedFile->storeAs('files_office', $fileName, 'public');
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
        $FilesOffice = FilesOffice::find($id);

        if (!$FilesOffice) {
            return $this->apiResponse(null, MessageConstants::NOT_FOUND, 404);
        }
        $FilesOffice->delete();

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
