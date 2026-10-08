import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import '../css/innovation-disclosure.css';

const disclosureForm = document.querySelector('#new-disclosure form');
const modalTargetFor = form => form.closest('.modal.show') ?? document.body;

async function validateUploadedFiles(form) {
  for (const input of form.querySelectorAll('input[type="file"]')) {
    const file = input.files?.[0];
    if (!file) continue;

    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension === 'pdf') continue;

    await Swal.fire({
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
    await Swal.fire({
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
  const result = await Swal.fire({
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

document.addEventListener('creator:save-requested', async event => {
  const { creator, editingIndex } = event.detail;
  const isEditing = editingIndex !== null;
  const result = await Swal.fire({
    title: isEditing ? 'Save creator changes?' : 'Add this creator?',
    text: `${creator.last_name}, ${creator.first_name} will be ${isEditing ? 'updated' : 'added'} to the disclosure.`,
    target: document.querySelector('#creator-modal'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: isEditing ? 'Save changes' : 'Add creator',
    cancelButtonText: 'Review details',
    reverseButtons: true,
    focusCancel: true
  });

  if (result.isConfirmed) {
    document.dispatchEvent(new CustomEvent('creator:save-confirmed', {
      detail: { creator, editingIndex }
    }));
  }
});

document.addEventListener('material:save-requested', async event => {
  const { material, editingIndex } = event.detail;
  const isEditing = editingIndex !== null;
  const result = await Swal.fire({
    title: isEditing ? 'Save cost item changes?' : 'Add this cost item?',
    text: `${material.item} will be ${isEditing ? 'updated' : 'added'} to the costing table.`,
    target: document.querySelector('#material-modal'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: isEditing ? 'Save changes' : 'Add item',
    cancelButtonText: 'Review details',
    reverseButtons: true,
    focusCancel: true
  });

  if (result.isConfirmed) {
    document.dispatchEvent(new CustomEvent('material:save-confirmed', {
      detail: { material, editingIndex }
    }));
  }
});

document.addEventListener('material:delete', async event => {
  const result = await Swal.fire({
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

document.addEventListener('technology:save-requested', async event => {
  const { technology, editingIndex } = event.detail;
  const isEditing = editingIndex !== null;
  const result = await Swal.fire({
    title: isEditing ? 'Save technology changes?' : 'Add this technology?',
    text: `${technology.technology_title} will be ${isEditing ? 'updated' : 'added'} to the disclosure.`,
    target: document.querySelector('#technology-modal'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: isEditing ? 'Save changes' : 'Add technology',
    cancelButtonText: 'Review details',
    reverseButtons: true,
    focusCancel: true
  });

  if (result.isConfirmed) {
    document.dispatchEvent(new CustomEvent('technology:save-confirmed', {
      detail: event.detail
    }));
  }
});

document.addEventListener('technology:delete', async event => {
  const result = await Swal.fire({
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

    const result = await Swal.fire({
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

    disclosureForm.dataset.confirmed = 'true';
    HTMLFormElement.prototype.submit.call(disclosureForm);
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

    const result = await Swal.fire({
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

    const result = await Swal.fire({
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
  Swal.fire({
    title: 'Success',
    text: toastMessage,
    icon: 'success',
    confirmButtonText: 'Done'
  });
}
