<?php

namespace App\Modules\Web\Database\Seeders;

use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $featuresByKey = Feature::all()->keyBy('key');

        $plansData = [
            [
                'slug'        => 'free',
                'name'        => 'الباقة المجانية',
                'description' => 'باقة مجانية دائمة تتيح للشركات استخدام النظام بقدرات أساسية محددة.',
                'status'      => 'active',
                'monthly'     => 0.00,
                'yearly'      => 0.00,
                'features'    => [
                    'max_vendors'            => '5',
                    'max_certificates'       => '10',
                    'automated_verification' => '0',
                    'compliance_reports'     => '0',
                    'api_access'             => '0',
                ],
            ],
            [
                'slug'        => 'starter',
                'name'        => 'باقة الأساسيات',
                'description' => 'مخصصة للمنشآت والشركات الصغيرة ذات الأنشطة المحدودة.',
                'status'      => 'active',
                'monthly'     => 299.00,
                'yearly'      => 2990.00,
                'features'    => [
                    'max_vendors'            => '30',
                    'max_certificates'       => '100',
                    'automated_verification' => '1',
                    'compliance_reports'     => '0',
                    'api_access'             => '0',
                ],
            ],
            [
                'slug'        => 'growth',
                'name'        => 'باقة النمو',
                'description' => 'مثالية للشركات المتوسطة التي تتطلب إدارة متقدمة وفحصاً دقيقاً للموردين.',
                'status'      => 'active',
                'monthly'     => 699.00,
                'yearly'      => 6990.00,
                'features'    => [
                    'max_vendors'            => '150',
                    'max_certificates'       => '500',
                    'automated_verification' => '1',
                    'compliance_reports'     => '1',
                    'api_access'             => '0',
                ],
            ],
            [
                'slug'        => 'enterprise',
                'name'        => 'باقة المؤسسات',
                'description' => 'حلول امتثال شاملة وغير محدودة للمؤسسات والشركات الكبرى.',
                'status'      => 'active',
                'monthly'     => 1499.00,
                'yearly'      => 14990.00,
                'features'    => [
                    'max_vendors'            => '-1',
                    'max_certificates'       => '-1',
                    'automated_verification' => '1',
                    'compliance_reports'     => '1',
                    'api_access'             => '1',
                ],
            ],
        ];

        foreach ($plansData as $item) {
            $plan = Plan::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name'        => $item['name'],
                    'description' => $item['description'],
                    'status'      => $item['status'],
                ]
            );

            // أسعار الفوترة
            $plan->prices()->updateOrCreate(
                ['billing_cycle' => 'monthly'],
                ['price' => $item['monthly'], 'currency' => 'SAR', 'status' => 'active']
            );

            $plan->prices()->updateOrCreate(
                ['billing_cycle' => 'yearly'],
                ['price' => $item['yearly'], 'currency' => 'SAR', 'status' => 'active']
            );

            // ربط ميزات وحدود الباقة عبر مصفوفة الربط النظيفة
            $syncData = [];
            foreach ($item['features'] as $key => $value) {
                if (isset($featuresByKey[$key])) {
                    // نربط الميزات المشولة أو الحدود الفعالة
                    $syncData[$featuresByKey[$key]->id] = ['value' => (string) $value];
                }
            }

            $plan->features()->sync($syncData);
        }
    }
}
