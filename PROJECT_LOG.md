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

---

## [الخطوة رقم: 2] - إحكام حوكمة وقواعد طلبات التحقق (FormRequests Overhaul & Hardening)

### 1. السبب والهدف المعماري:
- معالجة الثغرات البرمجية في قواعد التحقق عبر الويب والـ API والتي كانت تتسبب في قبول نصوص غير صالحة مثل الرقم المجرد `"1"` في حقول الأسماء، وقبول نصوص عادية في الحقول الرقمية المخصصة كالسجل التجاري والهاتف وحدود الباقات.
- ضبط وفصل أنواع البيانات بصرامة وفق المعايير السحابية: فرض الحد الأدنى والنمط اللغوي على حقول النصوص لمنع الأرقام المجردة، فرض القيود العددية (`numeric`, `digits_between`) على السجلات التجارية، وفحص نمط أرقام الهواتف، وضبط قيم حدود الباقات.

---

### 2. الملفات المتأثرة والمحدثة:
1. `app/Modules/Web/Http/Requests/Auth/AdminRegisterRequest.php` (إحكام اسم المشرف)
2. `app/Modules/Web/Http/Requests/Profile/UpdateAdminProfileRequest.php` (إحكام اسم المشرف في البروفايل)
3. `app/Modules/Web/Http/Requests/Tenants/StoreTenantRequest.php` (إحكام اسم المنشأة، المدير، السجل التجاري، الهاتف، الرابط)
4. `app/Modules/Web/Http/Requests/Tenants/UpdateTenantRequest.php` (إحكام تعديل المنشأة والسجل التجاري والهاتف)
5. `app/Modules/Web/Http/Requests/Plans/StorePlanRequest.php` (إحكام اسم ورابط باقة الاشتراك)
6. `app/Modules/Web/Http/Requests/Plans/UpdatePlanRequest.php` (إحكام تعديل اسم ورابط باقة الاشتراك)
7. `app/Modules/Web/Http/Requests/Plans/UpdatePlanFeaturesRequest.php` (حوكمة قيم الحدود والميزات كرقمية تسمح بـ -1 لغير المحدود)
8. `app/Modules/Web/Http/Requests/Features/StoreFeatureRequest.php` (إحكام اسم ومفتاح الميزة)
9. `app/Modules/Web/Http/Requests/Features/UpdateFeatureRequest.php` (إحكام تعديل اسم ومفتاح الميزة)
10. `app/Modules/Api/Http/Requests/Auth/ApiRegisterTenantRequest.php` (إحكام التسجيل الذاتي للمنشأة والمدير والسجل التجاري والهاتف)
11. `app/Modules/Api/Http/Requests/Profile/UpdateUserProfileRequest.php` (إحكام اسم المستخدم الشخصي ومنع قبول '1' أو الأرقام المجردة)
12. `app/Modules/Api/Http/Requests/Company/UpdateCompanyProfileRequest.php` (إحكام بيانات المنشأة والسجل التجاري والهاتف)
13. `app/Modules/Api/Http/Requests/Vendors/StoreVendorCategoryRequest.php` (إضافة min:2 لاسم التصنيف)
14. `app/Modules/Api/Http/Requests/Vendors/UpdateVendorCategoryRequest.php` (إضافة min:2 لتعديل التصنيف)
15. `tests/Feature/FormRequestValidationTest.php` (اختبارات آلية مخصصة للتحقق من رفض '1' والأرقام المجردة وفحص السجلات والهواتف)

---

### 3. التفاصيل البرمجية المعتمدة:
- **حقول الأسماء والمنشآت**: إضافة `min:2` ونمط التحقق اللغوي `regex:/[\pL]/u` و `not_regex:/^[0-9]+$/` لضمان احتواء الاسم على أحرف حقيقية ومنع الأرقام المجردة كلياً.
- **السجل التجاري (`company_cr`)**: تحويله من `string` إلى `numeric` و `digits_between:10,20` لمنع النصوص والأحرف.
- **أرقام الهواتف (`phone`)**: تطبيق فحص النمط الدولي والمحلي عبر `regex:/^(\+?[0-9\s\-]{7,20})$/`.
- **حدود الباقات التشغيلية (`features.*.value`)**: إلزام القيمة بأن تكون رقمية `numeric` مع حد أدنى `min:-1` لدعم القيمة غير المحدودة.
- **صياغة رسائل خطأ عربية دقيقة وشاملة لكافة القواعد المستحدثة**.

---

### 4. النتيجة والتحقق (Verification):
- اجتياز اختبار `FormRequestValidationTest` المخصص بنجاح 100%.
- اجتياز كامل حزمة اختبارات المنظومة: **100 اختبار ناجح (100 passed, 547 assertions)**.

