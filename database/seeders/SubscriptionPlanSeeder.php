<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\SubscriptionPlanEntitlement;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        echo "📋 إنشاء خطط الاشتراك...\n\n";

        // خطط العيادات
        $clinicPlans = [
            [
                'name' => 'الخطة الأساسية للعيادات',
                'target_type' => 'clinic',
                'price' => 50000,
                'duration_days' => 30,
                'description' => 'خطة مناسبة للعيادات الصغيرة',
                'is_active' => true,
                'features' => [
                    'إدارة حتى 50 مريض شهرياً',
                    'دعم فني عبر البريد الإلكتروني',
                    'تقارير أساسية',
                ],
                'entitlements' => [
                    ['key' => 'max_patients_per_month', 'value' => '50', 'type' => 'integer'],
                    ['key' => 'enable_secretary', 'value' => 'false', 'type' => 'boolean'],
                    ['key' => 'max_appointments_per_day', 'value' => '20', 'type' => 'integer'],
                ],
            ],
            [
                'name' => 'الخطة الفضية للعيادات',
                'target_type' => 'clinic',
                'price' => 100000,
                'duration_days' => 30,
                'description' => 'خطة متوسطة للعيادات',
                'is_active' => true,
                'features' => [
                    'إدارة حتى 150 مريض شهرياً',
                    'دعم فني عبر الدردشة',
                    'تقارير متقدمة',
                    'إمكانية إضافة سكرتيرة',
                ],
                'entitlements' => [
                    ['key' => 'max_patients_per_month', 'value' => '150', 'type' => 'integer'],
                    ['key' => 'enable_secretary', 'value' => 'true', 'type' => 'boolean'],
                    ['key' => 'max_appointments_per_day', 'value' => '50', 'type' => 'integer'],
                    ['key' => 'max_secretaries', 'value' => '2', 'type' => 'integer'],
                ],
            ],
            [
                'name' => 'الخطة الذهبية للعيادات',
                'target_type' => 'clinic',
                'price' => 200000,
                'duration_days' => 30,
                'description' => 'خطة شاملة للعيادات الكبيرة',
                'is_active' => true,
                'features' => [
                    'إدارة مرضى غير محدود',
                    'دعم فني على مدار الساعة',
                    'تقارير احترافية',
                    'إمكانية إضافة سكرتيرات متعددة',
                    'ميزات متقدمة',
                ],
                'entitlements' => [
                    ['key' => 'max_patients_per_month', 'value' => '999999', 'type' => 'integer'],
                    ['key' => 'enable_secretary', 'value' => 'true', 'type' => 'boolean'],
                    ['key' => 'max_appointments_per_day', 'value' => '999', 'type' => 'integer'],
                    ['key' => 'max_secretaries', 'value' => '10', 'type' => 'integer'],
                ],
            ],
        ];

        // خطط المراكز الطبية
        $centerPlans = [
            [
                'name' => 'الخطة الأساسية للمراكز',
                'target_type' => 'medical_center',
                'price' => 200000,
                'duration_days' => 30,
                'description' => 'خطة مناسبة للمراكز الصغيرة',
                'is_active' => true,
                'features' => [
                    'إدارة حتى 5 عيادات',
                    'دعم فني عبر البريد الإلكتروني',
                    'تقارير أساسية',
                ],
                'entitlements' => [
                    ['key' => 'max_clinics', 'value' => '5', 'type' => 'integer'],
                    ['key' => 'enable_secretary', 'value' => 'false', 'type' => 'boolean'],
                ],
            ],
            [
                'name' => 'الخطة الفضية للمراكز',
                'target_type' => 'medical_center',
                'price' => 400000,
                'duration_days' => 30,
                'description' => 'خطة متوسطة للمراكز',
                'is_active' => true,
                'features' => [
                    'إدارة حتى 15 عيادة',
                    'دعم فني عبر الدردشة',
                    'تقارير متقدمة',
                    'إمكانية إضافة سكرتيرات',
                ],
                'entitlements' => [
                    ['key' => 'max_clinics', 'value' => '15', 'type' => 'integer'],
                    ['key' => 'enable_secretary', 'value' => 'true', 'type' => 'boolean'],
                    ['key' => 'max_secretaries_per_clinic', 'value' => '2', 'type' => 'integer'],
                ],
            ],
            [
                'name' => 'الخطة الذهبية للمراكز',
                'target_type' => 'medical_center',
                'price' => 800000,
                'duration_days' => 30,
                'description' => 'خطة شاملة للمراكز الكبيرة',
                'is_active' => true,
                'features' => [
                    'إدارة عيادات غير محدود',
                    'دعم فني على مدار الساعة',
                    'تقارير احترافية',
                    'إمكانية إضافة سكرتيرات غير محدود',
                    'ميزات متقدمة',
                ],
                'entitlements' => [
                    ['key' => 'max_clinics', 'value' => '999', 'type' => 'integer'],
                    ['key' => 'enable_secretary', 'value' => 'true', 'type' => 'boolean'],
                    ['key' => 'max_secretaries_per_clinic', 'value' => '999', 'type' => 'integer'],
                ],
            ],
        ];

        // إنشاء خطط العيادات
        echo "🏥 إنشاء خطط العيادات...\n";
        foreach ($clinicPlans as $planData) {
            $features = $planData['features'];
            $entitlements = $planData['entitlements'];
            unset($planData['features'], $planData['entitlements']);

            $plan = SubscriptionPlan::firstOrCreate(
                [
                    'name' => $planData['name'],
                    'target_type' => $planData['target_type'],
                ],
                $planData
            );

            // إضافة الميزات
            foreach ($features as $featureText) {
                SubscriptionPlanFeature::firstOrCreate([
                    'subscription_plan_id' => $plan->id,
                    'text' => $featureText,
                ]);
            }

            // إضافة Entitlements
            foreach ($entitlements as $entitlement) {
                SubscriptionPlanEntitlement::firstOrCreate([
                    'subscription_plan_id' => $plan->id,
                    'key' => $entitlement['key'],
                ], [
                    'value' => $entitlement['value'],
                    'type' => $entitlement['type'],
                ]);
            }

            echo "  ✅ {$plan->name} (ID: {$plan->id})\n";
        }

        // إنشاء خطط المراكز الطبية
        echo "\n🏨 إنشاء خطط المراكز الطبية...\n";
        foreach ($centerPlans as $planData) {
            $features = $planData['features'];
            $entitlements = $planData['entitlements'];
            unset($planData['features'], $planData['entitlements']);

            $plan = SubscriptionPlan::firstOrCreate(
                [
                    'name' => $planData['name'],
                    'target_type' => $planData['target_type'],
                ],
                $planData
            );

            // إضافة الميزات
            foreach ($features as $featureText) {
                SubscriptionPlanFeature::firstOrCreate([
                    'subscription_plan_id' => $plan->id,
                    'text' => $featureText,
                ]);
            }

            // إضافة Entitlements
            foreach ($entitlements as $entitlement) {
                SubscriptionPlanEntitlement::firstOrCreate([
                    'subscription_plan_id' => $plan->id,
                    'key' => $entitlement['key'],
                ], [
                    'value' => $entitlement['value'],
                    'type' => $entitlement['type'],
                ]);
            }

            echo "  ✅ {$plan->name} (ID: {$plan->id})\n";
        }

        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🎉 تم إنشاء خطط الاشتراك بنجاح!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📊 ملخص:\n";
        echo "  ✅ " . SubscriptionPlan::where('target_type', 'clinic')->count() . " خطط للعيادات\n";
        echo "  ✅ " . SubscriptionPlan::where('target_type', 'medical_center')->count() . " خطط للمراكز الطبية\n";
        echo "  ✅ " . SubscriptionPlanFeature::count() . " ميزة\n";
        echo "  ✅ " . SubscriptionPlanEntitlement::count() . " entitlement\n\n";
    }
}
