# معايير الواجهات البرمجية ودورة حياة المنشآت والاشتراكات (API Architecture & Subscription Lifecycle)

تم إعداد هذا المستند كمرجع إلزامي لكافة نماذج الذكاء الاصطناعي والمطورين لتوحيد عقود الاستجابة (API Response Contracts)، منع التكرار، وضبط دورة حياة المنشآت والاشتراكات في موديول الـ API (`app/Modules/Api/`).

---

## 1. عقد الاستجابة ومنع تكرار البيانات (Anti-Duplication & API Payloads)

### مبدأ المصدر الموحد للحقيقة (Single Source of Truth & Zero Duplicate Root Keys):
- **حظر تام لتكرار كائن الباقة في جذر الاستجابة (`data.plan`)**:
  - الكائنات التابعة تُدرج حصراً داخل الكيان الذي يملكها؛ فالباقة وميزاتها وحدودها تتبع المنشأة (`tenant.plan`).
  - في استجابات تفاصيل الاشتراك (`GET /api/v1/subscription`) وترقية الاشتراك (`POST /api/v1/subscription/upgrade`) وإلغائه (`POST /api/v1/subscription/cancel`)، تظهر الباقة حصراً داخل المنشأة `data.tenant.plan`.
  - يُمنع منعاً باتاً إرسال كائن `plan` منفصل ومكرر في جذر `data` لتجنب تضارب الحالة وتقليل حجم البيانات.

### إلزامية دوال التسلسل المركزية للموديلات:
- **`Tenant::toApiArray()`**:
  - المصدر الوحيد والمسؤول الحصري عن تسلسل بيانات المنشأة الصادرة للفرونت إند:
    ```php
    public function toApiArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'company_cr' => $this->company_cr,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'plan'       => $this->currentPlan()?->toApiArray(),
        ];
    }
    ```
  - يُمنع بناء مصفوفات يدوية مكررة لبيانات المنشأة داخل أي متحكم (مثل `login`, `me`, `register`, `show`, `update`).
- **`Plan::toApiArray()`**:
  - المسؤول الوحيد عن تسلسل بيانات الباقة بحدودها وميزاتها الصافية:
    ```php
    public function toApiArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'limits'      => $this->dynamicLimits(),
            'features'    => $this->dynamicFeatures(),
        ];
    }
    ```

### العقد المعياري الموحد لبيانات الاشتراك (`data` في مسار `/subscription`):
```json
{
  "success": true,
  "message": "تم استرجاع تفاصيل الاشتراك بنجاح.",
  "data": {
    "tenant": {
      "id": "uuid",
      "name": "اسم المنشأة",
      "slug": "slug",
      "company_cr": "1010123456",
      "email": "info@tenant.sa",
      "phone": "+966500000000",
      "status": "active",
      "plan": {
        "id": 1,
        "name": "الباقة الأساسية",
        "slug": "basic",
        "description": "...",
        "limits": { "max_vendors": 20 },
        "features": ["feature_key_1"]
      }
    },
    "subscription": {
      "id": "uuid",
      "status": "active",
      "billing_cycle": "monthly",
      "current_period_end": "2026-10-13T00:00:00+03:00",
      "price": 199.0,
      "currency": "SAR"
    }
  }
}
```

---

## 2. حوكمة دورة حياة المنشآت والاشتراكات (Tenant & Subscription Lifecycle)

### 1. التأسيس الذري الثلاثي للمنشأة (`Atomic Provisioning`):
- يتم إنشاء حسابات العملاء حصراً عبر `TenantService::createTenantWithSubscription`:
  1. إنشاء المنشأة (`Tenant`).
  2. إنشاء المستخدم المسؤول الأول (`User`) بصفته مالك المنشأة.
  3. إنشاء الاشتراك المبدئي (`Subscription`) وربطه بباقة البداية.
- هذه العملية مغلفة بالكامل داخل معاملة قاعدة بيانات ذرية (`DB::transaction`)؛ إما أن تنجح كافة العناصر الثلاثة معاً أو تفشل معاً دون ترك سجلات معلقة.

### 2. إسناد الباقة الافتراضية عند التسجيل:
- يُسند لكل منشأة جديدة تلقائياً اشتراك في الباقة المجانية (`free`) كقيمة افتراضية، أو باقة البداية المحددة، لضمان امتلاك كل منشأة خطة عمل صالحة فور التأسيس.

### 3. ترقية وتبديل الباقات (`SubscriptionService::changePlan`):
- التحقق من وجود الباقة والسعر الفعلي المناسب لدورة الفوترة المطلوبة (`monthly` أو `yearly`).
- تحديث سجل الاشتراك، السعر، دورة الفوترة، وتاريخ انتهاء الفترة الحالية، وإرجاع بيانات المنشأة والاشتراك النظيفة فوراً للفرونت إند.

### 4. الإلغاء الآمن للاشتراك المدفوع (`SubscriptionService::cancelSubscription`):
- عند إلغاء أي اشتراك مدفوع:
  1. وسم سجل الاشتراك السابق بحالة ملغي (`canceled`).
  2. إعادة المنشأة فوراً وتلقائياً للباقة المجانية الافتراضية (`free`).
  3. حماية العمليات التشغيلية للمنشأة من التوقف المفاجئ.

### 5. الإنهاء الآمن لحساب المنشأة (`TenantService::terminateAccountSafely`):
- عند طلب إنهاء أو حذف الحساب:
  1. سحب وإلغاء كافة رموز الدخول الرقمية (Revoke All Sanctum Personal Access Tokens).
  2. تحويل حالة المنشأة إلى معلقة (`suspended`).
  3. الحفاظ على السجلات المشتركة في قاعدة البيانات دون كسر العلاقات الأجنبية (Foreign Keys).

---

## 3. معايير بساطة الواجهات ونظافة الأكواد (API Simplicity & Clean Code)

### بساطة البيانات ومكافحة التعقيد الزائد (Anti Over-Engineering):
- **الامتناع عن فرض الترقيم (Pagination) والحصص (Quotas)**:
  - ما لم يُطلب صراحة من المستخدم، تُعاد بيانات القوائم في الـ API كمصفوفات نقية ومباشرة مسلسلة عبر دالة الموديل المركزية (مثل: `$vendors = Vendor::with('category')->latest()->get()->map->toApiArray();`).
  - تجنب إقحام قيود الحصص (`quota`) أو عمليات الترقيم التعسفية التي تعقد استهلاك الفرونت إند المستقل في المراحل المبكرة.
- **الاعتماد على Route Model Binding القياسي**:
  - الاعتماد التام على حقن الموديلات المباشر في دوال المتحكم (`show`, `update`, `destroy`) عبر `Vendor $vendor` أو `VendorCategory $vendorCategory` لتقليص الأكواد وحذف الشروط المكررة.

### معايير التوثيق باللغة الإنجليزية في موديول الـ API:
- كافة الـ Docblocks والتعليقات في موديول الـ API تُكتب باللغة الإنجليزية الاحترافية حصراً.
- حظر ترقيم الخطوات (`// 1.`, `// 2.`) أو الشروح البديهية للعمليات المعتادة.

