<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')->references('id')->on('folders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
