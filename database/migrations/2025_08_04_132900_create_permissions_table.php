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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name_ar');
            $table->string('display_name_en');
            $table->text('description')->nullable();
            $table->string('module'); // التقييمات، العملاء، المستخدمين، إلخ
            $table->string('action'); // create, read, update, delete, approve, etc.
            $table->string('resource'); // valuations, clients, users, etc.
            $table->timestamps();
            
            $table->index(['module', 'action']);
            $table->index('resource');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

