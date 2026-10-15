const Swal = window.Swal;
const openTechnologyFilesStorageKey = 'ip-head-open-technology-files';

const rememberOpenTechnologyFilesPanel = form => {
  if (form.id) {
    window.sessionStorage.setItem(openTechnologyFilesStorageKey, form.id);
  }
};

if (document.querySelector('[data-file-review-saved="true"]')) {
  Swal.fire({
    title: 'File review saved',
    text: 'Your file review has been saved.',
    icon: 'success',
    confirmButtonText: 'Continue'
  });
}

document.querySelectorAll('[data-open-technology-files]').forEach(button => {
  button.addEventListener('click', () => {
    const item = button.closest('[data-review-table-page]');
    const table = item?.closest('[data-review-table]');
    const panel = document.getElementById(button.dataset.openTechnologyFiles);
    if (!item || !table || !panel) return;

    const isOpening = panel.hidden;
    table.querySelectorAll('[data-review-table-page]').forEach(page => {
      page.classList.remove('technology-review-item--files-open');
      const pagePanel = page.querySelector('.technology-review-files-panel');
      if (pagePanel) pagePanel.hidden = true;
      const pageButton = page.querySelector('[data-open-technology-files]');
      if (pageButton) {
        pageButton.setAttribute('aria-expanded', 'false');
        pageButton.innerHTML = '<i class="icon-base ri ri-folder-open-line me-1" aria-hidden="true"></i>Open files';
      }
    });

    table.classList.toggle('technology-review-list--files-open', isOpening);
    if (isOpening) {
      item.classList.add('technology-review-item--files-open');
      panel.hidden = false;
      item.querySelector('.technology-review-count--updated')?.remove();
      button.setAttribute('aria-expanded', 'true');
      button.innerHTML = '<i class="icon-base ri ri-arrow-left-line me-1" aria-hidden="true"></i>Back to technologies';
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

const technologyFilesPanelToRestore = window.sessionStorage.getItem(openTechnologyFilesStorageKey);
if (technologyFilesPanelToRestore) {
  const restoreTechnologyFilesPanel = event => {
    const panel = document.getElementById(technologyFilesPanelToRestore);
    if (!panel || panel.closest('[data-review-table]') !== event.target) return;

    window.sessionStorage.removeItem(openTechnologyFilesStorageKey);
    document.removeEventListener('review-table:pagechange', restoreTechnologyFilesPanel);
    [...document.querySelectorAll('[data-open-technology-files]')]
      .find(button => button.dataset.openTechnologyFiles === technologyFilesPanelToRestore)
      ?.click();
  };

  document.addEventListener('review-table:pagechange', restoreTechnologyFilesPanel);
}

document.querySelectorAll('[data-close-technology-files]').forEach(button => {
  button.addEventListener('click', () => {
    const panel = document.getElementById(button.dataset.closeTechnologyFiles);
    const item = panel?.closest('[data-review-table-page]');
    const table = item?.closest('[data-review-table]');
    if (!panel || !item || !table) return;

    panel.hidden = true;
    item.classList.remove('technology-review-item--files-open');
    table.classList.remove('technology-review-list--files-open');
    const openButton = item.querySelector('[data-open-technology-files]');
    if (openButton) {
      openButton.setAttribute('aria-expanded', 'false');
      openButton.innerHTML = '<i class="icon-base ri ri-folder-open-line me-1" aria-hidden="true"></i>Open files';
    }
    item.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

document.querySelectorAll('.ip-head-attachment-review-form').forEach(form => {
  const saveButton = form.querySelector('.ip-head-file-card__save');
  const attachmentStatuses = [...form.querySelectorAll('.ip-head-attachment-file-status')];
  const technologyStatuses = [...form.querySelectorAll('.ip-head-technology-file-status')];
  const statuses = [...attachmentStatuses, ...technologyStatuses];
  let confirmationPending = false;

  if (!saveButton || statuses.length === 0) return;

  const updateFileControls = () => {
    saveButton.disabled = !statuses.some(status => !status.disabled && status.value !== status.dataset.initialStatus);

    for (const status of statuses) {
      status.dataset.reviewStatus = status.value;
      const statusControl = status.closest('.ip-head-file-status-control');
      if (statusControl) statusControl.dataset.reviewStatus = status.value;
      const row = status.closest('tr');
      const notes = row.querySelector('textarea[name$="[revision_notes]"]');
      const notesWrap = row.querySelector('.ip-head-technology-file-notes, .ip-head-attachment-file-notes');
      if (!notes || !notesWrap) continue;

      if (notesWrap.classList.contains('ip-head-technology-file-notes')) {
        notes.required = false;
        notes.disabled = false;
        notesWrap.classList.add('d-none');
        continue;
      }

      const needsRevision = status.value === 'revision_requested' && !status.disabled;
      notes.required = needsRevision;
      notes.disabled = !needsRevision;
      if (!status.disabled) notesWrap.classList.toggle('d-none', !needsRevision);
    }
  };

  updateFileControls();
  form.closest('[data-review-table-page]')?.closest('[data-review-table]')?.addEventListener('review-table:pagechange', () => {
    updateFileControls();
    const table = form.closest('[data-review-table]');
    table?.classList.remove('technology-review-list--files-open');
    table?.querySelectorAll('[data-review-table-page]').forEach(page => {
      page.classList.remove('technology-review-item--files-open');
      const panel = page.querySelector('.technology-review-files-panel');
      if (panel) panel.hidden = true;
      const button = page.querySelector('[data-open-technology-files]');
      if (button) {
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = '<i class="icon-base ri ri-folder-open-line me-1" aria-hidden="true"></i>Open files';
      }
    });
  });
  statuses.forEach(status => status.addEventListener('change', updateFileControls));

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (confirmationPending) return;

    const selectedCount = statuses.filter(status => !status.disabled && status.value !== status.dataset.initialStatus).length;
    if (selectedCount === 0) return;
    confirmationPending = true;

    const result = await Swal.fire({
      title: form.dataset.confirmTitle,
      text: `${selectedCount} file status${selectedCount === 1 ? '' : 'es'} will be updated.`,
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
        text: payload.message ?? 'The selected file statuses were saved.',
        icon: 'success',
        confirmButtonText: 'Continue',
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      rememberOpenTechnologyFilesPanel(form);
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

document.addEventListener('click', async event => {
  const trigger = event.target.closest('[data-ip-head-technology-review]');
  if (!trigger) return;

  const statusInput = document.getElementById(trigger.dataset.fileStatusInputId);
  const notesInput = document.getElementById(trigger.dataset.fileNotesInputId);
  const form = document.getElementById(trigger.dataset.fileFormId);
  if (!statusInput || !notesInput || !form) {
    await Swal.fire({
      title: 'Unable to open file review',
      text: 'The technology file review controls could not be found.',
      icon: 'error',
      confirmButtonText: 'OK',
      buttonsStyling: false,
      customClass: { confirmButton: 'btn btn-primary' }
    });
    return;
  }

  const dialog = document.createElement('div');
  dialog.className = 'technology-file-dialog ip-head-technology-review-dialog';
  dialog.innerHTML = `
    <header class="technology-file-dialog__header">
      <span class="technology-file-dialog__file-icon"><i class="icon-base ri ri-file-pdf-2-line" aria-hidden="true"></i></span>
      <div class="technology-file-dialog__heading">
        <h2 data-review-file-name></h2>
        <p data-review-file-meta></p>
      </div>
      <span class="technology-file-dialog__status" data-review-file-status-label></span>
    </header>
    <div class="technology-file-dialog__body ip-head-technology-review-dialog__body">
      <section class="technology-file-dialog__preview ip-head-technology-review-dialog__preview" aria-label="PDF preview">
        <iframe class="technology-file-dialog__frame" data-review-file-frame title="PDF preview"></iframe>
      </section>
      <aside class="technology-file-dialog__details ip-head-technology-review-dialog__controls">
        <h3 class="technology-file-dialog__section-title">Details</h3>
        <div class="technology-file-dialog__detail"><span>Technology</span><strong data-review-technology-title></strong></div>
        <div class="technology-file-dialog__detail"><span>Category</span><strong data-review-technology-category></strong></div>
        <div class="technology-file-dialog__detail"><span>Uploaded</span><strong data-review-file-date></strong></div>
        <div class="technology-file-dialog__detail"><span>Current version</span><strong data-review-file-version></strong></div>
        <section class="d-none ip-head-technology-review-dialog__saved-note" data-review-file-initial-note-section>
          <h3 class="technology-file-dialog__section-title">Previous revision note — IP Head</h3>
          <div class="technology-file-dialog__revision-note" data-review-file-initial-note></div>
        </section>
        <h3 class="technology-file-dialog__section-title mt-3">Review decision</h3>
        <label class="form-label fw-semibold" for="ip-head-technology-review-status">Set file status</label>
        <select class="form-select" id="ip-head-technology-review-status" data-review-file-status>
          <option value="review_needed">Review Needed</option>
          <option value="revision_requested">Request Revision</option>
          <option value="accepted">Approve</option>
        </select>
        <section class="d-none ip-head-technology-review-dialog__saved-note" data-review-file-saved-note-section>
          <h3 class="technology-file-dialog__section-title">Revision note — IP Head</h3>
          <div class="technology-file-dialog__revision-note" data-review-file-saved-note></div>
        </section>
        <div class="d-none ip-head-technology-review-dialog__notes" data-review-file-notes-wrap>
          <label class="form-label fw-semibold" for="ip-head-technology-review-notes">Revision note — Inventor</label>
          <textarea class="form-control" id="ip-head-technology-review-notes" rows="5" maxlength="2000" placeholder="Explain what the inventor needs to revise."></textarea>
        </div>
        <div class="d-none ip-head-technology-review-dialog__guidance" data-review-guidance-wrap>
          <label class="form-label fw-semibold" for="ip-head-technology-review-guidance">Noted PDF — Inventor guidance</label>
          <input class="form-control" id="ip-head-technology-review-guidance" type="file" accept="application/pdf,.pdf" data-review-guidance-input>
          <small class="text-body-secondary" data-review-guidance-filename>No noted PDF selected. PDF only, maximum 15 MB.</small>
          <button class="btn btn-primary mt-2 d-none" type="button" data-review-guidance-existing>View Noted PDF</button>
        </div>
        <p class="ip-head-technology-review-dialog__hint mb-0" data-review-status-hint>Approving or requesting a revision updates this file’s review status.</p>
      </aside>
    </div>`;

  dialog.querySelector('[data-review-file-name]').textContent = trigger.dataset.fileName;
  const sizeInBytes = Number(trigger.dataset.fileSize);
  const formattedSize = sizeInBytes >= 1024 ** 2
    ? `${(sizeInBytes / 1024 ** 2).toFixed(1)} MB`
    : sizeInBytes >= 1024
      ? `${(sizeInBytes / 1024).toFixed(0)} KB`
      : `${sizeInBytes} B`;
  dialog.querySelector('[data-review-file-meta]').textContent =
    `${trigger.dataset.fileCategory} · ${formattedSize} · ${trigger.dataset.fileDate}`;
  dialog.querySelector('[data-review-technology-title]').textContent = trigger.dataset.technologyTitle;
  dialog.querySelector('[data-review-technology-category]').textContent = trigger.dataset.technologyCategory;
  dialog.querySelector('[data-review-file-date]').textContent = trigger.dataset.fileDate;
  dialog.querySelector('[data-review-file-version]').textContent = `v${trigger.dataset.fileVersion}`;
  const initialRevisionNote = trigger.dataset.fileInitialRevisionNote?.trim() ?? '';
  const initialNoteSection = dialog.querySelector('[data-review-file-initial-note-section]');
  const initialNote = dialog.querySelector('[data-review-file-initial-note]');
  initialNote.textContent = initialRevisionNote;
  initialNoteSection.classList.toggle('d-none', initialRevisionNote === '');
  const statusLabel = dialog.querySelector('[data-review-file-status-label]');
  const statusLabels = {
    review_needed: 'Review Needed',
    revision_requested: 'Requested Revision',
    accepted: 'Approved'
  };
  statusLabel.textContent = statusLabels[statusInput.value] ?? 'Review Needed';
  statusLabel.classList.add(`is-${statusInput.value}`);
  const frame = dialog.querySelector('[data-review-file-frame]');
  frame.src = trigger.dataset.filePreviewUrl;
  frame.title = `Preview of ${trigger.dataset.fileName}`;
  const modalStatus = dialog.querySelector('[data-review-file-status]');
  const modalNotes = dialog.querySelector('#ip-head-technology-review-notes');
  const notesWrap = dialog.querySelector('[data-review-file-notes-wrap]');
  const guidanceWrap = dialog.querySelector('[data-review-guidance-wrap]');
  const guidanceInput = dialog.querySelector('[data-review-guidance-input]');
  const guidanceFilename = dialog.querySelector('[data-review-guidance-filename]');
  const guidanceExisting = dialog.querySelector('[data-review-guidance-existing]');
  const savedNoteSection = dialog.querySelector('[data-review-file-saved-note-section]');
  const savedNote = dialog.querySelector('[data-review-file-saved-note]');
  const savedRevisionNote = trigger.dataset.fileRevisionNote?.trim() ?? '';
  const hasSavedRevisionNote = statusInput.value === 'revision_requested' && savedRevisionNote !== '';
  modalStatus.value = statusInput.value;
  modalNotes.value = notesInput.value;
  savedNote.textContent = savedRevisionNote;
  const existingGuidanceUrl = trigger.dataset.fileGuidanceUrl?.trim() ?? '';
  if (existingGuidanceUrl) {
    guidanceExisting.classList.remove('d-none');
    guidanceExisting.textContent = 'View Noted PDF';
  }
  let guidancePreviewOpen = false;
  guidanceExisting.addEventListener('click', () => {
    guidancePreviewOpen = !guidancePreviewOpen;
    frame.src = guidancePreviewOpen ? existingGuidanceUrl : trigger.dataset.filePreviewUrl;
    frame.title = guidancePreviewOpen
      ? `Preview of noted PDF ${trigger.dataset.fileGuidanceName || ''}`.trim()
      : `Preview of ${trigger.dataset.fileName}`;
    guidanceExisting.textContent = guidancePreviewOpen
      ? 'View Submitted PDF'
      : 'View Noted PDF';
  });
  guidanceInput.addEventListener('change', () => {
    const selectedFile = guidanceInput.files?.[0];
    guidanceFilename.textContent = selectedFile?.name || 'No noted PDF selected. PDF only, maximum 15 MB.';
  });

  const updateNotesVisibility = () => {
    const needsNotes = modalStatus.value === 'revision_requested' && !modalStatus.disabled;
    const showSavedNote = needsNotes && hasSavedRevisionNote;
    savedNoteSection.classList.toggle('d-none', !showSavedNote);
    notesWrap.classList.toggle('d-none', !needsNotes || showSavedNote);
    guidanceWrap.classList.toggle('d-none', !needsNotes);
    modalNotes.required = needsNotes && !showSavedNote;
    statusLabel.textContent = statusLabels[modalStatus.value] ?? 'Review Needed';
    statusLabel.classList.remove('is-review_needed', 'is-revision_requested', 'is-accepted');
    statusLabel.classList.add(`is-${modalStatus.value}`);
  };
  modalStatus.addEventListener('change', updateNotesVisibility);
  const isApproved = statusInput.value === 'accepted';
  modalStatus.disabled = isApproved;
  if (isApproved) {
    dialog.querySelector('[data-review-status-hint]').textContent =
      'This file is approved, so its review status is locked.';
  }
  updateNotesVisibility();

  const result = await Swal.fire({
    html: dialog,
    width: 'min(95vw, 1280px)',
    showCloseButton: true,
    showCancelButton: true,
    showConfirmButton: !isApproved,
    confirmButtonText: 'Save review decision',
    cancelButtonText: 'Close',
    buttonsStyling: false,
    customClass: {
      popup: 'technology-file-swal ip-head-technology-file-swal',
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-label-secondary'
    },
    preConfirm: () => {
      const usesSavedRevisionNote = modalStatus.value === 'revision_requested' && hasSavedRevisionNote;
      if (modalStatus.value === 'revision_requested' && !usesSavedRevisionNote && !modalNotes.value.trim()) {
        Swal.showValidationMessage('Add a revision note before requesting changes.');
        return false;
      }
      const guidancePdf = guidanceInput.files?.[0] ?? null;
      if (guidancePdf && (!guidancePdf.name.toLowerCase().endsWith('.pdf') || guidancePdf.size > 15 * 1024 * 1024)) {
        Swal.showValidationMessage('Choose a noted PDF no larger than 15 MB.');
        return false;
      }
      if (guidancePdf && modalStatus.value !== 'revision_requested') {
        Swal.showValidationMessage('A noted PDF can only be uploaded when requesting a revision.');
        return false;
      }
      return {
        status: modalStatus.value,
        revisionNotes: usesSavedRevisionNote ? savedRevisionNote : modalNotes.value.trim(),
        guidancePdf
      };
    }
  });
  if (!result.isConfirmed) return;

  const decisionLabel = result.value.status === 'accepted'
    ? 'approve this file'
    : result.value.status === 'revision_requested'
      ? 'request a revision for this file'
      : 'return this file to review';
  const confirmation = await Swal.fire({
    title: 'Save this file review?',
    text: `This will ${decisionLabel}.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Confirm decision',
    cancelButtonText: 'Go back',
    reverseButtons: true,
    focusCancel: true,
    buttonsStyling: false,
    customClass: {
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-label-secondary'
    }
  });
  if (!confirmation.isConfirmed) return;

  const fileId = statusInput.name.match(/^technology_files\[(\d+)\]\[status\]$/)?.[1];
  const csrfToken = form.querySelector('input[name="_token"]')?.value;
  if (!fileId || !csrfToken) {
    await Swal.fire({
      title: 'Unable to save file review',
      text: 'The file review request is missing required form data.',
      icon: 'error',
      confirmButtonText: 'OK',
      buttonsStyling: false,
      customClass: { confirmButton: 'btn btn-primary' }
    });
    return;
  }

  try {
    const body = new FormData();
    body.append('_token', csrfToken);
    body.append(`technology_files[${fileId}][status]`, result.value.status);
    body.append(`technology_files[${fileId}][revision_notes]`, result.value.revisionNotes);
    if (result.value.guidancePdf) {
      body.append(`technology_files[${fileId}][revision_guidance_pdf]`, result.value.guidancePdf);
    }
    const response = await fetch(form.action, {
      method: form.method,
      body,
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
      throw new Error(validationMessage ?? payload.message ?? 'The technology file review could not be saved.');
    }

    await Swal.fire({
      title: 'File review saved',
      text: payload.message ?? 'The technology file review has been saved.',
      icon: 'success',
      confirmButtonText: 'Continue',
      buttonsStyling: false,
      customClass: { confirmButton: 'btn btn-primary' }
    });
    rememberOpenTechnologyFilesPanel(form);
    window.location.reload();
  } catch (error) {
    await Swal.fire({
      title: 'Unable to save file review',
      text: error.message,
      icon: 'error',
      confirmButtonText: 'OK',
      buttonsStyling: false,
      customClass: { confirmButton: 'btn btn-primary' }
    });
  }
});

document.querySelectorAll('.ip-head-approval-form').forEach(form => {
  const button = form.querySelector('button[type="submit"]');
  let confirmationPending = false;

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!button || button.disabled || confirmationPending) return;

    confirmationPending = true;
    const result = await Swal.fire({
      title: 'Approve this submitted IDF?',
      text: 'This will approve the submission because every current submitted file is approved.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Approve IDF',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
      focusCancel: true,
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-outline-secondary ms-2'
      }
    });

    if (!result.isConfirmed) {
      confirmationPending = false;
      return;
    }

    button.disabled = true;
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
        throw new Error(validationMessage ?? payload.message ?? 'The submitted IDF could not be approved.');
      }

      await Swal.fire({
        title: 'Submitted IDF approved',
        text: payload.message ?? 'The submitted IDF has been approved.',
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
        title: 'Unable to approve submission',
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
      button.disabled = false;
    }
  });
});
