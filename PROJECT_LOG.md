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

---

## [الخطوة رقم: 3] - تأسيس محرك إدارة الموردين في الـ API (Vendors Engine)

### 1. السبب والهدف المعماري:
- استكمال الجزء المحوري في المنصة وهو إدارة الموردين للمنشآت (Tenants) عبر واجهات RESTful API خفيفة وبسيطة.
- ضمان أعلى معايير النظافة البرمجية (Clean Controller & Standard Form Requests) والعزل التلقائي المتعدد عبر `BelongsToTenant`.
- توفير عقد استجابة JSON نقي ومباشر بدون تعقيدات الترقيم (Pagination) أو الحصص (Quota) لتسهيل استهلاك الفرونت إند المنفصل.

---

### 2. الملفات المتأثرة والمضافة:
1. `app/Modules/Api/Http/Requests/Vendors/StoreVendorRequest.php` (طلب إضافة مورد جديد مع التحقق الصارم)
2. `app/Modules/Api/Http/Requests/Vendors/UpdateVendorRequest.php` (طلب تعديل بيانات المورد مع استثناء المورد الحالي من فحص فرادة البريد)
3. `app/Modules/Api/Http/Controllers/Vendors/ApiVendorController.php` (متحكم الموردين المعياري)
4. `app/Modules/Api/routes/api.php` (تسجيل مسارات apiResource للموردين)
5. `tests/Feature/VendorApiTest.php` (اختبارات آلية شاملة تغطي الصلاحيات، التحقق، العزل، والعمليات)

---

### 3. التفاصيل البرمجية المعتمدة:

#### أ. طلبات التحقق (`StoreVendorRequest` و `UpdateVendorRequest`):
- فرض الحد الأدنى والنمط اللغوي على حقول الأسماء لمنع الأرقام المجردة: `min:2` و `regex:/[\pL]/u` على `name` و `contact_name`.
- قيد فرادة البريد الإلكتروني للمنشأة الواحدة فقط:
  `Rule::unique('vendors', 'email')->where('tenant_id', $this->user()->tenant_id)`.
- فحص صحة رقم الهاتف بالنمط الدولي والمحلي: `regex:/^(\+?[0-9\s\-]{7,20})$/`.
- التحقق من تبعية التصنيف `vendor_category_id` لنفس المنشأة عبر:
  `Rule::exists('vendor_categories', 'id')->where('tenant_id', $this->user()->tenant_id)`.
- حوكمة حالات المورد بحسب القيم المصرح بها: `in:active,pending,suspended`.

#### ب. متحكم الموردين (`ApiVendorController`):
- الوراثة المباشرة من `ApiController` والاعتماد على `$this->successResponse(...)`.
- استخدام Route Model Binding القياسي في لارافيل لكافة العمليات الفردية (`show`, `update`, `destroy`).
- استرجاع قائمة الموردين كمصفوفة نقية ومباشرة عبر `$vendor->toApiArray()`.
- تعبئة `tenant_id` تلقائياً عبر السمة `BelongsToTenant`.

#### ج. المسارات المعتمدة:
```text
GET|HEAD        api/v1/vendors
POST            api/v1/vendors
GET|HEAD        api/v1/vendors/{vendor}
PUT|PATCH       api/v1/vendors/{vendor}
DELETE          api/v1/vendors/{vendor}
```

---

### 4. النتيجة والتحقق (Verification):
- كتابة وتشغيل اختبارات `VendorApiTest` الآلية الشاملة:
  1. حظر غير الموثقين (401).
  2. إنشاء المورد واسترجاع بياناته بصيغة معيارية (201).
  3. التحقق من رفض الأرقام المجردة في الأسماء وتنسيق البريد والهاتف (422).
  4. منع تكرار نفس البريد للمنشأة الواحدة (422).
  5. السماح لمنشآت مختلفة بإضافة موردين بنفس البريد بشكل مستقل تماماً.
  6. العزل التلقائي (عدم ظهور موردي منشأة لمنشأة أخرى).
  7. العرض الفردي للمورد وتفاصيله.
  8. الحماية الصارمة من الوصول أو التعديل أو الحذف لمورد تابع لمنشأة أخرى (404).
  9. التعديل والحذف الآمن للمورد.
- نجاح كافة اختبارات الموردين: **10 passed (28 assertions)**.
- نجاح كامل طقم اختبارات المنظومة: **112 passed (579 assertions)**.

---

## [الخطوة رقم: 4] - التنظيف والتوثيق المعماري الشامل لموديول الويب وملفات المنصة (Web Module & App Architecture Cleanup)

### 1. السبب والهدف المعماري:
- استئصال مظاهر التلوث البرمجي والتعليقات العشوائية (AI Slop) والترقيمات المصطنعة (`// 1.`, `// 2.`) والفواصل التقليدية القديمة (`// ==== العلاقات ====`).
- توحيد لغة وأسلوب التوثيق البرمجي (Docblocks & Code Comments) باللغة الإنجليزية المعيارية والموجزة للمطورين، والتخلص من الشروح الزائدة للأكواد البديهية.
- إصلاح المسافات البادئة والتنسيق الداخلي في دوال الخدمات المالية (`StripePaymentService`).
- الحفاظ التام والصلب على استقرار النظام وكامل الوظائف والاختبارات دون أدنى كسر.

---

### 2. الملفات المحدثة:
1. `app/Modules/Web/Http/Controllers/DashboardController.php` (حذف Docblock والتعليقات الرقمية)
2. `app/Modules/Web/Http/Controllers/Auth/AdminAuthController.php` (حذف تعليق التحقق البديهي)
3. `app/Modules/Web/Http/Controllers/Plans/PlanController.php` (تحويل الـ Docblocks إلى الإنجليزية الموجزة)
4. `app/Modules/Web/Http/Controllers/Tenants/TenantController.php` (حذف الشروح الزائدة والتعليقات البديهية)
5. `app/Modules/Web/Http/Controllers/Payments/PaymentController.php` (تنظيف كامل الـ Docblocks الزائدة)
6. `app/Modules/Web/Http/Controllers/Subscriptions/SubscriptionController.php` (تنظيف الـ Docblocks الزائدة)
7. `app/Modules/Web/Models/Plan.php` (حذف التعليقات العربية على الدوال الواضحة وتوحيد وثائق الأنواع)
8. `app/Modules/Web/Models/PlanPrice.php` (تنظيف التوثيق المعياري وحذف حشو الـ @property الزائد)
9. `app/Modules/Web/Models/Admin.php` (تنظيف حشو الـ @property والتعليق العربي)
10. `app/Models/Payment.php` (استئصال فواصل الأقسام القديمة والـ Docblocks العربية)
11. `app/Modules/Web/Services/Plans/PlanService.php` (حذف الترقيمات والـ Docblocks العربية واعتماد توثيق إنجليزي معياري)
12. `app/Services/Payments/StripePaymentService.php` (حذف التعليقات الرقمية العربية، تصحيح مسافات `getReceiptUrl()`)
13. `app/Modules/Web/routes/web.php` (توحيد تعليقات الأقسام بالإنجليزية المعيارية)

---

### 3. النتيجة والتحقق (Verification):
- تشغيل اختبارات النظام الشاملة بنجاح 100%:
  **112 passed (579 assertions)**.



