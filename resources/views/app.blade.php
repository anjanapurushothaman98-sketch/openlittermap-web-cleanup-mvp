<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('header')
    <style>
        .site-topbar {
            background: #e8e6c2;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: sans-serif;
            border-bottom: 1px solid #d4d2a8;
        }
        .site-topbar .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 700;
            color: #2d3a1f;
            text-decoration: none;
        }
        .site-topbar .logo-mark {
            width: 32px;
            height: 32px;
            background: #6b7a4a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #faf9f0;
            font-size: 18px;
        }
        .site-topbar .logo-sub {
            font-size: 10px;
            font-weight: 400;
            color: #6b7a4a;
            display: block;
            margin-top: -2px;
        }
        .site-topbar nav {
            display: flex;
            gap: 24px;
        }
        .site-topbar nav a {
            color: #2d3a1f;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .site-topbar nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
<div class="site-topbar">
        <a href="/" class="logo">
            <img src="/assets/pickitup-logo.png" alt="Pick!tUp" style="height:36px;">
            <span>
                Pick!tUp
                <span class="logo-sub">make the earth a better place</span>
            </span>
        </a>
        <nav>
            <a href="/map">Map</a>
            <a href="/profile">Profile</a>
            <a href="/auth">Login / Register</a>
        </nav>
    </div>

    <div id="app">
        @yield('content')
    </div>

    <script>
        window.initialProps = {!! json_encode([
            'auth'           => $auth ?? false,
            'user'           => $user ?? null,
            'impersonating'  => $impersonating ?? false,
        ]) !!};
    </script>

    <script src="https://js.stripe.com/v3"></script>
</body>
</html>