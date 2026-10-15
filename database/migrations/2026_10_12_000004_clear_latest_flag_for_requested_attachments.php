<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::table('disclosure_attachments')
      ->where('needs_revision', true)
      ->where('is_latest_submission', true)
      ->update(['is_latest_submission' => false]);
  }

  public function down(): void
  {
  }
};
