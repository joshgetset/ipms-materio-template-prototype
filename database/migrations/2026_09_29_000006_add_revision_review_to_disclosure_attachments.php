<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->boolean('needs_revision')->default(false);
      $table->text('revision_notes')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('disclosure_attachments', function (Blueprint $table) {
      $table->dropColumn(['needs_revision', 'revision_notes']);
    });
  }
};