import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

let validationAlertOpen = false;

document.addEventListener(
  'invalid',
  event => {
    const control = event.target;
    if (!(control instanceof HTMLElement) || !('validity' in control) || !control.form) return;

    event.preventDefault();
    if (validationAlertOpen) return;

    validationAlertOpen = true;
    const fieldLabel = control.labels?.[0]?.textContent.trim() || control.getAttribute('aria-label') || 'This field';
    let message = control.validationMessage;

    if (control.validity.valueMissing) {
      message = `${fieldLabel} is required.`;
    } else if (control.validity.typeMismatch) {
      message = `Enter a valid ${fieldLabel.toLowerCase()}.`;
    } else if (control.validity.patternMismatch && control.title) {
      message = control.title;
    }

    Swal.fire({
      title: 'Check your input',
      text: message,
      icon: 'warning',
      confirmButtonText: 'OK'
    }).then(() => {
      validationAlertOpen = false;
      control.focus();
    });
  },
  true
);

/*
  Add custom scripts here
*/
import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);
