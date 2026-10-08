<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  private const STATUSES = ['submitted', 'under_review', 'returned', 'revised_submission', 'endorsed', 'filed'];

  public function up(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->enum('status', self::STATUSES)->default('submitted')->change();
    });
  }

  public function down(): void
  {
    DB::table('innovation_disclosures')
      ->where('status', 'revised_submission')
      ->update(['status' => 'submitted']);

    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->enum('status', ['submitted', 'under_review', 'returned', 'endorsed', 'filed'])
        ->default('submitted')
        ->change();
    });
  }
};
