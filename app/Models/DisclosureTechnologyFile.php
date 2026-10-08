<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclosureTechnologyFile extends Model
{
  protected $fillable = [
    'narrative_key',
    'original_name',
    'stored_path',
    'mime_type',
    'size_bytes',
  ];

  public function technology(): BelongsTo
  {
    return $this->belongsTo(DisclosureTechnology::class, 'disclosure_technology_id');
  }
}
