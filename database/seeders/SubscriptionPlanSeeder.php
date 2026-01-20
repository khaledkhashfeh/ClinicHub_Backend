<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\SubscriptionPlanEntitlement;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default basic plan for clinics
        $basicPlan = SubscriptionPlan::firstOrCreate(
            [
                "name" => "الخطة الأساسية",
                "target_type" => "clinic",
            ],
            [
                "name" => "الخطة الأساسية",
                "target_type" => "clinic",
                "price" => 0.00, // Free plan
                "duration_days" => 30, // 30 days trial
                "is_active" => true,
                "description" => "خطة تجريبية مجانية للعيادات الجديدة"
            ]
        );

        // Add some basic features to the plan
        SubscriptionPlanFeature::firstOrCreate(
            [
                "subscription_plan_id" => $basicPlan->id,
                "text" => "الوصول إلى لوحة التحكم",
            ],
            [
                "text" => "الوصول إلى لوحة التحكم"
            ]
        );

        SubscriptionPlanFeature::firstOrCreate(
            [
                "subscription_plan_id" => $basicPlan->id,
                "text" => "إدارة المواعيد",
            ],
            [
                "text" => "إدارة المواعيد"
            ]
        );

        // Add some basic entitlements to the plan
        SubscriptionPlanEntitlement::firstOrCreate(
            [
                "subscription_plan_id" => $basicPlan->id,
                "key" => "max_patients",
            ],
            [
                "key" => "max_patients",
                "value" => "50",
                "type" => "integer"
            ]
        );

        SubscriptionPlanEntitlement::firstOrCreate(
            [
                "subscription_plan_id" => $basicPlan->id,
                "key" => "max_appointments_per_day",
            ],
            [
                "key" => "max_appointments_per_day",
                "value" => "20",
                "type" => "integer"
            ]
        );

        echo "✅ تم إنشاء خطة الاشتراك الأساسية للعيادات\n";
        
        // Create a paid plan as well
        $premiumPlan = SubscriptionPlan::firstOrCreate(
            [
                "name" => "الخطة الاحترافية",
                "target_type" => "clinic",
            ],
            [
                "name" => "الخطة الاحترافية",
                "target_type" => "clinic",
                "price" => 100.00,
                "duration_days" => 365, // Annual plan
                "is_active" => true,
                "description" => "خطة احترافية لجميع احتياجات العيادة"
            ]
        );

        echo "✅ تم إنشاء خطة الاشتراك الاحترافية للعيادات\n";
    }
}
