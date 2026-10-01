<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InnovationDisclosure extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'email',
    'mobile_no',
    'technology_title',
    'technology_type',
    'university_relationship',
    'funding_source',
    'has_substantial_support',
    'ownership_declaration',
    'ownership_consent',
    'research_title',
    'research_approval_date',
    'background_summary',
    'detailed_description',
    'drawings_description',
    'status',
    'review_notes',
  ];

  protected function casts(): array
  {
    return [
      'research_approval_date' => 'date',
      'has_substantial_support' => 'boolean',
      'ownership_consent' => 'boolean',
    ];
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function inventors(): HasMany
  {
    return $this->hasMany(Inventor::class);
  }

  public function attachments(): HasMany
  {
    return $this->hasMany(DisclosureAttachment::class);
  }
}
