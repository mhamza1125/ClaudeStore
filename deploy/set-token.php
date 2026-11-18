<?php

/**
 * Create or rotate the browser deploy token. Run over SSH from the project root:
 *
 *   php deploy/set-token.php
 *
 * Prints a new random token once (store it in your password manager) and saves only
 * its hash outside the web root. Running it again replaces the old token.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__.'/Deployer.php';

$root = dirname(__DIR__);
$privateDir = getenv('DEPLOY_PRIVATE_DIR') ?: dirname($root).'/.deploy';
$token = bin2hex(random_bytes(32));

Deployer::storeToken($privateDir, $token);

fwrite(STDOUT, "Deploy token (shown once — save it in your password manager):\n\n  {$token}\n\n");
fwrite(STDOUT, "Its hash was saved to {$privateDir}/token.hash (mode 0600). Any previous token no longer works.\n");
