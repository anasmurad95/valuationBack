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
        Schema::create('valuation_sketches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('valuation_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            
            // معلومات الكروكي
            $table->enum('sketch_type', ['image', 'digital_map', 'both'])->default('digital_map');
            $table->string('sketch_image_path')->nullable(); // مسار صورة الكروكي
            $table->json('map_data')->nullable(); // بيانات الخريطة الرقمية
            
            // الإحداثيات والموقع
            $table->decimal('center_latitude', 10, 8)->nullable();
            $table->decimal('center_longitude', 11, 8)->nullable();
            $table->integer('zoom_level')->default(15);
            $table->json('bounds')->nullable(); // حدود المنطقة المعروضة
            
            // نقاط التقييم على الكروكي
            $table->json('valuation_points')->nullable(); // نقاط التقييمات الأخرى في المنطقة
            $table->json('comparable_points')->nullable(); // نقاط العقارات المقارنة
            $table->json('landmarks')->nullable(); // المعالم المهمة
            
            // إعدادات العرض
            $table->json('display_settings')->nullable();
            $table->boolean('show_prices')->default(true);
            $table->boolean('show_valuator_names')->default(true);
            $table->boolean('show_property_types')->default(true);
            $table->boolean('show_dates')->default(true);
            
            // معلومات إضافية
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->foreign('valuation_id')->references('id')->on('valuations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['valuation_id']);
            $table->index(['center_latitude', 'center_longitude']);
            $table->index('sketch_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valuation_sketches');
    }
};

