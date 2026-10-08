const statusUrl = document.querySelector('[data-submission-status-url]')?.dataset.submissionStatusUrl;

if (statusUrl) {
  const statusLabels = {
    submitted: 'Submitted',
    under_review: 'Under review',
    revised_submission: 'Revised Submission',
    returned: 'Revision requested',
    endorsed: 'Endorsed',
    filed: 'Filed'
  };
  const statusColors = {
    submitted: 'primary',
    under_review: 'warning',
    revised_submission: 'warning',
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
        const revisionForm = document.querySelector(`[data-disclosure-status-id="${disclosure.id}"]`);

        document.querySelectorAll('[data-disclosure-status], [data-modal-disclosure-status]').forEach(badge => {
          if (
            badge.dataset.disclosureStatus !== String(disclosure.id) &&
            badge.dataset.modalDisclosureStatus !== String(disclosure.id)
          )
            return;

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
          const isFlagged = Boolean(attachment.needs_revision);
          const card = document.querySelector(`[data-file-attachment="${attachment.id}"]`);
          card?.classList.toggle('user-submission-file-card--revision-requested', isFlagged);
          card?.classList.toggle(
            'user-submission-file-card--revised',
            Boolean(attachment.is_latest_submission) && Number(attachment.version) > 1
          );

          const notes = document.querySelector(`[data-file-revision-modal-notes="${attachment.id}"]`);
          if (notes) {
            const notesText = notes.querySelector(`[data-file-revision-notes="${attachment.id}"]`);
            if (notesText) notesText.textContent = attachment.revision_notes ?? '';
            notes.classList.toggle('d-none', !isFlagged);
          }
        });

        if (revisionForm) {
          const canSubmitRevisions = ['under_review', 'returned', 'revised_submission'].includes(disclosure.status);
          const flaggedAttachmentIds = new Set(
            (disclosure.attachments ?? [])
              .filter(attachment => attachment.needs_revision)
              .map(attachment => String(attachment.id))
          );
          revisionForm.dataset.disclosureStatus = disclosure.status;

          revisionForm.querySelectorAll('[data-file-attachment-input]').forEach(input => {
            const isFlagged = flaggedAttachmentIds.has(input.dataset.fileAttachmentInput);
            input.disabled = !canSubmitRevisions || !isFlagged;
            input.closest('[data-revision-upload-control]')?.classList.toggle('d-none', !canSubmitRevisions || !isFlagged);
          });

          const canUpdateFiles = canSubmitRevisions && flaggedAttachmentIds.size > 0;
          const detailPage = revisionForm.closest('.submission-details-page');
          detailPage?.querySelector('[data-update-file-button]')?.classList.toggle('d-none', !canUpdateFiles);
          const notice = detailPage?.querySelector('[data-file-update-notice]');
          if (notice) {
            notice.textContent = disclosure.status === 'submitted'
              ? 'Files cannot be changed until the evaluator reviews this submission.'
              : canSubmitRevisions
                ? 'No files have been marked for revision.'
                : 'File revisions are not available for this submission status.';
            notice.classList.toggle('d-none', canUpdateFiles);
          }
        }
      });

      document.querySelectorAll('[data-dashboard-status-count]').forEach(counter => {
        const status = counter.dataset.dashboardStatusCount;
        counter.textContent = status === 'total' ? disclosures.length : (counts[status] ?? 0);
      });

      const chart = document.querySelector('#disclosureStatusChart');
      if (chart && window.disclosureStatusChart) {
        const statuses = JSON.parse(chart.dataset.chartStatuses);
        window.disclosureStatusChart.updateSeries([
          {
            name: 'Disclosures',
            data: statuses.map(status => counts[status] ?? 0)
          }
        ]);
      }
    } catch {
      // Keep dashboard pages usable when the status endpoint is unavailable.
    }
  }

  refreshSubmissionStatuses();
  window.setInterval(refreshSubmissionStatuses, 10000);
  document.addEventListener('visibilitychange', refreshSubmissionStatuses);
}
