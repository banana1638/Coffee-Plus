<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin - {{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[rgb(var(--cp-canvas))] font-sans text-[rgb(var(--cp-ink))] antialiased">
    <div class="min-h-screen">
        @include('layouts.admin-navigation')

        <main class="min-h-screen lg:pl-72">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>

</html>
