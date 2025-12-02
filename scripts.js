const roleButtons = document.querySelectorAll('.role-filter button');
const moduleCards = document.querySelectorAll('#modules .card');
roleButtons.forEach((btn) => {
  btn.addEventListener('click', () => {
    roleButtons.forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    const role = btn.dataset.role;
    moduleCards.forEach((card) => {
      const allowed = card.dataset.roles.split(',').map((r) => r.trim());
      const hasAccess = allowed.includes(role);
      card.style.opacity = hasAccess ? '1' : '0.25';
      card.style.pointerEvents = hasAccess ? 'auto' : 'none';
    });
  });
});

const budgetScopeSelectors = document.querySelectorAll('[name="budget-scope"]');
const projectSelect = document.querySelector('#budget-project');
const subBatchSelect = document.querySelector('#budget-sub-batch');
if (budgetScopeSelectors.length && projectSelect && subBatchSelect) {
  const toggleScope = () => {
    const value = document.querySelector('[name="budget-scope"]:checked').value;
    projectSelect.disabled = value !== 'project';
    subBatchSelect.disabled = value !== 'sub-batch';
  };
  budgetScopeSelectors.forEach((input) => input.addEventListener('change', toggleScope));
  toggleScope();
}

const requestedDate = document.querySelector('#requested-date');
const dueDate = document.querySelector('#due-date');
if (requestedDate && dueDate) {
  requestedDate.addEventListener('change', () => {
    const base = new Date(requestedDate.value);
    if (!isNaN(base.getTime())) {
      base.setDate(base.getDate() + 3);
      dueDate.valueAsDate = base;
    }
  });
}

const paymentStatus = document.querySelector('#payment-status');
const paymentPaid = document.querySelector('#payment-paid');
if (paymentStatus && paymentPaid) {
  paymentPaid.addEventListener('change', () => {
    paymentStatus.textContent = paymentPaid.checked ? 'Paid' : 'Pending';
    paymentStatus.className = `status-pill ${paymentPaid.checked ? 'success' : 'warning'}`;
  });
}

const invoiceVat = document.querySelector('#invoice-vat');
const invoiceSubtotal = document.querySelector('#invoice-subtotal');
const invoiceTotal = document.querySelector('#invoice-total');
if (invoiceVat && invoiceSubtotal && invoiceTotal) {
  const updateTotal = () => {
    const subtotal = parseFloat(invoiceSubtotal.value || '0');
    const vatRate = parseFloat(invoiceVat.value || '0');
    const total = subtotal + subtotal * (vatRate / 100);
    invoiceTotal.textContent = total.toFixed(2);
  };
  [invoiceVat, invoiceSubtotal].forEach((field) => field.addEventListener('input', updateTotal));
  updateTotal();
}