<!-- Sidebar -->
<div
    id="sidebar"
    x-show="sidebarOpen"
    x-transition:enter="transition transform duration-300 ease-out"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition transform duration-300 ease-in"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    x-cloak
    class="fixed top-0 left-0 lg:block z-20 w-64 h-full text-black transform overflow-y-auto bg-[#F1F5F9]"
>
    <!-- Header Sidebar: White Background -->
    <div class="flex items-center justify-between p-4 border-b border-blue-300 dark:border-black/30 bg-white h-16">
        <div class="flex items-center gap-2">
            <img src="{{ asset(config('app.logo_path')) }}" class="w-8 h-8" alt="Logo">
            <span class="text-lg font-semibold">{{ config('app.name') }}</span>
        </div>
        <!-- Close button (mobile) -->
        <button class="lg:hidden text-black text-xl" @click="sidebarOpen = false">&times;</button>
    </div>
@php
$targetRoute = 'admin';
$active = fn($pattern) => request()->routeIs("{$targetRoute}.$pattern") ? 'bg-[#FFFFFF]' : '';

$menuSections = [
    // Main Menu Section
    'Main Menu' => [
        // Dashboard
        [
            'type' => 'single',
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fas fa-gauge-high',
            'route' => "{$targetRoute}.dashboard",
            'always_show' => true,
        ],
        // Category
        [
            'type' => 'single',
            'key' => 'category',
            'label' => 'Category',
            'route' => "{$targetRoute}.category.index",
            'icon' => 'fas fa-bell',
            'always_show' => true,
        ],
        // Course
        [
            'type' => 'single',
            'key' => 'course',
            'label' => 'Course',
            'route' => "{$targetRoute}.course.index",
            'icon' => 'fas fa-bell',
            'always_show' => true,
        ],

        // Notification
        // [
        //     'type' => 'single',
        //     'key' => 'notification',
        //     'label' => 'Notification',
        //     'route' => "{$targetRoute}.notification.index",
        //     'icon' => 'fas fa-bell',
        // ],
    ],
];

@endphp

<nav class="p-4 space-y-2 text-sm">
    @php
        function superadminOnlyAccess($key){
            $specialPrivilege = [
                'api_documentation'
            ];

            // If the key is in the special privilege list, only allow super admins
            if (in_array($key, $specialPrivilege)) {
                return auth()->user()->isSuperAdmin();
            }

            // All other menu items are allowed
            return true;
        }

        function hasActiveChild($items) {

            foreach ($items as $child) {
                if (($child['type'] ?? '') === 'single' && request()->routeIs("*.{$child['key']}.*")) {
                    return true;
                }
                if (($child['type'] ?? '') === 'dropdown' && hasActiveChild($child['items'] ?? [])) {
                    return true;
                }
            }
            return false;
        }

        function hasAllowedChild($item, $resources) {
            if (!empty($item['key']) && in_array($item['key'], $resources)) {
                return true;
            }
            if (!empty($item['items'])) {
                foreach ($item['items'] as $child) {
                    if (hasAllowedChild($child, $resources)) {
                        return true;
                    }
                }
            }
            return false;
        }

        function renderMenuItems($items, $resources, $active) {
            foreach ($items as $item) {
                // Skip if superadmin-only and current user is not superadmin
                if (!superadminOnlyAccess($item['key'] ?? '')) {
                    continue;
                }

                // Check visibility rules
                $showItem = ($item['always_show'] ?? false) || hasAllowedChild($item, $resources);
                if (!$showItem) {
                    continue;
                }

                if ($item['type'] === 'single') {
                    $href = $item['url'] ?? route($item['route'] ?? ($item['key'] . ".index"));
                    $target = !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '';

                    echo '<a href="' . $href . '"'. $target .' 
                        class="flex items-center gap-2 px-2 py-2 rounded hover:bg-[#06B6D4]/20 ' 
                        . $active(($item['key'] ?? '') . '.*') . '">
                        <i class="' . $item['icon'] . ' w-5"></i>
                        ' . $item['label'] . '
                    </a>';
                } elseif ($item['type'] === 'dropdown') {
                    $isOpen = hasActiveChild($item['items']);
                    echo '<div x-data="{ open: ' . ($isOpen ? 'true' : 'false') . ' }" class="dropdown">';
                    
                    echo '<button @click="open = !open" 
                            class="w-full flex items-center justify-between gap-2 px-2 py-2 rounded hover:bg-[#06B6D4]/20">
                            <div class="flex items-center gap-2">
                                <i class="' . $item['icon'] . ' w-5"></i> ' . $item['label'] . '
                            </div>
                            <i :class="open ? \'fas fa-chevron-up\' : \'fas fa-chevron-down\'"></i>
                        </button>';
                    
                    echo '<div x-show="open" x-transition class="pl-4 space-y-1">';
                    renderMenuItems($item['items'], $resources, $active);
                    echo '</div>';

                    echo '</div>';
                }
            }
        }
    @endphp

    @foreach($menuSections as $section => $items)
        @if(count($items) > 0)
            <p class="mt-4 mb-1 text-xs uppercase tracking-wide opacity-75">{{ $section }}</p>
        @endif
        
        @php renderMenuItems($items, $resources, $active); @endphp
    @endforeach
</nav>
</div>