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
        Schema::table('valuations', function (Blueprint $table) {
            // معلومات المقيم المعتمد
            $table->string('valuator_license_number')->nullable()->after('id');
            $table->string('valuator_name')->nullable()->after('valuator_license_number');
            $table->date('valuator_license_date')->nullable()->after('valuator_name');
            $table->text('work_scope')->nullable()->after('valuator_license_date');
            
            // معلومات التقييم المفصلة
            $table->enum('current_usage', ['residential', 'commercial', 'industrial', 'agricultural', 'mixed', 'vacant'])->nullable()->after('work_scope');
            $table->enum('value_hypothesis', ['highest_best_use', 'current_use'])->nullable()->after('current_usage');
            $table->text('valuation_purpose')->nullable()->after('value_hypothesis');
            $table->text('client_relationship')->nullable()->after('valuation_purpose');
            $table->date('inspection_date')->nullable()->after('client_relationship');
            $table->date('assignment_date')->nullable()->after('inspection_date');
            $table->string('reference_number')->nullable()->after('assignment_date');
            $table->enum('report_type', ['comprehensive', 'brief'])->default('comprehensive')->after('reference_number');
            
            // إلى من التقييم
            $table->unsignedBigInteger('to_whom_type_id')->nullable()->after('report_type');
            $table->string('specific_entity')->nullable()->after('to_whom_type_id');
            $table->text('branch_details')->nullable()->after('specific_entity');
            $table->text('additional_notes')->nullable()->after('branch_details');
            $table->enum('urgency_level', ['urgent', 'high', 'medium', 'low'])->default('medium')->after('additional_notes');
            $table->date('expected_delivery_date')->nullable()->after('urgency_level');
            
            // تفاصيل العقار المحدثة
            $table->enum('property_condition', ['new', 'excellent', 'good', 'average', 'needs_maintenance', 'under_construction'])->nullable()->after('expected_delivery_date');
            $table->integer('construction_percentage')->default(0)->after('property_condition');
            $table->integer('building_age')->default(0)->after('construction_percentage');
            $table->integer('total_floors')->default(0)->after('building_age');
            $table->decimal('basement_area', 10, 2)->default(0)->after('total_floors');
            $table->decimal('attachments_area', 10, 2)->default(0)->after('basement_area');
            
            // معلومات الموقع التفصيلية
            $table->string('district')->nullable()->after('attachments_area');
            $table->string('street_name')->nullable()->after('district');
            $table->string('plot_number')->nullable()->after('street_name');
            $table->string('plan_number')->nullable()->after('plot_number');
            $table->string('plan_name')->nullable()->after('plan_number');
            $table->string('block_number')->nullable()->after('plan_name');
            $table->string('location_name')->nullable()->after('block_number');
            $table->text('address_details')->nullable()->after('location_name');
            $table->decimal('latitude', 10, 8)->nullable()->after('address_details');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // معلومات الصك والملكية
            $table->string('owner_name')->nullable()->after('longitude');
            $table->string('deed_number')->nullable()->after('owner_name');
            $table->date('deed_date')->nullable()->after('deed_number');
            $table->enum('ownership_type', ['private', 'government', 'waqf', 'shared'])->nullable()->after('deed_date');
            
            // معلومات البناء
            $table->string('building_license_number')->nullable()->after('ownership_type');
            $table->date('building_license_date')->nullable()->after('building_license_number');
            $table->enum('building_structure', ['reinforced_concrete', 'steel', 'block', 'brick', 'mixed'])->nullable()->after('building_license_date');
            
            // مكونات العقار
            $table->integer('bedrooms_count')->default(0)->after('building_structure');
            $table->integer('living_rooms_count')->default(0)->after('bedrooms_count');
            $table->integer('dining_rooms_count')->default(0)->after('living_rooms_count');
            $table->integer('bathrooms_count')->default(0)->after('dining_rooms_count');
            $table->integer('kitchens_count')->default(0)->after('bathrooms_count');
            $table->integer('maid_rooms_count')->default(0)->after('kitchens_count');
            $table->integer('driver_rooms_count')->default(0)->after('maid_rooms_count');
            $table->integer('parking_spaces')->default(0)->after('driver_rooms_count');
            
            // المرافق الإضافية
            $table->boolean('has_garden')->default(false)->after('parking_spaces');
            $table->boolean('has_pool')->default(false)->after('has_garden');
            $table->boolean('has_elevator')->default(false)->after('has_pool');
            $table->boolean('has_basement')->default(false)->after('has_elevator');
            
            // مستوى التشطيبات
            $table->enum('finishing_level', ['luxury', 'medium', 'basic', 'incomplete'])->nullable()->after('has_basement');
            $table->string('finishing_grade')->nullable()->after('finishing_level');
            $table->text('exterior_finishing')->nullable()->after('finishing_grade');
            $table->text('interior_finishing')->nullable()->after('exterior_finishing');
            
            // الخدمات والمرافق
            $table->boolean('has_electricity')->default(false)->after('interior_finishing');
            $table->boolean('has_water')->default(false)->after('has_electricity');
            $table->boolean('has_sewage')->default(false)->after('has_water');
            $table->boolean('has_phone')->default(false)->after('has_sewage');
            
            // البيئة المحيطة
            $table->json('surroundings')->nullable()->after('has_phone');
            
            // الحدود والأطوال
            $table->json('boundaries')->nullable()->after('surroundings');
            
            // طرق التقييم
            $table->boolean('market_approach_used')->default(false)->after('boundaries');
            $table->boolean('income_approach_used')->default(false)->after('market_approach_used');
            $table->boolean('cost_approach_used')->default(false)->after('income_approach_used');
            
            // بيانات طرق التقييم
            $table->json('market_approach_data')->nullable()->after('cost_approach_used');
            $table->json('income_approach_data')->nullable()->after('market_approach_data');
            $table->json('cost_approach_data')->nullable()->after('income_approach_data');
            $table->json('weighting_data')->nullable()->after('cost_approach_data');
            
            // نتائج التقييم
            $table->decimal('market_value', 15, 2)->nullable()->after('weighting_data');
            $table->decimal('land_value', 15, 2)->nullable()->after('market_value');
            $table->decimal('building_value', 15, 2)->nullable()->after('land_value');
            $table->decimal('final_value', 15, 2)->nullable()->after('building_value');
            $table->text('value_in_words')->nullable()->after('final_value');
            
            // معلومات إضافية
            $table->text('inspection_limitations')->nullable()->after('value_in_words');
            $table->text('special_assumptions')->nullable()->after('inspection_limitations');
            $table->text('report_restrictions')->nullable()->after('special_assumptions');
            
            // إضافة المفاتيح الخارجية
            $table->foreign('to_whom_type_id')->references('id')->on('to_whom_types')->onDelete('set null');
            
            // إضافة فهارس
            $table->index(['to_whom_type_id']);
            $table->index(['urgency_level']);
            $table->index(['property_condition']);
            $table->index(['report_type']);
            $table->index(['expected_delivery_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valuations', function (Blueprint $table) {
            // حذف المفاتيح الخارجية أولاً
            $table->dropForeign(['to_whom_type_id']);
            
            // حذف الفهارس
            $table->dropIndex(['to_whom_type_id']);
            $table->dropIndex(['urgency_level']);
            $table->dropIndex(['property_condition']);
            $table->dropIndex(['report_type']);
            $table->dropIndex(['expected_delivery_date']);
            
            // حذف الأعمدة
            $table->dropColumn([
                'valuator_license_number', 'valuator_name', 'valuator_license_date', 'work_scope',
                'current_usage', 'value_hypothesis', 'valuation_purpose', 'client_relationship',
                'inspection_date', 'assignment_date', 'reference_number', 'report_type',
                'to_whom_type_id', 'specific_entity', 'branch_details', 'additional_notes',
                'urgency_level', 'expected_delivery_date', 'property_condition', 'construction_percentage',
                'building_age', 'total_floors', 'basement_area', 'attachments_area',
                'district', 'street_name', 'plot_number', 'plan_number', 'plan_name', 'block_number',
                'location_name', 'address_details', 'latitude', 'longitude',
                'owner_name', 'deed_number', 'deed_date', 'ownership_type',
                'building_license_number', 'building_license_date', 'building_structure',
                'bedrooms_count', 'living_rooms_count', 'dining_rooms_count', 'bathrooms_count',
                'kitchens_count', 'maid_rooms_count', 'driver_rooms_count', 'parking_spaces',
                'has_garden', 'has_pool', 'has_elevator', 'has_basement',
                'finishing_level', 'finishing_grade', 'exterior_finishing', 'interior_finishing',
                'has_electricity', 'has_water', 'has_sewage', 'has_phone',
                'surroundings', 'boundaries',
                'market_approach_used', 'income_approach_used', 'cost_approach_used',
                'market_approach_data', 'income_approach_data', 'cost_approach_data', 'weighting_data',
                'market_value', 'land_value', 'building_value', 'final_value', 'value_in_words',
                'inspection_limitations', 'special_assumptions', 'report_restrictions'
            ]);
        });
    }
};

