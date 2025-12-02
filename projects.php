<?php
require_once __DIR__ . '/helpers.php';

$currentUser = require_login();
$customers = fetch_table('customers', 'customer_id');
$customerOptions = to_options($customers, 'customer_id', 'customer_name');

$error = '';
$success = '';

$submitted = [
    'project_id' => trim($_POST['project_id'] ?? ''),
    'project_name' => trim($_POST['project_name'] ?? ''),
    'cost_center_no' => trim($_POST['cost_center_no'] ?? ''),
    'po_number' => trim($_POST['po_number'] ?? ''),
    'customer_id' => trim($_POST['customer_id'] ?? ''),
    'contract_date' => trim($_POST['contract_date'] ?? ''),
    'expected_end_date' => trim($_POST['expected_end_date'] ?? ''),
    'actual_end_date' => trim($_POST['actual_end_date'] ?? ''),
];
$selectedIds = array_filter(array_map('trim', (array) ($_POST['selected_ids'] ?? [])));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = get_pdo();

    try {
        if ($action === 'create') {
            if ($submitted['project_name'] === '' || $submitted['cost_center_no'] === '') {
                $error = 'Project name and cost center are required.';
            } else {
                $projectId = $submitted['project_id'] !== '' ? $submitted['project_id'] : 'prj_' . bin2hex(random_bytes(4));

                $exists = $pdo->prepare('SELECT 1 FROM projects WHERE project_id = :id');
                $exists->execute([':id' => $projectId]);

                if ($exists->fetchColumn()) {
                    $error = 'A project with this ID already exists.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO projects (project_id, project_name, cost_center_no, po_number, customer_id, contract_date, expected_end_date, actual_end_date) VALUES (:id, :name, :cost, :po, :customer, :contract, :expected, :actual)');
                    $stmt->execute([
                        ':id' => $projectId,
                        ':name' => $submitted['project_name'],
                        ':cost' => $submitted['cost_center_no'],
                        ':po' => $submitted['po_number'],
                        ':customer' => $submitted['customer_id'] ?: null,
                        ':contract' => $submitted['contract_date'] ?: null,
                        ':expected' => $submitted['expected_end_date'] ?: null,
                        ':actual' => $submitted['actual_end_date'] ?: null,
                    ]);

                    $success = 'Project saved successfully.';
                    $submitted = array_fill_keys(array_keys($submitted), '');
                }
            }
        } elseif ($action === 'update') {
            if ($submitted['project_id'] === '') {
                $error = 'Provide the Project ID to update.';
            } elseif ($submitted['project_name'] === '' || $submitted['cost_center_no'] === '') {
                $error = 'Project name and cost center are required.';
            } else {
                $stmt = $pdo->prepare('UPDATE projects SET project_name = :name, cost_center_no = :cost, po_number = :po, customer_id = :customer, contract_date = :contract, expected_end_date = :expected, actual_end_date = :actual WHERE project_id = :id');
                $stmt->execute([
                    ':id' => $submitted['project_id'],
                    ':name' => $submitted['project_name'],
                    ':cost' => $submitted['cost_center_no'],
                    ':po' => $submitted['po_number'],
                    ':customer' => $submitted['customer_id'] ?: null,
                    ':contract' => $submitted['contract_date'] ?: null,
                    ':expected' => $submitted['expected_end_date'] ?: null,
                    ':actual' => $submitted['actual_end_date'] ?: null,
                ]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Project not found.';
                } else {
                    $success = 'Project updated successfully.';
                }
            }
        } elseif ($action === 'view') {
            $criteria = [];
            $params = [];

            if ($submitted['project_id'] !== '') {
                $criteria[] = 'project_id = :id';
                $params[':id'] = $submitted['project_id'];
            }
            if ($submitted['project_name'] !== '') {
                $criteria[] = 'project_name = :name';
                $params[':name'] = $submitted['project_name'];
            }
            if ($submitted['cost_center_no'] !== '') {
                $criteria[] = 'cost_center_no = :cost';
                $params[':cost'] = $submitted['cost_center_no'];
            }

            if (!$criteria) {
                $error = 'Provide at least one field to search.';
            } else {
                $where = implode(' OR ', $criteria);
                $stmt = $pdo->prepare("SELECT * FROM projects WHERE {$where} LIMIT 1");
                $stmt->execute($params);
                $found = $stmt->fetch();

                if ($found) {
                    foreach ($submitted as $key => $_) {
                        $submitted[$key] = (string) ($found[$key] ?? '');
                    }
                    $success = 'Project loaded. You can update or delete it.';
                } else {
                    $error = 'No project found with those details.';
                }
            }
        } elseif ($action === 'delete') {
            if ($submitted['project_id'] === '') {
                $error = 'Enter a Project ID to delete.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM projects WHERE project_id = :id');
                $stmt->execute([':id' => $submitted['project_id']]);

                if ($stmt->rowCount() === 0) {
                    $error = 'Project not found or already deleted.';
                } else {
                    $success = 'Project deleted successfully.';
                    $submitted = array_fill_keys(array_keys($submitted), '');
                }
            }
        } elseif ($action === 'bulk_delete') {
            if (!$selectedIds) {
                $error = 'Select at least one project to delete.';
            } else {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM projects WHERE project_id IN ({$placeholders})");
                $stmt->execute($selectedIds);
                $deleted = $stmt->rowCount();
                $success = $deleted . ' project(s) removed.';
            }
        }
    } catch (Throwable $e) {
        $error = format_db_error($e, 'projects table');
    }
}

