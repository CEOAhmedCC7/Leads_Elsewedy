<?php
require_once __DIR__ . '/helpers.php';

$currentUser = require_login();
$error = '';
$success = '';

$userIdPosted = trim($_POST['user_id'] ?? '');
$userIdCustom = trim($_POST['user_id_custom'] ?? '');
$usernamePosted = trim($_POST['username'] ?? '');
$usernameCustom = trim($_POST['username_custom'] ?? '');

$submitted = [
    'user_id' => $userIdPosted === '__custom__' ? $userIdCustom : $userIdPosted,
    'username' => $usernamePosted === '__custom__' ? $usernameCustom : $usernamePosted,
    'password' => (string) ($_POST['password'] ?? ''),
    'role' => $_POST['role'] ?? 'viewer',
    'status' => $_POST['status'] ?? 'active',
];
$selectedIds = array_filter(array_map('trim', (array) ($_POST['selected_ids'] ?? [])));
$filterIds = array_filter(array_map('trim', (array) ($_POST['filter_user_ids'] ?? [])));


$allowedRoles = ['admin', 'project_manager', 'finance', 'viewer'];
$allowedStatuses = ['active', 'inactive'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = get_pdo();

    try {
        if ($action === 'create') {
            if ($usernamePosted === '__custom__' && $submitted['username'] === '') {
                $error = 'Please enter a username for the new account.';
            } elseif ($userIdPosted === '__custom__' && $submitted['user_id'] === '') {
                $error = 'Please enter a custom User ID or choose Auto-generate.';
            } elseif ($submitted['username'] === '' || $submitted['password'] === '') {
                $error = 'Username and password are required to create a user.';
            } elseif (!in_array($submitted['role'], $allowedRoles, true) || !in_array($submitted['status'], $allowedStatuses, true)) {
                $error = 'Please select a valid role and status.';
            } elseif (strlen($submitted['username']) < 3) {
                $error = 'Username must be at least 3 characters long.';
            } elseif (strlen($submitted['password']) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                $userId = $submitted['user_id'] !== '' ? $submitted['user_id'] : 'usr_' . bin2hex(random_bytes(4));

                $exists = $pdo->prepare('SELECT 1 FROM users WHERE user_id = :id OR username = :username');
                $exists->execute([
                    ':id' => $userId,
                    ':username' => $submitted['username'],
                ]);

                if ($exists->fetchColumn()) {
                    $error = 'A user with this ID or username already exists.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (user_id, username, password_hash, role, status) VALUES (:id, :username, :hash, :role, :status)');
                    $stmt->execute([
                        ':id' => $userId,
                        ':username' => $submitted['username'],
                        ':hash' => password_hash($submitted['password'], PASSWORD_DEFAULT),
                        ':role' => $submitted['role'],
                        ':status' => $submitted['status'],
                    ]);

                    $success = 'User created successfully.';
                    $submitted = [
                        'user_id' => '',
                        'username' => '',
                        'password' => '',
                        'role' => 'viewer',
                        'status' => 'active',
                    ];
                }
            }
        } elseif ($action === 'update') {
            if ($submitted['user_id'] === '') {
                $error = 'Please provide the User ID to update.';
            } elseif ($submitted['username'] === '') {
                $error = 'Username cannot be empty when updating a user.';
            } elseif (!in_array($submitted['role'], $allowedRoles, true) || !in_array($submitted['status'], $allowedStatuses, true)) {
                $error = 'Please select a valid role and status.';
            } else {
                $check = $pdo->prepare('SELECT 1 FROM users WHERE user_id = :id');
                $check->execute([':id' => $submitted['user_id']]);

                if (!$check->fetchColumn()) {
                    $error = 'User not found. Please check the User ID.';
                } else {
                    $params = [
                        ':id' => $submitted['user_id'],
                        ':username' => $submitted['username'],
                        ':role' => $submitted['role'],
                        ':status' => $submitted['status'],
                    ];

                    $passwordSql = '';
                    if ($submitted['password'] !== '') {
                        if (strlen($submitted['password']) < 8) {
                            throw new RuntimeException('Password must be at least 8 characters long.');
                        }
                        $passwordSql = ', password_hash = :hash';
                        $params[':hash'] = password_hash($submitted['password'], PASSWORD_DEFAULT);
                    }

                    $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role, status = :status{$passwordSql} WHERE user_id = :id");
                    $stmt->execute($params);

                    $success = 'User updated successfully.';
                }
            }
        } elseif ($action === 'view') {
            if ($submitted['user_id'] === '') {
                $error = 'Please enter a User ID to view details.';
            } else {
                $stmt = $pdo->prepare('SELECT user_id, username, role, status FROM users WHERE user_id = :id');
                $stmt->execute([':id' => $submitted['user_id']]);
                $found = $stmt->fetch();

                if ($found) {
                    $submitted['username'] = $found['username'];
                    $submitted['role'] = $found['role'];
                    $submitted['status'] = $found['status'];
                    $submitted['password'] = '';
                    $success = 'User loaded. You can update or delete this account.';
               } else {
                    $error = 'No user found with that ID.';
                }
            }
        } elseif ($action === 'delete') {
            if ($submitted['user_id'] === '') { 
                $error = 'Please provide the User ID to delete.'; 
            } else { 
                $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :id'); 
                $stmt->execute([':id' => $submitted['user_id']]); 

                if ($stmt->rowCount() === 0) {
                    $error = 'User not found or already deleted.';
                } else {
                    $success = 'User deleted successfully.';
                    $submitted = [
                        'user_id' => '',
                        'username' => '',
                        'password' => '',
                        'role' => 'viewer',
                        'status' => 'active',
                    ];
                }
            }
        } elseif ($action === 'bulk_delete') {
          if (!$selectedIds) {
                $error = 'Select at least one user to delete.';
            } else {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id IN ({$placeholders})");
                $stmt->execute($selectedIds);
                $deleted = $stmt->rowCount();
                $success = $deleted . ' user(s) removed.';
            }
        } elseif ($action === 'filter') {
            $success = $filterIds ? 'Showing the selected users.' : 'Showing all users.';
        }
    } catch (Throwable $e) {
        $error = format_db_error($e, 'users table');
    }
}

