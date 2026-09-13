<?php

namespace App\Modules\Web\Database\Seeders;

use App\Modules\Web\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * زارع بيانات مدير المنصة الرئيسي (Super Admin Database Seeder).
 *
 * يتولى تهيئة الحساب الإداري الجذري للمنصة في بيئة التطوير والإنتاج الأولي،
 * لمنح المشرف صلاحية الوصول المباشرة للوحة التحكم.
 *
 * @package Database\Seeders
 * @author VendorProof Platform Architecture
 */
class AdminSeeder extends Seeder
{
    /**
     * تشغيل زارع البيانات لإنشاء الحساب الإداري الأولي.
     *
     * @return void
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@platform.com'],
            [
                'name' => 'مدير المنصة',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
    }
}
