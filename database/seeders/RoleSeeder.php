<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Tam yetkili sistem yoneticisi',
                'permissions' => ['*'],
            ],
            [
                'name' => 'hr_manager',
                'display_name' => 'IK Yoneticisi',
                'description' => 'Is ilanlari, adaylar ve mulakatlar uzerinde tam yetki',
                'permissions' => [
                    'jobs.*',
                    'candidates.*',
                    'interviews.*',
                    'reports.*',
                    'dashboard.*',
                ],
            ],
            [
                'name' => 'interviewer',
                'display_name' => 'Mulakatci',
                'description' => 'Mulakatlari goruntuleyebilir',
                'permissions' => [
                    'interviews.view',
                    'candidates.view',
                    'dashboard.view',
                ],
            ],
            [
                'name' => 'candidate',
                'display_name' => 'Aday',
                'description' => 'Mulakat yapabilir',
                'permissions' => [
                    'interviews.take',
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
