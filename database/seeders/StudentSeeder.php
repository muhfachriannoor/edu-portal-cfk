<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StudentSeeder extends Seeder
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
                    'name' => 'course',
                    'alias' => 'Course',
                    'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
                ],
            ],
        ];

        Permission::where('guard_name', 'student')->update(['is_show' => false]); // Nonaktifkan semua, kemudian diaktifkan ketika updateOrCreate
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
                                    'guard_name' => 'student'
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
                                'guard_name' => 'student',
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

        $role = Role::firstOrCreate(['guard_name' => 'student', 'name' => 'Student']);
        $role->syncPermissions(Permission::where('guard_name', 'student')->pluck('name')->toArray());

        $dataUsers = [
            [
                'name' => 'Student1',
                'email' => 'student1@mail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Student2',
                'email' => 'student2@mail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Student3',
                'email' => 'student3@mail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Student4',
                'email' => 'student4@mail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Student5',
                'email' => 'student5@mail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        ];

        foreach ($dataUsers as $user) {
            $newUser = User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
            User::find($newUser)->assignRole('Student');
        }
    }
}
