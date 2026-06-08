<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('survey.public.title') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @include('partials.vite-assets')
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-col items-center justify-center gap-4 text-center">
            <img src="{{ asset('storage/logo-pense.png') }}" alt="Colégio Pense" class="h-24 w-24">
            <h1 class="text-2xl font-semibold text-gray-900">
                {{ __('survey.public.title') }}
            </h1>
        </header>

        <main class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
            {{ $slot }}
        </main>

        <footer class="mt-8 text-center text-xs text-gray-500">
            &copy; {{ now()->year }} Colégio Pense
        </footer>
    </div>

    @livewireScripts
</body>
</html>
