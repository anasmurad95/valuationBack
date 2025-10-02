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
        Schema::create('valuations', function (Blueprint $table) {
           $table->id();

            // التقييم من أي نوع جهة
            $table->enum('request_source', [
                'phone_call', 'whatsapp', 'social_media',
                'old_client', 'friend', 'office_visit', 'bank'
            ])->nullable();

            // معلومات عن الشخص الذي أحال التقييم
            $table->enum('referred_by_type', ['client', 'user'])->nullable();
            $table->unsignedBigInteger('referred_by_id')->nullable();
            // branches
            $table->string('source_details')->nullable();
            // العميل المرتبط بالتقييم
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null')->nullable();

            // الموظف الذي أعد التقييم
            $table->foreignId('prepared_by')->constrained('users')->onDelete('cascade')->nullable();

            // الموظف المسؤول عن الزيارة الميدانية
            $table->foreignId('inspected_by')->nullable()->constrained('users')->onDelete('set null');

            // نوع الجهة التي يتم التقييم لها
            // $table->foreignId('to_whom_type_id')->nullable()->constrained('to_whom_types')->onDelete('set null');

            // نوع العقار
            $table->foreignId('property_type')->nullable()->constrained('users')->onDelete('set null');
           
            // استخدام الأرض أو العقار
            $table->enum('property_usage', [
                'residential', 'commercial', 'residential_commercial', 'industrial', 'agricultural', 'touristic', 'leased'
            ])->nullable();

            // وصف العقار
            $table->text('property_description')->nullable();

            // تفاصيل العنوان
            // $table->string('location_name')->nullable();
            $table->string('coordinates')->nullable(); // إحداثيات على الخريطة
            $table->string('krooki_path')->nullable(); // صورة كروكي
            // $table->text('address_details')->nullable();
            // $table->decimal('latitude', 10, 7)->nullable();
            // $table->decimal('longitude', 10, 7)->nullable();

            // تاريخ التقييم وزيارة الموقع
            $table->dateTime('site_visit_date')->nullable();
            $table->dateTime('report_submitted_at')->nullable();
            $table->dateTime('report_approved_at')->nullable();

            // حالة التقييم
            $table->enum('status', [
                'draft','pending', 'in_progress', 'submitted', 'approved', 'rejected'
            ])->default('pending');

            // رقم التقييم
            $table->string('valuation_number')->unique();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valuations');
    }
};
