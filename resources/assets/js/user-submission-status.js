const statusUrl = document.querySelector('[data-submission-status-url]')?.dataset.submissionStatusUrl;

if (statusUrl) {
  const statusLabels = {
    submitted: 'Submitted',
    under_review: 'Under review',
    returned: 'Revision requested',
    endorsed: 'Endorsed',
    filed: 'Filed'
  };
  const statusColors = {
    submitted: 'primary',
    under_review: 'warning',
    returned: 'danger',
    endorsed: 'success',
    filed: 'success'
  };

  async function refreshSubmissionStatuses() {
    if (document.hidden) return;

    try {
      const response = await fetch(statusUrl, {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });
      if (!response.ok) return;

      const disclosures = await response.json();
      const counts = Object.fromEntries(Object.keys(statusLabels).map(status => [status, 0]));

      disclosures.forEach(disclosure => {
        counts[disclosure.status] = (counts[disclosure.status] ?? 0) + 1;
        const color = statusColors[disclosure.status] ?? 'primary';

        document.querySelectorAll('[data-disclosure-status], [data-modal-disclosure-status]').forEach(badge => {
          if (badge.dataset.disclosureStatus !== String(disclosure.id) &&
              badge.dataset.modalDisclosureStatus !== String(disclosure.id)) return;

          badge.textContent = statusLabels[disclosure.status] ?? disclosure.status;
          badge.classList.remove('bg-label-primary', 'bg-label-warning', 'bg-label-danger', 'bg-label-success');
          badge.classList.add(`bg-label-${color}`);
        });

        document.querySelectorAll('[data-disclosure-review-note]').forEach(note => {
          if (note.dataset.disclosureReviewNote !== String(disclosure.id)) return;
          note.textContent = disclosure.review_notes ?? '';
          note.classList.toggle('d-none', !disclosure.review_notes);
        });

        document.querySelectorAll('[data-modal-review-note]').forEach(note => {
          if (note.dataset.modalReviewNote !== String(disclosure.id)) return;
          note.querySelector('span').textContent = disclosure.review_notes ?? '';
          note.classList.toggle('d-none', !disclosure.review_notes);
        });

        (disclosure.attachments ?? []).forEach(attachment => {
          const state = document.querySelector(`[data-file-revision-state="${attachment.id}"]`);
          if (!state) return;

          const isFlagged = Boolean(attachment.needs_revision);
          state.classList.toggle('d-none', !isFlagged);
          const notes = state.querySelector(`[data-file-revision-notes="${attachment.id}"]`);
          if (notes) notes.textContent = attachment.revision_notes ?? '';
        });
      });

      document.querySelectorAll('[data-dashboard-status-count]').forEach(counter => {
        const status = counter.dataset.dashboardStatusCount;
        counter.textContent = status === 'total'
          ? disclosures.length
          : counts[status] ?? 0;
      });

      const chart = document.querySelector('#disclosureStatusChart');
      if (chart && window.disclosureStatusChart) {
        const statuses = JSON.parse(chart.dataset.chartStatuses);
        window.disclosureStatusChart.updateSeries([{
          name: 'Disclosures',
          data: statuses.map(status => counts[status] ?? 0)
        }]);
      }
    } catch {
      // Keep dashboard pages usable when the status endpoint is unavailable.
    }
  }

  refreshSubmissionStatuses();
  window.setInterval(refreshSubmissionStatuses, 10000);
  document.addEventListener('visibilitychange', refreshSubmissionStatuses);
}
