<?php
require_once __DIR__ . '/helpers.php';

$currentUser = require_login();
$projects = fetch_table('projects', 'project_id');
$projectOptions = to_options($projects, 'project_id', 'project_name');

$error = '';
$success = '';

$submitted = [
    'invoice_id' => trim($_POST['invoice_id'] ?? ''),
    'invoice_number' => trim($_POST['invoice_number'] ?? ''),
    'project_id' => trim($_POST['project_id'] ?? ''),
    'invoice_date' => trim($_POST['invoice_date'] ?? ''),
    'total_amount' => trim($_POST['total_amount'] ?? ''),
    'vat_amount' => trim($_POST['vat_amount'] ?? ''),
    'amount_with_vat' => trim($_POST['amount_with_vat'] ?? ''),
    'status' => $_POST['status'] ?? 'draft',
    'collected_date' => trim($_POST['collected_date'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = get_pdo();

    try {
        if ($action === 'create') {
            if ($submitted['invoice_number'] === '' || $submitted['total_amount'] === '') {
                $error = 'Invoice number and total amount are required.';
            } else {
                $invoiceId = $submitted['invoice_id'] !== '' ? $submitted['invoice_id'] : 'inv_' . bin2hex(random_bytes(4));

                $exists = $pdo->prepare('SELECT 1 FROM invoices WHERE invoice_id = :id');
                $exists->execute([':id' => $invoiceId]);

                if ($exists->fetchColumn()) {
                    $error = 'An invoice with this ID already exists.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO invoices (invoice_id, project_id, description, invoice_number, invoice_date, total_amount, vat_amount, amount_with_vat, status, collected_date) VALUES (:id, :project, :description, :number, :date, :total, :vat, :with_vat, :status, :collected)');
                    $stmt->execute([
                        ':id' => $invoiceId,
                        ':project' => $submitted['project_id'] ?: null,
                        ':description' => $submitted['description'] ?: null,
                        ':number' => $submitted['invoice_number'],
                        ':date' => $submitted['invoice_date'] ?: null,
                        ':total' => $submitted['total_amount'],
                        ':vat' => $submitted['vat_amount'] !== '' ? $submitted['vat_amount'] : null,
                        ':with_vat' => $submitted['amount_with_vat'] !== '' ? $submitted['amount_with_vat'] : null,
                        ':status' => $submitted['status'] ?: 'draft',
                        ':collected' => $submitted['collected_date'] ?: null,
                    ]);

                    $success = 'Invoice saved successfully.';
                    $submitted = array_fill_keys(array_keys($submitted), '');
                    $submitted['status'] = 'draft';
                }
            }
        } elseif ($action === 'update') {
            if ($submitted['invoice_id'] === '') {
                $error = 'Enter the Invoice ID to update.';
            } elseif ($submitted['invoice_number'] === '' || $submitted['total_amount'] === '') {
                $error = 'Invoice number and total amount are required.';
            } else {
                $stmt = $pdo->prepare('UPDATE invoices SET project_id = :project, description = :description, invoice_number = :number, invoice_date = :date, total_amount = :total, vat_amount = :vat, amount_with_vat = :with_vat, status = :status, collected_date = :collected WHERE invoice_id = :id');
                $stmt->execute([
                    ':id' => $submitted['invoice_id'],
                    ':project' => $submitted['project_id'] ?: null,
                    ':description' => $submitted['description'] ?: null,
                    ':number' => $submitted['invoice_number'],
                    ':date' => $submitted['invoice_date'] ?: null,
                    ':total' => $submitted['total_amount'],
                    ':vat' => $submitted['vat_amount'] !== '' ? $submitted['vat_amount'] : null,
                    ':with_vat' => $submitted['amount_with_vat'] !== '' ? $submitted['amount_with_vat'] : null,
                    ':status' => $submitted['status'] ?: 'draft',
                    ':collected' => $submitted['collected_date'] ?: null,
                ]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Invoice not found.';
                } else {
                    $success = 'Invoice updated successfully.';
                }
            }
        } elseif ($action === 'view') {
            if ($submitted['invoice_id'] === '') {
                $error = 'Enter an Invoice ID to load details.';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_id = :id');
                $stmt->execute([':id' => $submitted['invoice_id']]);
                $found = $stmt->fetch();

                if ($found) {
                    foreach ($submitted as $key => $_) {
                        $submitted[$key] = (string) ($found[$key] ?? '');
                    }
                    $success = 'Invoice loaded. You can update or delete it.';
                } else {
                    $error = 'No invoice found with that ID.';
                }
            }
        } elseif ($action === 'delete') {
            if ($submitted['invoice_id'] === '') {
                $error = 'Enter an Invoice ID to delete.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM invoices WHERE invoice_id = :id');
                $stmt->execute([':id' => $submitted['invoice_id']]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Invoice not found or already deleted.';
                } else {
                    $success = 'Invoice deleted successfully.';
                    $submitted = array_fill_keys(array_keys($submitted), '');
                    $submitted['status'] = 'draft';
                }
            }
        }
    } catch (Throwable $e) {
        $error = format_db_error($e, 'invoices table');
    }
}

