<?php

namespace App\Jobs;

use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateExamActiveStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = Carbon::now();

        // اجعل الامتحانات المجدولة نشطة إذا كنا داخل المدة المحددة
        Exam::where('is_scheduled', true)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where('is_active', false)
            ->update(['is_active' => true]);

        // اجعل الامتحانات المجدولة غير نشطة إذا انتهت مدتها
        Exam::where('is_scheduled', true)
            ->where('end_at', '<', $now)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
