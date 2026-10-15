<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdfStagedUpload extends Model
{
  protected $fillable = [
    'user_id',
    'token_hash',
    'stored_path',
    'original_name',
    'mime_type',
    'size_bytes',
    'expires_at',
  ];

  protected function casts(): array
  {
    return [
      'expires_at' => 'datetime',
    ];
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
