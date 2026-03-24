<x-layout.app-single :header="$pageMeta['title']">
    <main class="min-h-screen flex items-center justify-center text-center">
        <div class="inline-flex items-center space-x-4 p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg">
            <div class="spinner-shadow">
                <svg class="h-12 w-12 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>

            <div class="text-left">
                <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 dark:text-gray-100">On progress</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">We’re working on it — check back soon.</p>
            </div>
        </div>
    </main>

</x-layout.app-single>