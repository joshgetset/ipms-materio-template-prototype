<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasOldRole = Schema::hasColumn('users', 'is_evaluator');
        $hasNewRole = Schema::hasColumn('users', 'is_ip_head');

        if ($hasOldRole && ! $hasNewRole) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_evaluator', 'is_ip_head');
            });

            return;
        }

        if ($hasOldRole && $hasNewRole) {
            DB::table('users')
                ->where('is_evaluator', true)
                ->update(['is_ip_head' => true]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_evaluator');
            });

            return;
        }

        if (! $hasNewRole) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_ip_head')->default(false);
            });
        }
    }

    public function down(): void
    {
        $hasOldRole = Schema::hasColumn('users', 'is_evaluator');
        $hasNewRole = Schema::hasColumn('users', 'is_ip_head');

        if ($hasNewRole && ! $hasOldRole) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_ip_head', 'is_evaluator');
            });

            return;
        }

        if ($hasNewRole && $hasOldRole) {
            DB::table('users')
                ->where('is_ip_head', true)
                ->update(['is_evaluator' => true]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_ip_head');
            });
        }
    }
};
