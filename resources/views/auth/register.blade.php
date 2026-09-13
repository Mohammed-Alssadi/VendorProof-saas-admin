@extends('layouts.guest')

@section('title', 'إنشاء حساب مسؤول')

@section('content')
<div class="auth-header">
    <div class="auth-brand-mark">
        <i class="ri-shield-check-fill"></i>
    </div>
    <h1 class="auth-brand-title">Vendor<span class="brand-accent">Proof</span></h1>
    <p>إنشاء حساب مسؤول جديد لإدارة امتثال الموردين</p>
</div>

<form action="{{ route('register') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="name" class="form-label">الاسم الكامل</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            class="form-control" 
            value="{{ old('name') }}" 
            required 
            autofocus 
            placeholder="مثال: أحمد محمد"
        >
    </div>

    <div class="form-group">
        <label for="email" class="form-label">البريد الإلكتروني</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            value="{{ old('email') }}" 
            required 
            placeholder="admin@example.com"
        >
    </div>

    <div class="form-group">
        <label for="password" class="form-label">كلمة المرور</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-control" 
            required 
            placeholder="6 أحرف على الأقل"
        >
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
        <input 
            type="password" 
            id="password_confirmation" 
            name="password_confirmation" 
            class="form-control" 
            required 
            placeholder="أعد إدخال كلمة المرور"
        >
    </div>

    <button type="submit" class="btn-primary">إنشاء الحساب ودخول اللوحة</button>
</form>

<div class="auth-footer">
    <span>لديك حساب بالفعل؟</span>
    <a href="{{ route('login') }}">تسجيل الدخول</a>
</div>
@endsection
