<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (! Schema::hasColumn('disclosure_technology_files', 'version')) {
      Schema::table('disclosure_technology_files', function (Blueprint $table) {
        $table->unsignedInteger('version')->default(1);
      });
    }
    if (! Schema::hasColumn('disclosure_technology_files', 'submission_note')) {
      Schema::table('disclosure_technology_files', function (Blueprint $table) {
        $table->text('submission_note')->nullable();
      });
    }

    if (! Schema::hasTable('disclosure_technology_file_versions')) {
      Schema::create('disclosure_technology_file_versions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('disclosure_technology_file_id');
        $table->unsignedInteger('version');
        $table->string('original_name', 255);
        $table->string('stored_path', 500);
        $table->string('mime_type', 100);
        $table->unsignedBigInteger('size_bytes');
        $table->string('review_status', 30);
        $table->text('revision_notes')->nullable();
        $table->text('submission_note')->nullable();
        $table->timestamp('submitted_at')->nullable();
        $table->timestamps();
        $table->unique(
          ['disclosure_technology_file_id', 'version'],
          'technology_file_versions_unique'
        );
      });
    }

    $hasFileForeignKey = collect(Schema::getForeignKeys('disclosure_technology_file_versions'))
      ->contains(fn (array $foreignKey): bool => in_array('disclosure_technology_file_id', $foreignKey['columns'], true));
    if (! $hasFileForeignKey) {
      Schema::table('disclosure_technology_file_versions', function (Blueprint $table) {
        $table->foreign('disclosure_technology_file_id', 'tech_file_version_file_fk')
          ->references('id')
          ->on('disclosure_technology_files')
          ->cascadeOnDelete();
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('disclosure_technology_file_versions');

    Schema::table('disclosure_technology_files', function (Blueprint $table) {
      $table->dropColumn(['version', 'submission_note']);
    });
  }
};
