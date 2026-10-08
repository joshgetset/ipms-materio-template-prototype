<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->boolean('is_latest_submission')->default(false)->after('submitted_at');
    });
  }

  public function down(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->dropColumn('is_latest_submission');
    });
  }
};
