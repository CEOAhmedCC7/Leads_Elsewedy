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
    'budget_id' => trim($_POST['budget_id'] ?? ''),
    'scope' => $_POST['budget_scope'] ?? 'project',
    'project_id' => trim($_POST['project_id'] ?? ''),
    'sub_batch_detail_id' => trim($_POST['sub_batch_detail_id'] ?? ''),
    'cost_type' => trim($_POST['cost_type'] ?? ''),
    'revenue_amount' => trim($_POST['revenue_amount'] ?? ''),
    'revenue_currency' => trim($_POST['revenue_currency'] ?? 'EGP'),
    'revenue_exchange_rate' => trim($_POST['revenue_exchange_rate'] ?? ''),
    'freight_amount' => trim($_POST['freight_amount'] ?? ''),
    'freight_currency' => trim($_POST['freight_currency'] ?? 'EGP'),
    'freight_exchange_rate' => trim($_POST['freight_exchange_rate'] ?? ''),
    'supplier_cost_amount' => trim($_POST['supplier_cost_amount'] ?? ''),
    'supplier_cost_currency' => trim($_POST['supplier_cost_currency'] ?? 'EGP'),
    'supplier_cost_exchange_rate' => trim($_POST['supplier_cost_exchange_rate'] ?? ''),
];
$selectedIds = array_filter(array_map('trim', (array) ($_POST['selected_ids'] ?? [])));
$costTypes = ['Materials', 'Freight', 'Customs', 'Services', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = get_pdo();

    $linkProject = $submitted['scope'] === 'project' ? $submitted['project_id'] : '';
    $linkSubBatch = $submitted['scope'] === 'sub-batch' ? $submitted['sub_batch_detail_id'] : '';

    try {
        if ($action === 'create') {
            if ($submitted['cost_type'] === '') {
                $error = 'Cost type is required.';
            } elseif ($linkProject === '' && $linkSubBatch === '') {
                $error = 'Select either a project or sub-batch.';
            } else {
                $budgetId = $submitted['budget_id'] !== '' ? $submitted['budget_id'] : 'bud_' . bin2hex(random_bytes(4));

                $exists = $pdo->prepare('SELECT 1 FROM budgets WHERE budget_id = :id');
                $exists->execute([':id' => $budgetId]);

                if ($exists->fetchColumn()) {
                    $error = 'A budget with this ID already exists.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO budgets (budget_id, project_id, sub_batch_detail_id, cost_type, revenue_amount, revenue_currency, revenue_exchange_rate, freight_amount, freight_currency, freight_exchange_rate, supplier_cost_amount, supplier_cost_currency, supplier_cost_exchange_rate) VALUES (:id, :project, :sub_batch, :cost_type, :rev_amount, :rev_currency, :rev_rate, :freight_amount, :freight_currency, :freight_rate, :supplier_amount, :supplier_currency, :supplier_rate)');
                    $stmt->execute([
                        ':id' => $budgetId,
                        ':project' => $linkProject !== '' ? $linkProject : null,
                        ':sub_batch' => $linkSubBatch !== '' ? $linkSubBatch : null,
                        ':cost_type' => $submitted['cost_type'],
                        ':rev_amount' => $submitted['revenue_amount'] !== '' ? $submitted['revenue_amount'] : null,
                        ':rev_currency' => $submitted['revenue_currency'] ?: null,
                        ':rev_rate' => $submitted['revenue_exchange_rate'] !== '' ? $submitted['revenue_exchange_rate'] : null,
                        ':freight_amount' => $submitted['freight_amount'] !== '' ? $submitted['freight_amount'] : null,
                        ':freight_currency' => $submitted['freight_currency'] ?: null,
                        ':freight_rate' => $submitted['freight_exchange_rate'] !== '' ? $submitted['freight_exchange_rate'] : null,
                        ':supplier_amount' => $submitted['supplier_cost_amount'] !== '' ? $submitted['supplier_cost_amount'] : null,
                        ':supplier_currency' => $submitted['supplier_cost_currency'] ?: null,
                        ':supplier_rate' => $submitted['supplier_cost_exchange_rate'] !== '' ? $submitted['supplier_cost_exchange_rate'] : null,
                    ]);

                    $success = 'Budget saved successfully.';
                    $submitted = array_merge($submitted, [
                        'budget_id' => '',
                        'project_id' => '',
                        'sub_batch_detail_id' => '',
                        'cost_type' => '',
                        'revenue_amount' => '',
                        'revenue_exchange_rate' => '',
                        'freight_amount' => '',
                        'freight_exchange_rate' => '',
                        'supplier_cost_amount' => '',
                        'supplier_cost_exchange_rate' => '',
                    ]);
                }
            }
        } elseif ($action === 'update') {
            if ($submitted['budget_id'] === '') {
                $error = 'Enter the Budget ID to update.';
            } elseif ($submitted['cost_type'] === '') {
                $error = 'Cost type is required.';
            } elseif ($linkProject === '' && $linkSubBatch === '') {
                $error = 'Select either a project or sub-batch.';
            } else {
                $stmt = $pdo->prepare('UPDATE budgets SET project_id = :project, sub_batch_detail_id = :sub_batch, cost_type = :cost_type, revenue_amount = :rev_amount, revenue_currency = :rev_currency, revenue_exchange_rate = :rev_rate, freight_amount = :freight_amount, freight_currency = :freight_currency, freight_exchange_rate = :freight_rate, supplier_cost_amount = :supplier_amount, supplier_cost_currency = :supplier_currency, supplier_cost_exchange_rate = :supplier_rate WHERE budget_id = :id');
                $stmt->execute([
                    ':id' => $submitted['budget_id'],
                    ':project' => $linkProject !== '' ? $linkProject : null,
                    ':sub_batch' => $linkSubBatch !== '' ? $linkSubBatch : null,
                    ':cost_type' => $submitted['cost_type'],
                    ':rev_amount' => $submitted['revenue_amount'] !== '' ? $submitted['revenue_amount'] : null,
                    ':rev_currency' => $submitted['revenue_currency'] ?: null,
                    ':rev_rate' => $submitted['revenue_exchange_rate'] !== '' ? $submitted['revenue_exchange_rate'] : null,
                    ':freight_amount' => $submitted['freight_amount'] !== '' ? $submitted['freight_amount'] : null,
                    ':freight_currency' => $submitted['freight_currency'] ?: null,
                    ':freight_rate' => $submitted['freight_exchange_rate'] !== '' ? $submitted['freight_exchange_rate'] : null,
                    ':supplier_amount' => $submitted['supplier_cost_amount'] !== '' ? $submitted['supplier_cost_amount'] : null,
                    ':supplier_currency' => $submitted['supplier_cost_currency'] ?: null,
                    ':supplier_rate' => $submitted['supplier_cost_exchange_rate'] !== '' ? $submitted['supplier_cost_exchange_rate'] : null,
                ]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Budget not found.';
                } else {
                    $success = 'Budget updated successfully.';
                }
            }
        } elseif ($action === 'view') {
            $criteria = [];
            $params = [];

            if ($submitted['budget_id'] !== '') {
                $criteria[] = 'budget_id = :id';
                $params[':id'] = $submitted['budget_id'];
            }
            if ($submitted['project_id'] !== '') {
                $criteria[] = 'project_id = :project';
                $params[':project'] = $submitted['project_id'];
            }
            if ($submitted['sub_batch_detail_id'] !== '') {
                $criteria[] = 'sub_batch_detail_id = :sub_batch';
                $params[':sub_batch'] = $submitted['sub_batch_detail_id'];
            }
            if ($submitted['cost_type'] !== '') {
                $criteria[] = 'cost_type = :cost_type';
                $params[':cost_type'] = $submitted['cost_type'];
            }

            if (!$criteria) {
                $error = 'Provide at least one field to search.';
            } else {
                $where = implode(' OR ', $criteria);
                $stmt = $pdo->prepare("SELECT * FROM budgets WHERE {$where} LIMIT 1");
                $stmt->execute($params);
                $found = $stmt->fetch();

                if ($found) {
                    foreach ($submitted as $key => $_) {
                        $submitted[$key] = (string) ($found[$key] ?? ($key === 'scope' ? '' : ''));
                    }
                    $submitted['scope'] = $found['project_id'] ? 'project' : 'sub-batch';
                    $success = 'Budget loaded. You can update or delete it.';
                } else {
                    $error = 'No budget found with those details.';
                }
            }
        } elseif ($action === 'delete') {
            if ($submitted['budget_id'] === '') {
                $error = 'Enter a Budget ID to delete.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id');
                $stmt->execute([':id' => $submitted['budget_id']]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Budget not found or already deleted.';
                } else {
                    $success = 'Budget deleted successfully.';
                    $submitted = array_merge($submitted, [
                        'budget_id' => '',
                        'project_id' => '',
                        'sub_batch_detail_id' => '',
                        'cost_type' => '',
                        'revenue_amount' => '',
                        'revenue_exchange_rate' => '',
                        'freight_amount' => '',
                        'freight_exchange_rate' => '',
                        'supplier_cost_amount' => '',
                        'supplier_cost_exchange_rate' => '',
                    ]);
                }
            }
        } elseif ($action === 'bulk_delete') {
            if (!$selectedIds) {
                $error = 'Select at least one budget to delete.';
            } else {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM budgets WHERE budget_id IN ({$placeholders})");
                $stmt->execute($selectedIds);
                $deleted = $stmt->rowCount();
                $success = $deleted . ' budget(s) removed.';
            }
        }
    } catch (Throwable $e) {
        $error = format_db_error($e, 'budgets table');
    }
}

