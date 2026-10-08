<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->string('technology_title', 500)->change();
      $table->string('technology_type')->change();
      $table->string('university_relationship')->change();
      $table->text('funding_source')->change();
      $table->string('ownership_declaration')->change();

      $table->string('lead_creator_name')->nullable();
      $table->string('college_department')->nullable();
      $table->string('campus')->nullable();
      $table->string('other_relationship')->nullable();
      $table->date('date_of_creation')->nullable();
      $table->date('disclosure_date')->nullable();
      $table->longText('abstract_summary')->nullable();
      $table->longText('technical_field')->nullable();
      $table->longText('invention_summary')->nullable();
      $table->longText('examples_results')->nullable();
      $table->text('file_drive_link')->nullable();
      $table->decimal('total_development_cost', 12, 2)->default(0);
      $table->string('received_by')->nullable();
      $table->date('date_received')->nullable();
    });

    Schema::table('inventors', function (Blueprint $table) {
      $table->string('affiliation')->change();
      $table->string('country_of_citizenship')->nullable()->change();
      $table->string('full_name')->nullable();
      $table->text('position_affiliation')->nullable();
      $table->longText('technical_contribution')->nullable();
      $table->decimal('contribution_percent', 5, 2)->nullable();
      $table->json('contribution_nature')->nullable();
      $table->string('contribution_other')->nullable();
      $table->string('signature_name')->nullable();
      $table->boolean('declaration_confirmed')->default(false);
      $table->date('declaration_date')->nullable();
    });

    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->string('type')->change();
    });

    Schema::create('disclosure_cost_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('innovation_disclosure_id')->constrained()->cascadeOnDelete();
      $table->string('item');
      $table->text('description');
      $table->decimal('quantity', 12, 2);
      $table->decimal('unit_cost', 12, 2);
      $table->decimal('total_cost', 12, 2);
      $table->text('purpose')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('disclosure_cost_items');

    Schema::table('innovation_disclosures', function (Blueprint $table) {
      $table->dropColumn([
        'lead_creator_name',
        'college_department',
        'campus',
        'other_relationship',
        'date_of_creation',
        'disclosure_date',
        'abstract_summary',
        'technical_field',
        'invention_summary',
        'examples_results',
        'file_drive_link',
        'total_development_cost',
        'received_by',
        'date_received',
      ]);
    });

    Schema::table('inventors', function (Blueprint $table) {
      $table->dropColumn([
        'position_affiliation',
        'full_name',
        'technical_contribution',
        'contribution_percent',
        'contribution_nature',
        'contribution_other',
        'signature_name',
        'declaration_confirmed',
        'declaration_date',
      ]);
    });
  }
};
