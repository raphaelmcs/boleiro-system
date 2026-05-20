<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Boleiro Club Store') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-white">
        <div class="min-h-screen flex">
            
            <!-- Left Side - Form -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 sm:p-12 lg:p-24 relative">
                
                <div class="w-full max-w-md relative z-10">
                    
                    <!-- Large Logo -->
                    <div class="flex flex-col items-center mb-12">
                        <x-application-logo class="w-auto h-40 md:h-48 mb-6" />
                        <h1 class="text-3xl font-extrabold text-gray-900 text-center">Boleiro Club Store</h1>
                    </div>

                    <div class="mb-8 text-center">
                        <h2 class="text-2xl font-bold text-gray-800">Acesse sua conta</h2>
                        <p class="text-sm text-gray-500 mt-2">Insira seus dados abaixo para entrar no sistema.</p>
                    </div>

                    {{ $slot }}
                    
                    <p class="text-center text-xs text-gray-400 mt-12">
                        &copy; {{ date('Y') }} Boleiro Club Store.<br>Todos os direitos reservados.
                    </p>
                </div>
            </div>

            <!-- Right Side - Image -->
            <div class="hidden lg:block lg:w-1/2 bg-emerald-900 relative overflow-hidden">
                <!-- Imagem de fundo (futebol/esporte) -->
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=1500&auto=format&fit=crop')] bg-cover bg-center"></div>
                <!-- Camada escura por cima da imagem para dar contraste -->
                <div class="absolute inset-0 bg-emerald-900/40 mix-blend-multiply"></div>
            </div>

        </div>
    </body>
</html>
