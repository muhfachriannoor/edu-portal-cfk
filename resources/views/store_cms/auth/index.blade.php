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
<body class="bg-gray-200 flex flex-col items-center justify-center min-h-screen p-4">

    <!-- Main Logo at Top Center -->
    <div class="flex justify-center mt-4 mb-6">
        <img src="{{ asset(config('app.logo_png_path'))}}" 
             alt="Main Logo" 
             class="w-32 md:w-48 h-auto">
    </div>
        
    <!-- Login Box with Store Logo Above -->
    <div class="w-full max-w-md p-6 md:p-8 bg-white rounded-2xl shadow-md flex flex-col items-center">
        
        <!-- Store Logo -->
        <img src="{{ $store->logo }}" 
             alt="Store Logo" 
             class="w-16 h-16 mb-4 rounded-full object-cover">

        <!-- Login Title -->
        <h2 class="text-xl md:text-2xl font-bold mb-6 text-center text-gray-800">
            Store {{ $store->name }} Login
        </h2>

        <!-- Error Alert -->
        @if($errors->any())
            <div class="mb-4 p-3 md:p-4 rounded-lg bg-red-100 text-red-700 border border-red-300 flex items-center gap-2 text-sm md:text-base">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('store_cms.login', $store) }}" x-data="{ show: false }" class="w-full space-y-4 md:space-y-5">
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
</body>
</html>