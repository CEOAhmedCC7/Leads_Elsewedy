<?php
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['user'])) {
    header('Location: home.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        try {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('SELECT user_id, username, password_hash, role, status FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'User not found. Use the Username shown in the Users list (not email).';
            } elseif ($user['status'] !== 'active') {
                $error = 'This account is inactive. Please contact an administrator.';
            } else {
                $storedHash = (string) $user['password_hash'];

                $passwordMatches = password_verify($password, $storedHash);
                $passwordWasPlaintext = false;

                if (!$passwordMatches) {
                    $hashInfo = password_get_info($storedHash);
                    $looksPlaintext = $hashInfo['algo'] === 0;

                    if ($looksPlaintext && hash_equals($storedHash, $password)) {
                        $passwordMatches = true;
                        $passwordWasPlaintext = true;
                    }
                }

                if ($passwordMatches) {
                    if ($passwordWasPlaintext || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
                        $update->execute([':hash' => $newHash, ':id' => $user['user_id']]);
                    }

                    $_SESSION['user'] = [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                    ];
                    header('Location: home.php');
                    exit;
                }

                $error = 'Invalid password. Double-check your Username and password and try again.';
            }
        } catch (Throwable $e) {
            $error = format_db_error($e, 'users table');
        }
    } else {
        $error = 'Please provide both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Elsewedy Machinery | Login</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body class="page login-page">
  <div class="login-card">
    <div class="logo-wrap">
      <img src="../EM%20Logo.jpg" alt="Elsewedy Machinery" class="logo" />
    </div>
    <h1>Welcome Back</h1>
    <p class="helper-text"><em>Secure access for Elsewedy Machinery teams.</em></p>
    <?php if ($error): ?>
      <div class="alert" style="color: var(--secondary); text-align:center; margin-bottom:12px;">
        <?php echo safe($error); ?>
      </div>
    <?php endif; ?>
    <form class="form-container" method="POST" action="login.php">
      <div class="form-row">
        <div>
          <label class="label" for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Enter your username" required />
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Enter your password" required />
        </div>
      </div>
       <div class="actions" style="justify-content:space-between; align-items:center;">
        <a href="signup.php" class="btn btn-neutral" style="text-decoration:none;">Create Account</a>
        <button class="btn btn-save" type="submit">Login</button>
      </div>
    </form>
  </div>
</body>
</html>