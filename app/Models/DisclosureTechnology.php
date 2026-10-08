<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisclosureTechnology extends Model
{
  protected $fillable = [
    'technology_title',
    'abstract_summary',
    'technical_field',
    'background_summary',
    'invention_summary',
    'detailed_description',
    'examples_results',
  ];

  public function innovationDisclosure(): BelongsTo
  {
    return $this->belongsTo(InnovationDisclosure::class);
  }

  public function files(): HasMany
  {
    return $this->hasMany(DisclosureTechnologyFile::class);
  }
}
