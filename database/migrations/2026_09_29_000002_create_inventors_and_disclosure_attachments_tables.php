<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('inventors', function (Blueprint $table) {
      $table->id();
      $table->foreignId('innovation_disclosure_id')->constrained()->cascadeOnDelete();
      $table->string('last_name', 100);
      $table->string('first_name', 100);
      $table->string('suffix', 20)->nullable();
      $table->char('middle_initial', 1)->nullable();
      $table->string('country_of_citizenship', 100);
      $table->enum('affiliation', [
        'slsu_employee',
        'external_inventor',
        'student',
        'faculty_adviser',
        'external_collaborator',
      ]);
      $table->boolean('is_primary')->default(false);
      $table->unsignedBigInteger('primary_disclosure_id')
        ->nullable()
        ->virtualAs('CASE WHEN is_primary = 1 THEN innovation_disclosure_id ELSE NULL END');
      $table->unique('primary_disclosure_id');
      $table->timestamps();
    });

    Schema::create('disclosure_attachments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('innovation_disclosure_id')->constrained()->cascadeOnDelete();
      $table->enum('type', ['drawings', 'methodology', 'assistance_form']);
      $table->string('original_name', 255);
      $table->string('stored_path', 500);
      $table->string('mime_type', 100);
      $table->unsignedBigInteger('size_bytes');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('disclosure_attachments');
    Schema::dropIfExists('inventors');
  }
};
