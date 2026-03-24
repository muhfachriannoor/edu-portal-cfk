<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ config('app.name') }}</title>
    
    <link href="{{ asset('vendors/fontawesome_6.5.0/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    
    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset(config('app.logo_path'))}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('app.logo_path')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset(config('app.logo_path')) }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(config('app.logo_path')) }}">
    <link rel="manifest" href="{{ asset('assets/img/favicons/manifest.json') }}">
    <meta name="msapplication-TileImage" content="{{ asset(config('app.logo_path'))}}">
    

    <script src="{{ asset('vendors/alpine@3.14.9/alpine.min.js') }}" defer></script>
    <script src="{{ asset('vendors/tailwind/tailwind.js') }}"></script>

</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen p-4">

    <!-- Logo + Project Title (stacked vertically) -->
    <div class="flex flex-col items-center space-y-3 mb-8">
        <!-- Logo -->
        <img src="{{ asset(config('app.logo_png_path'))}}" 
            alt="Logo" 
            class="w-32 md:w-48 h-auto">

        <!-- Project Title -->
        {{-- <h1 class="text-2xl md:text-3xl font-bold text-gray-800 text-center">
            {{ config('app.name') }}
        </h1> --}}
    </div>


    <!-- Login Box -->
    <div class="w-full max-w-md p-6 md:p-8 bg-white rounded-2xl shadow-md">
        <div class="mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800">Login as Superadmin</h2>
            <p class="text-gray-400">Enter your details below to login</p>
        </div>

        <!-- Error Alert -->
        @if($errors->any())
            <div class="mb-4 p-3 md:p-4 rounded-lg bg-red-100 text-red-700 border border-red-300 flex items-center gap-2 text-sm md:text-base">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" x-data="{ show: false }" class="space-y-4 md:space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" placeholder="Email" required
                       class="mt-1 w-full px-4 py-3 text-base md:py-2 border rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" id="password" name="password" placeholder="Password" required
                        class="mt-1 w-full px-4 py-3 text-base md:py-2 pr-12 border rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" />

                    <!-- Font Awesome Toggle Icon -->
                    <div class="absolute inset-y-0 right-0 flex items-center px-3 cursor-pointer text-gray-500"
                        @click="show = !show">
                        <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-lg"></i>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 text-base md:py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                Sign In
            </button>
        </form>
    </div>

    <footer class="text-xs text-gray-400 dark:text-gray-500 text-right mt-5">
      {{ date('Y') }} © Sarinah | Panggung Karya Indonesia
    </footer>
</body>
</html>