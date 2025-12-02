<?php
require_once __DIR__ . '/helpers.php';
// Schema does not yet include suppliers; surface connection status for visibility.
try {
    get_pdo();
    $dbNotice = 'Connected to database. Add a suppliers table to manage records here.';
} catch (Throwable $e) {
    $dbNotice = 'Database connection unavailable: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Suppliers | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
      <div class="title">Suppliers</div>
    </div>
    <div class="links">
      <a href="./home.php">Home</a>
      <a href="./login.php">Logout</a>
    </div>
  </header>

  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">Supplier Placeholder</h3>
      <p style="margin:0;">This module is wired for database connectivity but requires a <code>suppliers</code> table to be added to the PostgreSQL schema.</p>
      <p style="margin:0; color:var(--secondary); font-weight:600;">Status: <?php echo safe($dbNotice); ?></p>
    </div>
  </main>
</body>
</html>