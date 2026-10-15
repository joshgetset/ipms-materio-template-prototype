<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclosureTechnologyFileVersion extends Model
{
  protected $fillable = [
    'version',
    'original_name',
    'stored_path',
    'mime_type',
    'size_bytes',
    'review_status',
    'revision_notes',
    'revision_guidance_path',
    'revision_guidance_name',
    'submission_note',
    'submitted_at',
  ];

  protected function casts(): array
  {
    return [
      'version' => 'integer',
      'submitted_at' => 'datetime',
    ];
  }

  public function technologyFile(): BelongsTo
  {
    return $this->belongsTo(DisclosureTechnologyFile::class, 'disclosure_technology_file_id');
  }
}
