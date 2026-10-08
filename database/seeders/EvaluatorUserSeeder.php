<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class EvaluatorUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('IPMS_EVALUATOR_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set IPMS_EVALUATOR_PASSWORD before running EvaluatorUserSeeder.');
        }

        $evaluator = User::firstOrNew(['username' => 'evaluatordemo']);
        $evaluator->name = 'IPMS Evaluator';
        $evaluator->email = env('IPMS_EVALUATOR_EMAIL', 'evaluator@ipms.local');
        $evaluator->password = Hash::make($password);
        $evaluator->is_evaluator = true;
        $evaluator->save();
    }
}
