<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->boolean('has_substantial_support')->nullable()->after('funding_source');
      $table->boolean('ownership_consent')->default(false)->after('ownership_declaration');
    });
  }

  public function down(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->dropColumn(['has_substantial_support', 'ownership_consent']);
    });
  }
};
