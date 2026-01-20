<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = DB::connection();
        
        // 1. إزالة unique constraint من clinics إذا كان موجوداً
        if (Schema::hasColumn('clinics', 'phone')) {
            $constraintExists = $connection->selectOne("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'clinics' 
                AND constraint_type = 'UNIQUE' 
                AND constraint_name LIKE '%phone%'
            ");
            
            if ($constraintExists) {
                Schema::table('clinics', function (Blueprint $table) use ($constraintExists) {
                    $table->dropUnique([$constraintExists->constraint_name]);
                });
            }
        }
        
        // 2. إزالة unique constraint من medical_centers إذا كان موجوداً
        if (Schema::hasColumn('medical_centers', 'phone')) {
            $constraintExists = $connection->selectOne("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'medical_centers' 
                AND constraint_type = 'UNIQUE' 
                AND constraint_name LIKE '%phone%'
            ");
            
            if ($constraintExists) {
                Schema::table('medical_centers', function (Blueprint $table) use ($constraintExists) {
                    $table->dropUnique([$constraintExists->constraint_name]);
                });
            }
        }
        
        // 3. إنشاء Trigger Function للتحقق من عدم تكرار phone في clinics
        DB::unprepared("
            CREATE OR REPLACE FUNCTION check_clinic_phone_unique()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.phone IS NOT NULL AND EXISTS (
                    SELECT 1 FROM medical_centers WHERE phone = NEW.phone
                ) THEN
                    RAISE EXCEPTION 'رقم الهاتف % مستخدم بالفعل في مركز طبي آخر', NEW.phone;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
        
        // 4. إنشاء Trigger Function للتحقق من عدم تكرار phone في medical_centers
        DB::unprepared("
            CREATE OR REPLACE FUNCTION check_medical_center_phone_unique()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.phone IS NOT NULL AND EXISTS (
                    SELECT 1 FROM clinics WHERE phone = NEW.phone
                ) THEN
                    RAISE EXCEPTION 'رقم الهاتف % مستخدم بالفعل في عيادة أخرى', NEW.phone;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
        
        // 5. إضافة unique constraint داخل كل جدول (لضمان عدم التكرار داخل نفس الجدول)
        Schema::table('clinics', function (Blueprint $table) {
            if (Schema::hasColumn('clinics', 'phone')) {
                $table->unique('phone', 'clinics_phone_unique');
            }
        });
        
        Schema::table('medical_centers', function (Blueprint $table) {
            if (Schema::hasColumn('medical_centers', 'phone')) {
                $table->unique('phone', 'medical_centers_phone_unique');
            }
        });
        
        // 6. إنشاء Trigger على جدول clinics (للتحقق من medical_centers)
        DB::unprepared("
            DROP TRIGGER IF EXISTS check_clinic_phone_unique ON clinics;
            CREATE TRIGGER check_clinic_phone_unique
            BEFORE INSERT OR UPDATE OF phone ON clinics
            FOR EACH ROW
            WHEN (NEW.phone IS NOT NULL)
            EXECUTE FUNCTION check_clinic_phone_unique();
        ");
        
        // 7. إنشاء Trigger على جدول medical_centers (للتحقق من clinics)
        DB::unprepared("
            DROP TRIGGER IF EXISTS check_medical_center_phone_unique ON medical_centers;
            CREATE TRIGGER check_medical_center_phone_unique
            BEFORE INSERT OR UPDATE OF phone ON medical_centers
            FOR EACH ROW
            WHEN (NEW.phone IS NOT NULL)
            EXECUTE FUNCTION check_medical_center_phone_unique();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف Triggers
        DB::unprepared("DROP TRIGGER IF EXISTS check_clinic_phone_unique ON clinics;");
        DB::unprepared("DROP TRIGGER IF EXISTS check_medical_center_phone_unique ON medical_centers;");
        
        // حذف Functions
        DB::unprepared("DROP FUNCTION IF EXISTS check_clinic_phone_unique();");
        DB::unprepared("DROP FUNCTION IF EXISTS check_medical_center_phone_unique();");
        
        // إعادة unique constraint على كل جدول على حدة (اختياري)
        // يمكنك إضافة هذا إذا أردت
    }
};
