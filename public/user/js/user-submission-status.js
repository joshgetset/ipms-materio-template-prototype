const notificationBell = document.querySelector('[data-submission-notifications]');
const statusUrl = notificationBell?.dataset.submissionStatusUrl
  ?? document.querySelector('[data-submission-status-url]')?.dataset.submissionStatusUrl;
const notificationList = notificationBell?.querySelector('[data-submission-notification-list]');
const notificationCount = notificationBell?.querySelector('[data-submission-notification-count]');
const notificationSummary = notificationBell?.querySelector('[data-submission-notification-summary]');
const notificationStorageKey = notificationBell
  ? `ipms-submission-notifications-user-${notificationBell.dataset.notificationUser}`
  : null;
let readNotificationIds = new Set();
let lastDisclosures = [];

document.addEventListener('click', event => {
  const trigger = event.target.closest('[data-timeline-narrative-trigger]');
  if (!trigger) return;

  const tracker = trigger.closest('.submission-review-tracker');
  const narrativeContainer = document.getElementById(trigger.getAttribute('aria-controls'));
  if (!tracker || !narrativeContainer || !tracker.contains(trigger)) return;

  const selectedNarrative = trigger.dataset.timelineNarrativeTrigger;
  narrativeContainer.querySelectorAll('[data-timeline-narrative]').forEach(narrative => {
    narrative.hidden = narrative.dataset.timelineNarrative !== selectedNarrative;
  });

  tracker.querySelectorAll('[data-timeline-narrative-trigger]').forEach(stepTrigger => {
    const isSelected = stepTrigger === trigger;
    stepTrigger.setAttribute('aria-pressed', String(isSelected));
    stepTrigger.closest('[data-timeline-step]')?.classList.toggle('is-selected', isSelected);
  });
});

const submissionStatusColors = {
  submitted: 'primary',
  under_review: 'warning',
  revised_submission: 'warning',
  returned: 'danger',
  endorsed: 'success',
  filed: 'success'
};

if (notificationStorageKey) {
  try {
    const savedIds = localStorage.getItem(notificationStorageKey);
    if (savedIds) {
      const parsedIds = JSON.parse(savedIds);
      if (Array.isArray(parsedIds) && parsedIds.every(id => typeof id === 'string')) {
        readNotificationIds = new Set(parsedIds);
      } else {
        console.error('Saved submission notification state has an invalid format.');
      }
    }
  } catch (error) {
    console.error('Could not read submission notification state.', error);
  }
}

const submissionStatusLabels = {
  submitted: 'Submitted',
  under_review: 'Under review',
  revised_submission: 'Revised submission',
  returned: 'Revision requested',
  endorsed: 'Approved',
  filed: 'Filed'
};

function saveReadNotificationIds() {
  if (!notificationStorageKey) return;
  try {
    localStorage.setItem(notificationStorageKey, JSON.stringify([...readNotificationIds].slice(-200)));
  } catch (error) {
    console.error('Could not save submission notification state.', error);
  }
}

function renderSubmissionNotifications(items) {
  if (!notificationList || !notificationCount || !notificationSummary) return;

  notificationList.replaceChildren();
  const unreadCount = items.filter(item => !readNotificationIds.has(item.id)).length;
  notificationCount.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
  notificationCount.classList.toggle('d-none', unreadCount === 0);
  notificationSummary.textContent = unreadCount > 0 ? `${unreadCount} new` : `${items.length} updates`;

  if (items.length === 0) {
    const empty = document.createElement('li');
    empty.className = 'dropdown-notifications-item px-4 py-3 text-body-secondary';
    empty.textContent = 'No submission updates yet.';
    notificationList.append(empty);
    return;
  }

  const submissionUrlTemplate = notificationBell.dataset.submissionUrlTemplate;
  items.forEach(item => {
    const row = document.createElement('li');
    row.className = 'dropdown-notifications-item border-bottom';
    row.classList.toggle('marked-as-read', readNotificationIds.has(item.id));
    row.dataset.notificationId = item.id;

    const link = document.createElement('a');
    link.className = 'd-flex align-items-start text-reset text-decoration-none';
    link.href = submissionUrlTemplate.replace('__DISCLOSURE__', encodeURIComponent(item.disclosureId));

    const avatarWrap = document.createElement('div');
    avatarWrap.className = 'flex-shrink-0 me-3';
    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    const avatarIcon = document.createElement('span');
    avatarIcon.className = `avatar-initial rounded-circle bg-label-${item.color}`;
    const icon = document.createElement('i');
    icon.className = `icon-base ri ${item.icon}`;
    icon.setAttribute('aria-hidden', 'true');
    avatarIcon.append(icon);
    avatar.append(avatarIcon);
    avatarWrap.append(avatar);

    const body = document.createElement('div');
    body.className = 'flex-grow-1';
    const title = document.createElement('h6');
    title.className = 'mb-1 text-body';
    title.textContent = item.title;
    const message = document.createElement('p');
    message.className = 'mb-1 small text-body-secondary';
    message.textContent = item.message;
    const date = document.createElement('small');
    date.className = 'text-body-secondary';
    const updatedAt = new Date(item.updatedAt);
    date.textContent = Number.isNaN(updatedAt.getTime())
      ? 'Recently'
      : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(updatedAt);
    body.append(title, message, date);
    link.append(avatarWrap, body);
    row.append(link);
    notificationList.append(row);
  });
}

