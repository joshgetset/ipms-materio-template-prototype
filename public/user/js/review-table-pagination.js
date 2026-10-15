document.querySelectorAll('[data-review-table]').forEach(table => {
  const pagination = table.parentElement.querySelector('[data-review-table-pagination]');
  const pageSize = Number(table.dataset.pageSize);
  const pages = [...table.querySelectorAll('[data-review-table-page]')];
  const body = table.tBodies?.[0];
  const rows = pages.length ? [] : [...(body?.rows ?? [])];
  const disableInactivePages = table.hasAttribute('data-disable-inactive');

  if ((!pages.length && !body) || !pagination || !Number.isInteger(pageSize) || pageSize < 1) return;

  const items = pages.length ? pages : rows;
  const paginationThreshold = Number(table.dataset.paginationThreshold);
  const shouldPaginate = !Number.isInteger(paginationThreshold)
    || paginationThreshold < 0
    || items.length > paginationThreshold;
  const effectivePageSize = shouldPaginate ? pageSize : Math.max(items.length, 1);
  const pageCount = Math.ceil(items.length / effectivePageSize);
  const initiallyDisabled = new WeakMap();

  let currentPage = 1;

  const render = () => {
    const start = (currentPage - 1) * effectivePageSize;
    items.forEach((item, index) => {
      const active = index >= start && index < start + effectivePageSize;
      item.hidden = !active;

      const controls = disableInactivePages
        ? item.querySelectorAll('input, select, textarea')
        : item.querySelectorAll('[data-disable-when-page-hidden]');
      controls.forEach(control => {
          if (!initiallyDisabled.has(control)) initiallyDisabled.set(control, control.disabled);
          control.disabled = initiallyDisabled.get(control) || !active;
      });
    });

    table.dispatchEvent(new CustomEvent('review-table:pagechange', {
      bubbles: true,
      detail: { page: currentPage }
    }));

    if (pageCount < 2) {
      pagination.hidden = true;
      return;
    }

    pagination.hidden = false;
    const list = document.createElement('ul');
    list.className = 'pagination mb-0';

    const addPageButton = (label, page, { disabled = false, current = false } = {}) => {
      const item = document.createElement('li');
      item.className = `page-item${disabled ? ' disabled' : ''}${current ? ' active' : ''}`;
      const button = document.createElement('button');
      button.className = 'page-link';
      button.type = 'button';
      button.textContent = label;
      button.disabled = disabled;
      if (current) button.setAttribute('aria-current', 'page');
      button.addEventListener('click', () => {
        currentPage = page;
        render();
      });
      item.append(button);
      list.append(item);
    };

    addPageButton('Previous', currentPage - 1, { disabled: currentPage === 1 });
    for (let page = 1; page <= pageCount; page += 1) {
      addPageButton(String(page), page, { current: page === currentPage });
    }
    addPageButton('Next', currentPage + 1, { disabled: currentPage === pageCount });

    pagination.replaceChildren(list);
  };

  render();
});
