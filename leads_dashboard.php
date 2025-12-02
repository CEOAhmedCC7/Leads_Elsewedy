<?php
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login_new.php');
    exit;
}

$user = $_SESSION['user'];
$message = '';
$error = '';
$editing = null;

try {
    $pdo = get_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $leadId = isset($_POST['id']) ? (int) $_POST['id'] : null;
            $payload = [
                'platform' => trim($_POST['platform'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'mobile_number' => trim($_POST['mobile_number'] ?? ''),
                'inquiries' => trim($_POST['inquiries'] ?? ''),
                'business_unit' => trim($_POST['business_unit'] ?? ''),
                'owner' => trim($_POST['owner'] ?? $user['name']),
                'status' => trim($_POST['status'] ?? ''),
                'lead_date' => $_POST['lead_date'] ?? null,
                'response_date' => $_POST['response_date'] ?? null,
                'response_time' => $_POST['response_time'] !== '' ? (int) $_POST['response_time'] : null,
                'note' => trim($_POST['note'] ?? ''),
            ];

            if ($payload['platform'] === '' || $payload['contact_email'] === '') {
                $error = 'Platform and contact email are required.';
            } else {
                if ($leadId) {
                    $stmt = $pdo->prepare('UPDATE leads_tracking SET platform = :platform, contact_email = :contact_email, mobile_number = :mobile_number, inquiries = :inquiries, business_unit = :business_unit, owner = :owner, status = :status, lead_date = :lead_date, response_date = :response_date, response_time = :response_time, note = :note WHERE id = :id');
                    $stmt->execute($payload + ['id' => $leadId]);
                    $message = 'Lead updated successfully.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO leads_tracking (platform, contact_email, mobile_number, inquiries, business_unit, owner, status, lead_date, response_date, response_time, note) VALUES (:platform, :contact_email, :mobile_number, :inquiries, :business_unit, :owner, :status, :lead_date, :response_date, :response_time, :note)');
                    $stmt->execute($payload);
                    $message = 'Lead created successfully.';
        }
            }
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $stmt = $pdo->prepare('DELETE FROM leads_tracking WHERE id = :id');
            $stmt->execute([':id' => (int) $_POST['id']]);
            $message = 'Lead deleted.';
        } elseif ($action === 'bulk_delete' && !empty($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
            $ids = array_map('intval', $_POST['selected_ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM leads_tracking WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $message = 'Selected leads deleted.';
        }
    }

    $leads = $pdo->query('SELECT * FROM leads_tracking ORDER BY lead_date DESC NULLS LAST, id DESC')->fetchAll();
} catch (Throwable $e) {
    $error = format_db_error($e, 'leads_tracking table');
    $leads = [];
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leads Desk | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./new-styles.css">
</head>
<body>
  <header class="navbar">
    <div class="brand">
      <div class="brand-mark">
        <img src="elsewedy_logo.jpg" alt="Elsewedy Machinery logo">
      </div>
      <div>Elsewedy Machinery</div>
    </div>
    <h1 class="page-title">Leads Desk</h1>
    <div class="user-pill">
      <div>
        <div><?php echo h($user['name']); ?></div>
        <strong><?php echo h(strtoupper((string) $user['role'])); ?></strong>
      </div>
      <form action="logout_new.php" method="POST" style="margin:0;">
        <button class="logout-btn" type="submit" title="Logout" aria-label="Logout">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
        </button>
      </form>
    </div>
  </header>

  <main class="main">
    <section class="cards-grid">
      <div class="card">
        <h3>Leads intake</h3>
        <p>Capture incoming requests across platforms with crisp visibility.</p>
      </div>
      <div class="card">
        <h3>Owner routing</h3>
        <p>Assign a responsible owner and track follow-ups and responses.</p>
      </div>
      <div class="card">
        <h3>Pipeline health</h3>
        <p>Monitor status, response times, and notes in one responsive view.</p>
      </div>
    </section>

    <?php if ($message): ?>
      <div class="alert" style="background:#f0fbf4; color:#1b8b4c; border-color:#cce9d8;">&check; <?php echo h($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert"><?php echo h($error); ?></div>
    <?php endif; ?>

<section class="panel">
      <h2><?php echo $editing ? 'Edit lead' : 'Create lead'; ?></h2>
      <div class="actions" style="justify-content: flex-end; margin-top:0;">
        <a class="btn btn-secondary" href="leads_dashboard.php">Create new lead</a>
      </div>
      <form method="POST" class="form-grid">
        <input type="hidden" name="action" value="save">
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?php echo h((string) $editing['id']); ?>">
        <?php endif; ?>
        <div>
          <label class="label" for="platform">Platform</label>
          <input class="input" type="text" id="platform" name="platform" value="<?php echo h($editing['platform'] ?? ''); ?>" placeholder="Website, Phone, Social" required>
        </div>
        <div>
          <label class="label" for="contact_email">Contact email</label>
          <input class="input" type="email" id="contact_email" name="contact_email" value="<?php echo h($editing['contact_email'] ?? ''); ?>" placeholder="lead@example.com" required>
        </div>
        <div>
          <label class="label" for="mobile_number">Mobile number</label>
          <input class="input" type="text" id="mobile_number" name="mobile_number" value="<?php echo h($editing['mobile_number'] ?? ''); ?>" placeholder="+20 ...">
        </div>
        <div>
          <label class="label" for="inquiries">Inquiry details</label>
          <textarea id="inquiries" name="inquiries" placeholder="Describe the inquiry" ><?php echo h($editing['inquiries'] ?? ''); ?></textarea>
        </div>
        <div>
          <label class="label" for="business_unit">Business unit</label>
          <input class="input" type="text" id="business_unit" name="business_unit" value="<?php echo h($editing['business_unit'] ?? ''); ?>" placeholder="Unit or division">
        </div>
        <div>
          <label class="label" for="owner">Owner</label>
          <input class="input" type="text" id="owner" name="owner" value="<?php echo h($editing['owner'] ?? $user['name']); ?>" placeholder="Assigned owner">
        </div>
        <div>
          <label class="label" for="status">Status</label>
          <select id="status" name="status">
            <?php
              $statuses = ['Open', 'In Progress', 'Closed'];
              $currentStatus = $editing['status'] ?? 'Open';
              foreach ($statuses as $status) {
                  $selected = $currentStatus === $status ? 'selected' : '';
                  echo '<option value="' . h($status) . '" ' . $selected . '>' . h($status) . '</option>';
              }
            ?>
          </select>
        </div>
        <div>
          <label class="label" for="lead_date">Lead date</label>
          <input class="input" type="date" id="lead_date" name="lead_date" value="<?php echo h($editing['lead_date'] ?? ''); ?>">
        </div>
        <div>
          <label class="label" for="response_date">Response date</label>
          <input class="input" type="date" id="response_date" name="response_date" value="<?php echo h($editing['response_date'] ?? ''); ?>">
        </div>
        <div>
          <label class="label" for="response_time">Response time (mins)</label>
          <input class="input" type="number" min="0" id="response_time" name="response_time" value="<?php echo h($editing['response_time'] ?? ''); ?>" placeholder="e.g. 45">
        </div>
        <div>
          <label class="label" for="note">Internal note</label>
          <textarea id="note" name="note" placeholder="Follow-up notes" ><?php echo h($editing['note'] ?? ''); ?></textarea>
        </div>
        <div class="actions">
          <?php if ($editing): ?>
            <a class="btn btn-secondary" href="leads_dashboard.php">Cancel</a>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update lead' : 'Create lead'; ?></button>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>Leads table</h2>
      <div class="table-actions">
        <div class="badge">Manage leads</div>
        <form method="POST" id="bulk-delete-form" style="margin:0; display:flex; gap:8px; align-items:center;">
          <input type="hidden" name="action" value="bulk_delete">
          <button type="submit" class="btn btn-primary" onclick="return confirm('Delete selected leads?');">Delete selected</button>
        </form>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Select</th>
              <th>ID</th>
              <th>Platform</th>
              <th>Contact</th>
              <th>Owner</th>
              <th>Status</th>
              <th>Lead date</th>
              <th>Response</th>
              <th>Note</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$leads): ?>
              <tr><td colspan="10" style="text-align:center; padding:18px; color:var(--muted);">No leads found.</td></tr>
            <?php else: ?>
              <?php foreach ($leads as $lead): ?>
                <tr>
                  <td>
                    <input type="checkbox" form="bulk-delete-form" name="selected_ids[]" value="<?php echo h((string) $lead['id']); ?>" aria-label="Select lead <?php echo h((string) $lead['id']); ?>">
                  </td>
                  <td class="badge">#<?php echo h((string) $lead['id']); ?></td>
                  <td><?php echo h($lead['platform']); ?></td>
                  <td>
                    <div><?php echo h($lead['contact_email']); ?></div>
                    <small style="color:var(--muted);"><?php echo h($lead['mobile_number']); ?></small>
                  </td>
                  <td><?php echo h($lead['owner']); ?></td>
                  <td>
                    <?php
                      $status = strtolower((string) $lead['status']);
                      $pillClass = 'status-pill';
                      if ($status === 'open') { $pillClass .= ' status-open'; }
                      elseif ($status === 'in progress') { $pillClass .= ' status-progress'; }
                      elseif ($status === 'closed') { $pillClass .= ' status-closed'; }
                    ?>
                    <span class="<?php echo $pillClass; ?>"><?php echo h($lead['status']); ?></span>
                  </td>
                  <td><?php echo h($lead['lead_date']); ?></td>
                  <td>
                    <div><?php echo h($lead['response_date']); ?></div>
                    <small style="color:var(--muted);"><?php echo h($lead['response_time']); ?> mins</small>
                  </td>
                  <td><?php echo h($lead['note']); ?></td>
                  <td style="white-space:nowrap;">
                    <a class="btn btn-secondary" href="?edit=<?php echo h((string) $lead['id']); ?>">Update lead</a>
                    <form method="POST" action="" style="display:inline;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo h((string) $lead['id']); ?>">
                      <button type="submit" class="btn btn-primary" onclick="return confirm('Delete this lead?');">Delete lead</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <footer class="footer">
    <div>Created by | PMO Team</div>
    <a href="https://elsewedymachinery.com" target="_blank" rel="noopener noreferrer">Elsewedy Machinery</a>
    <div>2025</div>
  </footer>
</body>
</html>