function getSubmissionNotificationItems(disclosures) {
  const items = [];

  disclosures.forEach(disclosure => {
    const title = disclosure.technology_title || `Submission #${disclosure.id}`;
    const statusLabel = submissionStatusLabels[disclosure.status] ?? disclosure.status;
    items.push({
      id: `submission-${disclosure.id}-${disclosure.status}-${disclosure.updated_at}`,
      disclosureId: disclosure.id,
      title,
      message: `Submission status: ${statusLabel}`,
      updatedAt: disclosure.updated_at,
      color: submissionStatusColors[disclosure.status] ?? 'primary',
      icon: 'ri-file-list-3-line'
    });

    (disclosure.attachments ?? []).forEach(attachment => {
      if (!['accepted', 'revision_requested'].includes(attachment.review_status)) return;
      const approved = attachment.review_status === 'accepted';
      items.push({
        id: `attachment-${attachment.id}-${attachment.review_status}-${attachment.updated_at}`,
        disclosureId: disclosure.id,
        title: attachment.original_name,
        message: approved ? `Approved · ${title}` : `Revision requested · ${title}`,
        updatedAt: attachment.updated_at,
        color: approved ? 'success' : 'warning',
        icon: approved ? 'ri-checkbox-circle-line' : 'ri-edit-line'
      });
    });

    (disclosure.technologies ?? []).forEach(technology => {
      (technology.files ?? []).forEach(file => {
        if (!['accepted', 'revision_requested'].includes(file.review_status)) return;
        const approved = file.review_status === 'accepted';
        items.push({
          id: `technology-file-${file.id}-${file.review_status}-${file.updated_at}`,
          disclosureId: disclosure.id,
          title: file.original_name,
          message: `${approved ? 'Approved' : 'Revision requested'} · ${technology.technology_title || title}`,
          updatedAt: file.updated_at,
          color: approved ? 'success' : 'warning',
          icon: approved ? 'ri-checkbox-circle-line' : 'ri-edit-line'
        });
      });
    });
  });

  return items
    .sort((left, right) => new Date(right.updatedAt).getTime() - new Date(left.updatedAt).getTime())
    .slice(0, 10);
}

notificationBell?.querySelector('[data-bs-toggle="dropdown"]')?.addEventListener('click', () => {
  readNotificationIds = new Set([
    ...readNotificationIds,
    ...[...(notificationList?.querySelectorAll('[data-notification-id]') ?? [])]
      .map(item => item.dataset.notificationId)
  ]);
  saveReadNotificationIds();
  renderSubmissionNotifications(getSubmissionNotificationItems(lastDisclosures));
});

if (statusUrl) {
  async function refreshSubmissionStatuses() {
    if (document.hidden) return;

    try {
      const response = await fetch(statusUrl, {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });
      if (!response.ok) {
        throw new Error(`Submission status request failed with HTTP ${response.status}.`);
      }

      const disclosures = await response.json();
      lastDisclosures = disclosures;
      renderSubmissionNotifications(getSubmissionNotificationItems(disclosures));

      const counts = Object.fromEntries(Object.keys(submissionStatusLabels).map(status => [status, 0]));

      disclosures.forEach(disclosure => {
        counts[disclosure.status] = (counts[disclosure.status] ?? 0) + 1;
        const color = submissionStatusColors[disclosure.status] ?? 'primary';
        const revisionForm = document.querySelector(`[data-disclosure-status-id="${disclosure.id}"]`);

        document.querySelectorAll('[data-disclosure-status], [data-modal-disclosure-status]').forEach(badge => {
          if (
            badge.dataset.disclosureStatus !== String(disclosure.id) &&
            badge.dataset.modalDisclosureStatus !== String(disclosure.id)
          )
            return;

          badge.textContent = submissionStatusLabels[disclosure.status] ?? disclosure.status;
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
          document.querySelectorAll(`[data-file-status-badge="${attachment.id}"]`).forEach(badge => {
            const status = isFlagged
              ? ['Revision requested', 'warning']
              : attachment.is_latest_submission
                ? ['Latest', 'success']
                : ['Superseded', 'secondary'];
            badge.textContent = status[0];
            badge.className = `badge rounded-pill bg-label-${status[1]}`;
          });
          document.querySelectorAll(`[data-file-attachment="${attachment.id}"]`).forEach(card => {
            card.classList.toggle('user-submission-file-card--revision-requested', isFlagged);
            card.classList.toggle(
              'user-submission-file-card--revised',
              Boolean(attachment.is_latest_submission) && Number(attachment.version) > 1
            );
          });
          document.querySelectorAll(`[data-file-attachment-row="${attachment.id}"]`).forEach(row => {
            row.classList.toggle('review-files-row--revision', isFlagged);
          });

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

          document.querySelectorAll('[data-file-attachment-input]').forEach(input => {
            if (input.form !== revisionForm) return;
            const isFlagged = flaggedAttachmentIds.has(input.dataset.fileAttachmentInput);
            input.disabled = !canSubmitRevisions || !isFlagged || Boolean(input.closest('[data-review-table-page]')?.hidden);
            input.closest('[data-revision-upload-control]')?.classList.toggle('d-none', !canSubmitRevisions || !isFlagged);
          });

          const canUpdateFiles = canSubmitRevisions && flaggedAttachmentIds.size > 0;
          const detailPage = revisionForm.closest('.submission-details-page');
          detailPage?.querySelectorAll('[data-update-file-button]').forEach(button => {
            button.classList.toggle('d-none', !canUpdateFiles);
          });
          const notice = detailPage?.querySelector('[data-file-update-notice]');
          if (notice) {
            notice.textContent = disclosure.status === 'submitted'
              ? 'Files cannot be changed until the IP Head reviews this submission.'
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
    } catch (error) {
      console.error('Could not refresh inventor submission statuses.', error);
    }
  }

  refreshSubmissionStatuses();
  window.setInterval(refreshSubmissionStatuses, 10000);
  document.addEventListener('visibilitychange', refreshSubmissionStatuses);
}
