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
  const parseIniSize = value => {
    const match = String(value || '').trim().match(/^(\d+(?:\.\d+)?)\s*([KMG])?$/i);
    if (!match) return 0;
    const unit = (match[2] || '').toUpperCase();
    const multiplier = unit === 'G' ? 1024 ** 3 : unit === 'M' ? 1024 ** 2 : unit === 'K' ? 1024 : 1;
    return Number(match[1]) * multiplier;
  };
  const uploads = [...form.elements].filter(element =>
    element.type === 'file' && element.name && element.files?.[0]
  );
  const maxUploadBytes = parseIniSize(form.dataset.uploadMaxSize);
  const postMaxBytes = parseIniSize(form.dataset.postMaxSize);
  const effectiveFileLimit = Math.min(
    maxUploadBytes || Number.POSITIVE_INFINITY,
    postMaxBytes ? Math.max(0, postMaxBytes - 64 * 1024) : Number.POSITIVE_INFINITY
  );
  const oversizedFile = uploads.find(input => input.files[0].size > effectiveFileLimit);
  if (oversizedFile) {
    const maxUploadLabel = effectiveFileLimit === maxUploadBytes
      ? form.dataset.uploadMaxSize
      : form.dataset.postMaxSize;
    await window.Swal.fire({
      title: 'PDF exceeds the server upload limit',
      text: `${oversizedFile.dataset.fileLabel || 'This PDF'} is too large for a single upload request (server limit: ${maxUploadLabel}). Compress the PDF or contact the administrator to increase the server upload limits.`,
      target: modalTargetFor(form),
      icon: 'error',
      confirmButtonText: 'Got it'
    });
    return false;
  }

  for (const input of uploads) {
    const file = input.files[0];

    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension === 'pdf') continue;

    await window.Swal.fire({
      title: 'Unsupported file type',
      text: 'All uploaded files must be PDF documents.',
      target: modalTargetFor(form),
      icon: 'error',
      confirmButtonText: 'Got it'
    });
    if (typeof window.showIdfWizardField === 'function') window.showIdfWizardField(input);
    input.value = '';
    input.focus();
    return false;
  }

  return true;
}

function stagedTokenFieldName(fileFieldName) {
  const attachmentMatch = fileFieldName.match(/^attachments\[([^\]]+)\]$/);
  if (attachmentMatch) {
    return `idf_upload_tokens[attachments][${attachmentMatch[1]}]`;
  }

  const technologyMatch = fileFieldName.match(/^technologies\[(\d+)\]\[files\]\[([^\]]+)\]$/);
  if (technologyMatch) {
    return `idf_upload_tokens[technologies][${technologyMatch[1]}][${technologyMatch[2]}]`;
  }

  throw new Error(`Unsupported IDF PDF field: ${fileFieldName}`);
}

function removeFilesFromFinalIdfSubmission(form) {
  form.dataset.idfFilesStaged = 'true';
  document.querySelectorAll('input[type="file"]').forEach(input => {
    if (input.form !== form && !form.contains(input)) return;
    input.disabled = true;
    input.removeAttribute('name');
    input.removeAttribute('form');
  });

  form.enctype = 'application/x-www-form-urlencoded';
  const finalFormData = new FormData(form);
  const unstagedFile = [...finalFormData.entries()]
    .find(([, value]) => value instanceof File && value.size > 0);
  if (unstagedFile) {
    throw new Error('A PDF is still attached to the final form request. Please refresh and try submitting again.');
  }

  const requiredFields = [
    'lead_creator_name',
    'email',
    'college_department',
    'campus',
    'university_relationship',
    'mobile_no',
    'research_title',
    'funding_source',
    'technology_type',
    'date_of_creation',
    'research_approval_date',
    'ownership_declaration'
  ];
  const missingFields = requiredFields.filter(name => !String(finalFormData.get(name) || '').trim());
  if (missingFields.length) {
    throw new Error('Some IDF details are not included in the final request. Please refresh the form, review all wizard pages, and submit again.');
  }

  const technologyTitles = [...form.querySelectorAll('#technology-hidden-fields input[name$="[technology_title]"]')];
  if (!technologyTitles.length || technologyTitles.some(input => !String(finalFormData.get(input.name) || '').trim())) {
    throw new Error('A technology title is missing from the final request. Please review the Technical Description page and try again.');
  }

  const requiredTechnologyFiles = [
    'abstract_summary',
    'technical_field',
    'background_summary',
    'invention_summary',
    'detailed_description',
    'examples_results',
    'sample_photo',
    'technical_drawing'
  ];
  for (const titleInput of technologyTitles) {
    const technologyIndex = titleInput.name.match(/^technologies\[(\d+)\]\[technology_title\]$/)?.[1];
    if (technologyIndex === undefined) {
      throw new Error('The technology data is incomplete. Please review the Technical Description page and try again.');
    }
    const missingFile = requiredTechnologyFiles.find(fileKey =>
      !String(finalFormData.get(`idf_upload_tokens[technologies][${technologyIndex}][${fileKey}]`) || '').trim()
    );
    if (missingFile) {
      throw new Error('One or more technology PDFs are not staged. Please reopen the Technical Description page, reselect the missing PDF, and submit again.');
    }
  }

  return finalFormData;
}

