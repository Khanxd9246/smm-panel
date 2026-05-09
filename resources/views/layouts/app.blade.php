<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <style>
        :root {
            --primary: #3b82f6;
            --bg-dark: #0f172a;
            --sidebar-bg: #111827;
            --card-bg: #1e293b;
            --border-color: #1f2937;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        aside.sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 50;
        }

        .brand {
            padding: 2rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 0 1rem;
        }

        .nav-group-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin: 1.5rem 0 0.5rem 0.75rem;
            font-weight: 700;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            background-color: #1f2937;
            color: white;
        }

        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }

        .nav-link .material-symbols-outlined {
            font-size: 1.25rem;
        }

        /* Main Content */
        main.main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        header.top-header {
            height: 64px;
            background-color: var(--sidebar-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .page-wrapper {
            padding: 2rem;
        }

        /* Flash Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success { background-color: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid #22c55e; }
        .alert-danger { background-color: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid #ef4444; }

        @media (max-width: 768px) {
            aside.sidebar { display: none; }
            main.main-content { margin-left: 0; }
        }
    </style>
    @yield('css')
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            {{ config('app.name') }}
        </div>

        <nav class="nav-scroll">
            <div class="nav-group-label">Menu</div>
            
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('orders.create') }}" class="nav-link {{ request()->routeIs('orders.create') ? 'active' : '' }}">
                <span class="material-symbols-outlined">add_shopping_cart</span>
                <span>New Order</span>
            </a>

            <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                <span class="material-symbols-outlined">list_alt</span>
                <span>History</span>
            </a>

            <a href="{{ route('funds.index') }}" class="nav-link {{ request()->routeIs('funds.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined">wallet</span>
                <span>Add Funds</span>
            </a>

            @if(auth()->check() && auth()->user()->is_admin)
                <div class="nav-group-label" style="color: #ef4444;">Administration</div>

                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">monitoring</span>
                    <span>Admin Overview</span>
                </a>

                <a href="{{ route('admin.fund_accounts.index') }}" class="nav-link {{ request()->routeIs('admin.fund_accounts.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">payments</span>
                    <span>Payment Methods</span>
                </a>

                <a href="{{ route('admin.fund-requests.index') }}" class="nav-link {{ request()->routeIs('admin.fund-requests.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">pending_actions</span>
                    <span>Fund Requests</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">group_manage</span>
                    <span>Users</span>
                </a>

                <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Services</span>
                </a>

                <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">settings</span>
                    <span>Settings</span>
                </a>
            @endif
        </nav>

        <div style="padding: 1rem; border-top: 1px solid var(--border-color);">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-link" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div>
                <h2 style="margin: 0; font-size: 1.1rem; font-weight: 600;">@yield('page-title', 'Dashboard')</h2>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.9rem; color: var(--text-muted);">{{ auth()->user()->name }}</span>
                <div style="height: 35px; width: 35px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin:0; padding-left: 1.25rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @yield('js')
</body>
</html>