$projects = fetch_table('projects', 'project_id');
$projectIdOptions = array_column($projects, 'project_id');
$projectNameOptions = array_column($projects, 'project_name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Projects | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <script src="./assets/app.js" defer></script>
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <div class="title">Projects</div>
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
      <h3 style="margin-top:0; color:var(--secondary);">Create, View, Update or Delete Projects</h3>
      <?php if ($error): ?>
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;">
          <?php echo safe($error); ?>
        </div>
      <?php elseif ($success): ?>
        <div class="alert" style="color: var(--primary); margin-bottom:12px;">
          <?php echo safe($success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="projects.php">
        <div class="form-row">
          <div>
            <label class="label" for="project-id">Project ID</label>
            <input id="project-id" name="project_id" type="text" list="project-id-options" placeholder="PRJ-001" value="<?php echo safe($submitted['project_id']); ?>" />
          </div>
          <div>
            <label class="label" for="project-name">Project Name</label>
            <input id="project-name" name="project_name" type="text" list="project-name-options" placeholder="Wind Farm Expansion" value="<?php echo safe($submitted['project_name']); ?>" />
          </div>
          <div>
            <label class="label" for="cost-center">Cost Center No</label>
            <input id="cost-center" name="cost_center_no" type="text" placeholder="CC-1001" value="<?php echo safe($submitted['cost_center_no']); ?>" />
          </div>
          <div>
            <label class="label" for="po-number">PO Number</label>
            <input id="po-number" name="po_number" type="text" placeholder="PO-2025-01" value="<?php echo safe($submitted['po_number']); ?>" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="project-customer">Customer</label>
            <select id="project-customer" name="customer_id">
              <option value="">-- Select Customer --</option>
              <?php foreach ($customerOptions as $option): ?>
                <option value="<?php echo safe($option['value']); ?>" <?php echo $submitted['customer_id'] === $option['value'] ? 'selected' : ''; ?>><?php echo safe($option['value'] . ' | ' . $option['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="project-start">Contract Date</label>
            <input id="project-start" name="contract_date" type="date" value="<?php echo safe($submitted['contract_date']); ?>" />
          </div>
          <div>
            <label class="label" for="project-end">Expected End</label>
            <input id="project-end" name="expected_end_date" type="date" value="<?php echo safe($submitted['expected_end_date']); ?>" />
          </div>
          <div>
            <label class="label" for="project-actual-end">Actual End</label>
            <input id="project-actual-end" name="actual_end_date" type="date" value="<?php echo safe($submitted['actual_end_date']); ?>" />
          </div>
        </div>
        <div class="actions">
          <button class="btn btn-save" type="submit" name="action" value="create">Create New Project</button>
          <button class="btn btn-neutral" type="submit" name="action" value="view">View</button>
          <button class="btn btn-neutral" type="submit" name="action" value="update">Update</button>
          <button class="btn btn-delete" type="submit" name="action" value="delete" onclick="return confirm('Delete this project?');">Delete</button>
        </div>
      </form>
    </div>

    <form method="POST" action="projects.php">
      <div class="table-actions">
        <div class="filters">
          <label class="label" for="multi-project">Select Projects</label>
          <select id="multi-project" name="selected_ids[]" multiple size="4">
            <?php foreach ($projects as $project): ?>
              <option value="<?php echo safe($project['project_id']); ?>"><?php echo safe($project['project_id'] . ' | ' . $project['project_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="actions">
          <button class="btn btn-delete" type="submit" name="action" value="bulk_delete" onclick="return confirm('Delete selected projects?');">Delete Selected</button>
          <button class="btn btn-neutral" type="button" onclick="exportSelected('projects-table')">Download Excel</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="projects-table">
          <thead>
            <tr><th><input type="checkbox" onclick="toggleAll(this, 'projects-table')" aria-label="Select all projects" /></th><th>ID</th><th>Name</th><th>Cost Center</th><th>Customer</th><th>PO Number</th><th>Contract</th><th>Expected End</th><th>Actual End</th></tr>
          </thead>
          <tbody>
            <?php if ($projects): ?>
              <?php foreach ($projects as $project): ?>
                <tr>
                  <td><input type="checkbox" name="selected_ids[]" value="<?php echo safe($project['project_id']); ?>" /></td>
                  <td><?php echo safe($project['project_id']); ?></td>
                  <td><?php echo safe($project['project_name']); ?></td>
                  <td><?php echo safe($project['cost_center_no']); ?></td>
                  <td><?php echo safe($project['customer_id']); ?></td>
                  <td><?php echo safe($project['po_number']); ?></td>
                  <td><?php echo safe($project['contract_date']); ?></td>
                  <td><?php echo safe($project['expected_end_date']); ?></td>
                  <td><?php echo safe($project['actual_end_date']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="9">No projects recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>

    <datalist id="project-id-options">
      <?php foreach ($projectIdOptions as $option): ?>
        <option value="<?php echo safe($option); ?>"></option>
      <?php endforeach; ?>
    </datalist>
    <datalist id="project-name-options">
      <?php foreach ($projectNameOptions as $option): ?>
        <option value="<?php echo safe($option); ?>"></option>
      <?php endforeach; ?>
    </datalist>
  </main>
</body>
</html>