import Swal from 'sweetalert2';

function updateReviewNotes(form) {
  const status = form.querySelector('.admin-review-status');
  const notes = form.querySelector('.admin-review-notes');
  const notesWrap = form.querySelector('.admin-review-notes-wrap');

  if (!status || !notes || !notesWrap) return;

  const requiresNotes = status.value === 'returned';
  notes.required = requiresNotes;
  notesWrap.classList.toggle('d-none', !requiresNotes);
}

document.querySelectorAll('.admin-review-form').forEach((form) => {
  const status = form.querySelector('.admin-review-status');
  let confirmationPending = false;

  updateReviewNotes(form);
  status?.addEventListener('change', () => updateReviewNotes(form));

  form.addEventListener('submit', async (event) => {
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;
    confirmationPending = true;

    const decision = status.selectedOptions[0]?.textContent.trim() || 'Save review';
    const result = await Swal.fire({
      title: `${decision}?`,
      text: decision === 'Request revision'
        ? 'The submitter will see your revision notes on their dashboard.'
        : 'This review decision will appear on the submitter dashboard.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Save review',
      cancelButtonText: 'Continue editing',
      reverseButtons: true,
      focusCancel: true
    });

    confirmationPending = false;
    if (!result.isConfirmed) return;

    form.dataset.confirmed = 'true';
    HTMLFormElement.prototype.submit.call(form);
  });
});

document.querySelectorAll('.admin-attachment-review-form').forEach((form) => {
  const toggle = form.querySelector('.admin-file-revision-toggle');
  const notes = form.querySelector('textarea[name="revision_notes"]');
  const notesWrap = form.querySelector('.admin-file-revision-notes');
  let confirmationPending = false;

  const updateNotes = () => {
    const required = toggle.checked;
    notes.required = required;
    notesWrap.classList.toggle('d-none', !required);
  };

  updateNotes();
  toggle.addEventListener('change', updateNotes);

  form.addEventListener('submit', async (event) => {
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;
    confirmationPending = true;

    const result = await Swal.fire({
      title: form.dataset.confirmTitle,
      text: form.dataset.confirmText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Save review',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
      focusCancel: true
    });

    confirmationPending = false;
    if (!result.isConfirmed) return;

    form.dataset.confirmed = 'true';
    HTMLFormElement.prototype.submit.call(form);
  });
});
