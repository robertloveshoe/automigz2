<?php
require __DIR__ . '/lib.php';
start_session();

$local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$msg = ''; $done = false; $exists = false;

try {
    $exists = (bool)db()->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->fetch();
} catch (Throwable $e) { $msg = DEBUG ? $e->getMessage() : 'Database error.'; }

if ($local && !$exists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = is_string($_POST['csrf'] ?? null) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    $first = trim((string)($_POST['first'] ?? '')); $last = trim((string)($_POST['last'] ?? ''));
    $mobile = trim((string)($_POST['mobile'] ?? '')); $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['pass'] ?? '');
    if (!$ok) $msg = 'Session expired, please try again.';
    elseif ($first === '' || $last === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = 'Please fill in your name and a valid email.';
    elseif (!preg_match('/^[0-9+\s-]{7,20}$/', $mobile)) $msg = 'Please enter a valid mobile number.';
    elseif (strlen($pass) < 10 || !preg_match('/[a-z]/', $pass) || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) $msg = 'Admin password: at least 10 characters with a lowercase letter, an uppercase letter and a number.';
    else {
        try {
            $st = db()->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([$email]);
            if ($row = $st->fetch()) {
                db()->prepare("UPDATE users SET role='admin', pass_hash=? WHERE id=?")->execute([password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
            } else {
                db()->prepare("INSERT INTO users (first_name,last_name,mobile,email,pass_hash,created_at,role) VALUES (?,?,?,?,?,?,'admin')")
                    ->execute([$first, $last, $mobile, $email, password_hash($pass, PASSWORD_DEFAULT), gmdate('Y-m-d\TH:i:s\Z')]);
            }
            $done = true;
        } catch (Throwable $e) { $msg = DEBUG ? $e->getMessage() : 'Could not create the account.'; }
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin setup</title>
<style>body{font:14px Arial,sans-serif;background:#f5f5f5;margin:0;padding:40px 16px}.c{max-width:420px;margin:0 auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 8px 28px rgba(0,0,0,.12)}h2{margin:0 0 6px}label{display:block;font-weight:600;font-size:12px;margin:12px 0 4px}input{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #ccc;border-radius:5px}button{margin-top:16px;width:100%;padding:12px;border:0;border-radius:6px;background:#d32f2f;color:#fff;font-weight:700;cursor:pointer}.e{color:#b00020;margin-top:10px}.ok{color:#1a7f37;font-weight:700}</style></head><body><div class="c">
<h2>Create the admin account</h2>
<?php if ($done): ?>
  <p class="ok">Admin account ready.</p>
  <p>Log in on the site with that email and password. You'll see <b>Admin Dashboard</b> in your account menu.</p>
  <p><b>Now delete <code>admin-setup.php</code> from your automigz folder.</b></p>
<?php elseif (!$local): ?>
  <p class="e">This page only works on the computer that runs the server (localhost).</p>
<?php elseif ($exists): ?>
  <p>An admin account already exists, so this page is locked. Please delete <code>admin-setup.php</code>.</p>
<?php else: ?>
  <p>Pick your own password. Nothing is pre-set.</p>
  <form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
    <label>First name</label><input name="first" required>
    <label>Last name</label><input name="last" required>
    <label>Mobile number</label><input name="mobile" required placeholder="09XX-XXX-XXXX">
    <label>Email (used to log in)</label><input name="email" type="email" required>
    <label>Password (10+ characters, upper + lower case + number)</label><input name="pass" type="password" required minlength="10" autocomplete="new-password">
    <div class="e"><?= htmlspecialchars($msg) ?></div>
    <button type="submit">Create admin</button>
  </form>
<?php endif; ?>
</div></body></html>
