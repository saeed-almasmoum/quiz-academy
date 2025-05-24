<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            $table->string('title'); 
            $table->string('attempt_limit'); 
            $table->text('description')->nullable(); 

            $table->integer('duration_minutes')->nullable();

            $table->boolean('is_active')->default(false); // يمكن التحكم به يدويًا فقط إذا لم يكن مجدول
            $table->boolean('allow_review')->default(false); 
            $table->boolean('is_scheduled')->default(false); 

            $table->timestamp('start_at')->nullable(); 
            $table->timestamp('end_at')->nullable();
            
            $table->foreignId('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');

            $table->timestamps(); // created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
