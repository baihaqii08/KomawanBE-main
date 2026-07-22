<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('folder_id')->nullable();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension');
            $table->unsignedBigInteger('size');
            $table->string('storage_path');
            $table->string('public_url')->nullable();
            $table->string('visibility')->default('private');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('folder_id')->references('id')->on('folders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