$budgets = fetch_table('budgets', 'budget_id');
$budgetIdOptions = array_column($budgets, 'budget_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Budgets | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <script src="./assets/app.js" defer></script>
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <div class="title">Budgets</div>
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
      <h3 style="margin-top:0; color:var(--secondary);">Create, View, Update or Delete Budgets</h3>
      <?php if ($error): ?>
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;">
          <?php echo safe($error); ?>
        </div>
      <?php elseif ($success): ?>
        <div class="alert" style="color: var(--primary); margin-bottom:12px;">
          <?php echo safe($success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="budgets.php">
        <div class="form-row">
          <div>
            <label class="label" for="budget-id">Budget ID</label>
            <input id="budget-id" name="budget_id" type="text" list="budget-id-options" placeholder="BUD-001" value="<?php echo safe($submitted['budget_id']); ?>" />
          </div>
          <div>
            <label class="label">Scope</label>
            <div style="display:flex; gap:10px; align-items:center;">
              <label><input type="radio" name="budget_scope" value="project" <?php echo $submitted['scope'] === 'project' ? 'checked' : ''; ?> /> Project</label>
              <label><input type="radio" name="budget_scope" value="sub-batch" <?php echo $submitted['scope'] === 'sub-batch' ? 'checked' : ''; ?> /> Sub-Batch Detail</label>
            </div>
          </div>
          <div>
            <label class="label" for="budget-project">Project</label>
            <select id="budget-project" name="project_id">
              <option value="">-- Select Project --</option>
              <?php foreach ($projectOptions as $option): ?>
                <option value="<?php echo safe($option['value']); ?>" <?php echo $submitted['project_id'] === $option['value'] ? 'selected' : ''; ?>><?php echo safe($option['value'] . ' | ' . $option['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="budget-sub-batch">Sub-Batch Detail</label>
            <select id="budget-sub-batch" name="sub_batch_detail_id">
              <option value="">-- Select Sub-Batch --</option>
              <?php foreach ($subBatchOptions as $option): ?>
                <option value="<?php echo safe($option['value']); ?>" <?php echo $submitted['sub_batch_detail_id'] === $option['value'] ? 'selected' : ''; ?>><?php echo safe($option['value'] . ' | ' . $option['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="budget-type">Cost Type</label>
            <select id="budget-type" name="cost_type">
              <option value="">-- Select Cost Type --</option>
              <?php foreach ($costTypes as $type): ?>
                <option value="<?php echo safe($type); ?>" <?php echo $submitted['cost_type'] === $type ? 'selected' : ''; ?>><?php echo safe($type); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="revenue-amount">Revenue Amount</label>
            <input id="revenue-amount" name="revenue_amount" type="number" step="0.01" placeholder="100000" value="<?php echo safe($submitted['revenue_amount']); ?>" />
          </div>
          <div>
            <label class="label" for="revenue-currency">Revenue Currency</label>
            <select id="revenue-currency" name="revenue_currency">
              <?php foreach (['EGP','USD','EUR'] as $currency): ?>
                <option value="<?php echo $currency; ?>" <?php echo $submitted['revenue_currency'] === $currency ? 'selected' : ''; ?>><?php echo $currency; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="revenue-rate">Revenue Exchange Rate</label>
            <input id="revenue-rate" name="revenue_exchange_rate" type="number" step="0.0001" placeholder="48.50" value="<?php echo safe($submitted['revenue_exchange_rate']); ?>" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="freight-amount">Freight Amount</label>
            <input id="freight-amount" name="freight_amount" type="number" step="0.01" placeholder="5000" value="<?php echo safe($submitted['freight_amount']); ?>" />
          </div>
          <div>
            <label class="label" for="freight-currency">Freight Currency</label>
            <select id="freight-currency" name="freight_currency">
              <?php foreach (['EGP','USD','EUR'] as $currency): ?>
                <option value="<?php echo $currency; ?>" <?php echo $submitted['freight_currency'] === $currency ? 'selected' : ''; ?>><?php echo $currency; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="freight-rate">Freight Exchange Rate</label>
            <input id="freight-rate" name="freight_exchange_rate" type="number" step="0.0001" placeholder="48.50" value="<?php echo safe($submitted['freight_exchange_rate']); ?>" />
          </div>
          <div>
            <label class="label" for="supplier-cost">Supplier Cost Amount</label>
            <input id="supplier-cost" name="supplier_cost_amount" type="number" step="0.01" placeholder="7500" value="<?php echo safe($submitted['supplier_cost_amount']); ?>" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="supplier-currency">Supplier Currency</label>
            <select id="supplier-currency" name="supplier_cost_currency">
              <?php foreach (['EGP','USD','EUR'] as $currency): ?>
                <option value="<?php echo $currency; ?>" <?php echo $submitted['supplier_cost_currency'] === $currency ? 'selected' : ''; ?>><?php echo $currency; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="supplier-rate">Supplier Exchange Rate</label>
            <input id="supplier-rate" name="supplier_cost_exchange_rate" type="number" step="0.0001" placeholder="48.50" value="<?php echo safe($submitted['supplier_cost_exchange_rate']); ?>" />
          </div>
        </div>
        <div class="actions">
          <button class="btn btn-save" type="submit" name="action" value="create">Create New Budget</button>
          <button class="btn btn-neutral" type="submit" name="action" value="view">View</button>
          <button class="btn btn-neutral" type="submit" name="action" value="update">Update</button>
          <button class="btn btn-delete" type="submit" name="action" value="delete" onclick="return confirm('Delete this budget?');">Delete</button>
        </div>
      </form>
    </div>

    <form method="POST" action="budgets.php">
      <div class="table-actions">
        <div class="filters">
          <label class="label" for="multi-budget">Select Budgets</label>
          <select id="multi-budget" name="selected_ids[]" multiple size="4">
            <?php foreach ($budgets as $budget): ?>
              <option value="<?php echo safe($budget['budget_id']); ?>"><?php echo safe($budget['budget_id'] . ' | ' . ($budget['cost_type'] ?? '')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="actions">
          <button class="btn btn-delete" type="submit" name="action" value="bulk_delete" onclick="return confirm('Delete selected budgets?');">Delete Selected</button>
          <button class="btn btn-neutral" type="button" onclick="exportSelected('budgets-table')">Download Excel</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="budgets-table">
          <thead>
            <tr><th><input type="checkbox" onclick="toggleAll(this, 'budgets-table')" aria-label="Select all budgets" /></th><th>ID</th><th>Scope</th><th>Link</th><th>Cost Type</th><th>Revenue</th><th>Freight</th><th>Supplier Cost</th></tr>
          </thead>
          <tbody>
            <?php if ($budgets): ?>
              <?php foreach ($budgets as $budget): ?>
                <?php
                  $scope = $budget['project_id'] ? 'Project' : 'Sub-Batch';
                  $link = $budget['project_id'] ?: $budget['sub_batch_detail_id'];
                ?>
                <tr>
                  <td><input type="checkbox" name="selected_ids[]" value="<?php echo safe($budget['budget_id']); ?>" /></td>
                  <td><?php echo safe($budget['budget_id']); ?></td>
                  <td><?php echo safe($scope); ?></td>
                  <td><?php echo safe($link); ?></td>
                  <td><?php echo safe($budget['cost_type']); ?></td>
                  <td><?php echo safe(($budget['revenue_currency'] ?? '') . ' ' . ($budget['revenue_amount'] ?? '')); ?></td>
                  <td><?php echo safe(($budget['freight_currency'] ?? '') . ' ' . ($budget['freight_amount'] ?? '')); ?></td>
                  <td><?php echo safe(($budget['supplier_cost_currency'] ?? '') . ' ' . ($budget['supplier_cost_amount'] ?? '')); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8">No budgets recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>

    <datalist id="budget-id-options">
      <?php foreach ($budgetIdOptions as $option): ?>
        <option value="<?php echo safe($option); ?>"></option>
      <?php endforeach; ?>
    </datalist>
  </main>
</body>
</html>