/* =====================================================
   FIX — re-enable every field the wizard disabled on
   inactive pages. Must run BEFORE stageSelectedIdfFiles:
   that function validates the required fields from the
   form data and disables the file inputs (files submit
   via staged tokens). Disabled inputs are excluded from
   the POST, which is what caused the "required field"
   error wall on final submission.
===================================================== */
function reenableAllWizardFields() {
  document
    .querySelectorAll(
      '#new-disclosure-form input:not([type="file"]), #new-disclosure-form select, #new-disclosure-form textarea, ' +
      '#technology-modal input:not([type="file"]), #technology-modal select, #technology-modal textarea'
    )
    .forEach(element => {
      element.disabled = false;
    });
}

async function stageSelectedIdfFiles(form) {
  const uploads = [...form.elements].filter(element =>
    element.type === 'file' && element.name && element.files?.[0]
  );
  if (uploads.length === 0) {
    return removeFilesFromFinalIdfSubmission(form);
  }

  const csrfToken = form.querySelector('input[name="_token"]')?.value;
  if (!csrfToken || !form.dataset.uploadUrl) {
    throw new Error('The PDF upload endpoint is not available. Refresh the page and try again.');
  }

  window.Swal.fire({
    title: 'Preparing PDF uploads',
    text: `Uploading PDF 1 of ${uploads.length}...`,
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => window.Swal.showLoading()
  });

  try {
    for (const [index, input] of uploads.entries()) {
      const file = input.files[0];
      window.Swal.update({ text: `Uploading ${input.dataset.fileLabel || file.name} (${index + 1} of ${uploads.length})...` });

      const body = new FormData();
      body.append('file', file, file.name);
      const response = await fetch(form.dataset.uploadUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || typeof payload.token !== 'string') {
        const validationMessage = Object.values(payload.errors || {}).flat()[0];
        throw new Error(validationMessage || payload.message || `Could not upload ${file.name}.`);
      }

      const tokenFieldName = stagedTokenFieldName(input.name);
      let tokenInput = [...form.querySelectorAll('[data-idf-upload-token]')]
        .find(field => field.name === tokenFieldName);
      if (!tokenInput) {
        tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = tokenFieldName;
        tokenInput.dataset.idfUploadToken = '';
        form.querySelector('[data-idf-staged-upload-tokens]').append(tokenInput);
      }
      tokenInput.value = payload.token;
      tokenInput.dataset.originalName = payload.original_name || file.name;
      input.required = false;
      input.value = '';
    }
  } catch (error) {
    window.Swal.close();
    throw error;
  }

  window.Swal.close();
  return removeFilesFromFinalIdfSubmission(form);
}

async function submitIdfDisclosure(form, formData) {
  const requestBody = {};
  for (const [key, value] of formData.entries()) {
    if (typeof value !== 'string') continue;
    const path = [...key.matchAll(/([^[\]]+)|\[([^\]]*)\]/g)]
      .map(match => match[1] ?? match[2]);
    if (!path.length) continue;

    let current = requestBody;
    path.forEach((part, index) => {
      const isLast = index === path.length - 1;
      if (part === '') {
        if (!Array.isArray(current)) {
          throw new Error(`Could not encode repeated form field "${key}".`);
        }
        current.push(value);
        return;
      }
      if (isLast) {
        if (Object.prototype.hasOwnProperty.call(current, part)) {
          const previous = current[part];
          current[part] = Array.isArray(previous) ? [...previous, value] : [previous, value];
        } else {
          current[part] = value;
        }
        return;
      }

      const nextPart = path[index + 1];
      if (!Object.prototype.hasOwnProperty.call(current, part)) {
        current[part] = nextPart === '' ? [] : {};
      }
      current = current[part];
    });
  }

  window.Swal.fire({
    title: 'Submitting disclosure',
    text: 'Saving your IDF and staged PDFs...',
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => window.Swal.showLoading()
  });

  const response = await fetch(form.action, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(requestBody),
    credentials: 'same-origin',
    redirect: 'follow'
  });

  if (!response.ok) {
    const payload = await response.json().catch(() => ({}));
    const validationMessages = Object.values(payload.errors || {}).flat();
    throw new Error(validationMessages.slice(0, 5).join(' ') || payload.message || `The server could not submit the IDF (HTTP ${response.status}). Your entered details are still on this page; please try again.`);
  }

  window.Swal.close();
  window.clearIdfSubmissionPending();
  window.location.assign(response.url);
}

