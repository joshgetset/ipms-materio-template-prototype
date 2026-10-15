<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisclosureTechnologyFile extends Model
{
  protected $fillable = [
    'narrative_key',
    'original_name',
    'stored_path',
    'mime_type',
    'size_bytes',
    'review_status',
    'revision_notes',
    'revision_guidance_path',
    'revision_guidance_name',
    'version',
    'submission_note',
  ];

  protected function casts(): array
  {
    return [
      'version' => 'integer',
    ];
  }

  public function technology(): BelongsTo
  {
    return $this->belongsTo(DisclosureTechnology::class, 'disclosure_technology_id');
  }

  public function versions(): HasMany
  {
    return $this->hasMany(DisclosureTechnologyFileVersion::class, 'disclosure_technology_file_id')
      ->orderByDesc('version');
  }
}
