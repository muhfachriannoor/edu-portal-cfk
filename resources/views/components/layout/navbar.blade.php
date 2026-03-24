<!-- Header -->
<header class="bg-white dark:bg-gray-800 dark:text-white shadow flex items-center px-4 py-3 h-16">
    <!-- Sidebar toggle (all screen sizes) -->
    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 dark:text-gray-200 mr-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Spacer to push content to right -->
    <div class="flex-1"></div>

    <!-- Header controls -->
    <div class="flex items-center gap-4">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="focus:outline-none">
                <img src="{{ asset(config('app.logo_path')) }}" alt="Profile" class="w-10 h-10 rounded-full object-contain">
            </button>
            <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10">
                <a href="{{ route('admin.logout') }}" class="block border px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Logout</a>
            </div>
        </div>
    </div>
</header>