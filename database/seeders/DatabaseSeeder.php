<?php

namespace Database\Seeders;

use App\Modules\Web\Database\Seeders\AdminSeeder;
use App\Modules\Web\Database\Seeders\FeatureSeeder;
use App\Modules\Web\Database\Seeders\PlanSeeder;
use Illuminate\Database\Seeder;

/**
 * زارع قاعدة البيانات المركزي للمنظومة (Central Database Seeder).
 *
 * يتولى تشغيل زارعي البيانات المعتمدين لتجهيز قاعدة البيانات الأولية.
 *
 * @author VendorProof Platform Architecture
 */
class DatabaseSeeder extends Seeder
{
    /**
     * تشغيل زارعي البيانات المعتمدين للمنصة.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            FeatureSeeder::class,
            PlanSeeder::class,
        ]);
    }
}
