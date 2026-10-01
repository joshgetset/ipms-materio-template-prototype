<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclosureAttachment extends Model
{
  protected $fillable = [
    'type',
    'original_name',
    'stored_path',
    'mime_type',
    'size_bytes',
    'version',
    'needs_revision',
    'revision_notes',
  ];

  protected function casts(): array
  {
    return [
      'needs_revision' => 'boolean',
      'version' => 'integer',
    ];
  }

  public function innovationDisclosure(): BelongsTo
  {
    return $this->belongsTo(InnovationDisclosure::class);
  }
}
