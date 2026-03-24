<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | {{ config('app.name') }}</title>
    
    <link href="{{ asset('vendors/fontawesome_6.5.0/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    
    <!-- Favicons -->
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

    <div class="flex flex-col items-center justify-center flex-grow px-4 w-full">
        <!-- Logo -->
        <div class="text-center my-8">
            <img src="{{ asset(config('app.logo_png_path'))}}" 
                alt="Logo" 
                class="w-32 md:w-48 h-auto">
        </div>

        <!-- Form Card (Transparent background, subtle shadow) -->
        <div class="w-full max-w-md p-8 rounded-2xl border border-gray-200 shadow-sm backdrop-blur-sm bg-white/5">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">
                Reset Password
            </h2>

            <form id="form-validation" 
                name="form-validation" 
                action="{{ route('api.password.update') }}" 
                method="POST" 
                class="space-y-4">
                
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email -->
                <div>
                    <input 
                        type="email"
                        id="validation-email"
                        name="email"
                        value="{{ $email ?? old('email') }}"
                        placeholder="Email Address"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required readonly
                    >
                </div>

                <!-- Password -->
                <div>
                    <input 
                        type="password"
                        id="validation-password"
                        name="password"
                        placeholder="New Password"
                        minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                        autocomplete="off"
                        autofill="off"
                    >
                </div>

                <!-- Confirm Password -->
                <div>
                    <input 
                        type="password"
                        id="validation-password-confirmation"
                        name="password_confirmation"
                        placeholder="Confirm New Password"
                        minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <!-- Submit -->
                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition-colors duration-200">
                    Change Password
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center text-gray-500 py-6 text-sm">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>
</body>
</html>
