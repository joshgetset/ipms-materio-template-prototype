<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::table('disclosure_attachments')
      ->where('version', 1)
      ->where('needs_revision', false)
      ->where('is_latest_submission', false)
      ->update(['is_latest_submission' => true]);
  }

  public function down(): void
  {
  }
};
