@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="auth-header">
    <div class="auth-brand-mark">
        <i class="ri-shield-check-fill"></i>
    </div>
    <h1 class="auth-brand-title">Vendor<span class="brand-accent">Proof</span></h1>
    <p>منصة إدارة الامتثال والاعتماد المؤسسي للموردين</p>
</div>

<form action="{{ route('login') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="email" class="form-label">البريد الإلكتروني</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            value="{{ old('email') }}" 
            required 
            autofocus 
            placeholder="admin@platform.com"
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
            placeholder="••••••••"
        >
    </div>

    <div class="form-check">
        <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
        <label for="remember">تذكر تسجيل الدخول</label>
    </div>

    <button type="submit" class="btn-primary">تسجيل الدخول</button>
</form>

<div class="auth-footer">
    <span>هل تحتاج إلى تسجيل مسؤول جديد؟</span>
    <a href="{{ route('register') }}">إنشاء حساب مسؤول</a>
</div>
@endsection
