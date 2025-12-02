<?php
require_once __DIR__ . '/helpers.php';
$user = require_login();

$roles = ['admin', 'project_manager', 'finance', 'logistics', 'procurement', 'customer_support', 'viewer'];
$modules = [
    'User Management',
    'Projects',
    'Budgets',
    'Payments',
    'Invoices',
    'Customers',
    'Suppliers',
    'Budget Update Requests',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Role Access | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <script src="./assets/app.js" defer></script>
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <div class="title">Role Access</div>
    <div class="links">
      <div class="user-chip">
        <span class="name"><?php echo safe($user['username']); ?></span>
        <span class="role"><?php echo strtoupper(safe($user['role'])); ?></span>
      </div>
      <a href="./home.php">Home</a>
      <a class="logout-icon" href="./logout.php" aria-label="Logout">⎋</a>
    </div>
  </header>
  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">Module access by role</h3>
      <p style="margin-top:0; color:#444;">Use the checklist below to quickly visualize which modules each role can access. Adjustments can be saved manually for now.</p>
      <div class="table-wrapper">
        <table id="access-table">
          <thead>
            <tr>
              <th>Module</th>
              <?php foreach ($roles as $role): ?>
                <th><?php echo safe(ucwords(str_replace('_', ' ', $role))); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($modules as $module): ?>
              <tr>
                <td><?php echo safe($module); ?></td>
                <?php foreach ($roles as $role): ?>
                  <td style="text-align:center;"><input type="checkbox" checked /></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>