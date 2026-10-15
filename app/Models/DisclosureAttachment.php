<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisclosureAttachment extends Model
{
  protected $fillable = [
    'type',
    'original_name',
    'stored_path',
    'mime_type',
    'size_bytes',
    'version',
    'submitted_at',
    'is_latest_submission',
    'review_status',
    'needs_revision',
    'revision_notes',
  ];

  protected function casts(): array
  {
    return [
      'needs_revision' => 'boolean',
      'version' => 'integer',
      'submitted_at' => 'datetime',
      'is_latest_submission' => 'boolean',
    ];
  }

  public function innovationDisclosure(): BelongsTo
  {
    return $this->belongsTo(InnovationDisclosure::class);
  }

  public function events(): HasMany
  {
    return $this->hasMany(DisclosureAttachmentEvent::class);
  }
}
