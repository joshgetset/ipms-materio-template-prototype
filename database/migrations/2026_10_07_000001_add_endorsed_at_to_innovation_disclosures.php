<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->timestamp('endorsed_at')->nullable()->after('status');
    });

    DB::table('innovation_disclosures')
      ->where('status', 'endorsed')
      ->update(['endorsed_at' => DB::raw('updated_at')]);
  }

  public function down(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->dropColumn('endorsed_at');
    });
  }
};
