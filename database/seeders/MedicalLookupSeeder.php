<?php

namespace Database\Seeders;

use App\Models\DiagnosisCode;
use App\Models\LabTestCatalog;
use App\Models\MedicalTerm;
use Illuminate\Database\Seeder;

class MedicalLookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMedicalTerms();
        $this->seedDiagnosisCodes();
        $this->seedLabTestCatalog();
    }

    private function seedMedicalTerms(): void
    {
        $chronic = [
            'سكري النوع الأول',
            'سكري النوع الثاني',
            'سكري حملي',
            'ضغط دم مرتفع',
            'ربو',
            'قصور كلوي مزمن',
            'داء الانسداد الرئوي المزمن',
            'صرع',
            'قصور الدرقية',
            'فرط الدرقية',
        ];

        foreach ($chronic as $name) {
            MedicalTerm::firstOrCreate(
                ['category' => MedicalTerm::CATEGORY_CHRONIC_DISEASE, 'name' => $name],
                ['name_en' => null]
            );
        }

        $medications = [
            'بنادول 500',
            'بنادول إكسترا',
            'أموكسيسيلين 500',
            'إيبوبروفين 400',
            'باراسيتامول',
            'ميتفورمين 850',
            'لوسارتان 50',
            'أوميغا 3',
            'فيتامين د',
            'أسبرين 100',
        ];

        foreach ($medications as $name) {
            MedicalTerm::firstOrCreate(
                ['category' => MedicalTerm::CATEGORY_MEDICATION, 'name' => $name],
                ['name_en' => null]
            );
        }

        $surgery = [
            'استئصال الزائدة',
            'استئصال المرارة',
            'جراحة إصلاح الفتق',
            'جراحة العظام',
            'جراحة العيون',
        ];

        foreach ($surgery as $name) {
            MedicalTerm::firstOrCreate(
                ['category' => MedicalTerm::CATEGORY_SURGERY, 'name' => $name],
                ['name_en' => null]
            );
        }

        $this->command->info('Medical terms seeded.');
    }

    private function seedDiagnosisCodes(): void
    {
        $codes = [
            ['code' => 'E10', 'name' => 'داء السكري النوع الأول'],
            ['code' => 'E11', 'name' => 'داء السكري النوع الثاني'],
            ['code' => 'I10', 'name' => 'فرط ضغط الدم الأساسي'],
            ['code' => 'J45', 'name' => 'الربو'],
            ['code' => 'N18', 'name' => 'القصور الكلوي المزمن'],
            ['code' => 'J44', 'name' => 'داء الانسداد الرئوي المزمن'],
            ['code' => 'G40', 'name' => 'الصرع'],
            ['code' => 'E03', 'name' => 'قصور الدرقية'],
            ['code' => 'E05', 'name' => 'فرط الدرقية'],
            ['code' => 'K35', 'name' => 'التهاب الزائدة الدودية الحاد'],
        ];

        foreach ($codes as $row) {
            DiagnosisCode::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'name_en' => null]
            );
        }

        $this->command->info('Diagnosis codes seeded.');
    }

    private function seedLabTestCatalog(): void
    {
        $tests = [
            'سكر الدم الصائم',
            'الهيموغلوبين السكري',
            'تحليل الدم الشامل',
            'وظائف الكلى',
            'وظائف الكبد',
            'هرمون الغدة الدرقية',
            'تحليل البول',
            'تحليل البراز',
            'فحص فيتامين د',
            'فحص الكوليسترول',
        ];

        foreach ($tests as $name) {
            LabTestCatalog::firstOrCreate(
                ['name' => $name],
                ['name_en' => null]
            );
        }

        $this->command->info('Lab test catalog seeded.');
    }
}
