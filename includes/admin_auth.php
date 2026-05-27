<?php
require_once __DIR__ . '/db.php';

const ADMIN_SESSION_LIFETIME = 2592000; // 30 days
const ADMIN_REMEMBER_COOKIE = 'admin_remember';

function admin_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.gc_maxlifetime', (string) ADMIN_SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => ADMIN_SESSION_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function admin_remember_secret(): string
{
    return hash('sha256', __DIR__ . '|ecloths-admin-remember-v1');
}

function admin_token_for(array $admin): string
{
    $data = $admin['id'] . '|' . $admin['email'] . '|' . $admin['password'];
    return hash_hmac('sha256', $data, admin_remember_secret());
}

function admin_hydrate_session(array $admin): void
{
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_type'] = $admin['user_type'];
    $_SESSION['admin_image'] = $admin['image'];
}

function admin_set_remember_cookie(array $admin): void
{
    $payload = base64_encode($admin['id'] . '|' . admin_token_for($admin));
    setcookie(ADMIN_REMEMBER_COOKIE, $payload, [
        'expires' => time() + ADMIN_SESSION_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    setcookie('admin_email', $admin['email'], [
        'expires' => time() + ADMIN_SESSION_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function admin_clear_remember_cookie(): void
{
    foreach ([ADMIN_REMEMBER_COOKIE, 'admin_email', 'email'] as $cookie) {
        setcookie($cookie, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function admin_try_remember_login(mysqli $con): bool
{
    if (!empty($_SESSION['admin_email']) && ($_SESSION['admin_type'] ?? '') === 'admin') {
        return true;
    }

    $raw = $_COOKIE[ADMIN_REMEMBER_COOKIE] ?? '';
    if ($raw === '') {
        return false;
    }

    $decoded = base64_decode($raw, true);
    if ($decoded === false || !str_contains($decoded, '|')) {
        admin_clear_remember_cookie();
        return false;
    }

    [$adminId, $token] = explode('|', $decoded, 2);
    $adminId = (int) $adminId;
    if ($adminId <= 0 || $token === '') {
        admin_clear_remember_cookie();
        return false;
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL AND user_type = 'admin' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$admin || !hash_equals(admin_token_for($admin), $token)) {
        admin_clear_remember_cookie();
        return false;
    }

    admin_hydrate_session($admin);
    admin_set_remember_cookie($admin);
    return true;
}

function require_admin(?mysqli $con = null, string $loginPath = '../Admin/Adminlogin.php'): mysqli
{
    admin_session_start();
    $con = $con ?: db_connect();

    if (!admin_try_remember_login($con)) {
        header("Location: $loginPath");
        exit();
    }

    return $con;
}
