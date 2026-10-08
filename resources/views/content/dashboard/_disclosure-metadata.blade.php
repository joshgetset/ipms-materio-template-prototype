@php
$metadata = [
'Lead creator' => $disclosure->lead_creator_name ?? '',
'College / Department' => $disclosure->college_department ?? '',
'Campus' => $disclosure->campus ?? '',
'Technology type' => str_replace('_', ' ', ucfirst($disclosure->technology_type)),
'University relationship' => str_replace('_', ' ', ucfirst($disclosure->university_relationship)),
'Funding source' => str_replace('_', ' ', ucfirst($disclosure->funding_source)),
'Ownership declaration' => str_replace('_', ' ', ucfirst($disclosure->ownership_declaration)),
'Date of creation' => $disclosure->date_of_creation?->format('M j, Y') ?? '',
'Date of this disclosure' => $disclosure->disclosure_date?->format('M j, Y') ?? '',
'Total development cost' => '₱' . number_format((float) $disclosure->total_development_cost, 2),
'Substantial SLSU support' => $disclosure->has_substantial_support === null
? 'Not recorded'
: ($disclosure->has_substantial_support ? 'Yes' : 'No'),
'Ownership consent' => $disclosure->ownership_consent ? 'Confirmed' : 'Not recorded',
'Submitted' => $disclosure->created_at->format('M j, Y g:i A'),
];
@endphp

<div class="row g-4 mb-5 submission-metadata">
  <div class="col-12">
    <label class="form-label text-uppercase small fw-semibold" for="disclosure-title-{{ $disclosure->id }}">Technology Title</label>
    <textarea class="form-control" id="disclosure-title-{{ $disclosure->id }}" rows="2" disabled readonly>{{ $disclosure->technology_title }}</textarea>
  </div>
  @foreach ($metadata as $label => $value)
  @php($fieldId = 'disclosure-metadata-' . $disclosure->id . '-' . $loop->index)
  <div class="col-md-6">
    <label class="form-label text-uppercase small fw-semibold" for="{{ $fieldId }}">{{ $label }}</label>
    <input class="form-control" id="{{ $fieldId }}" type="text" value="{{ $value }}" disabled readonly>
  </div>
  @endforeach
  @if ($disclosure->research_title)
  <div class="col-md-8">
    <label class="form-label text-uppercase small fw-semibold" for="research-title-{{ $disclosure->id }}">Research Title</label>
    <input class="form-control" id="research-title-{{ $disclosure->id }}" type="text" value="{{ $disclosure->research_title }}" disabled readonly>
  </div>
  <div class="col-md-4">
    <label class="form-label text-uppercase small fw-semibold" for="research-approval-date-{{ $disclosure->id }}">Research Approval Date</label>
    <input class="form-control" id="research-approval-date-{{ $disclosure->id }}" type="text" value="{{ $disclosure->research_approval_date?->format('M j, Y') ?? 'Not recorded' }}" disabled readonly>
  </div>
  @endif
  @if ($disclosure->other_relationship)
  <div class="col-md-6">
    <label class="form-label text-uppercase small fw-semibold" for="other-relationship-{{ $disclosure->id }}">Other relationship</label>
    <input class="form-control" id="other-relationship-{{ $disclosure->id }}" type="text" value="{{ $disclosure->other_relationship }}" disabled readonly>
  </div>
  @endif
  @if ($disclosure->file_drive_link)
  <div class="col-12">
    <span class="form-label text-uppercase small fw-semibold d-block">External file / Drive link</span>
    <a href="{{ $disclosure->file_drive_link }}" target="_blank" rel="noopener noreferrer">{{ $disclosure->file_drive_link }}</a>
  </div>
  @endif
</div>