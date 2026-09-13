<?php

namespace Tests\Feature;

use App\Modules\Web\Models\Admin;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test for platform features and limits management.
 */
class FeatureManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'مدير النظام',
            'email' => 'admin@vendorproof.sa',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_guest_cannot_manage_features(): void
    {
        $this->get(route('features.index'))->assertRedirect(route('login'));
        $this->get(route('features.create'))->assertRedirect(route('login'));
        $this->post(route('features.store'), [])->assertRedirect(route('login'));
    }

    public function test_admin_can_view_features_list(): void
    {
        Feature::create([
            'name' => 'عدد الموردين المفحوصين',
            'key' => 'max_vendors',
            'type' => 'limit',
        ]);

        $response = $this->actingAs($this->admin)->get(route('features.index'));

        $response->assertStatus(200);
        $response->assertSee('عدد الموردين المفحوصين');
        $response->assertSee('max_vendors');
    }

    public function test_admin_can_create_a_valid_feature(): void
    {
        $payload = [
            'name' => 'التحقق السحابي الفوري',
            'key' => 'instant_cloud_verification',
            'type' => 'feature',
        ];

        $response = $this->actingAs($this->admin)->post(route('features.store'), $payload);

        $response->assertRedirect(route('features.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('features', [
            'name' => 'التحقق السحابي الفوري',
            'key' => 'instant_cloud_verification',
            'type' => 'feature',
        ]);
    }

    public function test_feature_requires_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('features.store'), [
            'name' => '',
            'key' => '',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['name', 'key', 'type']);
    }

    public function test_feature_key_must_be_unique(): void
    {
        Feature::create([
            'name' => 'حد الموردين',
            'key' => 'max_vendors',
            'type' => 'limit',
        ]);

        $response = $this->actingAs($this->admin)->post(route('features.store'), [
            'name' => 'اسم آخر',
            'key' => 'max_vendors',
            'type' => 'limit',
        ]);

        $response->assertSessionHasErrors(['key']);
    }

    public function test_feature_key_must_be_lowercase_slug(): void
    {
        $response = $this->actingAs($this->admin)->post(route('features.store'), [
            'name' => 'ميزة خاطئة',
            'key' => 'INVALID KEY!@#',
            'type' => 'feature',
        ]);

        $response->assertSessionHasErrors(['key']);
    }

    public function test_admin_can_update_feature(): void
    {
        $feature = Feature::create([
            'name' => 'الاسم القديم',
            'key' => 'old_key',
            'type' => 'limit',
        ]);

        $response = $this->actingAs($this->admin)->put(route('features.update', $feature), [
            'name' => 'الاسم الجديد المحدث',
            'key' => 'updated_key',
            'type' => 'feature',
        ]);

        $response->assertRedirect(route('features.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('features', [
            'id' => $feature->id,
            'name' => 'الاسم الجديد المحدث',
            'key' => 'updated_key',
            'type' => 'feature',
        ]);
    }

    public function test_admin_can_update_feature_keeping_same_key(): void
    {
        $feature = Feature::create([
            'name' => 'الاسم الحالي',
            'key' => 'same_key',
            'type' => 'limit',
        ]);

        $response = $this->actingAs($this->admin)->put(route('features.update', $feature), [
            'name' => 'اسم جديد فقط',
            'key' => 'same_key',
            'type' => 'limit',
        ]);

        $response->assertRedirect(route('features.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('features', [
            'id' => $feature->id,
            'name' => 'اسم جديد فقط',
            'key' => 'same_key',
        ]);
    }

    public function test_admin_can_delete_unattached_feature(): void
    {
        $feature = Feature::create([
            'name' => 'ميزة قابلة للحذف',
            'key' => 'to_delete',
            'type' => 'feature',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('features.destroy', $feature));

        $response->assertRedirect(route('features.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('features', [
            'id' => $feature->id,
        ]);
    }

    public function test_admin_cannot_delete_feature_attached_to_plans(): void
    {
        $feature = Feature::create([
            'name' => 'ميزة مرتبطة بباقة',
            'key' => 'attached_feature',
            'type' => 'feature',
        ]);

        $plan = Plan::create([
            'name' => 'باقة تجريبية',
            'slug' => 'test-plan',
        ]);

        $plan->features()->attach($feature->id, ['value' => '1']);

        $response = $this->actingAs($this->admin)->delete(route('features.destroy', $feature));

        $response->assertRedirect(route('features.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('features', [
            'id' => $feature->id,
        ]);
    }
}
