<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'TozPie Shop' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="{{ route('shop.index') }}">
            <span class="brand-mark">K</span>
            <span>KitoShopAI</span>
        </a>

        <div class="nav-group">Tong quan</div>
        <a class="nav-link {{ request()->routeIs('shop.index') ? 'active' : '' }}" href="{{ route('shop.index') }}">
            <span>HM</span> Trang chu
        </a>

        <div class="nav-group">Cua hang & vi</div>
        <a class="nav-link {{ request()->routeIs('shop.index') ? 'active' : '' }}" href="{{ route('shop.index') }}">
            <span>SP</span> Cua hang
        </a>
        @auth
            @if (! auth()->user()->is_admin)
                <a class="nav-link {{ request()->routeIs('wallet.*') ? 'active' : '' }}" href="{{ route('wallet.topup') }}">
                    <span>VI</span> Nap tien
                </a>
                <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
                    <span>DH</span> Don hang
                </a>
            @endif
        @endauth

        <div class="nav-group">Affiliate & API</div>
        <a class="nav-link" href="#"><span>AF</span> Affiliate</a>
        @auth
            @if (! auth()->user()->is_admin)
                <a class="nav-link {{ request()->routeIs('api.docs') ? 'active' : '' }}" href="{{ route('api.docs') }}">
                    <span>AP</span> Tai lieu API
                </a>
            @else
                <a class="nav-link" href="#"><span>AP</span> Tai lieu API</a>
            @endif
        @else
            <a class="nav-link" href="{{ route('login') }}"><span>&lt;/&gt;</span> Tai lieu API</a>
        @endauth
        <a class="nav-link {{ request()->routeIs('guides.*') ? 'active' : '' }}" href="{{ route('guides.cursor-pro') }}"><span>?</span> Huong dan</a>

        @auth
            <div class="nav-group">Tai khoan</div>
            @if (auth()->user()->is_admin)
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span>AD</span> Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <span>PR</span> San pham
                </a>
                <a class="nav-link {{ request()->routeIs('admin.finance.*') ? 'active' : '' }}" href="{{ route('admin.finance.index') }}">
                    <span>$</span> Tai chinh
                </a>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <span>#</span> Don hang
                </a>
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                    <span>ST</span> Cau hinh
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="nav-link nav-button" type="submit"><span>--</span> Dang xuat</button>
            </form>
        @else
            <div class="nav-group">Tai khoan</div>
            <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">
                <span>IN</span> Dang nhap
            </a>
            <a class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}">
                <span>+</span> Dang ky
            </a>
        @endauth
    </aside>

    <main class="page">
        <div class="topbar">
            <div class="breadcrumb">Home <span>/</span> {{ $crumb ?? 'Cua hang' }}</div>
            <div class="account-box">
                @auth
                    @if (! auth()->user()->is_admin)
                        <a class="balance-pill" href="{{ route('wallet.topup') }}">
                            So du: <strong>{{ number_format(auth()->user()->wallet_balance, 0, ',', '.') }}d</strong>
                        </a>
                    @endif
                    <span class="account-name">{{ auth()->user()->name }}</span>
                    @if (auth()->user()->is_admin)
                        <a class="outline-button" href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <span class="role-pill">Khach hang</span>
                    @endif
                @else
                    <a class="outline-button" href="{{ route('login') }}">Dang nhap</a>
                    <a class="primary-button small" href="{{ route('register') }}">Dang ky</a>
                @endauth
            </div>
        </div>

        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
