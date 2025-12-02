<?php
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    header('Location: home.php');
    exit;
}

$error = '';
$success = '';
$submittedUsername = '';
$selectedRole = 'viewer';
$allowedRoles = ['viewer', 'project_manager', 'finance','admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $selectedRole = in_array($_POST['role'] ?? '', $allowedRoles, true) ? (string) $_POST['role'] : 'viewer';

    if ($submittedUsername === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($submittedUsername) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $pdo = get_pdo();
            $check = $pdo->prepare('SELECT 1 FROM users WHERE username = :username');
            $check->execute([':username' => $submittedUsername]);

            if ($check->fetchColumn()) {
                $error = 'A user with this username already exists. Please choose another one or log in.';
            } else {
                $newUserId = 'usr_' . bin2hex(random_bytes(8));
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare('INSERT INTO users (user_id, username, password_hash, role, status) VALUES (:id, :username, :hash, :role, :status)');
                $insert->execute([
                    ':id' => $newUserId,
                    ':username' => $submittedUsername,
                    ':hash' => $hash,
                    ':role' => $selectedRole,
                    ':status' => 'active',
                ]);

                $success = 'Account created successfully. You can now log in.';
                $submittedUsername = '';
                $selectedRole = 'viewer';
            }
        } catch (Throwable $e) {
            $error = format_db_error($e, 'users table');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Elsewedy Machinery | Sign Up</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page login-page">
  <div class="login-card">
    <div class="logo-wrap">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <h1>Create Account</h1>
    <p class="helper-text"><em>Sign up to access Elsewedy Machinery tools.</em></p>

    <?php if ($error): ?>
      <div class="alert" style="color: var(--secondary); text-align:center; margin-bottom:12px;">
        <?php echo safe($error); ?>
      </div>
    <?php elseif ($success): ?>
      <div class="alert" style="color: var(--primary); text-align:center; margin-bottom:12px;">
        <?php echo safe($success); ?>
      </div>
    <?php endif; ?>

    <form class="form-container" method="POST" action="signup.php">
      <div class="form-row">
        <div>
          <label class="label" for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Choose a username" value="<?php echo safe($submittedUsername); ?>" required />
        </div>
        <div>
          <label class="label" for="role">Role</label>
          <select id="role" name="role">
            <?php foreach ($allowedRoles as $role): ?>
              <option value="<?php echo safe($role); ?>" <?php echo $selectedRole === $role ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $role)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Create a password" required />
        </div>
        <div>
          <label class="label" for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter password" required />
        </div>
      </div>
      <div class="actions" style="justify-content:space-between; align-items:center;">
        <a href="login.php" class="btn btn-neutral" style="text-decoration:none;">Back to Login</a>
        <button class="btn btn-save" type="submit">Sign Up</button>
      </div>
    </form>
  </div>
</body>
</html>