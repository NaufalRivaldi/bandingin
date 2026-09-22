<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Bandingin.id' }}</title>
        <meta name="description" content="{{ $description ?? 'Adu spesifikasi, harga, dan performa dua HP berdampingan. Kami tandai mana yang menang di tiap baris.' }}">

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#2563eb">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        @stack('head')
    </head>
    <body class="overflow-x-hidden bg-white">
        {{ $slot }}

        @livewireScripts

        @stack('scripts')
    </body>
</html>
