<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class IpHeadUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('IPMS_IP_HEAD_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set IPMS_IP_HEAD_PASSWORD before running IpHeadUserSeeder.');
        }

        $ipHead = User::firstOrNew(['username' => 'ipheaddemo']);
        $ipHead->name = 'IPMS IP Head';
        $ipHead->email = env('IPMS_IP_HEAD_EMAIL', 'iphead@ipms.local');
        $ipHead->password = Hash::make($password);
        $ipHead->is_ip_head = true;
        $ipHead->save();
    }
}
