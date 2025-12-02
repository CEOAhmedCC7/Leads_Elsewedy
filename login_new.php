<?php
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$error = '';

if (isset($_SESSION['user'])) {
    header('Location: leads_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please provide both email and password.';
    } else {
        try {
 $pdo = get_pdo();
            $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role, is_active FROM users_login WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Account not found. Please double-check your email.';
            } elseif (!$user['is_active']) {
                $error = 'This account is inactive. Contact an administrator.';
            } else {
                $storedPassword = (string) $user['password_hash'];

                $isHash = preg_match('/^\$(2[aby]|argon2i|argon2id)\$/', $storedPassword) === 1;
                $isValid = $isHash ? password_verify($password, $storedPassword) : hash_equals($storedPassword, $password);

                if (!$isValid) {
                    $error = 'Incorrect password. Please try again.';
                } else {
                    $_SESSION['user'] = [
                        'id' => $user['user_id'],
                        'name' => $user['full_name'],
                        'email' => $user['email'],
                        'role' => $user['role'] ?: 'USER',
                    ];
                    header('Location: leads_dashboard.php');
                    exit;
                }
            }
        } catch (Throwable $e) {
            $error = format_db_error($e, 'users_login table');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Elsewedy Machinery | Login</title>
  <link rel="stylesheet" href="./new-styles.css">
</head>
<body>
  <div class="login-viewport">
    <div class="login-card">
      <div class="logo-mark">EM</div>
      <h1>Welcome back</h1>
      <p>Secure access to the Elsewedy Machinery leads desk.</p>
      <?php if ($error): ?>
        <div class="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <form method="POST" class="form-grid">
        <div>
          <label class="label" for="email">Email</label>
          <input class="input" type="email" id="email" name="email" placeholder="name@company.com" required>
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input class="input" type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <div class="actions">
          <button type="submit" class="btn btn-primary">Sign in</button>
        </div>
      </form>
      <div class="helper-row">
        <span class="badge">Elsewedy Machinery</span>
        <small>Role-based access with active state.</small>
      </div>
    </div>
  </div>
</body>
</html>