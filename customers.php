<?php
require_once __DIR__ . '/helpers.php';

$currentUser = require_login();
$error = '';
$success = '';

$submitted = [
    'customer_id' => trim($_POST['customer_id'] ?? ''),
    'customer_name' => trim($_POST['customer_name'] ?? ''),
];
$selectedIds = array_filter(array_map('trim', (array) ($_POST['selected_ids'] ?? [])));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = get_pdo();

    try {
        if ($action === 'create') {
            if ($submitted['customer_name'] === '') {
                $error = 'Customer name is required.';
            } else {
                $customerId = $submitted['customer_id'] !== '' ? $submitted['customer_id'] : 'cust_' . bin2hex(random_bytes(4));

                $exists = $pdo->prepare('SELECT 1 FROM customers WHERE customer_id = :id');
                $exists->execute([':id' => $customerId]);

                if ($exists->fetchColumn()) {
                    $error = 'A customer with this ID already exists.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO customers (customer_id, customer_name) VALUES (:id, :name)');
                    $stmt->execute([
                        ':id' => $customerId,
                        ':name' => $submitted['customer_name'],
                    ]);

                    $success = 'Customer saved successfully.';
                    $submitted = ['customer_id' => '', 'customer_name' => ''];
                }
            }
        } elseif ($action === 'update') {
            if ($submitted['customer_id'] === '' || $submitted['customer_name'] === '') {
                $error = 'Provide both Customer ID and Name to update.';
            } else {
                $stmt = $pdo->prepare('UPDATE customers SET customer_name = :name WHERE customer_id = :id');
                $stmt->execute([
                    ':id' => $submitted['customer_id'],
                    ':name' => $submitted['customer_name'],
                ]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Customer not found.';
                } else {
                    $success = 'Customer updated successfully.';
                }
            }
        } elseif ($action === 'view') {
            $criteria = [];
            $params = [];

            if ($submitted['customer_id'] !== '') {
                $criteria[] = 'customer_id = :id';
                $params[':id'] = $submitted['customer_id'];
            }
            if ($submitted['customer_name'] !== '') {
                $criteria[] = 'customer_name = :name';
                $params[':name'] = $submitted['customer_name'];
            }

            if (!$criteria) {
                $error = 'Provide at least one field to search.';
            } else {
                $where = implode(' OR ', $criteria);
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE {$where} LIMIT 1");
                $stmt->execute($params);
                $found = $stmt->fetch();

                if ($found) {
                    $submitted['customer_id'] = $found['customer_id'];
                    $submitted['customer_name'] = $found['customer_name'];
                    $success = 'Customer loaded. You can update or delete it.';
                } else {
                    $error = 'No customer found with those details.';
                }
            }
        } elseif ($action === 'delete') {
            if ($submitted['customer_id'] === '') {
                $error = 'Enter a Customer ID to delete.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM customers WHERE customer_id = :id');
                $stmt->execute([':id' => $submitted['customer_id']]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Customer not found or already deleted.';
                } else {
                    $success = 'Customer deleted successfully.';
                    $submitted = ['customer_id' => '', 'customer_name' => ''];
                }
            }
        } elseif ($action === 'bulk_delete') {
            if (!$selectedIds) {
                $error = 'Select at least one customer to delete.';
            } else {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id IN ({$placeholders})");
                $stmt->execute($selectedIds);
                $deleted = $stmt->rowCount();
                $success = $deleted . ' customer(s) removed.';
            }
        }
    } catch (Throwable $e) {
        $error = format_db_error($e, 'customers table');
    }
}

$customers = fetch_table('customers', 'customer_id');
$customerIdOptions = array_column($customers, 'customer_id');
$customerNameOptions = array_column($customers, 'customer_name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customers | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <script src="./assets/app.js" defer></script>
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <div class="title">Customers</div>
    <div class="links">
      <div class="user-chip">
        <span class="name"><?php echo safe($currentUser['username']); ?></span>
        <span class="role"><?php echo strtoupper(safe($currentUser['role'])); ?></span>
      </div>
      <a href="./home.php">Home</a>
      <a class="logout-icon" href="./logout.php" aria-label="Logout">⎋</a>
    </div>
  </header>

  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">Create, View, Update or Delete Customers</h3>
      <?php if ($error): ?>
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;">
          <?php echo safe($error); ?>
        </div>
      <?php elseif ($success): ?>
        <div class="alert" style="color: var(--primary); margin-bottom:12px;">
          <?php echo safe($success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="customers.php">
        <div class="form-row">
          <div>
            <label class="label" for="customer-id">Customer ID</label>
            <input id="customer-id" name="customer_id" type="text" list="customer-id-options" placeholder="CUST-001" value="<?php echo safe($submitted['customer_id']); ?>" />
          </div>
          <div>
            <label class="label" for="customer-name">Customer Name</label>
            <input id="customer-name" name="customer_name" type="text" list="customer-name-options" placeholder="Customer name" value="<?php echo safe($submitted['customer_name']); ?>" />
          </div>
        </div>
        <div class="actions">
          <button class="btn btn-save" type="submit" name="action" value="create">Create New Customer</button>
          <button class="btn btn-neutral" type="submit" name="action" value="view">View</button>
          <button class="btn btn-neutral" type="submit" name="action" value="update">Update</button>
          <button class="btn btn-delete" type="submit" name="action" value="delete" onclick="return confirm('Delete this customer?');">Delete</button>
        </div>
      </form>
    </div>

    <form method="POST" action="customers.php">
      <div class="table-actions">
        <div class="filters">
          <label class="label" for="multi-customer">Select Customers</label>
          <select id="multi-customer" name="selected_ids[]" multiple size="4">
            <?php foreach ($customers as $customer): ?>
              <option value="<?php echo safe($customer['customer_id']); ?>"><?php echo safe($customer['customer_id'] . ' | ' . $customer['customer_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="actions">
          <button class="btn btn-delete" type="submit" name="action" value="bulk_delete" onclick="return confirm('Delete selected customers?');">Delete Selected</button>
          <button class="btn btn-neutral" type="button" onclick="exportSelected('customers-table')">Download Excel</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="customers-table">
          <thead>
            <tr><th><input type="checkbox" onclick="toggleAll(this, 'customers-table')" aria-label="Select all customers" /></th><th>ID</th><th>Name</th></tr>
          </thead>
          <tbody>
            <?php if ($customers): ?>
              <?php foreach ($customers as $customer): ?>
                <tr>
                  <td><input type="checkbox" name="selected_ids[]" value="<?php echo safe($customer['customer_id']); ?>" /></td>
                  <td><?php echo safe($customer['customer_id']); ?></td>
                  <td><?php echo safe($customer['customer_name']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="3">No customers recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>

    <datalist id="customer-id-options">
      <?php foreach ($customerIdOptions as $option): ?>
        <option value="<?php echo safe($option); ?>"></option>
      <?php endforeach; ?>
    </datalist>
    <datalist id="customer-name-options">
      <?php foreach ($customerNameOptions as $option): ?>
        <option value="<?php echo safe($option); ?>"></option>
      <?php endforeach; ?>
    </datalist>
  </main>
</body>
</html>