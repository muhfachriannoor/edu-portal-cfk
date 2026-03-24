<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We'll Be Back Soon</title>
    <script src="{{ asset('vendors/tailwind/tailwind.js') }}"></script>
</head>
<body class="bg-white text-gray-800 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
        
        <div>
            <h1 class="text-xl text-blue-400 font-medium mb-2">Under Maintenance</h1>
            <h2 class="text-5xl font-bold text-purple-600 mb-6">We'll Be Back Soon.</h2>
            <p class="text-gray-500 mb-4">
                We're busy upgrading with new technology. We apologize for the inconvenience.
            </p>
            <p class="text-gray-500 mb-6">
                You can contact us through one of these channels:
            </p>
            <div class="space-x-4">
                <a href="https://twitter.com" target="_blank" class="text-blue-500 hover:underline">Twitter</a>
                <a href="https://facebook.com" target="_blank" class="text-blue-500 hover:underline">Facebook</a>
            </div>

            <p class="mt-10 text-gray-400 text-sm">
                &copy; {{ date('Y') }} Unictive Media
            </p>
        </div>

        <div class="flex justify-center">
            <svg class="w-64 h-64" viewBox="0 0 512 512" fill="none">
                <defs>
                    <linearGradient id="gearGradient" x1="0" x2="1" y1="0" y2="1">
                        <stop offset="0%" stop-color="#7B2FF7" />
                        <stop offset="100%" stop-color="#22D3EE" />
                    </linearGradient>
                </defs>
                <path fill="url(#gearGradient)" d="M256 128a128 128 0 1 1 0 256 128 128 0 0 1 0-256zm0-128c17.7 0 32 14.3 32 32v32a192 192 0 1 1-64 0V32c0-17.7 14.3-32 32-32z"/>
            </svg>
        </div>

    </div>
</body>
</html>