function formatFileSize(bytes) {
  if (bytes >= 1024 ** 2) return `${(bytes / 1024 ** 2).toFixed(1)} MB`;
  if (bytes >= 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${bytes} B`;
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
      detail: {
        index: event.detail.index,
        staged: Boolean(event.detail.staged)
      }
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
    text: `"${event.detail.title}" and its uploaded PDFs will be removed from this disclosure.`,
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
      window.prepareIdfWizardSubmission();

      reenableAllWizardFields();

      const finalFormData = await stageSelectedIdfFiles(disclosureForm);
      window.markIdfSubmissionPending();
      await submitIdfDisclosure(disclosureForm, finalFormData);
    } catch (error) {
      window.clearIdfSubmissionPending();
      confirmationPending = false;
      window.Swal.close();
      console.error('Could not prepare the IDF draft for submission recovery:', error);
      await window.Swal.fire({
        title: 'Could not submit disclosure',
        text: error instanceof Error ? error.message : 'A PDF could not be prepared for submission. Please try again.',
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

document.addEventListener('click', event => {
  const fixFirst = event.target.closest('[data-technology-fix-first]');
  if (fixFirst) {
    fixFirst.closest('.inventor-technology-card')
      ?.querySelector('[data-technology-file-open].is-revision_requested')
      ?.click();
    return;
  }

  const trigger = event.target.closest('[data-review-file-replace]');
  if (!trigger) return;
  document.getElementById(trigger.dataset.reviewFileReplace)?.click();
});

function formatTechnologyFileSize(bytes) {
  if (bytes >= 1024 ** 2) return `${(bytes / 1024 ** 2).toFixed(1)} MB`;
  if (bytes >= 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${bytes} B`;
}

function createTechnologyFileDialog(trigger) {
  const dialog = document.createElement('div');
  dialog.className = 'technology-file-dialog';
  dialog.innerHTML = `
    <header class="technology-file-dialog__header">
      <span class="technology-file-dialog__file-icon"><i class="icon-base ri ri-file-pdf-2-line" aria-hidden="true"></i></span>
      <div class="technology-file-dialog__heading">
        <h2 data-dialog-file-name></h2>
        <p data-dialog-file-meta></p>
      </div>
      <span class="technology-file-dialog__status" data-dialog-status></span>
    </header>
    <div class="technology-file-dialog__body">
      <section class="technology-file-dialog__preview" aria-label="PDF preview">
        <iframe class="technology-file-dialog__frame" data-dialog-frame title="PDF preview"></iframe>
      </section>
      <aside class="technology-file-dialog__details">
        <h3 class="technology-file-dialog__section-title">Details</h3>
        <div class="technology-file-dialog__detail"><span>Technology</span><strong data-dialog-technology></strong></div>
        <div class="technology-file-dialog__detail"><span>Category</span><strong data-dialog-category></strong></div>
        <div class="technology-file-dialog__detail"><span>Uploaded</span><strong data-dialog-date></strong></div>
        <div class="technology-file-dialog__detail"><span>Current version</span><strong data-dialog-version></strong></div>
        <div class="technology-file-dialog__detail"><span>Status</span><strong data-dialog-status-detail></strong></div>
        <h3 class="technology-file-dialog__section-title mt-3">Version history</h3>
        <div class="technology-file-dialog__version-list" data-dialog-versions></div>
        <section class="d-none" data-dialog-revision-note-section>
          <h3 class="technology-file-dialog__section-title">Revision note — IP Head</h3>
          <div class="technology-file-dialog__revision-note" data-dialog-revision-note></div>
        </section>
        <section class="d-none" data-dialog-guidance-section>
          <h3 class="technology-file-dialog__section-title mt-3">Noted PDF — IP Head guidance</h3>
          <p class="small text-body-secondary mb-2" data-dialog-guidance-name></p>
          <button class="btn btn-primary" type="button" data-dialog-guidance-toggle>View Noted PDF</button>
        </section>
        <section class="d-none" data-dialog-resubmit-section>
          <h3 class="technology-file-dialog__section-title mt-3">Resubmit as next version</h3>
          <button class="technology-file-dialog__upload" type="button" data-dialog-upload>
            <span class="technology-file-dialog__upload-copy">
              <strong><i class="icon-base ri ri-file-upload-line me-1" aria-hidden="true"></i><span data-dialog-upload-label>Click to select the updated file</span></strong>
              <small>A new version is created — previous versions stay in the history</small>
            </span>
          </button>
          <textarea class="form-control technology-file-dialog__submission-note" rows="2" maxlength="2000" data-dialog-submission-note placeholder="Message to the reviewer (optional)..."></textarea>
          <small class="text-body-secondary">Previous versions stay in the version history.</small>
        </section>
      </aside>
    </div>`;

  const data = trigger.dataset;
  const fileName = dialog.querySelector('[data-dialog-file-name]');
  const fileMeta = dialog.querySelector('[data-dialog-file-meta]');
  const status = dialog.querySelector('[data-dialog-status]');
  const statusDetail = dialog.querySelector('[data-dialog-status-detail]');
  const preview = dialog.querySelector('[data-dialog-frame]');
  const versionLabel = `v${data.fileVersion}`;
  const fileSize = Number(data.fileSize);
  const statusLabel = data.fileStatusLabel || 'In review';

  fileName.textContent = data.fileName;
  fileMeta.textContent = `${data.fileLabel} · ${formatTechnologyFileSize(fileSize)} · ${data.fileDate || 'Date unavailable'}`;
  status.textContent = statusLabel;
  status.classList.add(`is-${data.fileStatus}`);
  statusDetail.textContent = statusLabel;
  dialog.querySelector('[data-dialog-technology]').textContent = data.technologyTitle;
  dialog.querySelector('[data-dialog-category]').textContent = data.technologyCategory;
  dialog.querySelector('[data-dialog-date]').textContent = data.fileDate || 'Date unavailable';
  dialog.querySelector('[data-dialog-version]').textContent = versionLabel;
  preview.src = data.filePreviewUrl;
  preview.title = `Preview of ${data.fileName}`;

  const versionsContainer = dialog.querySelector('[data-dialog-versions]');
  let versions = [];
  try {
    versions = JSON.parse(data.technologyFileVersions || '[]');
  } catch (error) {
    console.error('Could not read technology file version history.', error);
  }
  const currentVersion = {
    version: Number(data.fileVersion),
    name: data.fileName,
    size: fileSize,
    date: data.fileDate,
    url: data.filePreviewUrl,
    status: data.fileStatus,
    guidanceUrl: data.fileGuidanceUrl || null,
    guidanceName: data.fileGuidanceName || null
  };
  let selectedVersion = currentVersion;
  let isShowingGuidance = false;
  const guidanceSection = dialog.querySelector('[data-dialog-guidance-section]');
  const guidanceName = dialog.querySelector('[data-dialog-guidance-name]');
  const guidanceToggle = dialog.querySelector('[data-dialog-guidance-toggle]');
  const updateGuidanceSection = version => {
    selectedVersion = version;
    isShowingGuidance = false;
    const guidanceUrl = version.guidanceUrl || null;
    guidanceSection.classList.toggle('d-none', !guidanceUrl);
    guidanceName.textContent = version.guidanceName || 'The IP Head attached a marked-up PDF with specific requested changes.';
    guidanceToggle.textContent = 'View Noted PDF';
  };
  updateGuidanceSection(currentVersion);
  guidanceToggle.addEventListener('click', () => {
    const guidanceUrl = selectedVersion.guidanceUrl;
    if (!guidanceUrl) return;
    isShowingGuidance = !isShowingGuidance;
    preview.src = isShowingGuidance ? guidanceUrl : selectedVersion.url;
    preview.title = isShowingGuidance
      ? `IP Head noted PDF for ${selectedVersion.name}`
      : `Preview of ${selectedVersion.name}`;
    guidanceToggle.textContent = isShowingGuidance ? 'View Submitted PDF' : 'View Noted PDF';
  });
  [currentVersion, ...versions].forEach(version => {
    const versionButton = document.createElement('button');
    versionButton.className = 'technology-file-dialog__version';
    versionButton.type = 'button';
    versionButton.classList.toggle('is-current', Number(version.version) === currentVersion.version);
    versionButton.dataset.versionUrl = version.url;
    const badge = document.createElement('span');
    badge.className = 'technology-file-dialog__version-badge';
    badge.textContent = `v${version.version}`;
    const name = document.createElement('span');
    name.className = 'technology-file-dialog__version-name';
    name.textContent = version.name;
    const meta = document.createElement('small');
    meta.className = 'text-body-secondary';
    meta.textContent = `${version.date || 'Date unavailable'} · ${formatTechnologyFileSize(Number(version.size) || 0)}`;
    versionButton.append(badge, name, meta);
    versionsContainer.append(versionButton);
  });

  const revisionNoteSection = dialog.querySelector('[data-dialog-revision-note-section]');
  const resubmitSection = dialog.querySelector('[data-dialog-resubmit-section]');
  const fileInput = data.fileInputId ? document.getElementById(data.fileInputId) : null;
  const noteInput = data.submissionNoteInputId ? document.getElementById(data.submissionNoteInputId) : null;
  const revisionForm = data.revisionFormId ? document.getElementById(data.revisionFormId) : null;
  if (data.fileStatus === 'revision_requested') {
    revisionNoteSection.classList.remove('d-none');
    dialog.querySelector('[data-dialog-revision-note]').textContent =
      data.fileRevisionNote || 'The IP Head requested an update to this file.';
  }
  if (data.fileStatus === 'revision_requested' && fileInput && noteInput && revisionForm) {
    resubmitSection.classList.remove('d-none');
    fileInput.addEventListener('change', () => {
      dialog.querySelector('[data-dialog-upload-label]').textContent =
        fileInput.files?.[0]?.name || 'Click to select the updated file';
    });
  }

  versionsContainer.addEventListener('click', event => {
    const button = event.target.closest('[data-version-url]');
    if (!button) return;
    const selectedVersionNumber = Number(button.querySelector('.technology-file-dialog__version-badge')?.textContent.replace(/^v/, ''));
    selectedVersion = [currentVersion, ...versions].find(version => Number(version.version) === selectedVersionNumber) ?? currentVersion;
    preview.src = selectedVersion.url;
    preview.title = `Preview of ${selectedVersion.name}`;
    updateGuidanceSection(selectedVersion);
    dialog.querySelectorAll('.technology-file-dialog__version').forEach(item => {
      item.classList.toggle('is-current', item === button);
    });
  });

  return { dialog, fileInput, noteInput, revisionForm };
}

document.addEventListener('click', async event => {
  const trigger = event.target.closest('[data-technology-file-open]');
  if (!trigger) return;

  const { dialog, fileInput, noteInput, revisionForm } = createTechnologyFileDialog(trigger);
  const canResubmit = Boolean(fileInput && noteInput && revisionForm);
  const result = await window.Swal.fire({
    html: dialog,
    width: 'min(96vw, 1600px)',
    showCloseButton: true,
    showCancelButton: true,
    showConfirmButton: canResubmit,
    confirmButtonText: `Resubmit as v${Number(trigger.dataset.fileVersion) + 1}`,
    cancelButtonText: canResubmit ? 'Cancel' : 'Close',
    customClass: {
      popup: 'technology-file-swal',
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false,
    didOpen: () => {
      dialog.querySelector('[data-dialog-upload]')?.addEventListener('click', () => fileInput?.click());
    },
    preConfirm: () => {
      const file = fileInput?.files?.[0];
      if (!file) {
        window.Swal.showValidationMessage('Select the updated PDF before resubmitting.');
        return false;
      }
      if (!file.name.toLowerCase().endsWith('.pdf') || file.size > 15 * 1024 * 1024) {
        window.Swal.showValidationMessage('Choose a PDF no larger than 15 MB.');
        return false;
      }
      noteInput.value = dialog.querySelector('[data-dialog-submission-note]').value.trim();
      return true;
    }
  });

  if (result.isConfirmed && revisionForm && fileInput?.files?.length) {
    const confirmation = await window.Swal.fire({
      title: 'Is this your final file?',
      text: 'Confirm that this updated file is final and ready to be submitted for review.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, submit this file',
      cancelButtonText: 'Go back',
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-label-secondary'
      }
    });
    if (confirmation.isConfirmed) {
      revisionForm.dataset.confirmed = 'true';
      revisionForm.requestSubmit();
    } else if (fileInput) {
      fileInput.value = '';
    }
  } else if (fileInput) {
    fileInput.value = '';
  }
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