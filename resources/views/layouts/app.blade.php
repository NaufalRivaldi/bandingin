<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Bandingin.id' }}</title>
        <meta name="description" content="{{ $description ?? 'Adu spesifikasi, harga, dan performa dua HP berdampingan. Kami tandai mana yang menang di tiap baris.' }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="overflow-x-hidden bg-white">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
