<?php
require_once __DIR__ . '/helpers.php';
// No dedicated table yet; keep connected to PDO for readiness.
try {
    get_pdo();
    $dbNotice = 'Connected to database. Create a budget_requests table to persist these forms.';
} catch (Throwable $e) {
    $dbNotice = 'Database connection unavailable: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Budget Update Requests | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page">
  <header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
      <div class="title">Budget Update Requests</div>
    </div>
    <div class="links">
      <a href="./home.php">Home</a>
      <a href="./login.php">Logout</a>
    </div>
  </header>
  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">Request Additional Budget</h3>
      <p style="margin:0; color:var(--secondary); font-weight:600;">Status: <?php echo safe($dbNotice); ?></p>
      <div class="form-row" style="margin-top:12px;">
        <div>
          <label class="label" for="request-id">Request ID</label>
          <input id="request-id" type="text" placeholder="REQ-001" />
        </div>
        <div>
          <label class="label" for="request-project">Project</label>
          <input id="request-project" type="text" placeholder="Project reference" />
        </div>
        <div>
          <label class="label" for="request-owner">Requested By</label>
          <input id="request-owner" type="text" placeholder="Owner" />
        </div>
      </div>
      <div class="form-row">
        <div>
          <label class="label" for="request-amount">Requested Amount</label>
          <input id="request-amount" type="number" step="0.01" placeholder="25000" />
        </div>
        <div>
          <label class="label" for="request-currency">Currency</label>
          <select id="request-currency"><option>EGP</option><option>USD</option><option>EUR</option></select>
        </div>
        <div>
          <label class="label" for="request-reason">Reason</label>
          <input id="request-reason" type="text" placeholder="Change of scope" />
        </div>
      </div>
      <div class="actions">
        <button class="btn btn-save" type="button">Submit Request</button>
        <button class="btn btn-neutral" type="button">View</button>
        <button class="btn btn-delete" type="button">Delete</button>
      </div>
    </div>
  </main>
</body>
</html>