<?php
require_once __DIR__ . '/helpers.php';

$currentUser = require_login();
$projects = fetch_table('projects', 'project_id');
$subBatchDetails = fetch_table('sub_batch_details', 'sub_batch_detail_id');
$projectOptions = to_options($projects, 'project_id', 'project_name');
$subBatchOptions = to_options($subBatchDetails, 'sub_batch_detail_id', 'sub_batch_name');

$error = '';
$success = '';

$submitted = [
    'payment_id' => trim($_POST['payment_id'] ?? ''),
    'scope' => $_POST['payment_scope'] ?? 'project',
    'project_id' => trim($_POST['project_id'] ?? ''),
    'sub_batch_detail_id' => trim($_POST['sub_batch_detail_id'] ?? ''),
    'payment_code' => trim($_POST['payment_code'] ?? ''),
    'payment_type' => trim($_POST['payment_type'] ?? ''),
    'requested_by' => trim($_POST['requested_by'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'amount' => trim($_POST['amount'] ?? ''),
    'currency' => trim($_POST['currency'] ?? 'EGP'),
    'exchange_rate' => trim($_POST['exchange_rate'] ?? ''),
    'requested_date' => trim($_POST['requested_date'] ?? ''),
    'due_date' => trim($_POST['due_date'] ?? ''),
    'paid_date' => trim($_POST['paid_date'] ?? ''),
    'status' => $_POST['status'] ?? 'pending',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = get_pdo();

    $linkProject = $submitted['scope'] === 'project' ? $submitted['project_id'] : '';
    $linkSubBatch = $submitted['scope'] === 'sub-batch' ? $submitted['sub_batch_detail_id'] : '';

    try {
        if ($action === 'create') {
            if ($submitted['payment_type'] === '' || $submitted['amount'] === '') {
                $error = 'Payment type and amount are required.';
            } elseif ($linkProject === '' && $linkSubBatch === '') {
                $error = 'Select either a project or sub-batch.';
            } else {
                $paymentId = $submitted['payment_id'] !== '' ? $submitted['payment_id'] : 'pay_' . bin2hex(random_bytes(4));

                $exists = $pdo->prepare('SELECT 1 FROM payments WHERE payment_id = :id');
                $exists->execute([':id' => $paymentId]);

                if ($exists->fetchColumn()) {
                    $error = 'A payment with this ID already exists.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO payments (payment_id, project_id, sub_batch_detail_id, payment_code, payment_type, requested_by, requested_date, due_date, paid_date, status, description, amount, currency, exchange_rate) VALUES (:id, :project, :sub_batch, :code, :type, :requested_by, :requested_date, :due_date, :paid_date, :status, :description, :amount, :currency, :exchange_rate)');
                    $stmt->execute([
                        ':id' => $paymentId,
                        ':project' => $linkProject !== '' ? $linkProject : null,
                        ':sub_batch' => $linkSubBatch !== '' ? $linkSubBatch : null,
                        ':code' => $submitted['payment_code'] ?: null,
                        ':type' => $submitted['payment_type'],
                        ':requested_by' => $submitted['requested_by'] ?: null,
                        ':requested_date' => $submitted['requested_date'] ?: null,
                        ':due_date' => $submitted['due_date'] ?: null,
                        ':paid_date' => $submitted['paid_date'] ?: null,
                        ':status' => $submitted['status'] ?: 'pending',
                        ':description' => $submitted['description'] ?: null,
                        ':amount' => $submitted['amount'],
                        ':currency' => $submitted['currency'] ?: null,
                        ':exchange_rate' => $submitted['exchange_rate'] !== '' ? $submitted['exchange_rate'] : null,
                    ]);

                    $success = 'Payment saved successfully.';
                    $submitted = array_fill_keys(array_keys($submitted), '');
                    $submitted['scope'] = 'project';
                    $submitted['currency'] = 'EGP';
                    $submitted['status'] = 'pending';
                }
            }
        } elseif ($action === 'update') {
            if ($submitted['payment_id'] === '') {
                $error = 'Enter the Payment ID to update.';
            } elseif ($submitted['payment_type'] === '' || $submitted['amount'] === '') {
                $error = 'Payment type and amount are required.';
            } elseif ($linkProject === '' && $linkSubBatch === '') {
                $error = 'Select either a project or sub-batch.';
            } else {
                $stmt = $pdo->prepare('UPDATE payments SET project_id = :project, sub_batch_detail_id = :sub_batch, payment_code = :code, payment_type = :type, requested_by = :requested_by, requested_date = :requested_date, due_date = :due_date, paid_date = :paid_date, status = :status, description = :description, amount = :amount, currency = :currency, exchange_rate = :exchange_rate WHERE payment_id = :id');
                $stmt->execute([
                    ':id' => $submitted['payment_id'],
                    ':project' => $linkProject !== '' ? $linkProject : null,
                    ':sub_batch' => $linkSubBatch !== '' ? $linkSubBatch : null,
                    ':code' => $submitted['payment_code'] ?: null,
                    ':type' => $submitted['payment_type'],
                    ':requested_by' => $submitted['requested_by'] ?: null,
                    ':requested_date' => $submitted['requested_date'] ?: null,
                    ':due_date' => $submitted['due_date'] ?: null,
                    ':paid_date' => $submitted['paid_date'] ?: null,
                    ':status' => $submitted['status'] ?: 'pending',
                    ':description' => $submitted['description'] ?: null,
                    ':amount' => $submitted['amount'],
                    ':currency' => $submitted['currency'] ?: null,
                    ':exchange_rate' => $submitted['exchange_rate'] !== '' ? $submitted['exchange_rate'] : null,
                ]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Payment not found.';
                } else {
                    $success = 'Payment updated successfully.';
                }
            }
        } elseif ($action === 'view') {
            if ($submitted['payment_id'] === '') {
                $error = 'Enter a Payment ID to load details.';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM payments WHERE payment_id = :id');
                $stmt->execute([':id' => $submitted['payment_id']]);
                $found = $stmt->fetch();

                if ($found) {
                    foreach ($submitted as $key => $_) {
                        $submitted[$key] = (string) ($found[$key] ?? '');
                    }
                    $submitted['scope'] = $found['project_id'] ? 'project' : 'sub-batch';
                    $success = 'Payment loaded. You can update or delete it.';
                } else {
                    $error = 'No payment found with that ID.';
                }
            }
        } elseif ($action === 'delete') {
            if ($submitted['payment_id'] === '') {
                $error = 'Enter a Payment ID to delete.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM payments WHERE payment_id = :id');
                $stmt->execute([':id' => $submitted['payment_id']]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Payment not found or already deleted.';
                } else {
                    $success = 'Payment deleted successfully.';
                    $submitted = array_fill_keys(array_keys($submitted), '');
                    $submitted['scope'] = 'project';
                    $submitted['currency'] = 'EGP';
                    $submitted['status'] = 'pending';
                }
            }
        }
    } catch (Throwable $e) {
        $error = format_db_error($e, 'payments table');
    }
}

