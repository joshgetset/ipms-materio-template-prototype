<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('IPMS_ADMIN_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set IPMS_ADMIN_PASSWORD before running AdminUserSeeder.');
        }

        $admin = User::firstOrNew(['username' => 'superadmingetes']);
        $admin->name = 'IPMS Administrator';
        $admin->email = env('IPMS_ADMIN_EMAIL', 'superadmingetes@ipms.local');
        $admin->password = Hash::make($password);
        $admin->is_admin = true;
        $admin->save();
    }
}
