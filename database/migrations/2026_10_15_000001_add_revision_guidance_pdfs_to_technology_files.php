<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('disclosure_technology_files', function (Blueprint $table): void {
      $table->string('revision_guidance_path', 500)->nullable();
      $table->string('revision_guidance_name', 255)->nullable();
    });

    Schema::table('disclosure_technology_file_versions', function (Blueprint $table): void {
      $table->string('revision_guidance_path', 500)->nullable();
      $table->string('revision_guidance_name', 255)->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('disclosure_technology_file_versions', function (Blueprint $table): void {
      $table->dropColumn(['revision_guidance_path', 'revision_guidance_name']);
    });

    Schema::table('disclosure_technology_files', function (Blueprint $table): void {
      $table->dropColumn(['revision_guidance_path', 'revision_guidance_name']);
    });
  }
};
