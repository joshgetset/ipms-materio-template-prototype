<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (! Schema::hasTable('disclosure_attachment_events')) {
      Schema::create('disclosure_attachment_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('disclosure_attachment_id')
          ->constrained('disclosure_attachments')
          ->cascadeOnDelete();
        $table->string('event_type', 32);
        $table->unsignedInteger('version');
        $table->string('original_name');
        $table->text('revision_notes')->nullable();
        $table->timestamp('occurred_at');
        $table->timestamps();
      });
    }

    DB::table('disclosure_attachments')
      ->orderBy('id')
      ->chunkById(500, function ($attachments): void {
        foreach ($attachments as $attachment) {
          $uploadedAt = $attachment->submitted_at ?? $attachment->created_at;
          DB::table('disclosure_attachment_events')->insert([
            'disclosure_attachment_id' => $attachment->id,
            'event_type' => 'uploaded',
            'version' => $attachment->version,
            'original_name' => $attachment->original_name,
            'revision_notes' => null,
            'occurred_at' => $uploadedAt,
            'created_at' => $uploadedAt,
            'updated_at' => $uploadedAt,
          ]);

          if ($attachment->needs_revision) {
            DB::table('disclosure_attachment_events')->insert([
              'disclosure_attachment_id' => $attachment->id,
              'event_type' => 'revision_requested',
              'version' => $attachment->version,
              'original_name' => $attachment->original_name,
              'revision_notes' => $attachment->revision_notes,
              'occurred_at' => $attachment->updated_at,
              'created_at' => $attachment->updated_at,
              'updated_at' => $attachment->updated_at,
            ]);
          }
        }
      });
  }

  public function down(): void
  {
    Schema::dropIfExists('disclosure_attachment_events');
  }
};
