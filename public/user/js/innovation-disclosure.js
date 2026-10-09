const disclosureForm = document.querySelector('#new-disclosure form');
const modalTargetFor = form => form.closest('.modal.show') ?? document.body;
const submissionValidationErrors = document.querySelector('[data-submission-validation-errors]');

document.querySelectorAll('.modal[id^="technology-narrative-modal-"]').forEach(modal => {
  modal.addEventListener('show.bs.modal', event => {
    const trigger = event.relatedTarget;
    if (!trigger?.matches('[data-narrative-content]')) return;
    modal.querySelector('[data-narrative-modal-title]').textContent = trigger.dataset.narrativeTitle;
    modal.querySelector('[data-narrative-modal-content]').value = trigger.dataset.narrativeContent;
  });
});

if (submissionValidationErrors) {
  document.addEventListener('DOMContentLoaded', () => {
    const messages = JSON.parse(submissionValidationErrors.dataset.messages);
    window.Swal.fire({
      title: 'Unable to update submission',
      text: messages.join(' '),
      icon: 'error',
      confirmButtonText: 'OK'
    });
  }, { once: true });
}

async function validateUploadedFiles(form) {
  for (const input of form.querySelectorAll('input[type="file"]')) {
    const file = input.files?.[0];
    if (!file) continue;

    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension === 'pdf') continue;

    await window.Swal.fire({
      title: 'Unsupported file type',
      text: 'All uploaded files must be PDF documents.',
      target: modalTargetFor(form),
      icon: 'error',
      confirmButtonText: 'Got it'
    });
    input.value = '';
    input.focus();
    return false;
  }

  return true;
}

const relationshipField = disclosureForm?.querySelector('[name="university_relationship"]');
const ownershipField = disclosureForm?.querySelector('[name="ownership_declaration"]');
let studentAssignmentWasSelected = false;

async function announceStudentAssignment() {
  if (!relationshipField || !ownershipField) return;

  const isStudentAssignment =
    relationshipField.value === 'student' && ownershipField.value === 'assigned_to_slsu';
  const shouldAnnounce = isStudentAssignment && !studentAssignmentWasSelected;
  studentAssignmentWasSelected = isStudentAssignment;
  if (shouldAnnounce) {
    await window.Swal.fire({
      title: 'Visit the IP Office',
      text: 'As a student assigning ownership to SLSU, you must go to the IP Office to complete and sign your Deed of Assignment. Upload the signed deed before submitting.',
      icon: 'info',
      confirmButtonText: 'Understood'
    });
  }
}

relationshipField?.addEventListener('change', announceStudentAssignment);
ownershipField?.addEventListener('change', announceStudentAssignment);
announceStudentAssignment();

document.addEventListener('creator:delete', async event => {
  const result = await window.Swal.fire({
    title: 'Delete this creator?',
    text: 'This creator will be removed from the disclosure.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    reverseButtons: true,
    focusCancel: true
  });

  if (result.isConfirmed) {
    document.dispatchEvent(new CustomEvent('creator:delete-confirmed', {
      detail: { index: event.detail.index }
    }));
  }
});

document.addEventListener('material:delete', async event => {
  const result = await window.Swal.fire({
    title: 'Delete this cost item?',
    text: 'This item will be removed from the costing table.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    reverseButtons: true,
    focusCancel: true
  });

  if (result.isConfirmed) {
    document.dispatchEvent(new CustomEvent('material:delete-confirmed', {
      detail: { index: event.detail.index }
    }));
  }
});

document.addEventListener('technology:delete', async event => {
  const result = await window.Swal.fire({
    title: 'Delete this technology?',
    text: 'Its narratives and selected PDF files will be removed from this disclosure.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    reverseButtons: true,
    focusCancel: true
  });

  if (result.isConfirmed) {
    document.dispatchEvent(new CustomEvent('technology:delete-confirmed', {
      detail: { index: event.detail.index }
    }));
  }
});

function updateNarrativeToggle(section) {
  const text = section.querySelector('.submission-narrative__text');
  const button = section.querySelector('.submission-narrative__toggle');

  if (!text || !button) return;
  if (text.classList.contains('is-expanded')) {
    button.hidden = false;
    return;
  }

  text.classList.remove('is-collapsed');
  const fullHeight = text.scrollHeight;
  text.classList.add('is-collapsed');
  button.hidden = fullHeight <= text.clientHeight + 1;
}

document.querySelectorAll('.submission-narrative').forEach(section => {
  const button = section.querySelector('.submission-narrative__toggle');
  const text = section.querySelector('.submission-narrative__text');

  button.addEventListener('click', () => {
    const expanded = text.classList.toggle('is-expanded');
    text.classList.toggle('is-collapsed', !expanded);
    button.setAttribute('aria-expanded', String(expanded));
    button.textContent = expanded ? 'Read less' : 'Read more';
  });

  updateNarrativeToggle(section);
});

document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('shown.bs.modal', () => {
    modal.querySelectorAll('.submission-narrative').forEach(updateNarrativeToggle);
  });
});

if (disclosureForm) {
  let confirmationPending = false;

  disclosureForm.addEventListener('submit', async event => {
    if (disclosureForm.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;
    confirmationPending = true;

    if (!(await validateUploadedFiles(disclosureForm))) {
      confirmationPending = false;
      return;
    }

    const result = await window.Swal.fire({
      title: 'Are all input fields final?',
      text: 'Please review the disclosure before submitting.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, submit',
      cancelButtonText: 'Review inputs',
      reverseButtons: true,
      focusCancel: true
    });

    confirmationPending = false;
    if (!result.isConfirmed) return;

    try {
      const draftSaved = await window.saveIdfDraftBeforeSubmit();
      if (draftSaved === false) {
        confirmationPending = false;
        return;
      }
      window.markIdfSubmissionPending();
      disclosureForm.dataset.confirmed = 'true';
      HTMLFormElement.prototype.submit.call(disclosureForm);
    } catch (error) {
      confirmationPending = false;
      console.error('Could not prepare the IDF draft for submission recovery:', error);
      await window.Swal.fire({
        title: 'Could not submit disclosure',
        text: 'Your draft could not be saved for recovery. Please try again.',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    }
  });
}

document.querySelectorAll('.attachment-update-form').forEach(form => {
  let confirmationPending = false;

  form.addEventListener('submit', async event => {
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;
    confirmationPending = true;

    if (!(await validateUploadedFiles(form))) {
      confirmationPending = false;
      return;
    }

    const result = await window.Swal.fire({
      title: form.dataset.confirmTitle,
      text: form.dataset.confirmText,
      target: modalTargetFor(form),
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Continue',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
      focusCancel: true
    });

    confirmationPending = false;
    if (!result.isConfirmed) return;

    form.dataset.confirmed = 'true';
    form.requestSubmit();
  });
});

document.querySelectorAll('.disclosure-delete-form').forEach(form => {
  let confirmationPending = false;

  form.addEventListener('submit', async event => {
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;
    confirmationPending = true;

    const result = await window.Swal.fire({
      title: form.dataset.confirmTitle,
      text: form.dataset.confirmText,
      target: modalTargetFor(form),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33',
      reverseButtons: true,
      focusCancel: true
    });

    confirmationPending = false;
    if (!result.isConfirmed) return;

    form.dataset.confirmed = 'true';
    form.requestSubmit();
  });
});

const { toastType, toastMessage } = document.body.dataset;

if (toastType === 'success' && toastMessage) {
  window.Swal.fire({
    title: 'Success',
    text: toastMessage,
    icon: 'success',
    confirmButtonText: 'Done'
  });
}
