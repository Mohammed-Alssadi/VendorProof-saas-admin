{{-- حاوية صامتة لنقل رسائل الجلسة البرمجية إلى محرك الإشعارات المستقل --}}
<div id="flashToastData" 
    class="flash-toast-data"
    data-success="{{ session('success') ?? '' }}" 
    data-error="{{ session('error') ?? '' }}" 
    data-errors="{{ isset($errors) && $errors->any() ? json_encode(array_values(array_unique($errors->all()))) : '' }}">
</div>

{{-- محرك Notyf المركزي المستقل --}}
<script src="{{ asset('js/notyf-init.js') }}?v={{ filemtime(public_path('js/notyf-init.js')) }}"></script>
