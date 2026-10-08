import Swal from 'sweetalert2';

if (document.querySelector('[data-file-review-saved="true"]')) {
  Swal.fire({
    title: 'File review saved',
    text: 'Your file review has been saved.',
    icon: 'success',
    confirmButtonText: 'Continue'
  });
}

function updateReviewNotes(form) {
  const status = form.querySelector('.evaluator-review-status');
  const notes = form.querySelector('.evaluator-review-notes');
  const notesWrap = form.querySelector('.evaluator-review-notes-wrap');

  if (!status || !notes || !notesWrap) return;

  const requiresNotes = status.value === 'returned';
  notes.required = requiresNotes;
  notesWrap.classList.toggle('d-none', !requiresNotes);
}

document.querySelectorAll('.evaluator-review-form').forEach(form => {
  const status = form.querySelector('.evaluator-review-status');
  let confirmationPending = false;

  updateReviewNotes(form);
  status?.addEventListener('change', () => updateReviewNotes(form));

  form.addEventListener('submit', async event => {
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;

    if (status.value === 'endorsed' && form.dataset.hasPendingFileRevisions === 'true') {
      await Swal.fire({
        title: 'Cannot endorse submission',
        text: 'Resolve all requested file revisions before endorsing this submission.',
        icon: 'warning',
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    confirmationPending = true;

    const decision = status.selectedOptions[0]?.textContent.trim() || 'Save review';
    const result = await Swal.fire({
      title: `${decision}?`,
      text:
        decision === 'Request revision'
          ? 'The submitter will see your revision notes on their dashboard.'
          : 'This review decision will appear on the submitter dashboard.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Save review',
      cancelButtonText: 'Continue editing',
      reverseButtons: true,
      focusCancel: true
    });

    if (!result.isConfirmed) {
      confirmationPending = false;
      return;
    }

    try {
      const response = await fetch(form.action, {
        method: form.method,
        body: new FormData(form),
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const contentType = response.headers.get('content-type') ?? '';
      const payload = contentType.includes('application/json')
        ? await response.json()
        : { message: 'The server returned an unexpected response. Please try again.' };

      if (!response.ok) {
        const validationMessage = Object.values(payload.errors ?? {}).flat()[0];
        throw new Error(validationMessage ?? payload.message ?? 'The disclosure review could not be saved.');
      }

      const statusBadge = document.querySelector('[data-evaluator-review-status]');
      if (statusBadge && payload.disclosure) {
        const statusLabels = {
          under_review: ['Under review', 'warning'],
          returned: ['Revision requested', 'danger'],
          endorsed: ['Endorsed', 'success']
        };
        const [label, color] = statusLabels[payload.disclosure.status] ?? [payload.disclosure.status, 'primary'];
        statusBadge.textContent = label;
        statusBadge.classList.remove('bg-label-primary', 'bg-label-warning', 'bg-label-danger', 'bg-label-success');
        statusBadge.classList.add(`bg-label-${color}`);
      }

      await Swal.fire({
        title: 'Disclosure review saved',
        text: payload.message ?? 'Your disclosure review has been saved.',
        icon: 'success',
        confirmButtonText: 'Continue'
      });
    } catch (error) {
      await Swal.fire({
        title: 'Unable to save disclosure review',
        text: error.message,
        icon: 'error',
        confirmButtonText: 'OK'
      });
    } finally {
      confirmationPending = false;
    }
  });
});

document.querySelectorAll('.evaluator-attachment-review-form').forEach(form => {
  const saveButton = form.querySelector('.evaluator-file-card__save');
  let confirmationPending = false;

  const toggles = [...form.querySelectorAll('.evaluator-file-revision-toggle')];
  const updateFileControls = () => {
    const hasSelectedFiles = toggles.some(toggle => toggle.checked && !toggle.disabled);
    saveButton.disabled = !hasSelectedFiles;

    for (const toggle of toggles) {
      const notes = toggle.closest('.evaluator-file-card').querySelector('textarea[name$="[revision_notes]"]');
      const notesWrap = notes.closest('.evaluator-file-revision-notes');
      const selected = toggle.checked && !toggle.disabled;

      notes.required = selected;
      notes.disabled = !selected || toggle.disabled;
      if (!toggle.disabled) notesWrap.classList.toggle('d-none', !selected);
    }
  };

  updateFileControls();
  toggles.forEach(toggle => toggle.addEventListener('change', updateFileControls));

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (confirmationPending) return;

    const selectedCount = toggles.filter(toggle => toggle.checked && !toggle.disabled).length;
    if (selectedCount === 0) return;
    confirmationPending = true;

    const result = await Swal.fire({
      title: form.dataset.confirmTitle,
      text: `${selectedCount} file${selectedCount === 1 ? '' : 's'} will be marked as requiring revision.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Save File Review',
      cancelButtonText: 'Continue editing',
      reverseButtons: true,
      focusCancel: true
    });

    if (!result.isConfirmed) {
      confirmationPending = false;
      return;
    }

    saveButton.disabled = true;
    try {
      const response = await fetch(form.action, {
        method: form.method,
        body: new FormData(form),
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const contentType = response.headers.get('content-type') ?? '';
      const payload = contentType.includes('application/json')
        ? await response.json()
        : { message: 'The server returned an unexpected response. Please try again.' };

      if (!response.ok) {
        const validationMessage = Object.values(payload.errors ?? {}).flat()[0];
        throw new Error(validationMessage ?? payload.message ?? 'File reviews could not be saved.');
      }

      await Swal.fire({
        title: 'File reviews saved',
        text: payload.message ?? 'The selected files were marked for revision.',
        icon: 'success',
        confirmButtonText: 'Continue',
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      window.location.reload();
    } catch (error) {
      await Swal.fire({
        title: 'Unable to save file reviews',
        text: error.message,
        icon: 'error',
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
    } finally {
      confirmationPending = false;
      updateFileControls();
    }
  });
});
