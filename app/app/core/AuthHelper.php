<?php

/**
 * Transitional switch for legacy $_SESSION['USER'] compatibility.
 * Keep false to rely on scalar auth keys only.
 */
function keepLegacyUserSessionObject(): bool
{
    return false;
}

/**
 * Keep scalar auth session keys aligned with legacy USER object sessions.
 */
function authSyncFromLegacySession(): void
{
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        return;
    }

    // If normalized scalar keys already exist, ensure logged_in is normalized too.
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
        $_SESSION['logged_in'] = true;
    }

    $hasLegacyUser = isset($_SESSION['USER']);
    $nameMissing = empty($_SESSION['user_name']);

    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
        if (!$hasLegacyUser || !$nameMissing) {
            return;
        }
    }

    if (!$hasLegacyUser) {
        return;
    }

    $legacyUser = $_SESSION['USER'];

    if (is_array($legacyUser)) {
        $userId = (int)($legacyUser['id'] ?? 0);
        $role = strtolower(trim((string)($legacyUser['role'] ?? '')));
        $name = (string)($legacyUser['name'] ?? ($_SESSION['user_name'] ?? ''));
        $email = (string)($legacyUser['email'] ?? ($_SESSION['user_email'] ?? ''));
        $location = (string)($legacyUser['location'] ?? ($_SESSION['user_location'] ?? ''));
        // Legacy array payload is only used for one-time scalar sync.
    } elseif (is_object($legacyUser)) {
        $userId = (int)($legacyUser->id ?? 0);
        $role = strtolower(trim((string)($legacyUser->role ?? '')));
        $name = (string)($legacyUser->name ?? ($_SESSION['user_name'] ?? ''));
        $email = (string)($legacyUser->email ?? ($_SESSION['user_email'] ?? ''));
        $location = (string)($legacyUser->location ?? ($_SESSION['user_location'] ?? ''));
    } else {
        return;
    }

    if ($userId <= 0 || $role === '') {
        return;
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_location'] = $location;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;

    if (!keepLegacyUserSessionObject()) {
        unset($_SESSION['USER']);
    }
}

/**
 * Store authenticated user details using the normalized session shape.
 */
function setAuthSession(object $user): void
{
    $userId = (int)($user->id ?? 0);
    $role = strtolower(trim((string)($user->role ?? '')));

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = (string)($user->name ?? '');
    $_SESSION['user_email'] = (string)($user->email ?? '');
    $_SESSION['user_location'] = (string)($user->location ?? '');
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = $userId > 0 && $role !== '';

    // New logins should rely on scalar keys only.
    if (keepLegacyUserSessionObject()) {
        $_SESSION['USER'] = $user;
    }
}

function setAuthUserName(string $name): void
{
    $_SESSION['user_name'] = $name;

    if (keepLegacyUserSessionObject() && isset($_SESSION['USER']) && is_object($_SESSION['USER'])) {
        $_SESSION['USER']->name = $name;
    }
}

function setAuthUserEmail(string $email): void
{
    $_SESSION['user_email'] = $email;

    if (keepLegacyUserSessionObject() && isset($_SESSION['USER']) && is_object($_SESSION['USER'])) {
        $_SESSION['USER']->email = $email;
    }
}

function authUserId(): int
{
    authSyncFromLegacySession();
    return (int)($_SESSION['user_id'] ?? 0);
}

function authUserName(): string
{
    authSyncFromLegacySession();
    return trim((string)($_SESSION['user_name'] ?? ''));
}

function authUserEmail(): string
{
    authSyncFromLegacySession();
    return trim((string)($_SESSION['user_email'] ?? ''));
}

function authUserLocation(): string
{
    authSyncFromLegacySession();
    return trim((string)($_SESSION['user_location'] ?? ''));
}

function authUserRole(): string
{
    authSyncFromLegacySession();
    return strtolower(trim((string)($_SESSION['role'] ?? '')));
}

function authUserInitials(): string
{
    $name = authUserName();
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $first = strtoupper(substr((string)($parts[0] ?? ''), 0, 1));
    $second = strtoupper(substr((string)($parts[1] ?? ''), 0, 1));
    $initials = $first . $second;

    return $initials !== '' ? $initials : 'U';
}

function isLoggedIn(): bool
{
    authSyncFromLegacySession();

    return !empty($_SESSION['logged_in']) && authUserId() > 0;
}

function hasRole(string $role): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    return authUserRole() === strtolower(trim($role));
}

/**
 * @param array<int, string> $roles
 */
function hasAnyRole(array $roles): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    $currentRole = authUserRole();
    foreach ($roles as $role) {
        if ($currentRole === strtolower(trim((string)$role))) {
            return true;
        }
    }

    return false;
}

function authDashboardPath(?string $role = null): string
{
    $resolvedRole = strtolower(trim((string)($role ?? authUserRole())));

    switch ($resolvedRole) {
        case 'buyer':
            return 'buyerdashboard';
        case 'farmer':
            return 'farmerdashboard';
        case 'transporter':
            return 'transporterdashboard';
        case 'admin':
        case 'superadmin':
            return 'admindashboard';
        default:
            return 'home';
    }
}

function authProfilePath(?string $role = null): string
{
    $resolvedRole = strtolower(trim((string)($role ?? authUserRole())));

    switch ($resolvedRole) {
        case 'buyer':
            return 'buyerprofile';
        case 'farmer':
            return 'farmerprofile';
        case 'transporter':
            return 'transporterprofile';
        case 'admin':
        case 'superadmin':
            return 'admindashboard';
        default:
            return authDashboardPath($resolvedRole);
    }
}

/**
 * @param array{json?:bool,message?:string} $options
 */
function requireLogin(array $options = []): bool
{
    if (isLoggedIn()) {
        return true;
    }

    if (!empty($options['json'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => $options['message'] ?? 'Unauthorized',
        ]);
        return false;
    }

    redirect('login');
    return false;
}

/**
 * @param string|array<int, string> $role
 * @param array{json?:bool,message?:string} $options
 */
function requireRole($role, array $options = []): bool
{
    $allowedRoles = is_array($role) ? $role : [$role];

    if (!requireLogin($options)) {
        return false;
    }

    if (hasAnyRole($allowedRoles)) {
        return true;
    }

    if (!empty($options['json'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => $options['message'] ?? 'Forbidden',
        ]);
        return false;
    }

    redirect(authDashboardPath());
    return false;
}

function redirectIfLoggedIn(): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    redirect(authDashboardPath());
    return true;
}

function clearAuthSession(): void
{
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        return;
    }

    $_SESSION = [];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            !empty($params['secure']),
            !empty($params['httponly'])
        );
    }

    session_destroy();
}

authSyncFromLegacySession();
