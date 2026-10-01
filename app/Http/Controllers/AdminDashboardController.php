<?php

namespace App\Http\Controllers;

use App\Models\InnovationDisclosure;
use App\Models\DisclosureAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
  public function index(Request $request): View
  {
    $this->ensureAdministrator($request);

    return view('content.dashboard.admin-dashboard.index', [
      'statusCounts' => InnovationDisclosure::query()
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status'),
      'reviewQueue' => InnovationDisclosure::query()
        ->with(['user', 'attachments', 'inventors'])
        ->whereIn('status', ['submitted', 'under_review'])
        ->latest()
        ->paginate(20, ['*'], 'reviewQueuePage'),
      'reviewedSubmissions' => InnovationDisclosure::query()
        ->with(['user', 'attachments', 'inventors'])
        ->whereIn('status', ['returned', 'endorsed', 'filed'])
        ->latest()
        ->paginate(20, ['*'], 'reviewedPage'),
    ]);
  }

  public function showReview(Request $request, InnovationDisclosure $disclosure): View
  {
    $this->ensureAdministrator($request);
    $disclosure->load(['user', 'attachments', 'inventors']);
    $queue = $request->query('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $pageParameter = $queue === 'reviewed' ? 'reviewedPage' : 'reviewQueuePage';

    return view('content.dashboard.admin-dashboard.review', [
      'disclosure' => $disclosure,
      'page' => max(1, $request->integer('page', $request->integer($pageParameter, 1))),
      'pageParameter' => $pageParameter,
      'queue' => $queue,
    ]);
  }

  public function updateReview(Request $request, InnovationDisclosure $disclosure): RedirectResponse
  {
    $this->ensureAdministrator($request);

    $validated = $request->validate([
      'status' => ['required', Rule::in(['under_review', 'returned', 'endorsed'])],
      'review_notes' => [
        Rule::requiredIf($request->input('status') === 'returned'),
        'nullable',
        'string',
        'max:3000',
      ],
      'queue' => ['nullable', Rule::in(['for-review', 'reviewed'])],
      'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $disclosure->update([
      'status' => $validated['status'],
      'review_notes' => $validated['status'] === 'returned'
        ? $validated['review_notes']
        : null,
    ]);

    return redirect()
      ->route('admin-dashboard.index', $this->dashboardPageParameters($request))
      ->with('toast_type', 'success')
      ->with('toast_message', 'Disclosure review updated.');
  }

  public function updateAttachmentReview(Request $request, DisclosureAttachment $attachment): RedirectResponse
  {
    $this->ensureAdministrator($request);

    $validated = $request->validate([
      'needs_revision' => ['required', 'boolean'],
      'revision_notes' => [
        Rule::requiredIf($request->boolean('needs_revision')),
        'nullable',
        'string',
        'max:2000',
      ],
      'queue' => ['nullable', Rule::in(['for-review', 'reviewed'])],
      'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $attachment->update([
      'needs_revision' => $request->boolean('needs_revision'),
      'revision_notes' => $request->boolean('needs_revision') ? $validated['revision_notes'] : null,
    ]);

    return redirect()
      ->route('admin-dashboard.index', $this->dashboardPageParameters($request))
      ->with('toast_type', 'success')
      ->with('toast_message', 'File revision review updated.');
  }

  private function ensureAdministrator(Request $request): void
  {
    abort_unless($request->user()?->is_admin, 403);
  }

  private function dashboardPageParameters(Request $request): array
  {
    $queue = $request->input('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $page = max(1, $request->integer('page', 1));

    if ($page === 1) {
      return [];
    }

    return [$queue === 'reviewed' ? 'reviewedPage' : 'reviewQueuePage' => $page];
  }
}
