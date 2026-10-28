<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
start_session();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $d, int $code = 200): void {
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $msg, int $code = 400): void { out(['ok'=>false, 'error'=>$msg], $code); }
function text(array $in, string $key, int $max = 200): string {
    $v = $in[$key] ?? '';
    if (!is_string($v)) return '';
    $v = trim($v);
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}
function require_login(): array {
    $u = current_user();
    if (!$u) fail('Please log in first.', 401);
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if (($u['role'] ?? 'user') !== 'admin') fail('Admins only.', 403);
    return $u;
}

function check_password(string $pass, bool $admin = false): ?string {
    $min = $admin ? 10 : 8;
    if (strlen($pass) < $min || !preg_match('/[a-z]/', $pass) || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]|[^A-Za-z0-9\s]/', $pass)) {
        return "Password must be at least $min characters and include a lowercase letter, an uppercase letter, and a number or symbol.";
    }
    return null;
}
function user_fields(array $in): array {
    $first  = text($in, 'first', 60);
    $last   = text($in, 'last', 60);
    $mobile = text($in, 'mobile', 20);
    $email  = strtolower(text($in, 'email', 120));
    if ($first === '' || $last === '' || $mobile === '' || $email === '') fail('Please fill in first name, last name, mobile and email.');
    if (!preg_match('/^[0-9+\s-]{7,20}$/', $mobile)) fail('Invalid mobile number: numbers only.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
    return [$first, $last, $mobile, $email];
}

$action = $_GET['action'] ?? '';
$in = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        fail('Your session expired. Please refresh the page.', 403);
    }
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $in = is_array($decoded) ? $decoded : [];
}

