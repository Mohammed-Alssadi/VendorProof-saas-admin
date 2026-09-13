<?php

namespace App\Modules\Web\Database\Seeders;

use App\Modules\Web\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'name' => 'الحد الأقصى للموردين المسجلين',
                'key'  => 'max_vendors',
                'type' => 'limit',
            ],
            [
                'name' => 'الحد الأقصى للوثائق والشهادات',
                'key'  => 'max_certificates',
                'type' => 'limit',
            ],
            [
                'name' => 'التحقق الآلي من السجل التجاري والشهادات',
                'key'  => 'automated_verification',
                'type' => 'feature',
            ],
            [
                'name' => 'تصدير تقارير الامتثال والتدقيق المتقدمة',
                'key'  => 'compliance_reports',
                'type' => 'feature',
            ],
            [
                'name' => 'الربط البرمجي وتكامل الـ API المباشر',
                'key'  => 'api_access',
                'type' => 'feature',
            ],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(['key' => $feature['key']], $feature);
        }
    }
}
