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
    'full_name',
    'email',
    'country_of_citizenship',
    'affiliation',
    'position_affiliation',
    'technical_contribution',
    'contribution_percent',
    'contribution_nature',
    'contribution_other',
    'signature_name',
    'declaration_confirmed',
    'declaration_date',
    'is_primary',
  ];

  protected function casts(): array
  {
    return [
      'is_primary' => 'boolean',
      'contribution_nature' => 'array',
      'contribution_percent' => 'decimal:2',
      'declaration_confirmed' => 'boolean',
      'declaration_date' => 'date',
    ];
  }

  public function innovationDisclosure(): BelongsTo
  {
    return $this->belongsTo(InnovationDisclosure::class);
  }
}
