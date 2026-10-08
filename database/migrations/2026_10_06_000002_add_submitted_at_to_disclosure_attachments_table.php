<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->timestamp('submitted_at')->nullable()->after('version');
    });

    DB::table('disclosure_attachments')
      ->where('version', '>', 1)
      ->update(['submitted_at' => DB::raw('updated_at')]);

    DB::table('disclosure_attachments')
      ->where('version', '<=', 1)
      ->update(['submitted_at' => DB::raw('created_at')]);
  }

  public function down(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->dropColumn('submitted_at');
    });
  }
};
