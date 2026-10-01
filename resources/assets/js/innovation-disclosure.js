import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import '../css/innovation-disclosure.css';

const disclosureForm = document.querySelector('#new-disclosure form');
const modalTargetFor = form => form.closest('.modal.show') ?? document.body;

if (disclosureForm) {
  let confirmationPending = false;

  disclosureForm.addEventListener('submit', async event => {
    if (disclosureForm.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (confirmationPending) return;
    confirmationPending = true;

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
