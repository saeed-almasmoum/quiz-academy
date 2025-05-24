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
        Schema::create('files_offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');

            $table->foreignId('category_id');
            $table->foreign('category_id')->references('id')->on('files_categories')->onDelete('cascade');

            $table->string('file');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files_offices');
    }
};
