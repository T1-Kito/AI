@extends('layouts.app', ['title' => 'Dang ky', 'crumb' => 'Dang ky'])

@section('content')
    <section class="auth-shell">
        <div class="auth-copy">
            <span class="eyebrow">New Customer</span>
            <h1>Tao tai khoan mua hang</h1>
            <p>Sau khi dang ky, ban co the mua san pham ngay. Tai khoan moi mac dinh la khach hang, khong vao duoc admin.</p>
        </div>

        <form class="auth-card" method="POST" action="{{ route('register.store') }}">
            @csrf
            <h2>Dang ky</h2>
            <label>
                Ho ten
                <input name="name" value="{{ old('name') }}" placeholder="Ten cua ban" required autofocus>
            </label>
            <label>
                Email
                <input name="email" type="email" value="{{ old('email') }}" placeholder="ban@example.com" required>
            </label>
            <label>
                Mat khau
                <input name="password" type="password" minlength="6" required>
            </label>
            <label>
                Nhap lai mat khau
                <input name="password_confirmation" type="password" minlength="6" required>
            </label>
            <button class="primary-button wide" type="submit">Tao tai khoan</button>
            <p class="auth-note">Da co tai khoan? <a href="{{ route('login') }}">Dang nhap</a></p>
        </form>
    </section>
@endsection
