<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('disclosure_technologies', function (Blueprint $table) {
      $table->id();
      $table->foreignId('innovation_disclosure_id')->constrained()->cascadeOnDelete();
      $table->string('technology_title', 500);
      $table->longText('abstract_summary')->nullable();
      $table->longText('technical_field')->nullable();
      $table->longText('background_summary')->nullable();
      $table->longText('invention_summary')->nullable();
      $table->longText('detailed_description')->nullable();
      $table->longText('examples_results')->nullable();
      $table->timestamps();
    });

    Schema::create('disclosure_technology_files', function (Blueprint $table) {
      $table->id();
      $table->foreignId('disclosure_technology_id')->constrained()->cascadeOnDelete();
      $table->string('narrative_key', 50);
      $table->string('original_name', 255);
      $table->string('stored_path', 500);
      $table->string('mime_type', 100);
      $table->unsignedBigInteger('size_bytes');
      $table->timestamps();
      $table->unique(
        ['disclosure_technology_id', 'narrative_key'],
        'tech_files_narrative_unique'
      );
    });

    DB::table('innovation_disclosures')
      ->select([
        'id',
        'technology_title',
        'abstract_summary',
        'technical_field',
        'background_summary',
        'invention_summary',
        'detailed_description',
        'examples_results',
      ])
      ->orderBy('id')
      ->chunkById(200, function ($disclosures): void {
        $timestamp = now();
        DB::table('disclosure_technologies')->insert(
          $disclosures->map(fn ($disclosure) => [
            'innovation_disclosure_id' => $disclosure->id,
            'technology_title' => $disclosure->technology_title,
            'abstract_summary' => $disclosure->abstract_summary,
            'technical_field' => $disclosure->technical_field,
            'background_summary' => $disclosure->background_summary,
            'invention_summary' => $disclosure->invention_summary,
            'detailed_description' => $disclosure->detailed_description,
            'examples_results' => $disclosure->examples_results,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
          ])->all()
        );
      });
  }

  public function down(): void
  {
    Schema::dropIfExists('disclosure_technology_files');
    Schema::dropIfExists('disclosure_technologies');
  }
};
