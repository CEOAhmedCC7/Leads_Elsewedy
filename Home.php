<?php
require_once __DIR__ . '/helpers.php';
$user = require_login();

$role = $user['role'] ?? '';

$modules = [
    [
        'roles' => ['admin'],
        'href' => './users.php',
        'title' => 'User Management',
        'description' => 'Admin-only user provisioning',
    ],
    [
        'roles' => ['admin', 'finance', 'project_manager'],
        'href' => './projects.php',
        'title' => 'Projects',
        'description' => 'Create and track projects',
    ],
    [
        'roles' => ['admin', 'finance', 'project_manager', 'logistics', 'procurement'],
        'href' => './budgets.php',
        'title' => 'Budgets',
        'description' => 'Project / sub-batch budgets',
    ],
    [
        'roles' => ['admin', 'finance'],
        'href' => './payments.php',
        'title' => 'Payments',
        'description' => 'Outgoing payment tracking',
    ],
    [
        'roles' => ['admin', 'finance', 'project_manager', 'customer_support'],
        'href' => './invoices.php',
        'title' => 'Invoices',
        'description' => 'Project-linked invoices',
    ],
    [
        'roles' => ['admin', 'project_manager', 'customer_support'],
        'href' => './customers.php',
        'title' => 'Customers',
        'description' => 'Customer records',
    ],
    [
        'roles' => ['admin', 'procurement'],
        'href' => './suppliers.php',
        'title' => 'Suppliers',
        'description' => 'Procurement partners',
    ],
    [
        'roles' => ['admin', 'finance', 'project_manager'],
        'href' => './budget-requests.php',
        'title' => 'Budget Update Requests',
        'description' => 'Track scope change approvals',
    ],
    [
        'roles' => ['admin'],
        'href' => './role-access.php',
        'title' => 'Role Access',
        'description' => 'Configure module access per role',
    ],
];


$visibleModules = array_filter($modules, function ($module) use ($role) {
    return in_array($role, $module['roles'], true);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page">
 <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <div class="title">Home</div>
    <div class="links">
      <div class="user-chip">
        <span class="name"><?php echo safe($user['username']); ?></span>
        <span class="role"><?php echo strtoupper(safe($user['role'])); ?></span>
      </div>
      <a class="logout-icon" href="./logout.php" aria-label="Logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M15 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M10 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M16 8l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </a>
    </div>
  </header>
  <main class="home-grid">
    <section id="modules">
      <?php foreach ($visibleModules as $module): ?>
        <a class="card" href="<?php echo safe($module['href']); ?>">
          <div class="card-title"><?php echo safe($module['title']); ?></div>
          <p><?php echo safe($module['description']); ?></p>
        </a>
      <?php endforeach; ?>
    </section>
  </main>
  </body>
</html>