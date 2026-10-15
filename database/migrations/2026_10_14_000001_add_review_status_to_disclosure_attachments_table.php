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
      $table->string('review_status', 32)->default('review_needed')->after('is_latest_submission');
    });

    DB::table('disclosure_attachments')
      ->where('needs_revision', true)
      ->update(['review_status' => 'revision_requested']);
  }

  public function down(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->dropColumn('review_status');
    });
  }
};