$users = fetch_table('users', 'user_id');
$userIdOptions = array_column($users, 'user_id');
$usernameOptions = array_values(array_unique(array_column($users, 'username')));
$visibleUsers = $filterIds ? array_values(array_filter($users, fn ($user) => in_array($user['user_id'], $filterIds, true))) : $users;
$userIdSelectValue = $submitted['user_id'] === '' ? '' : (in_array($submitted['user_id'], $userIdOptions, true) ? $submitted['user_id'] : '__custom__');
$usernameSelectValue = $submitted['username'] === '' ? '' : (in_array($submitted['username'], $usernameOptions, true) ? $submitted['username'] : '__custom__');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users | Elsewedy Machinery</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <script src="./assets/app.js" defer></script>
</head>
<body class="page">
<header class="navbar">
    <div class="header">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <div class="title">Users</div>
    <div class="links">
      <div class="user-chip">
        <span class="name"><?php echo safe($currentUser['username']); ?></span>
        <span class="role"><?php echo strtoupper(safe($currentUser['role'])); ?></span>
      </div>
      <a href="./users-list.php">Copy Users</a>
      <a href="./home.php">Home</a>
      <a class="logout-icon" href="./logout.php" aria-label="Logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M15 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M10 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M16 8l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </a>
    </div>
  </header>
  <main style="padding:24px; display:grid; gap:20px;">
    <div class="form-container">
      <h3 style="margin-top:0; color:var(--secondary);">User Management</h3>
   <?php if ($error): ?> 
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;"> 
          <?php echo safe($error); ?> 
        </div> 
      <?php elseif ($success): ?> 
        <div class="alert" style="color: var(--secondary); margin-bottom:12px;">
          <?php echo safe($success); ?> 
        </div> 
      <?php endif; ?> 

  <form method="POST" action="users.php">
        <div class="form-row">
     <div>
            <label class="label" for="user-id">User ID</label>
            <select id="user-id" name="user_id">
              <option value="" <?php echo $userIdSelectValue === '' ? 'selected' : ''; ?>>Auto-generate</option>
              <?php foreach ($userIdOptions as $option): ?>
                <option value="<?php echo safe($option); ?>" <?php echo $userIdSelectValue === $option ? 'selected' : ''; ?>><?php echo safe($option); ?></option>
              <?php endforeach; ?>
              <option value="__custom__" <?php echo $userIdSelectValue === '__custom__' ? 'selected' : ''; ?>>Custom ID...</option>
            </select>
            <input id="user-id-custom" name="user_id_custom" type="text" class="<?php echo $userIdSelectValue === '__custom__' ? '' : 'hidden'; ?>" placeholder="Enter a new ID" value="<?php echo safe($userIdSelectValue === '__custom__' ? $submitted['user_id'] : ''); ?>" />
          </div>
          <div>
            <label class="label" for="username">Username</label>
            <select id="username" name="username">
              <option value="" <?php echo $usernameSelectValue === '' ? 'selected' : ''; ?>>Select an existing username</option>
              <?php foreach ($usernameOptions as $option): ?>
                <option value="<?php echo safe($option); ?>" <?php echo $usernameSelectValue === $option ? 'selected' : ''; ?>><?php echo safe($option); ?></option>
              <?php endforeach; ?>
              <option value="__custom__" <?php echo $usernameSelectValue === '__custom__' ? 'selected' : ''; ?>>Custom username...</option>
            </select>
            <input id="username-custom" name="username_custom" type="text" class="<?php echo $usernameSelectValue === '__custom__' ? '' : 'hidden'; ?>" placeholder="Type a new username" value="<?php echo safe($usernameSelectValue === '__custom__' ? $submitted['username'] : ''); ?>" />
          </div>
          <div>
            <label class="label" for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="********" />
          </div>
        </div>
        <div class="form-row">
          <div>
            <label class="label" for="role">Role</label>
            <select id="role" name="role">
              <?php foreach ($allowedRoles as $role): ?>
                <option value="<?php echo safe($role); ?>" <?php echo $submitted['role'] === $role ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $role)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="status">Status</label>
            <select id="status" name="status">
              <?php foreach ($allowedStatuses as $status): ?>
                <option value="<?php echo safe($status); ?>" <?php echo $submitted['status'] === $status ? 'selected' : ''; ?>><?php echo ucwords($status); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
       </div>
        <div class="actions">
          <button class="btn btn-save" type="submit" name="action" value="create">Create New User</button>
          <button class="btn btn-neutral" type="submit" name="action" value="view">View</button>
          <button class="btn btn-neutral" type="submit" name="action" value="update">Update</button>
          <button class="btn btn-delete" type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
        </div>
       </form>
    </div>

    <form method="POST" action="users.php">
      <div class="table-actions">
        <div class="filters">
          <label class="label" for="multi-user">Select Users</label>
          <select id="multi-user" name="filter_user_ids[]" multiple size="4">
            <?php foreach ($users as $user): ?>
              <option value="<?php echo safe($user['user_id']); ?>" <?php echo in_array($user['user_id'], $filterIds, true) ? 'selected' : ''; ?>><?php echo safe($user['user_id'] . ' | ' . $user['username']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="actions">
          <button class="btn btn-neutral" type="submit" name="action" value="filter">View Selection</button>
          <button class="btn btn-delete" type="submit" name="action" value="bulk_delete" onclick="return confirm('Delete selected users?');">Delete Selected</button>
          <button class="btn btn-neutral" type="button" onclick="exportSelected('users-table')">Download Excel</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table id="users-table">
          <thead>
            <tr><th><input type="checkbox" onclick="toggleAll(this, 'users-table')" aria-label="Select all users" /></th><th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th></tr>
          </thead>
          <tbody>
            <?php if ($visibleUsers): ?>
              <?php foreach ($visibleUsers as $user): ?>
                <tr>
                  <td><input type="checkbox" name="selected_ids[]" value="<?php echo safe($user['user_id']); ?>" /></td>
                  <td><?php echo safe($user['user_id']); ?></td>
                  <td><?php echo safe($user['username']); ?></td>
                  <td><?php echo safe($user['role']); ?></td>
                  <td><?php echo safe($user['status']); ?></td>
                  <td><?php echo safe($user['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6">No users recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
        </table>
      </div>
      <div class="selection-summary" id="selection-summary">
        <div class="selection-summary__header">
          <h4>Selected records</h4>
          <p class="muted">Use the checkboxes to preview which users will be exported or deleted.</p>
        </div>
        <ul class="selection-summary__list"></ul>
      </div>
    </form>
  </main>
</body>
</html>








