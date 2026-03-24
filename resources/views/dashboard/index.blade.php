<x-layout.app :header="$pageMeta['title']">
    
    <div class="bg-white dark:bg-gray-800 rounded-md shadow p-6">
        <h2 class="text-lg font-semibold text-gray-600 dark:text-gray-300">Hi {{ auth()->user()->name }}, welcome to</h2>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ config('app.name') }} <span class="text-blue-500">Apps</span></h1>
    </div>

</x-layout.app>