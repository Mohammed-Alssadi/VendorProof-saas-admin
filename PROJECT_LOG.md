# سجل تطوير وبناء منصة VendorProof (PROJECT_LOG.md)

---

## [الخطوة رقم: 1] - تأسيس نظام تصنيفات الموردين (Vendor Categories Engine)

### 1. السبب والهدف المعماري:
- البدء في صلب خدمة المنصة عبر تمكين منشآت العملاء (Tenants) من تصنيف الموردين وفق قطاعاتهم ونشاطاتهم المختلفة.
- تطبيق أقصى درجات البساطة وصفر تعقيد مع الالتزام التام بالمعمارية الموديلية، العزل التلقائي المتعدد (`BelongsToTenant`)، وقواعد التحقق القياسية النظيفة.

---

### 2. الملفات المتأثرة والمضافة:
1. `app/Modules/Api/Database/Migrations/2026_09_14_000001_create_vendor_categories_table.php` (جدول الترحيل)
2. `app/Modules/Api/Models/Vendors/VendorCategory.php` (الموديل الموديولي)
3. `app/Modules/Api/Http/Requests/Vendors/StoreVendorCategoryRequest.php` (طلب إنشاء التصنيف)
4. `app/Modules/Api/Http/Requests/Vendors/UpdateVendorCategoryRequest.php` (طلب تحديث التصنيف)
5. `app/Modules/Api/Http/Controllers/Vendors/ApiVendorCategoryController.php` (متحكم الـ API)
6. `app/Modules/Api/routes/api.php` (مسارات الـ Resource المحمية بـ Sanctum)
7. `tests/Feature/VendorCategoryApiTest.php` (اختبارات التحقق والعزل الشاملة)

---

### 3. التفاصيل البرمجية المعتمدة:

#### أ. الترحيل (`vendor_categories`):
- الأعمدة: `id` (bigIncrements)، `tenant_id` (foreignUuid -> tenants مع cascadeOnDelete)، `name` (string)، `description` (text nullable)، `is_active` (boolean default true)، `timestamps()`.
- القيد: `unique(['tenant_id', 'name'])`.

#### ب. الموديل (`VendorCategory`):
- تطبيق سمة `BelongsToTenant` لعزل الاستعلامات تلقائياً وتعبئة `tenant_id`.
- دعم `toApiArray()` لإرجاع استجابة JSON نظيفة ومتطابقة مع الفرونت إند.

#### ج. طلبات التحقق (`StoreVendorCategoryRequest` و `UpdateVendorCategoryRequest`):
- فحص فرادة الاسم لنفس المنشأة فقط عبر:
  `Rule::unique('vendor_categories')->where('tenant_id', $this->user()->tenant_id)`
  مع `ignore` عند التحديث.

#### د. مسارات ومتحكم الـ API المعتمد:
- استخدام **Route Model Binding** القياسي في لارافيل (`VendorCategory $vendorCategory`) في دوال `show`, `update`, `destroy` والتخلص التام من الشروط والتحقق اليدوي المكرر.
- تبسيط دالة `index` لإرجاع كافة التصنيفات (النشطة وغير النشطة) بدون تعقيد أو فلاتر زائدة.
- تعيين `tenant_id` تلقائياً عبر السمة `BelongsToTenant` في دالة `store`.
```text
GET|HEAD        api/v1/vendor-categories
POST            api/v1/vendor-categories
GET|HEAD        api/v1/vendor-categories/{vendor_category}
PUT|PATCH       api/v1/vendor-categories/{vendor_category}
DELETE          api/v1/vendor-categories/{vendor_category}
```

---

### 4. النتيجة والتحقق (Verification):
- تشغيل الترحيل بنجاح: `2026_09_14_000001_create_vendor_categories_table ... DONE`.
- كتابة اختبار آلي شامل `VendorCategoryApiTest` شمل:
  1. حظر غير الموثقين (401).
  2. إنشاء التصنيف واسترجاعه بصيغة معيارية (201).
  3. منع تكرار نفس الاسم للمنشأة الواحدة (422).
  4. السماح لمنشآت مختلفة باستخدام نفس الاسم بشكل مستقل تماماً.
  5. العزل التلقائي (عدم ظهور تصنيفات منشأة لمنشأة أخرى).
  6. التعديل والحذف الآمن.
- نجاح كافة الاختبارات: `7 passed (16 assertions)`.
- نجاح كامل طقم الاختبارات العام: `97 passed (530 assertions)`.
