<?php

/**
 * Browser-triggered deployment for shared hosting (see deploy/README.md).
 *
 * Deliberately framework-free: it must keep working when a bad deploy leaves Laravel
 * or vendor/ broken, so that the fix can be deployed. Commands run without a shell
 * (proc_open with an argument array), so no user input can reach a command line.
 *
 * Secrets and state live in a private directory outside the web root:
 *   token.hash     password_hash() of the deploy token (created by deploy/set-token.php)
 *   config.php     optional overrides of Deployer::DEFAULTS
 *   attempts.json  failed-login counters per IP
 *   deploy.lock    flock() target preventing concurrent deployments
 *   logs/          one log file per deployment (0600)
 */
final class Deployer
{
    public const DEFAULTS = [
        'remote' => 'origin',
        'branch' => 'claude/dreamy-pasteur-s1ejbf',
        'php' => 'php',                      // CLI PHP, e.g. /usr/bin/php or /opt/alt/php83/usr/bin/php
        'composer' => 'composer',
        'npm' => 'npm',
        'build_assets' => 'auto',            // auto: when front-end files changed and npm exists | always | never
        'path' => '/usr/local/bin:/usr/bin:/bin',
        'home' => null,                      // null: $HOME, else three levels above the project (~/domains/site/public_html)
        'require_https' => true,
        'allowed_ips' => [],                 // empty: any IP (the token is still required)
        'max_failures' => 5,
        'lockout_seconds' => 900,
        'step_timeout' => 600,
        'keep_logs' => 30,
    ];

    public readonly array $config;

    private array $steps = [];

    private array $warnings = [];

    /** @var resource|null */
    private $log = null;

    public function __construct(public readonly string $root, public readonly string $privateDir, array $config = [])
    {
        $this->config = array_replace(self::DEFAULTS, $config);
    }

    public static function fromPrivateDir(string $root, string $privateDir): self
    {
        $file = $privateDir.'/config.php';
        $config = is_file($file) ? (array) require $file : [];

        return new self($root, $privateDir, $config);
    }

    public function isConfigured(): bool
    {
        return is_file($this->privateDir.'/token.hash');
    }

    public static function storeToken(string $privateDir, string $token): void
    {
        self::ensureDir($privateDir);
        $file = $privateDir.'/token.hash';
        file_put_contents($file, password_hash($token, PASSWORD_DEFAULT), LOCK_EX);
        chmod($file, 0600);
    }

    // ---------------------------------------------------------------- authentication

    /** @return 'ok'|'denied'|'locked'|'ip' */
    public function authorize(string $ip, string $token): string
    {
        if ($this->config['allowed_ips'] && ! in_array($ip, $this->config['allowed_ips'], true)) {
            return 'ip';
        }

        return $this->withAttempts(function (array &$attempts) use ($ip, $token) {
            $now = time();
            $recent = array_values(array_filter($attempts[$ip] ?? [], fn ($t) => $t > $now - $this->config['lockout_seconds']));
            if (count($recent) >= $this->config['max_failures']) {
                $attempts[$ip] = $recent;

                return 'locked';
            }

            $hash = trim((string) @file_get_contents($this->privateDir.'/token.hash'));
            $valid = $hash !== '' && $token !== '' && strlen($token) <= 256 && password_verify($token, $hash);
            if ($valid) {
                unset($attempts[$ip]);

                return 'ok';
            }

            $recent[] = $now;
            $attempts[$ip] = $recent;

            return 'denied';
        });
    }

    private function withAttempts(callable $fn): string
    {
        self::ensureDir($this->privateDir);
        $fh = fopen($this->privateDir.'/attempts.json', 'c+');
        flock($fh, LOCK_EX);
        $attempts = json_decode(stream_get_contents($fh) ?: '[]', true) ?: [];
        $result = $fn($attempts);
        // Drop stale entries so the file cannot grow without bound.
        $cutoff = time() - $this->config['lockout_seconds'];
        $attempts = array_filter(array_map(fn ($ts) => array_values(array_filter($ts, fn ($t) => $t > $cutoff)), $attempts));
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($attempts));
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        @chmod($this->privateDir.'/attempts.json', 0600);