try {
    switch ($action) {

    case 'signup':
        $first  = text($in, 'first', 60);
        $last   = text($in, 'last', 60);
        $mobile = text($in, 'mobile', 20);
        $email  = strtolower(text($in, 'email', 120));
        $pass   = is_string($in['pass'] ?? null) ? $in['pass'] : '';

        if ($first === '' || $last === '' || $mobile === '' || $email === '' || $pass === '') fail('Please fill in all required fields.');
        if (!preg_match('/^[0-9+\s-]{7,20}$/', $mobile)) fail('Invalid mobile number: numbers only.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
        if (strlen($pass) < 8 || !preg_match('/[a-z]/', $pass) || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]|[^A-Za-z0-9\s]/', $pass)) {
            fail('Password must be at least 8 characters and include a lowercase letter, an uppercase letter, and a number or symbol.');
        }

        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) fail('An account with this email already exists.');

        $st = db()->prepare('INSERT INTO users (first_name,last_name,mobile,email,pass_hash,created_at) VALUES (?,?,?,?,?,?)');
        $st->execute([$first, $last, $mobile, $email, password_hash($pass, PASSWORD_DEFAULT), gmdate('Y-m-d\TH:i:s\Z')]);
        out(['ok'=>true]);

    case 'login':
        $id   = strtolower(text($in, 'id', 120));
        $pass = is_string($in['pass'] ?? null) ? $in['pass'] : '';
        $st = db()->prepare("SELECT id, first_name AS first, last_name AS last, mobile, email, role, pass_hash FROM users
                             WHERE email = :id1 OR LOWER(CONCAT(first_name, ' ', last_name)) = :id2");
        $st->execute([':id1' => $id, ':id2' => $id]);
        $found = null;
        foreach ($st->fetchAll() as $u) {
            if (password_verify($pass, $u['pass_hash'])) { $found = $u; break; }
        }
        if (!$found) { usleep(300000); fail('Invalid email/username or password.', 401); }

        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$found['id'];
        if (!empty($in['remember'])) {
            setcookie(session_name(), session_id(), [
                'expires'  => time() + 30 * 86400,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
        }
        out(['ok'=>true, 'user'=>public_user($found)]);

    case 'logout':
        unset($_SESSION['uid']);
        session_regenerate_id(true);
        out(['ok'=>true]);

    case 'bookings':
        $u = require_login();
        $st = db()->prepare('SELECT * FROM bookings WHERE user_id = ? ORDER BY id DESC');
        $st->execute([$u['id']]);
        out(['ok'=>true, 'bookings'=>array_map('format_booking', $st->fetchAll())]);

    case 'book':
        $u = require_login();

        $car = null;
        foreach (cars() as $c) if ($c['id'] === (int)($in['carId'] ?? 0)) $car = $c;
        if (!$car) fail('Unknown car.');

        $color = null;
        foreach ($car['colors'] as $c) if ($c['n'] === ($in['color'] ?? '')) $color = $c;
        if (!$color) fail('Please choose a valid color.');

        $qty = (int)($in['qty'] ?? 1);
        if ($qty < 1 || $qty > 10) fail('Number of vehicles must be between 1 and 10.');

        $pick = text($in, 'pickDate', 10);
        $ret  = text($in, 'retDate', 10);
        $d1 = DateTime::createFromFormat('!Y-m-d', $pick);
        $d2 = DateTime::createFromFormat('!Y-m-d', $ret);
        if (!$d1 || !$d2 || $d1->format('Y-m-d') !== $pick || $d2->format('Y-m-d') !== $ret) fail('Please fill in all fields.');
        if ($d2 < $d1) fail('Return date must be after pick-up date.');
        if ($d1 < new DateTime('today')) fail('Pick-up date cannot be in the past.');
        $days = max(1, (int)round(($d2->getTimestamp() - $d1->getTimestamp()) / 86400));

        $mode = ($in['mode'] ?? '') === 'deliver' ? 'deliver' : 'pickup';
        $loc  = text($in, 'location', 120);
        if (!in_array($loc, LOCATIONS, true)) fail('Please choose a pick-up location.');
        $address = '';
        $fee = 0;
        if ($mode === 'deliver') {
            $area = text($in, 'area', 80);
            $fee = delivery_fee($area);
            if ($fee === null) fail('Delivery is only available inside Luzon. Please choose your province or area.');
            $street = text($in, 'address', 140);
            if ($street === '') fail('Please enter your delivery address.');
            $address = $street . ', ' . $area;
        }

        $renter = text($in, 'name', 100);
        $phone  = text($in, 'phone', 20);
        if ($renter === '' || !preg_match('/^[0-9+\s-]{7,20}$/', $phone)) fail('Please enter your name and a valid phone number.');

        $lock = 'amz_stock_' . (int)$car['id'];
        $gl = db()->prepare('SELECT GET_LOCK(?, 5)'); $gl->execute([$lock]);
        $sk = stock_of((int)$car['id']);
        if ($sk['left'] < 1) {
            fail($car['name'] . ' is out of stock. Please wait for availability' . ($sk['next'] ? ' — expected back on ' . date('M j, Y', strtotime($sk['next'])) . '.' : '.'), 409);
        }
        if ($qty > $sk['left']) fail('Only ' . $sk['left'] . ' unit(s) of ' . $car['name'] . ' left. Please lower the number of vehicles.', 409);

        $base  = $car['price'] * $qty * $days;
        $total = $base + $fee;

        $st = db()->prepare('INSERT INTO bookings
            (user_id,car_id,car_name,img,price,color_name,color_hex,days,qty,base,fee,total,mode,location,address,renter,phone,pick_date,ret_date,status,created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$u['id'], $car['id'], $car['name'], strtok($color['img'] ?? $car['img'], '?'), $car['price'], $color['n'], $color['h'],
                      $days, $qty, $base, $fee, $total, $mode, $loc, $address, $renter, $phone, $pick, $ret,
                      'active', gmdate('Y-m-d\TH:i:s\Z')]);
        $id = (int)db()->lastInsertId();
        $rl = db()->prepare('SELECT RELEASE_LOCK(?)'); $rl->execute([$lock]);
        $row = db()->query('SELECT * FROM bookings WHERE id = ' . $id)->fetch();
        out(['ok'=>true, 'booking'=>format_booking($row)]);

    case 'cancel':
        $u = require_login();
        $st = db()->prepare("UPDATE bookings SET status='cancelled', cancelled_at=? WHERE id=? AND user_id=? AND status='active'");
        $st->execute([gmdate('Y-m-d\TH:i:s\Z'), (int)($in['id'] ?? 0), $u['id']]);
        if ($st->rowCount() === 0) fail('Booking not found or already cancelled.', 404);
        out(['ok'=>true]);

    case 'admin_bookings':
        require_admin();
        $rows = db()->query('SELECT b.*, u.first_name AS cf, u.last_name AS cl, u.email AS ce, u.mobile AS cm
                             FROM bookings b JOIN users u ON u.id = b.user_id ORDER BY b.id DESC')->fetchAll();
        $list = array_map(function ($r) {
            $b = format_booking($r);
            $b['userId'] = (int)$r['user_id'];
            $b['customer'] = ['name'=>$r['cf'] . ' ' . $r['cl'], 'email'=>$r['ce'], 'mobile'=>$r['cm']];
            return $b;
        }, $rows);
        $customers = (int)db()->query("SELECT COUNT(*) FROM users WHERE role <> 'admin'")->fetchColumn();
        out(['ok'=>true, 'bookings'=>$list, 'customers'=>$customers]);

    case 'admin_cancel':
        require_admin();
        $st = db()->prepare("UPDATE bookings SET status='cancelled', cancelled_at=? WHERE id=? AND status='active'");
        $st->execute([gmdate('Y-m-d\TH:i:s\Z'), (int)($in['id'] ?? 0)]);
        if ($st->rowCount() === 0) fail('Booking not found or already cancelled.', 404);
        out(['ok'=>true]);

    case 'admin_users':
        require_admin();
        $rows = db()->query("SELECT u.id, u.first_name, u.last_name, u.mobile, u.email, u.role, u.created_at,
                                    COUNT(b.id) AS total_bookings,
                                    COALESCE(SUM(b.status = 'active'), 0) AS active_bookings
                             FROM users u LEFT JOIN bookings b ON b.user_id = u.id
                             GROUP BY u.id ORDER BY u.id DESC")->fetchAll();
        out(['ok'=>true, 'users'=>array_map(fn($r) => [
            'id'=>(int)$r['id'], 'first'=>$r['first_name'], 'last'=>$r['last_name'], 'mobile'=>$r['mobile'], 'email'=>$r['email'],
            'role'=>$r['role'], 'joined'=>$r['created_at'],
            'bookings'=>(int)$r['total_bookings'], 'active'=>(int)$r['active_bookings'],
        ], $rows)]);

    case 'admin_user_add':
        require_admin();
        [$first, $last, $mobile, $email] = user_fields($in);
        $role = ($in['role'] ?? '') === 'admin' ? 'admin' : 'user';
        $pass = is_string($in['pass'] ?? null) ? $in['pass'] : '';
        if ($err = check_password($pass, $role === 'admin')) fail($err);
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) fail('An account with this email already exists.');
        $st = db()->prepare('INSERT INTO users (first_name,last_name,mobile,email,pass_hash,created_at,role) VALUES (?,?,?,?,?,?,?)');
        $st->execute([$first, $last, $mobile, $email, password_hash($pass, PASSWORD_DEFAULT), gmdate('Y-m-d\TH:i:s\Z'), $role]);
        out(['ok'=>true]);

    case 'admin_user_update':
        $me = require_admin();
        $id = (int)($in['id'] ?? 0);
        $st = db()->prepare('SELECT id FROM users WHERE id = ?');
        $st->execute([$id]);
        if (!$st->fetch()) fail('User not found.', 404);
        [$first, $last, $mobile, $email] = user_fields($in);
        $role = ($id === (int)$me['id']) ? 'admin' : ((($in['role'] ?? '') === 'admin') ? 'admin' : 'user');
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
        $st->execute([$email, $id]);
        if ($st->fetch()) fail('Another account already uses this email.');
        $pass = is_string($in['pass'] ?? null) ? $in['pass'] : '';
        if ($pass !== '') {
            if ($err = check_password($pass, $role === 'admin')) fail($err);
            db()->prepare('UPDATE users SET first_name=?, last_name=?, mobile=?, email=?, role=?, pass_hash=? WHERE id=?')
                ->execute([$first, $last, $mobile, $email, $role, password_hash($pass, PASSWORD_DEFAULT), $id]);
        } else {
            db()->prepare('UPDATE users SET first_name=?, last_name=?, mobile=?, email=?, role=? WHERE id=?')
                ->execute([$first, $last, $mobile, $email, $role, $id]);
        }
        out(['ok'=>true]);

    case 'admin_user_delete':
        $me = require_admin();
        $id = (int)($in['id'] ?? 0);
        if ($id === (int)$me['id']) fail('You cannot delete your own account.');
        $st = db()->prepare('SELECT id FROM users WHERE id = ?');
        $st->execute([$id]);
        if (!$st->fetch()) fail('User not found.', 404);
        $st = db()->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'active'");
        $st->execute([$id]);
        if ((int)$st->fetchColumn() > 0) fail('This user still has active bookings. Cancel them first, then remove the user.', 409);
        db()->beginTransaction();
        db()->prepare('DELETE FROM bookings WHERE user_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        db()->commit();
        out(['ok'=>true]);

    case 'stock':
        $list = [];
        $info = stock_info();
        foreach (cars_base() as $c) {
            $sk = $info[$c['id']] ?? ['total'=>CAR_STOCK, 'left'=>CAR_STOCK, 'next'=>null];
            $list[] = ['id'=>$c['id']] + $sk;
        }
        out(['ok'=>true, 'stock'=>$list]);

    default:
        fail('Unknown action.', 404);
    }
} catch (Throwable $e) {
    error_log('Automigz API error: ' . $e->getMessage());
    fail(DEBUG ? 'Server error: ' . $e->getMessage() : 'Server error. Please try again.', 500);
}
