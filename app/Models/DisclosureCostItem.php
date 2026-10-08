<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclosureCostItem extends Model
{
  protected $fillable = [
    'item',
    'description',
    'quantity',
    'unit_cost',
    'total_cost',
    'purpose',
  ];

  protected function casts(): array
  {
    return [
      'quantity' => 'decimal:2',
      'unit_cost' => 'decimal:2',
      'total_cost' => 'decimal:2',
    ];
  }

  public function innovationDisclosure(): BelongsTo
  {
    return $this->belongsTo(InnovationDisclosure::class);
  }
}
