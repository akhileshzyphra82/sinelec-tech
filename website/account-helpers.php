<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../common/functions.php';

function sinelec_get_signed_in_user(): array
{
    $user = $_SESSION['sinelec_user'] ?? [];
    return is_array($user) ? $user : [];
}

function sinelec_is_signed_in(): bool
{
    $user = sinelec_get_signed_in_user();
    return !empty($user['USER_ID']);
}

function sinelec_account_first_name(array $user): string
{
    $name = trim((string)($user['NAME'] ?? ''));
    if ($name === '') {
        return 'Guest';
    }

    $parts = preg_split('/\s+/', $name);
    return $parts[0] ?: 'Guest';
}

function sinelec_account_nav_items(): array
{
    return [
        'profile' => ['label' => 'Profile', 'href' => 'profile', 'icon' => 'user'],
        'my-orders' => ['label' => 'My Order', 'href' => 'my-orders', 'icon' => 'box'],
        'my-list'   => ['label' => 'My List',  'href' => 'my-list',   'icon' => 'list'],
        'delivery-address' => ['label' => 'My Address',     'href' => 'delivery-address', 'icon' => 'pin'],
        'support'          => ['label' => 'Support & Help', 'href' => 'my-tickets',       'icon' => 'support'],
        'change-password'  => ['label' => 'Change Password','href' => 'change-password',  'icon' => 'lock'],
        'logout' => ['label' => 'Logout', 'href' => 'service?urlstring=' . EncryptURL('action=Logout'), 'icon' => 'logout'],
    ];
}

function sinelec_account_icon(string $icon): string
{
    $icons = [
        'user' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'box' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
        'pin' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
        'lock' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 1 1 10 0v3"/></svg>',
        'list'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1.5" fill="currentColor" stroke="none"/></svg>',
        'support' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
        'logout' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];

    return $icons[$icon] ?? $icons['user'];
}

function sinelec_render_account_nav(string $current): void
{
    $items = sinelec_account_nav_items();
    echo '<aside class="account-sidebar">';
    echo '<div class="account-sidebar-title">Account</div>';
    echo '<nav class="account-nav" aria-label="Account navigation">';
    foreach ($items as $key => $item) {
        $active = $key === $current ? ' is-active' : '';
        $logoutClass = $key === 'logout' ? ' is-logout' : '';
        echo '<a class="account-nav-link' . $active . $logoutClass . '" href="' . htmlspecialchars($item['href']) . '">';
        echo '<span class="account-nav-icon">' . sinelec_account_icon($item['icon']) . '</span>';
        echo '<span>' . htmlspecialchars($item['label']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
    echo '</aside>';
}

function sinelec_require_login(): array
{
    $user = sinelec_get_signed_in_user();
    if (!empty($user['USER_ID'])) {
        return $user;
    }

    sinelec_set_flash('warn', 'Please sign in to access your account.');
    header('location:index');
    exit();
}
