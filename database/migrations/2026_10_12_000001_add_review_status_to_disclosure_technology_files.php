<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('disclosure_technology_files', function (Blueprint $table) {
      $table->string('review_status', 30)->default('review_needed');
      $table->text('revision_notes')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('disclosure_technology_files', function (Blueprint $table) {
      $table->dropColumn(['review_status', 'revision_notes']);
    });
  }
};
