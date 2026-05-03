@extends('layouts.app', ['title' => 'Dang nhap', 'crumb' => 'Dang nhap'])

@section('content')
    <section class="auth-shell">
        <div class="auth-copy" style="background-image: url('{{ asset('images/anhlogin.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        </div>

        <form class="auth-card" method="POST" action="{{ route('login.store') }}">
            @csrf
            <h2>Dang nhap</h2>
            <label>
                Email
                <input name="email" type="email" value="{{ old('email') }}" placeholder="user@tozpie.test" required autofocus>
            </label>
            <label>
                Mat khau
                <input name="password" type="password" placeholder="password" required>
            </label>
            <label class="check-line">
                <input name="remember" type="checkbox" value="1">
                Ghi nho dang nhap
            </label>
            <button class="primary-button wide" type="submit">Dang nhap</button>
            <a class="outline-button wide" href="{{ route('auth.google.redirect') }}">Dang nhap bang Google</a>
            <p class="auth-note">Chua co tai khoan? <a href="{{ route('register') }}">Dang ky ngay</a></p>
        </form>
    </section>
@endsection
