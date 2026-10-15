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
    'lead_creator_name',
    'college_department',
    'campus',
    'other_relationship',
    'date_of_creation',
    'disclosure_date',
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
    'abstract_summary',
    'technical_field',
    'invention_summary',
    'detailed_description',
    'drawings_description',
    'examples_results',
    'file_drive_link',
    'total_development_cost',
    'received_by',
    'date_received',
    'status',
    'endorsed_at',
    'review_notes',
  ];

  protected function casts(): array
  {
    return [
      'has_substantial_support' => 'boolean',
      'ownership_consent' => 'boolean',
      'research_approval_date' => 'date',
      'date_of_creation' => 'date',
      'disclosure_date' => 'date',
      'date_received' => 'date',
      'total_development_cost' => 'decimal:2',
      'endorsed_at' => 'datetime',
    ];
  }

  public function getTechnologyTypeLabelAttribute(): string
  {
    return match ($this->technology_type) {
      'mechanical', 'mechanical_engineering' => 'Mechanical',
      'chemical' => 'Chemical',
      default => str_replace('_', ' ', ucfirst((string) $this->technology_type)),
    };
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

  public function costItems(): HasMany
  {
    return $this->hasMany(DisclosureCostItem::class);
  }

  public function technologies(): HasMany
  {
    return $this->hasMany(DisclosureTechnology::class);
  }
}
