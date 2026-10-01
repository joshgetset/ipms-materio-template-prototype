<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('innovation_disclosures', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('email');
      $table->string('mobile_no', 20);
      $table->string('technology_title', 500);
      $table->enum('technology_type', ['chemical', 'mechanical', 'literary_creative']);
      $table->enum('university_relationship', ['student', 'employee', 'outsider']);
      $table->enum('funding_source', ['personal', 'slsu_funded', 'externally_funded']);
      $table->enum('ownership_declaration', ['assign_to_slsu', 'retain_ownership', 'not_applicable']);
      $table->string('research_title', 500)->nullable();
      $table->date('research_approval_date')->nullable();
      $table->longText('background_summary');
      $table->longText('detailed_description');
      $table->longText('drawings_description')->nullable();
      $table->enum('status', ['submitted', 'under_review', 'returned', 'endorsed', 'filed'])
        ->default('submitted');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('innovation_disclosures');
  }
};