        return $result;
    }

    // ---------------------------------------------------------------- deployment

    /**
     * @param  array{migrate?: bool, seed_roles?: bool}  $options
     * @return array{status: 'success'|'failed'|'busy', message: string, steps: list<array{0: string, 1: string}>, warnings: list<string>, from: ?string, to: ?string}
     */
    public function deploy(array $options = []): array
    {
        self::ensureDir($this->privateDir);
        $lock = fopen($this->privateDir.'/deploy.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return $this->result('busy', 'A deployment is already running. Try again in a minute.');
        }

        $this->openLog();
        $from = $to = null;
        try {
            if (! function_exists('proc_open')) {
                throw new DeployFailure('Preflight', 'This server does not allow PHP to run commands (proc_open is disabled).');
            }
            if (! is_dir($this->root.'/.git')) {
                throw new DeployFailure('Preflight', 'The project directory is not a Git checkout.');
            }

            $branch = trim($this->must('Check branch', ['git', 'rev-parse', '--abbrev-ref', 'HEAD']));
            if ($branch !== $this->config['branch']) {
                throw new DeployFailure('Check branch', "The server is on a different branch than {$this->config['branch']}.");
            }
            if (trim($this->must('Check local changes', ['git', 'status', '--porcelain', '--untracked-files=no'])) !== '') {
                throw new DeployFailure('Check local changes', 'Tracked files were modified on the server. Nothing was changed; review them over SSH (git status).');
            }

            $from = trim($this->must('Read current commit', ['git', 'rev-parse', 'HEAD']));
            // --ff-only: never creates merge commits or rewrites anything; refuses if histories diverged.
            $this->must('git pull', ['git', 'pull', '--ff-only', $this->config['remote'], $this->config['branch']]);
            $to = trim($this->must('Read new commit', ['git', 'rev-parse', 'HEAD']));
            $changed = $from === $to ? [] : array_filter(explode("\n", $this->must('List changed files', ['git', 'diff', '--name-only', $from, $to])));
            $this->log("Commits: {$from} -> {$to}; ".count($changed).' files changed');

            if (! is_file($this->root.'/vendor/autoload.php') || in_array('composer.lock', $changed, true)) {
                $this->must('composer install', [$this->config['composer'], 'install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress']);
            } else {
                $this->skip('composer install', 'composer.lock unchanged');
            }

            $this->buildAssets($changed);

            $this->must('artisan optimize:clear', [$this->config['php'], 'artisan', 'optimize:clear']);

            if (! empty($options['migrate'])) {
                $this->must('artisan migrate', [$this->config['php'], 'artisan', 'migrate', '--force', '--no-interaction']);
            }
            if (! empty($options['seed_roles'])) {
                $this->must('Seed roles & permissions', [$this->config['php'], 'artisan', 'db:seed', '--class=RolesAndPermissionsSeeder', '--force', '--no-interaction']);
            }

            $this->must('artisan optimize', [$this->config['php'], 'artisan', 'optimize']);

            if (! file_exists($this->root.'/public/storage')) {
                $this->must('artisan storage:link', [$this->config['php'], 'artisan', 'storage:link']);
            }
            $this->attempt('artisan queue:restart', [$this->config['php'], 'artisan', 'queue:restart']);

            if (empty($options['migrate'])) {
                [$code, $out] = $this->exec([$this->config['php'], 'artisan', 'migrate:status', '--pending', '--no-ansi']);
                $pending = $code === 0 ? preg_match_all('/\bPending\b/', $out) : 0;
                if ($pending > 0) {
                    $this->warnings[] = "{$pending} database migration(s) pending. Deploy again with “Run migrations” ticked, or run php artisan migrate --force over SSH.";
                }
            }

            $message = $from === $to ? 'Already up to date — caches rebuilt.' : 'Deployed '.substr($to, 0, 7).'.';

            return $this->result('success', $message, $from, $to);
        } catch (DeployFailure $e) {
            $this->steps[] = [$e->step, 'failed'];
            $this->log("FAILED at {$e->step}: {$e->getMessage()}");
            $hint = ($from && $to && $from !== $to) ? ' The new code is in place; the previous commit was '.substr($from, 0, 7).' (see deploy/README.md to roll back).' : '';

            return $this->result('failed', $e->getMessage().$hint, $from, $to);
        } finally {
            $this->closeLog();
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function buildAssets(array $changed): void
    {
        $mode = $this->config['build_assets'];
        $missing = ! is_file($this->root.'/public/build/manifest.json');
        $frontEnd = (bool) array_filter($changed, fn ($f) => str_starts_with($f, 'resources/') || in_array($f, ['package.json', 'package-lock.json', 'vite.config.js'], true));

        if ($mode === 'never' || ($mode === 'auto' && ! $missing && ! $frontEnd)) {
            $this->skip('Build assets', $mode === 'never' ? 'disabled' : 'no front-end changes');

            return;
        }
        [$code] = $this->exec([$this->config['npm'], '--version']);
        if ($code !== 0) {
            $this->skip('Build assets', 'npm not available');
            $this->warnings[] = 'Front-end files changed but npm is not available on this server, so CSS/JS were not rebuilt. Run npm run build locally and upload public/build.';

            return;
        }
        $this->must('npm ci', [$this->config['npm'], 'ci', '--no-audit', '--no-fund']);
        $this->must('npm run build', [$this->config['npm'], 'run', 'build']);
    }

    // ---------------------------------------------------------------- process helpers

    private function must(string $step, array $command): string
    {
        [$code, $out] = $this->exec($command);
        if ($code !== 0) {
            throw new DeployFailure($step, "Step “{$step}” failed (exit code {$code}).");
        }
        $this->steps[] = [$step, 'ok'];

        return $out;
    }

    /** Non-critical step: a failure becomes a warning. */
    private function attempt(string $step, array $command): void
    {
        [$code] = $this->exec($command);
        $this->steps[] = [$step, $code === 0 ? 'ok' : 'warning'];
        if ($code !== 0) {
            $this->warnings[] = "“{$step}” failed (exit code {$code}); see the deploy log.";
        }
    }

    private function skip(string $step, string $why): void
    {
        $this->steps[] = [$step, 'skipped'];
        $this->log("SKIP {$step}: {$why}");
    }

    /** @return array{0: int, 1: string} exit code and combined output */
    private function exec(array $command): array
    {
        $this->log('$ '.implode(' ', $command));
        $binary = $this->resolve($command[0]);
        if ($binary === null) {
            $this->log("  {$command[0]}: not found in configured path");

            return [127, ''];
        }
        $command[0] = $binary;
        $outFile = tempnam(sys_get_temp_dir(), 'deploy');
        $home = $this->config['home'] ?: (getenv('HOME') ?: dirname($this->root, 3));
        $env = [
            'PATH' => $this->config['path'],
            'HOME' => $home,
            'COMPOSER_HOME' => $home.'/.composer',
            'COMPOSER_MEMORY_LIMIT' => '-1',
            'GIT_TERMINAL_PROMPT' => '0',     // never wait for a password prompt
            'LANG' => 'C.UTF-8',
        ];

        $proc = @proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $outFile, 'w'], 2 => ['redirect', 1]], $pipes, $this->root, $env);
        if (! is_resource($proc)) {
            @unlink($outFile);
            $this->log('  could not start process');

            return [127, ''];
        }

        $deadline = microtime(true) + $this->config['step_timeout'];
        $code = -1;
        while (true) {
            $status = proc_get_status($proc);
            if (! $status['running']) {
                $code = $status['exitcode'];
                break;
            }
            if (microtime(true) > $deadline) {
                proc_terminate($proc, 9);
                $code = 124;
                $this->log('  timed out');
                break;
            }
            usleep(100_000);
        }
        proc_close($proc);
        $out = (string) file_get_contents($outFile);
        @unlink($outFile);
        $this->log(self::redact($out)."  [exit {$code}]");

        return [$code, $out];
    }

    /** Absolute path of a binary. The web server's own PATH may be empty, so search the configured one. */
    private function resolve(string $binary): ?string
    {
        if (str_contains($binary, '/')) {
            return is_executable($binary) ? $binary : null;
        }
        foreach (explode(':', $this->config['path']) as $dir) {
            if ($dir !== '' && is_file($dir.'/'.$binary) && is_executable($dir.'/'.$binary)) {
                return $dir.'/'.$binary;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------- logging & results

    /** Remove credentials embedded in URLs (https://user:token@host) from command output. */
    public static function redact(string $text): string
    {
        return preg_replace('#(\w+://)[^/@\s]+@#', '$1***@', $text);
    }

    private function openLog(): void
    {
        $dir = $this->privateDir.'/logs';
        self::ensureDir($dir);
        $file = $dir.'/deploy-'.date('Ymd-His').'-'.bin2hex(random_bytes(2)).'.log';
        $this->log = fopen($file, 'a');
        @chmod($file, 0600);
        $this->log('Deployment started '.date('c'));

        $logs = glob($dir.'/deploy-*.log') ?: [];
        sort($logs);
        foreach (array_slice($logs, 0, max(0, count($logs) - $this->config['keep_logs'])) as $old) {
            @unlink($old);
        }
    }

    private function log(string $line): void
    {
        if ($this->log) {
            fwrite($this->log, rtrim($line)."\n");
        }
    }

    private function closeLog(): void
    {
        if ($this->log) {
            $this->log('Finished '.date('c'));
            fclose($this->log);
            $this->log = null;
        }
    }

    private function result(string $status, string $message, ?string $from = null, ?string $to = null): array
    {
        foreach ($this->warnings as $w) {
            $this->log("WARNING: {$w}");
        }

        return ['status' => $status, 'message' => $message, 'steps' => $this->steps, 'warnings' => $this->warnings, 'from' => $from, 'to' => $to];
    }

    private static function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
    }
}

final class DeployFailure extends RuntimeException
{
    public function __construct(public readonly string $step, string $message)
    {
        parent::__construct($message);
    }
}
