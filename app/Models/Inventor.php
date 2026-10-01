<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventor extends Model
{
  protected $fillable = [
    'last_name',
    'first_name',
    'suffix',
    'middle_initial',
    'country_of_citizenship',
    'affiliation',
    'is_primary',
  ];

  protected function casts(): array
  {
    return [
      'is_primary' => 'boolean',
    ];
  }

  public function innovationDisclosure(): BelongsTo
  {
    return $this->belongsTo(InnovationDisclosure::class);
  }
}
