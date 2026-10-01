<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->boolean('is_admin')->default(false);
    });

    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->text('review_notes')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->dropColumn('review_notes');
    });

    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('is_admin');
    });
  }
};