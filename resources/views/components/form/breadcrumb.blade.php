{{-- 
    Blade Component: x-form.breadcrumb

    Usage Examples:

    1. On Index Page:
    <x-form.breadcrumb :title="$pageMeta['title']" />

    2. On Form Page (Create / Edit):
    <x-form.breadcrumb 
        :title="$pageMeta['title']" 
        :resourceName="$resourceName" 
        :indexLink="route('secretgate19.' . $resourceName . '.index')" 
        :action="ucfirst($mode)" 
    />

    3. For Custom Breadcrumbs:
    You can customize the parameters or structure as needed.
--}}

@props([
    'title' => null,
    'resourceName' => null,
    'indexLink' => null,
    'action' => null,
    'baseLink' => 'secretgate19',
    'routeParam' => []
])

@php
    $resourceName = ucwords( str_replace('_', ' ', $resourceName))
@endphp

<div class="bg-white dark:bg-gray-800 rounded-t-lg shadow p-6 relative overflow-hidden">
    <div class="absolute right-0 top-0 hidden lg:block opacity-20 w-48 h-48 bg-no-repeat bg-cover"
         style="background-image:url('{{ asset("assets/img/icons/spot-illustrations/corner-4.png") }}');">
    </div>
    <div class="relative z-10">
        <h4 class="text-xl font-semibold text-gray-800 dark:text-white">{{ $title }}</h4>
        <nav class="text-sm text-gray-600 dark:text-gray-300 mt-2">
            <ol class="flex space-x-2">
                <li><a href="{{ route("$baseLink.dashboard", $routeParam) }}" class="hover:underline text-blue-600">Dashboard</a></li>                

                @if($indexLink)
                    <li>/</li>
                    <li><a href="{{ $indexLink }}" class="hover:underline text-blue-600">{{ $resourceName }}</a></li>
                @endif

                <li>/</li>
                <li class="text-gray-500 dark:text-gray-400">{{ $action ?? $title }}</li>
            </ol>
        </nav>
    </div>
</div>
