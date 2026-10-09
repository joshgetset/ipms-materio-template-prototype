<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclosureAttachmentEvent extends Model
{
  protected $fillable = [
    'event_type',
    'version',
    'original_name',
    'revision_notes',
    'occurred_at',
  ];

  protected function casts(): array
  {
    return [
      'version' => 'integer',
      'occurred_at' => 'datetime',
    ];
  }

  public function attachment(): BelongsTo
  {
    return $this->belongsTo(DisclosureAttachment::class, 'disclosure_attachment_id');
  }
}