$invoices = fetch_table('invoices', 'invoice_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Invoices | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
      <div class="title">Invoices</div>
    </div>
    <div class="links">
<a href="./home.php">Home</a>␊
      <a href="./logout.php">Logout</a>
    </div>
  </header>
  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">Invoice Creation</h3>
       <?php if ($error): ?>
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;">
          <?php echo safe($error); ?>
        </div>
      <?php elseif ($success): ?>
        <div class="alert" style="color: var(--primary); margin-bottom:12px;">
          <?php echo safe($success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="invoices.php">
        <div class="form-row">
          <div>
            <label class="label" for="invoice-id">Invoice ID</label>
            <input id="invoice-id" name="invoice_id" type="text" placeholder="INV-001" value="<?php echo safe($submitted['invoice_id']); ?>" />
            <p class="helper-text">Leave blank to auto-generate when saving.</p>
          </div>
          <div>
            <label class="label" for="invoice-number">Invoice Number</label>
            <input id="invoice-number" name="invoice_number" type="text" placeholder="Official invoice number" value="<?php echo safe($submitted['invoice_number']); ?>" />
          </div>
          <div>
            <label class="label" for="invoice-project">Project</label>
            <select id="invoice-project" name="project_id">
              <option value="">-- Select Project --</option>
              <?php foreach ($projectOptions as $option): ?>
                <option value="<?php echo safe($option['value']); ?>" <?php echo $submitted['project_id'] === $option['value'] ? 'selected' : ''; ?>><?php echo safe($option['value'] . ' | ' . $option['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="invoice-date">Invoice Date</label>
            <input id="invoice-date" name="invoice_date" type="date" value="<?php echo safe($submitted['invoice_date']); ?>" />
          </div>
          <div>
            <label class="label" for="invoice-subtotal">Total Amount (before VAT)</label>
            <input id="invoice-subtotal" name="total_amount" type="number" step="0.01" placeholder="100000" value="<?php echo safe($submitted['total_amount']); ?>" />
          </div>
          <div>
            <label class="label" for="invoice-vat">VAT Amount</label>
            <input id="invoice-vat" name="vat_amount" type="number" step="0.01" placeholder="14000" value="<?php echo safe($submitted['vat_amount']); ?>" />
          </div>
          <div>
            <label class="label" for="invoice-total">Total with VAT</label>
            <input id="invoice-total" name="amount_with_vat" type="number" step="0.01" placeholder="114000" value="<?php echo safe($submitted['amount_with_vat']); ?>" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="invoice-description">Description</label>
            <input id="invoice-description" name="description" type="text" placeholder="Scope notes" value="<?php echo safe($submitted['description']); ?>" />
          </div>
          <div>
            <label class="label" for="invoice-status">Status</label>
            <select id="invoice-status" name="status">
              <?php foreach (['draft','issued','collected'] as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo $submitted['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="collected-date">Collected Date</label>
            <input id="collected-date" name="collected_date" type="date" value="<?php echo safe($submitted['collected_date']); ?>" />
          </div>
        </div>
        <div class="actions">
          <button class="btn btn-save" type="submit" name="action" value="create">Save Invoice</button>
          <button class="btn btn-neutral" type="submit" name="action" value="view">View</button>
          <button class="btn btn-neutral" type="submit" name="action" value="update">Update</button>
          <button class="btn btn-delete" type="submit" name="action" value="delete" onclick="return confirm('Delete this invoice?');">Delete</button>
        </div>
      </form>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>ID</th><th>Project</th><th>Invoice #</th><th>Date</th><th>Total</th><th>VAT</th><th>With VAT</th><th>Status</th><th>Collected</th></tr>
        </thead>
        <tbody>
          <?php if ($invoices): ?>
            <?php foreach ($invoices as $invoice): ?>
              <tr>
                <td><?php echo safe($invoice['invoice_id']); ?></td>
                <td><?php echo safe($invoice['project_id']); ?></td>
                <td><?php echo safe($invoice['invoice_number']); ?></td>
                <td><?php echo safe($invoice['invoice_date']); ?></td>
                <td><?php echo safe($invoice['total_amount']); ?></td>
                <td><?php echo safe($invoice['vat_amount']); ?></td>
                <td><?php echo safe($invoice['amount_with_vat']); ?></td>
                <td><?php echo safe($invoice['status']); ?></td>
                <td><?php echo safe($invoice['collected_date']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9">No invoices recorded yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
  <script src="./assets/scripts.js"></script>
</body>
</html>