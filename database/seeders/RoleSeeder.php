<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $actionsByResource = [
            'Main Menu' => [
                [
                    'group' => 'Product',
                    'resources' => [
                        [
                            'name' => 'master_product',
                            'alias' => 'Master Product',
                            'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                        ],
                        [
                            'name' => 'category',
                            'alias' => 'Category',
                            'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                        ],
                        [
                            'name' => 'brand',
                            'alias' => 'Brand',
                            'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                        ],
                        [
                            'name' => 'courier',
                            'alias' => 'Courier',
                            'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                        ],
                        [
                            'name' => 'product_option',
                            'alias' => 'Product Option',
                            'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                        ],
                    ],
                ],
                [
                    'name' => 'course',
                    'alias' => 'Course',
                    'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                ],
            ],
        ];

        Permission::where('guard_name', 'admin')->update(['is_show' => false]); // Nonaktifkan semua, kemudian diaktifkan ketika updateOrCreate
        foreach ($actionsByResource as $category => $resources) {
            foreach ($resources as $resource) {

                // Case 1: grouped resources
                if (isset($resource['group']) && isset($resource['resources'])) {
                    $group = $resource['group'];

                    foreach ($resource['resources'] as $res) {
                        $name  = $res['name'];
                        $alias = $res['alias'];
                        $actions = $res['actions'];

                        foreach ($actions as $action) {
                            Permission::updateOrCreate(
                                [
                                    'name' => "{$name}.{$action}",
                                    'guard_name' => 'admin'
                                ],
                                [
                                    'category'   => $category,
                                    'group'      => $group,
                                    'alias'      => $alias,
                                    'is_show'    => true
                                ]
                            );
                        }
                    }
                }

                // Case 2: single resource
                else {
                    $group = null;
                    $name  = $resource['name'];
                    $alias = $resource['alias'];
                    $actions = $resource['actions'];

                    foreach ($actions as $action) {
                        Permission::updateOrCreate(
                            [
                                'name' => "{$name}.{$action}",
                                'guard_name' => 'admin',
                            ],
                            [
                                'category'   => $category,
                                'group'      => $group,
                                'alias'      => $alias,
                                'is_show'    => true
                            ]
                        );
                    }
                }
            }
        }

        $role = Role::firstOrCreate(['guard_name' => 'admin', 'name' => 'Superadmin']);
        $role->syncPermissions(Permission::where('guard_name', 'admin')->pluck('name')->toArray());
        User::find(1)->assignRole('Superadmin');
    }
}
