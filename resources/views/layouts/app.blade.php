<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

@php
    $initialAuthTab = in_array(request('auth'), ['login', 'register'], true) ? request('auth') : null;
@endphp

<body class="bg-[rgb(var(--cp-canvas))] font-sans text-[rgb(var(--cp-ink))] antialiased"
    x-data="{ authModal: @js($initialAuthTab) }"
    @open-auth-modal.window="authModal = $event.detail.tab"
    @close-auth-modal.window="authModal = null">
    <div class="min-h-screen">
        @include('layouts.navigation')

        @isset($header)
            <header class="border-b border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))]">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main>
            {{ $slot }}
        </main>
    </div>

    @guest
        <x-auth-modals />
    @endguest

    @stack('scripts')
</body>

</html>
