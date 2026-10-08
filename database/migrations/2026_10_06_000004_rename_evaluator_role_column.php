<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_evaluator')) {
            if (Schema::hasColumn('users', 'is_admin')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('is_admin', 'is_evaluator');
                });

                return;
            }

            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_evaluator')->default(false);
            });

            return;
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            DB::table('users')
                ->where('is_admin', true)
                ->update(['is_evaluator' => true]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_evaluator') && ! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_evaluator', 'is_admin');
            });
        }
    }
};
