function toggleAll(masterCheckbox, tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
  checkboxes.forEach((cb) => {
    cb.checked = masterCheckbox.checked;
  });
}

function getSelectedRows(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return [];

  return Array.from(table.querySelectorAll('tbody tr')).filter((row) => {
    const checkbox = row.querySelector('input[type="checkbox"]');
    return checkbox && checkbox.checked;
  });
}

function exportSelected(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const selectedRows = getSelectedRows(tableId);
  if (!selectedRows.length) {
    alert('Select at least one row to download.');
    return;
  }

  const headerCells = Array.from(table.querySelectorAll('thead th')).slice(1);
  const headers = headerCells.map((cell) => cell.textContent.trim());

  const body = selectedRows
    .map((row) => {
      const cells = Array.from(row.querySelectorAll('td')).slice(1);
      const tds = cells.map((cell) => `<td>${cell.textContent.trim()}</td>`).join('');
      return `<tr>${tds}</tr>`;
    })
    .join('');

  const tableHtml = `<table><thead><tr>${headers.map((text) => `<th>${text}</th>`).join('')}</tr></thead><tbody>${body}</tbody></table>`;

  const blob = new Blob([tableHtml], { type: 'application/vnd.ms-excel' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `${tableId}-selection.xlsx`;
  link.click();
  URL.revokeObjectURL(url);
}

function renderSelectionSummary(tableId, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  const list = container.querySelector('.selection-summary__list');
  if (!list) return;

  const selectedRows = getSelectedRows(tableId);
  list.innerHTML = '';

  if (!selectedRows.length) {
    list.innerHTML = '<li>No rows selected.</li>';
    return;
  }

  selectedRows.forEach((row) => {
    const cells = Array.from(row.querySelectorAll('td')).map((cell) => cell.textContent.trim());
    const [id, username, role, status] = cells;
    const item = document.createElement('li');
    item.textContent = `${id} • ${username} • ${role.toUpperCase()} (${status})`;
    list.appendChild(item);
  });
}

function handleCustomSelect(selectId, inputId) {
  const select = document.getElementById(selectId);
  const input = document.getElementById(inputId);
  if (!select || !input) return;

  const toggle = () => {
    const showCustom = select.value === '__custom__';
    input.classList.toggle('hidden', !showCustom);
    if (!showCustom) {
      input.value = '';
    }
  };

  select.addEventListener('change', toggle);
  toggle();
}

document.addEventListener('DOMContentLoaded', () => {
  handleCustomSelect('user-id', 'user-id-custom');
  handleCustomSelect('username', 'username-custom');

  renderSelectionSummary('users-table', 'selection-summary');
  const usersTable = document.getElementById('users-table');
  if (usersTable) {
    usersTable.addEventListener('change', (event) => {
      if (event.target.matches('input[type="checkbox"]')) {
        renderSelectionSummary('users-table', 'selection-summary');
      }
    });
  }
});