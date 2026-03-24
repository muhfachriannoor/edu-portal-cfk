<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <!-- Breadcrumb & Page Header -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.' . $resourceName . '.index')" 
            :action="ucfirst($mode)" 
        />

        <!-- Form Card -->
        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf

                <!-- Card Body -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">

                    <x-form.text 
                        id="name" 
                        label="Name" 
                        :value="old('name', $privilege->name)" 
                        :required="true"
                        :disabled="$mode === 'show'" 
                    />

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Permissions <span class="text-red-500">*</span>
                    </label>

                    @error("permissions")
                        <span class="text-red-500 text-sm mt-1 block">
                            {{ $message }}
                        </span>
                    @enderror
                    
                    <nav class="flex items-center relative menu-project mt-2">
                        <ul class="flex space-x-10">
                            @foreach($lists['categories'] as $key => $category)
                                <li>
                                    <a class="nav-tab active text-gray-600 pb-1 transition" 
                                    href="#" data-target="{{ $category }}">
                                        {{ $category }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </div>
                
                @foreach($lists['permissions'] as $category => $groups)
                    <div class="px-6 py-4 hidden" data-tab="{{ $category }}">
                        <h2 class="text-xl font-bold mt-6 mb-2">{{ $category }}</h2>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-300 rounded-lg divide-y">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 border text-center">
                                            <input type="checkbox" class="check-all-table w-4 h-4">
                                        </th>
                                        <th class="px-4 py-2 border text-left">Menu</th>
                                        <th class="px-4 py-2 border">ViewAny</th>
                                        <th class="px-4 py-2 border">View</th>
                                        <th class="px-4 py-2 border">Create</th>
                                        <th class="px-4 py-2 border">Update</th>
                                        <th class="px-4 py-2 border">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groups as $groupName => $children)
                                        @if(count($children) === 1)
                                            {{-- Case 1: Single child --}}
                                            @php $child = $children[0]; @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 border text-center">
                                                    <input type="checkbox" class="w-4 h-4">
                                                </td>
                                                <td class="px-4 py-2 border">
                                                    <b>{{ $child['alias'] ?? ucfirst($child['name']) }}</b>
                                                </td>
                                                @foreach(['viewAny','view','create','update','delete'] as $perm)
                                                    @php
                                                        $action = collect($child['action'])->firstWhere('action', $perm);
                                                    @endphp
                                                    <td class="px-4 py-2 border text-center">
                                                        @if($action && !empty($action['id']))
                                                            <input type="checkbox" class="w-4 h-4" value="{{ $action['id'] }}" name="permissions[]" {{ in_array($action['id'], $lists['currentPermissions']) ? 'checked' : '' }}>
                                                        @else
                                                            <i class="fa fa-times text-red-500"></i>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @else
                                            {{-- Case 2: Multiple children (Group) --}}
                                            <tr class="bg-gray-50">
                                                <td></td>
                                                <td colspan="7" class="px-4 py-2 border font-semibold">
                                                    {{ $groupName }}
                                                </td>
                                            </tr>
                                            @foreach($children as $child)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 border text-center">
                                                        <input type="checkbox" class="w-4 h-4">
                                                    </td>
                                                    <td class="px-4 py-2 border ps-10">
                                                        <i>{{ $child['alias'] ?? ucfirst($child['name']) }}</i>
                                                    </td>
                                                    @foreach(['viewAny','view','create','update','delete'] as $perm)
                                                        @php
                                                            $action = collect($child['action'])->firstWhere('action', $perm);
                                                        @endphp
                                                        <td class="px-4 py-2 border text-center">
                                                            @if($action && !empty($action['id']))
                                                                <input type="checkbox" class="w-4 h-4" value="{{ $action['id'] }}" name="permissions[]" {{ in_array($action['id'], $lists['currentPermissions']) ? 'checked' : '' }}>
                                                            @else
                                                                <i class="fa fa-times text-red-500"></i>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach


                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl='optional($privilege)->id ? route("secretgate19.{$resourceName}.edit", $privilege->id) : null'  
                    :backUrl='route("secretgate19.{$resourceName}.index")' 
                />
            </form>
        </div>
    </div>

</x-layout.app>

<script>
    $(document).ready(function () {
        // Handle nav-tab click
        $('.nav-tab').on('click', function (e) {
            e.preventDefault();

            var target = $(this).data('target');

            $('.nav-tab').removeClass('font-bold text-black-700 border-b-[2px] border-black');
            $(this).addClass('font-bold text-black-700 border-b-[2px] border-black');

            $('[data-tab]').addClass('hidden');
            $('[data-tab="' + target + '"]').removeClass('hidden');
        });

        $('.nav-tab').first().trigger('click'); // Show first tab & table by default

        // ✅ Handle "row select all"
        $(document).on('change', 'tbody tr td:first-child input[type="checkbox"]', function () {
            var checked = $(this).prop('checked');
            $(this).closest('tr').find('td input[type="checkbox"]').prop('checked', checked);
        });

        // ✅ Handle "table select all" (header checkbox)
        $(document).on('change', '.check-all-table', function () {
            var checked = $(this).prop('checked');
            var $table = $(this).closest('table');
            $table.find('tbody input[type="checkbox"]').prop('checked', checked);
        });
    });
</script>


