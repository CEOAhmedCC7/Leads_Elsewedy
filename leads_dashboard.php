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
$messageType = '';
$error = '';
$editing = null;

$platformOptions = [
    'Website',
    'WhatsApp',
    'Facebook',
    'Instagram',
    'LinkedIn',
    'Call',
    'E-mail',
    'Tiktok',
    'Others',
];

$businessUnits = [
    'SANY',
    'Hitachi',
    'Elevators',
    'Partnership Opp',
    'Pumps',
    'Logistics',
];
  $options = [];
  $statusOptions = ['Qualified', 'Quotation', 'Negotiations', 'Award', 'Disqualified', 'Onhold'];

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
                'lead_date' => trim($_POST['lead_date'] ?? ''),
                'response_date' => trim($_POST['response_date'] ?? ''),
                'response_time' => null,
                'note' => trim($_POST['note'] ?? ''),
            ];

            $missingFields = [];
            foreach (['platform', 'business_unit', 'owner', 'status', 'lead_date'] as $field) {
                if ($payload[$field] === '') {
                    $missingFields[] = $field;
                }
            }

            foreach (['contact_email', 'mobile_number', 'inquiries', 'note'] as $optionalField) {
                if ($payload[$optionalField] === '') {
                    $payload[$optionalField] = null;
                }
            }


            $payload['response_date'] = $payload['response_date'] !== '' ? $payload['response_date'] : null;

            if ($payload['lead_date'] && $payload['response_date']) {
                try {
                    $leadDate = new DateTime($payload['lead_date']);
                    $responseDate = new DateTime($payload['response_date']);
                    $payload['response_time'] = max(0, (int) $leadDate->diff($responseDate)->format('%a'));
                } catch (Throwable $e) {
                    $error = 'Invalid date provided for lead or response date.';
                }
            }

            if ($missingFields) {
                $error = 'Please fill in all required fields.';
            }

            if (!$error) {
                if ($leadId) {
                    $stmt = $pdo->prepare('UPDATE leads_tracking SET platform = :platform, contact_email = :contact_email, mobile_number = :mobile_number, inquiries = :inquiries, business_unit = :business_unit, owner = :owner, status = :status, lead_date = :lead_date, response_date = :response_date, response_time = :response_time, note = :note WHERE id = :id');
                    $stmt->execute($payload + ['id' => $leadId]);
                    $message = 'Lead updated successfully.';
                    $messageType = 'update';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO leads_tracking (platform, contact_email, mobile_number, inquiries, business_unit, owner, status, lead_date, response_date, response_time, note) VALUES (:platform, :contact_email, :mobile_number, :inquiries, :business_unit, :owner, :status, :lead_date, :response_date, :response_time, :note)');
                    $stmt->execute($payload);
                    $message = 'Lead created successfully.';
                    $messageType = 'create';
                }
            }
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $stmt = $pdo->prepare('DELETE FROM leads_tracking WHERE id = :id');
            $stmt->execute([':id' => (int) $_POST['id']]);
            $message = 'Lead deleted successfully.';
            $messageType = 'delete';
        } elseif ($action === 'bulk_delete' && !empty($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
            $ids = array_map('intval', $_POST['selected_ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM leads_tracking WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $message = 'Selected leads deleted successfully.';
            $messageType = 'delete';
        }
    }

 $leads = $pdo->query('SELECT * FROM leads_tracking ORDER BY lead_date DESC NULLS LAST, id DESC')->fetchAll();

      if (isset($_GET['edit'])) {
          $stmt = $pdo->prepare('SELECT * FROM leads_tracking WHERE id = :id');
          $stmt->execute([':id' => (int) $_GET['edit']]);
          $editing = $stmt->fetch();

          if (!$editing) {
              $error = 'Lead not found for editing.';
          }
      }

        $optionFields = ['platform', 'contact_email', 'mobile_number', 'inquiries', 'business_unit', 'owner', 'status', 'note'];
      $options = [];

      foreach ($optionFields as $field) {
          $stmt = $pdo->query("SELECT DISTINCT $field FROM leads_tracking WHERE $field IS NOT NULL AND $field <> '' ORDER BY $field");
          $options[$field] = $stmt->fetchAll(PDO::FETCH_COLUMN);
      }

   $statusOptions = array_unique(array_merge(['Qualified', 'Quotation', 'Negotiation', 'Award', 'Disqualified', 'Onhold'], $options['status'] ?? []));
    } catch (Throwable $e) { 
      $error = format_db_error($e, 'leads_tracking table'); 
      $leads = []; 
    }

    $leadsForJs = json_encode($leads, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

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


 <section class="panel">
      <h2><?php echo $editing ? 'Edit lead' : 'Create lead'; ?></h2>
      <form method="POST" id="lead-form">
        <input type="hidden" name="action" value="save">
        <div class="form-grid">
          <div>
            <label class="label" for="lead_id">Lead ID</label>
            <?php if ($editing): ?>
              <input
                class="input"
                type="text"
                id="lead_id"
                value="<?php echo h((string) ($editing['id'] ?? '')); ?>"
                disabled
                aria-disabled="true"
              >
              <input type="hidden" name="id" value="<?php echo h((string) ($editing['id'] ?? '')); ?>">
            <?php else: ?>
              <input
                class="input"
                type="text"
                id="lead_id"
                value="Assigned automatically"
                disabled
                aria-disabled="true"
              >
              <small class="field-note"><em>ID is generated automatically</em></small>
            <?php endif; ?>
          </div>
          <div>
            <label class="label" for="platform">Platform</label>
            <select id="platform" name="platform" required>
            <option value="" disabled <?php echo empty($editing['platform']) ? 'selected' : ''; ?>>Select a platform</option>
            <?php
              $currentPlatform = $editing['platform'] ?? '';
              $platformChoices = array_unique(array_merge($platformOptions, $options['platform'] ?? []));
              foreach ($platformChoices as $platform) {
                  $selected = $currentPlatform === $platform ? 'selected' : '';
                  echo '<option value="' . h($platform) . '" ' . $selected . '>' . h($platform) . '</option>';
              }
            ?>
            </select>
          </div>
          <div>
            <label class="label" for="contact_email">Contact email</label>
            <input class="input" list="contact-email-options" type="email" id="contact_email" name="contact_email" value="<?php echo h($editing['contact_email'] ?? ''); ?>" placeholder="lead@example.com" >
            <datalist id="contact-email-options">
              <?php foreach ($options['contact_email'] ?? [] as $contactEmail): ?>
                <option value="<?php echo h($contactEmail); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label class="label" for="mobile_number">Mobile number</label>
            <input class="input" list="mobile-number-options" type="text" id="mobile_number" name="mobile_number" value="<?php echo h($editing['mobile_number'] ?? ''); ?>" placeholder="+20 ..." >
            <datalist id="mobile-number-options">
              <?php foreach ($options['mobile_number'] ?? [] as $mobileNumber): ?>
                <option value="<?php echo h($mobileNumber); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label class="label" for="inquiries">Inquiry details</label>
            <input class="input" list="inquiry-options" type="text" id="inquiries" name="inquiries" value="<?php echo h($editing['inquiries'] ?? ''); ?>" placeholder="Describe the inquiry" >
            <datalist id="inquiry-options">
              <?php foreach ($options['inquiries'] ?? [] as $inquiryValue): ?>
                <option value="<?php echo h($inquiryValue); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label class="label" for="business_unit">Business unit</label>
<select id="business_unit" name="business_unit" required>
              <option value="" disabled <?php echo empty($editing['business_unit']) ? 'selected' : ''; ?>>Select a business unit</option>
              <?php
                $currentBusinessUnit = $editing['business_unit'] ?? '';
                $businessUnitChoices = array_unique(array_merge($businessUnits, $options['business_unit'] ?? []));
                foreach ($businessUnitChoices as $businessUnit) {
                    $selected = $currentBusinessUnit === $businessUnit ? 'selected' : '';
                    echo '<option value="' . h($businessUnit) . '" ' . $selected . '>' . h($businessUnit) . '</option>';
                }
              ?>
            </select>
          </div>
          <div>
            <label class="label" for="owner">Owner</label>
            <input class="input" list="owner-options" type="text" id="owner" name="owner" value="<?php echo h($editing['owner'] ?? $user['name']); ?>" placeholder="Assigned owner" required>
            <datalist id="owner-options">
              <?php foreach ($options['owner'] ?? [] as $owner): ?>
                <option value="<?php echo h($owner); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label class="label" for="status">Status</label>
            <select id="status" name="status" required>
              <?php
                $currentStatus = $editing['status'] ?? 'Qualified';
                foreach ($statusOptions as $status) {
                    $selected = $currentStatus === $status ? 'selected' : '';
                    echo '<option value="' . h($status) . '" ' . $selected . '>' . h($status) . '</option>';
                }
              ?>
            </select>
          </div>
          <div>
            <label class="label" for="lead_date">Lead date</label>
            <input class="input" type="date" id="lead_date" name="lead_date" value="<?php echo h($editing['lead_date'] ?? ''); ?>" required>
          </div>
          <div>
            <label class="label" for="response_date">Response date</label>
            <input class="input" type="date" id="response_date" name="response_date" value="<?php echo h($editing['response_date'] ?? ''); ?>">
          </div>
          <div>
            <label class="label" for="response_time">Time to response (days)</label>
            <input class="input" type="text" id="response_time" value="<?php echo h($editing['response_time'] ?? ''); ?>" placeholder="Under reviewing" readonly>
          </div>
          <div>
            <label class="label" for="note">Internal note</label>
            <input class="input" list="note-options" type="text" id="note" name="note" value="<?php echo h($editing['note'] ?? ''); ?>" placeholder="Follow-up notes" >
            <datalist id="note-options">
              <?php foreach ($options['note'] ?? [] as $noteValue): ?>
                <option value="<?php echo h($noteValue); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>
        <div class="actions form-actions">
          <button
            type="submit"
            id="create-lead-btn"
            class="btn btn-success"
            <?php echo $editing ? 'disabled aria-disabled="true"' : ''; ?>
          >
            Create new lead
          </button>
          <div class="action-stack">
            <button
              type="submit"
              id="update-lead-btn"
              class="btn btn-info"
              <?php echo $editing ? '' : 'disabled aria-disabled="true"'; ?>
            >
              Update lead
            </button>
            <button
              type="submit"
              form="delete-form"
              id="delete-lead-btn"
              class="btn btn-secondary"
              onclick="return confirm('Delete this lead?');"
              <?php echo $editing ? '' : 'disabled aria-disabled="true"'; ?>
            >
              Delete lead
            </button>
          </div>
        </div>
      </form>
<form method="POST" id="delete-form" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo h((string) ($editing['id'] ?? '')); ?>">
      </form>
    </section>

    <section class="panel">
      <h2>Leads table</h2>
 <div class="table-actions">
        <!-- <div class="badge">Manage leads</div>  -->
        <form method="POST" id="bulk-delete-form" class="table-actions-form">
          <input type="hidden" name="action" value="bulk_delete">
           <a class="btn btn-dark dashboard-link" href="#" target="_blank" rel="noopener noreferrer">Dashboard</a>
          <button type="submit" class="btn btn-primary" id="bulk-delete-btn" onclick="return confirm('Delete selected leads?');" disabled aria-disabled="true">Delete selected</button>
        </form>
      </div>
 <div class="table-wrapper">
        <div class="filter-bar">
          <div class="filter-item">
            <label for="filter_platform">Platform</label>
            <select id="filter_platform" data-field="platform">
              <option value="">All</option>
              <?php foreach ($options['platform'] ?? [] as $platform): ?>
                <option value="<?php echo h($platform); ?>"><?php echo h($platform); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_contact_email">Contact</label>
            <select id="filter_contact_email" data-field="contact_email">
              <option value="">All</option>
              <?php foreach ($options['contact_email'] ?? [] as $contactEmail): ?>
                <option value="<?php echo h($contactEmail); ?>"><?php echo h($contactEmail); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_mobile_number">Phone number</label>
            <select id="filter_mobile_number" data-field="mobile_number">
              <option value="">All</option>
              <?php foreach ($options['mobile_number'] ?? [] as $mobileNumber): ?>
                <option value="<?php echo h($mobileNumber); ?>"><?php echo h($mobileNumber); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_owner">Owner</label>
            <select id="filter_owner" data-field="owner">
              <option value="">All</option>
              <?php foreach ($options['owner'] ?? [] as $owner): ?>
                <option value="<?php echo h($owner); ?>"><?php echo h($owner); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_status">Status</label>
            <select id="filter_status" data-field="status">
              <option value="">All</option>
              <?php foreach ($statusOptions as $status): ?>
                <option value="<?php echo h($status); ?>"><?php echo h($status); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_business_unit">Business unit</label>
            <select id="filter_business_unit" data-field="business_unit">
              <option value="">All</option>
              <?php foreach ($options['business_unit'] ?? [] as $businessUnit): ?>
                <option value="<?php echo h($businessUnit); ?>"><?php echo h($businessUnit); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_inquiries">Inquiry</label>
            <select id="filter_inquiries" data-field="inquiries">
              <option value="">All</option>
              <?php foreach ($options['inquiries'] ?? [] as $inquiryValue): ?>
                <option value="<?php echo h($inquiryValue); ?>"><?php echo h($inquiryValue); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Select</th>
              <th>ID</th>
              <th>Platform</th>
              <th>Business unit</th>
               <th class="contact-col">Contact</th>
              <th>Owner</th>
              <th>Status</th>
              <th>Lead date</th>
              <th>Response date</th>
  <th>Time to response</th>
              <th>Details & Actions</th>
            </tr>
          </thead>
            </tr>
          </thead>
          <tbody>
            <?php if (!$leads): ?>
              <tr><td colspan="11" class="empty-row">No leads found.</td></tr>
            <?php else: ?>
              <?php foreach ($leads as $lead): ?>
                 <tr
                  data-platform="<?php echo h(strtolower((string) $lead['platform'])); ?>"
                  data-contact_email="<?php echo h(strtolower((string) $lead['contact_email'])); ?>"
                  data-mobile_number="<?php echo h(strtolower((string) $lead['mobile_number'])); ?>"
                  data-owner="<?php echo h(strtolower((string) $lead['owner'])); ?>"
                  data-status="<?php echo h(strtolower((string) $lead['status'])); ?>"
                  data-business_unit="<?php echo h(strtolower((string) $lead['business_unit'])); ?>"
                  data-inquiries="<?php echo h(strtolower((string) $lead['inquiries'])); ?>"
                >
                  <td>
                    <input type="checkbox" form="bulk-delete-form" name="selected_ids[]" value="<?php echo h((string) $lead['id']); ?>" aria-label="Select lead <?php echo h((string) $lead['id']); ?>">
                  </td>
                  <td>#<?php echo h((string) $lead['id']); ?></td>
                  <td><?php echo h($lead['platform']); ?></td> 
                 <td><?php echo h($lead['business_unit']); ?></td>
                      <td class="contact-cell">
                    <div><?php echo h($lead['contact_email']); ?></div>
                    <small class="muted-text"><?php echo h($lead['mobile_number']); ?></small>
                  </td>
                  <td class="owner-cell"><?php echo h($lead['owner']); ?></td>
                  <td>
                    <?php
                      $status = strtolower((string) $lead['status']);
                      $statusClasses = [
                          'qualified' => 'status-qualified',
                          'quotation' => 'status-quotation',
                          'negotiation' => 'status-negotiation',
                          'award' => 'status-award',
                          'disqualified' => 'status-disqualified',
                          'onhold' => 'status-onhold',
                      ];
                      $pillClass = 'status-pill ' . ($statusClasses[$status] ?? 'status-default');
                          ?>
                    <span class="<?php echo $pillClass; ?>"><?php echo h($lead['status']); ?></span>
                  </td>
                  <td><?php echo h($lead['lead_date']); ?></td>
                  <td>
                   <div><?php echo h($lead['response_date']); ?></div>
                  </td>
                  <td>
                    <?php $isReviewing = $lead['response_time'] === null; ?>
                    <small class="response-time <?php echo $isReviewing ? 'pending' : 'muted-text'; ?>">
                      <?php echo $isReviewing ? 'Under reviewing' : h((string) $lead['response_time']) . ' days'; ?>
                    </small>
                  </td>
                  <td class="cell-actions">
             <button
              type="button"
              class="btn btn-details btn-compact view-details-btn"
              data-lead-id="<?php echo h((string) $lead['id']); ?>"
            >
              View details
            </button>
                    
            <a
              class="btn btn-info btn-compact"
              href="?edit=<?php echo h((string) $lead['id']); ?>"
            >
              Update lead
            </a>
            <form method="POST" action="" class="inline-form">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo h((string) $lead['id']); ?>">
              <button
                type="submit"
                class="btn btn-secondary btn-compact"
                onclick="return confirm('Delete this lead?');"
              >
                Delete lead
              </button>
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

  <div class="details-modal" id="details-modal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="details-title">Lead details</h3>
        <button type="button" class="modal-close" aria-label="Close details">×</button>
      </div>
      <div class="modal-body" id="details-body"></div>
    </div>
  </div>
  <footer class="footer">
    <div>Created by | PMO Team</div>
    <a href="https://elsewedymachinery.com" target="_blank" rel="noopener noreferrer">Elsewedy Machinery</a>
    <div>2025</div>
  </footer>
  <script>
    const leadsData = <?php echo $leadsForJs ?: '[]'; ?>;

    const flashMessage = <?php echo json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const flashMessageType = <?php echo json_encode($messageType, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const flashError = <?php echo json_encode($error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const toastStack = document.createElement('div');
    toastStack.className = 'toast-stack';
    document.body.appendChild(toastStack);

    function showToast(type, text) {
      if (!text) return;
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;

      const iconMap = {
        success: '✔️',
        create: '🆕',
        update: '✏️',
        delete: '🗑️',
        warning: '⚠️',
        error: '❌',
      };

      const label = document.createElement('span');
      label.className = 'toast-icon';
      label.textContent = iconMap[type] || iconMap.success;

      const message = document.createElement('div');
      message.className = 'toast-message';
      message.textContent = text;

      toast.append(label, message);
      toastStack.appendChild(toast);

      setTimeout(() => {
        toast.classList.add('toast-hide');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }

    if (flashMessage) {
      showToast(flashMessageType || 'success', flashMessage);
    }
    if (flashError) {
      showToast('error', flashError);
    }

    const leadIdInput = document.getElementById('lead_id');
    const platformSelect = document.getElementById('platform');
    const contactInput = document.getElementById('contact_email');
    const mobileInput = document.getElementById('mobile_number');
    const inquiryInput = document.getElementById('inquiries');
    const businessUnitSelect = document.getElementById('business_unit');
    const ownerInput = document.getElementById('owner');
    const statusSelect = document.getElementById('status');
    const leadDateInput = document.getElementById('lead_date');
    const responseDateInput = document.getElementById('response_date');
    const responseTimeInput = document.getElementById('response_time');
    const noteInput = document.getElementById('note');
    const deleteFormIdInput = document.querySelector('#delete-form input[name="id"]');
    const createButton = document.getElementById('create-lead-btn');
    const updateButton = document.getElementById('update-lead-btn');
    const deleteButton = document.getElementById('delete-lead-btn');
    const bulkDeleteButton = document.getElementById('bulk-delete-btn');
    const bulkCheckboxes = document.querySelectorAll('input[type="checkbox"][name="selected_ids[]"]');
    const initialExisting = <?php echo $editing ? 'true' : 'false'; ?>;

 function setActionAvailability(hasExistingLead) { 
      if (createButton) { 
        createButton.disabled = hasExistingLead; 
        createButton.setAttribute('aria-disabled', hasExistingLead ? 'true' : 'false'); 
      }
      if (updateButton) {
        updateButton.disabled = !hasExistingLead;
        updateButton.setAttribute('aria-disabled', !hasExistingLead ? 'true' : 'false');
      }
      if (deleteButton) {
        deleteButton.disabled = !hasExistingLead;
        deleteButton.setAttribute('aria-disabled', !hasExistingLead ? 'true' : 'false');
      }
    if (!hasExistingLead && deleteFormIdInput) {
        deleteFormIdInput.value = '';
      }
    }

    setActionAvailability(initialExisting);

    function updateResponseTime() {
      if (!responseTimeInput || !leadDateInput || !responseDateInput) return;
      const leadDateValue = leadDateInput.value;
      const responseDateValue = responseDateInput.value;
      if (!leadDateValue || !responseDateValue) {
        responseTimeInput.value = 'Under reviewing';
        return;
      }
      const leadDateObj = new Date(leadDateValue);
      const responseDateObj = new Date(responseDateValue);
      const diffMs = responseDateObj.getTime() - leadDateObj.getTime();
      const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
      responseTimeInput.value = diffDays >= 0 ? `${diffDays} days` : 'Under reviewing';
    }

    function ensureOption(selectEl, value) {
      if (!selectEl || !value) return;
      const exists = Array.from(selectEl.options).some(opt => opt.value === value);
      if (!exists) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        selectEl.appendChild(option);
      }
    }

     function fillFormFromLead(lead) {
      if (!lead) {
        setActionAvailability(false);
        if (responseDateInput) responseDateInput.value = '';
        if (responseTimeInput) responseTimeInput.value = 'Under reviewing';
        return;
      }
      ensureOption(platformSelect, lead.platform);
      ensureOption(businessUnitSelect, lead.business_unit);
      platformSelect.value = lead.platform || '';
      businessUnitSelect.value = lead.business_unit || '';
      contactInput.value = lead.contact_email || '';
      mobileInput.value = lead.mobile_number || '';
      inquiryInput.value = lead.inquiries || '';
      ownerInput.value = lead.owner || '';
      ensureOption(statusSelect, lead.status);
      statusSelect.value = lead.status || 'Qualified';
      leadDateInput.value = lead.lead_date || '';
      responseDateInput.value = lead.response_date || '';
      responseTimeInput.value = lead.response_time ? `${lead.response_time} days` : 'Under reviewing';
      updateResponseTime();
      noteInput.value = lead.note || '';
      if (deleteFormIdInput) {
        deleteFormIdInput.value = lead.id || '';
      }
      setActionAvailability(true);
    }

    const handleLeadLookup = () => {
      const enteredId = leadIdInput.value.trim();
      if (!enteredId) {
        setActionAvailability(false);
        if (responseDateInput) responseDateInput.value = '';
        if (responseTimeInput) responseTimeInput.value = 'Under reviewing';
        return;
      }
      const matchedLead = leadsData.find(lead => String(lead.id) === enteredId);
      fillFormFromLead(matchedLead);
      setActionAvailability(Boolean(matchedLead));
    };

    leadIdInput?.addEventListener('change', handleLeadLookup);
    leadIdInput?.addEventListener('input', handleLeadLookup);

      leadDateInput?.addEventListener('change', updateResponseTime);
    responseDateInput?.addEventListener('change', updateResponseTime);
    responseDateInput?.addEventListener('input', updateResponseTime);

    updateResponseTime();

    const detailsModal = document.getElementById('details-modal');
    const detailsBody = document.getElementById('details-body');
    const detailsTitle = document.getElementById('details-title');
    const modalCloseButton = document.querySelector('.modal-close');

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"]+/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
      }[match]));
    }

    function closeDetailsModal() {
      detailsModal?.classList.remove('open');
      detailsModal?.setAttribute('aria-hidden', 'true');
    }

    function openDetailsModal(lead) {
      if (!detailsModal || !detailsBody || !detailsTitle) return;
      detailsTitle.textContent = `Lead #${lead.id}`;
      const detailRows = [
        ['Platform', lead.platform],
        ['Business unit', lead.business_unit],
        ['Contact email', lead.contact_email],
        ['Mobile number', lead.mobile_number],
        ['Owner', lead.owner],
        ['Status', lead.status],
        ['Lead date', lead.lead_date],
        ['Response date', lead.response_date || 'Under reviewing'],
        ['Time to response', lead.response_time ? `${lead.response_time} days` : 'Under reviewing'],
        ['Inquiry details', lead.inquiries],
        ['Internal note', lead.note],
      ];

      detailsBody.innerHTML = detailRows.map(([label, value]) => (
        `<div class="detail-row"><span class="detail-label">${escapeHtml(label)}</span><span class="detail-value">${escapeHtml(value || 'N/A')}</span></div>`
      )).join('');

      detailsModal.classList.add('open');
      detailsModal.setAttribute('aria-hidden', 'false');
    }

    const detailButtons = document.querySelectorAll('.view-details-btn');

    detailButtons.forEach(button => {
      button.addEventListener('click', () => {
        const leadId = button.dataset.leadId;
        const lead = leadsData.find(item => String(item.id) === leadId);
        if (lead) {
          openDetailsModal(lead);
        }
      });
    });

    modalCloseButton?.addEventListener('click', closeDetailsModal);
    detailsModal?.addEventListener('click', (event) => {
      if (event.target === detailsModal) {
        closeDetailsModal();
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeDetailsModal();
      }
    });

    function updateBulkDeleteAvailability() {
      if (!bulkDeleteButton) return;
      const hasSelection = Array.from(bulkCheckboxes).some(cb => cb.checked);
      bulkDeleteButton.disabled = !hasSelection;
      bulkDeleteButton.setAttribute('aria-disabled', (!hasSelection).toString());
    }

    bulkCheckboxes.forEach(cb => {
      cb.addEventListener('change', updateBulkDeleteAvailability);
    });

    updateBulkDeleteAvailability();

    const filterControls = document.querySelectorAll('.filter-bar select[data-field]');
    const tableRows = document.querySelectorAll('tbody tr[data-platform]');

    function filterRows() {
      tableRows.forEach(row => {
        let visible = true;
        filterControls.forEach(select => {
          const selectedValue = select.value.trim().toLowerCase();
          if (!selectedValue) return;
          const field = select.dataset.field;
          const rowValue = (row.dataset[field] || '').toLowerCase();
          if (rowValue !== selectedValue) {
            visible = false;
          }
        });
        row.style.display = visible ? '' : 'none';
      });
    }

    filterControls.forEach(select => {
      select.addEventListener('change', filterRows);
    });

    filterRows();
  </script>
</body>
</html>