$payments = fetch_table('payments', 'payment_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payments | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
      <div class="title">Payments</div>
    </div>
    <div class="links">
         <a href="./home.php">Home</a>␊
      <a href="./logout.php">Logout</a>
    </div>
  </header>
  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">Outgoing Payment</h3>
      <?php if ($error): ?>
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;">
          <?php echo safe($error); ?>
        </div>
      <?php elseif ($success): ?>
        <div class="alert" style="color: var(--primary); margin-bottom:12px;">
          <?php echo safe($success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="payments.php">
        <div class="form-row">
          <div>
            <label class="label" for="payment-id">Payment ID</label>
            <input id="payment-id" name="payment_id" type="text" placeholder="PAY-001" value="<?php echo safe($submitted['payment_id']); ?>" />
            <p class="helper-text">Leave blank to auto-generate when saving.</p>
          </div>
          <div>
            <label class="label">Scope</label>
            <div style="display:flex; gap:10px; align-items:center;">
              <label><input type="radio" name="payment_scope" value="project" <?php echo $submitted['scope'] === 'project' ? 'checked' : ''; ?> /> Project</label>
              <label><input type="radio" name="payment_scope" value="sub-batch" <?php echo $submitted['scope'] === 'sub-batch' ? 'checked' : ''; ?> /> Sub-Batch Detail</label>
            </div>
          </div>
          <div>
            <label class="label" for="payment-project">Project</label>
            <select id="payment-project" name="project_id">
              <option value="">-- Select Project --</option>
              <?php foreach ($projectOptions as $option): ?>
                <option value="<?php echo safe($option['value']); ?>" <?php echo $submitted['project_id'] === $option['value'] ? 'selected' : ''; ?>><?php echo safe($option['value'] . ' | ' . $option['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="payment-sub-batch">Sub-Batch Detail</label>
            <select id="payment-sub-batch" name="sub_batch_detail_id">
              <option value="">-- Select Sub-Batch --</option>
              <?php foreach ($subBatchOptions as $option): ?>
                <option value="<?php echo safe($option['value']); ?>" <?php echo $submitted['sub_batch_detail_id'] === $option['value'] ? 'selected' : ''; ?>><?php echo safe($option['value'] . ' | ' . $option['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="payment-code">Payment Code</label>
            <input id="payment-code" name="payment_code" type="text" placeholder="PMT-REF-01" value="<?php echo safe($submitted['payment_code']); ?>" />
          </div>
          <div>
            <label class="label" for="payment-type">Payment Type</label>
            <input id="payment-type" name="payment_type" type="text" placeholder="Freight / Customs / Supplier" value="<?php echo safe($submitted['payment_type']); ?>" />
          </div>
          <div>
            <label class="label" for="payment-requested-by">Requested By</label>
            <input id="payment-requested-by" name="requested_by" type="text" placeholder="Requester name" value="<?php echo safe($submitted['requested_by']); ?>" />
          </div>
          <div>
            <label class="label" for="payment-description">Description</label>
            <input id="payment-description" name="description" type="text" placeholder="Short description" value="<?php echo safe($submitted['description']); ?>" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="payment-amount">Amount</label>
            <input id="payment-amount" name="amount" type="number" step="0.01" placeholder="50000" value="<?php echo safe($submitted['amount']); ?>" />
          </div>
          <div>
            <label class="label" for="payment-currency">Currency</label>
            <select id="payment-currency" name="currency">
              <?php foreach (['EGP','USD','EUR'] as $currency): ?>
                <option value="<?php echo $currency; ?>" <?php echo $submitted['currency'] === $currency ? 'selected' : ''; ?>><?php echo $currency; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="payment-rate">Exchange Rate</label>
            <input id="payment-rate" name="exchange_rate" type="number" step="0.0001" placeholder="48.50" value="<?php echo safe($submitted['exchange_rate']); ?>" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="requested-date">Requested Date</label>
            <input id="requested-date" name="requested_date" type="date" value="<?php echo safe($submitted['requested_date']); ?>" />
          </div>
          <div>
            <label class="label" for="due-date">Payment Due</label>
            <input id="due-date" name="due_date" type="date" value="<?php echo safe($submitted['due_date']); ?>" />
          </div>
          <div>
            <label class="label" for="paid-date">Paid Date</label>
            <input id="paid-date" name="paid_date" type="date" value="<?php echo safe($submitted['paid_date']); ?>" />
          </div>
          <div>
            <label class="label" for="payment-status">Status</label>
            <select id="payment-status" name="status">
              <?php foreach (['pending','paid'] as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo $submitted['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="actions">
          <button class="btn btn-save" type="submit" name="action" value="create">Save Payment</button>
          <button class="btn btn-neutral" type="submit" name="action" value="view">View</button>
          <button class="btn btn-neutral" type="submit" name="action" value="update">Update</button>
          <button class="btn btn-delete" type="submit" name="action" value="delete" onclick="return confirm('Delete this payment?');">Delete</button>
        </div>
      </form>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>ID</th><th>Scope</th><th>Link</th><th>Type</th><th>Amount</th><th>Requested</th><th>Due</th><th>Paid</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if ($payments): ?>
            <?php foreach ($payments as $payment): ?>
              <?php
                $scope = $payment['project_id'] ? 'Project' : 'Sub-Batch';
                $link = $payment['project_id'] ?: $payment['sub_batch_detail_id'];
                $statusClass = $payment['status'] === 'paid' ? 'success' : 'warning';
              ?>
              <tr>
                <td><?php echo safe($payment['payment_id']); ?></td>
                <td><?php echo safe($scope); ?></td>
                <td><?php echo safe($link); ?></td>
                <td><?php echo safe($payment['payment_type']); ?></td>
                <td><?php echo safe(($payment['currency'] ?? '') . ' ' . ($payment['amount'] ?? '')); ?></td>
                <td><?php echo safe($payment['requested_date']); ?></td>
                <td><?php echo safe($payment['due_date']); ?></td>
                <td><?php echo safe($payment['paid_date']); ?></td>
                <td><span class="status-pill <?php echo safe($statusClass); ?>"><?php echo safe(ucfirst($payment['status'])); ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9">No payments recorded yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
  <script src="./assets/scripts.js"></script>
</body>
</html>