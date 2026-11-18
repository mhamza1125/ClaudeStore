<?php

/**
 * Browser deployment endpoint. GET shows a password form; POST with the deploy token
 * runs the deployment immediately. See deploy/README.md for setup.
 *
 * Responses never contain command output, paths or configuration — details go to the
 * private deploy log outside the web root.
 */

declare(strict_types=1);

require dirname(__DIR__).'/deploy/Deployer.php';

$root = dirname(__DIR__);
$privateDir = getenv('DEPLOY_PRIVATE_DIR') ?: dirname($root).'/.deploy';
$deployer = Deployer::fromPrivateDir($root, $privateDir);

header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

$respond = function (int $code, string $title, string $message = '', array $result = []): never {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $icons = ['ok' => '✓', 'skipped' => '–', 'warning' => '!', 'failed' => '✗'];
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">',
        '<title>Deploy</title><style>body{font:16px/1.5 system-ui,sans-serif;max-width:32rem;margin:3rem auto;padding:0 1rem;color:#222;background:#faf8f5}',
        'input[type=password]{width:100%;padding:.6rem;font:inherit;box-sizing:border-box}button{margin-top:1rem;padding:.6rem 1.2rem;font:inherit;cursor:pointer}',
        'label{display:block;margin-top:.6rem}.success{color:#2d6a2d}.failed{color:#a12a1f}.warn{background:#fff3d6;padding:.5rem .75rem;border-radius:.4rem}ul{padding-left:1.2rem}</style></head><body>';
    echo '<h1>', $e($title), '</h1>';
    if ($message !== '') {
        echo '<p class="', $e($result['status'] ?? ''), '">', $e($message), '</p>';
    }
    if (! empty($result['steps'])) {
        echo '<ul>';
        foreach ($result['steps'] as [$step, $state]) {
            echo '<li>', $e($icons[$state] ?? '?'), ' ', $e($step), $state === 'skipped' ? ' (skipped)' : '', '</li>';
        }
        echo '</ul>';
    }
    foreach ($result['warnings'] ?? [] as $w) {
        echo '<p class="warn">', $e($w), '</p>';
    }
    echo '<form method="post" autocomplete="off"><label>Deploy token<input type="password" name="token" required autofocus></label>',
        '<label><input type="checkbox" name="migrate" value="1"> Run database migrations</label>',
        '<label><input type="checkbox" name="seed_roles" value="1"> Sync roles &amp; permissions (safe seeder)</label>',
        '<button type="submit">Deploy now</button></form>';
    echo '</body></html>';
    exit;
};

if (! $deployer->isConfigured()) {
    $respond(503, 'Deployment is not configured');
}

$https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTPS'] ?? '') === '1'
    || ($_SERVER['SERVER_PORT'] ?? '') === '443'
    || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
if ($deployer->config['require_https'] && ! $https) {
    $respond(403, 'HTTPS required', 'Open this page over https://.');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET' || $method === 'HEAD') {
    $respond(200, 'Deploy');
}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    $respond(405, 'Method not allowed');
}

$token = is_string($_POST['token'] ?? null) ? $_POST['token'] : '';
switch ($deployer->authorize($_SERVER['REMOTE_ADDR'] ?? '', $token)) {
    case 'ok':
        break;
    case 'locked':
        $respond(429, 'Too many attempts', 'Try again later.');
    default:
        $respond(403, 'Unauthorized', 'Invalid token.');
}

// Keep going if the browser disconnects or the request outlives the proxy timeout.
ignore_user_abort(true);
@set_time_limit(0);

$result = $deployer->deploy([
    'migrate' => ($_POST['migrate'] ?? '') === '1',
    'seed_roles' => ($_POST['seed_roles'] ?? '') === '1',
]);

match ($result['status']) {
    'success' => $respond(200, 'Deployment succeeded', $result['message'], $result),
    'busy' => $respond(409, 'Deployment in progress', $result['message'], $result),
    default => $respond(500, 'Deployment failed', $result['message'], $result),
};
