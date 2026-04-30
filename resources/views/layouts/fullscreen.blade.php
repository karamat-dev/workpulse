<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'muSharp') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('uploads/logo/favicon.png') }}">

        @stack('head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html, body { height: 100%; }
            body { margin: 0; }
        </style>
    </head>
    <body class="font-sans antialiased">
        {{ $slot }}
    </body>
</html>
