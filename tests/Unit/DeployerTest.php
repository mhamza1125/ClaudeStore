<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Browser deployment (deploy/Deployer.php, public/deploy.php) against throwaway Git
 * repositories. php/composer/npm are replaced by a stub that records its arguments.
 */
class DeployerTest extends TestCase
{
    private const BRANCH = 'claude/dreamy-pasteur-s1ejbf';

    private string $tmp;

    private string $remote;

    private string $project;

    private string $private;

    private string $calls;

    private ?Process $server = null;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/deploy/Deployer.php';

        $this->tmp = sys_get_temp_dir().'/deployer-test-'.bin2hex(random_bytes(4));
        mkdir($this->tmp);
        $this->remote = $this->tmp.'/remote.git';
        $this->project = $this->tmp.'/site/public_html';
        $this->private = $this->tmp.'/site/.deploy';
        $this->calls = $this->tmp.'/calls.log';

        $stub = $this->tmp.'/stub';
        file_put_contents($stub, "#!/bin/sh\necho \"\$(basename \"\$0\") \$*\" >> {$this->calls}\ncase \"\$*\" in *migrate:status*) echo '  2026_01_01_000000_example ........ Pending' ;; esac\n[ -f {$this->tmp}/fail-\$1 ] && exit 3\nexit 0\n");
        chmod($stub, 0755);
        foreach (['php', 'composer', 'npm'] as $bin) {
            symlink($stub, $this->tmp.'/'.$bin);
        }

        $this->git($this->tmp, 'init', '--bare', '-q', '-b', self::BRANCH, $this->remote);
        $seed = $this->tmp.'/seed';
        $this->git($this->tmp, 'clone', '-q', $this->remote, $seed);
        $this->git($seed, 'checkout', '-q', '-b', self::BRANCH);
        file_put_contents($seed.'/.gitignore', "/vendor\n/public/build\n.env\n");
        file_put_contents($seed.'/composer.lock', "{}\n");
        mkdir($seed.'/public');
        mkdir($seed.'/deploy');
        copy(dirname(__DIR__, 2).'/public/deploy.php', $seed.'/public/deploy.php');
        copy(dirname(__DIR__, 2).'/deploy/Deployer.php', $seed.'/deploy/Deployer.php');
        $this->commit($seed, 'initial');
        $this->git($seed, 'push', '-q', 'origin', self::BRANCH);

        mkdir(dirname($this->project), 0777, true);
        $this->git($this->tmp, 'clone', '-q', '-b', self::BRANCH, $this->remote, $this->project);
        // Server-only data that a deployment must never touch.
        file_put_contents($this->project.'/.env', "APP_KEY=secret-value\n");
        mkdir($this->project.'/vendor');
        file_put_contents($this->project.'/vendor/autoload.php', '<?php');
        mkdir($this->project.'/public/build', 0777, true);
        file_put_contents($this->project.'/public/build/manifest.json', '{}');
        mkdir($this->project.'/public/storage');

        \Deployer::storeToken($this->private, 'correct-token');
        file_put_contents($this->private.'/config.php', '<?php return '.var_export([
            'php' => $this->tmp.'/php', 'composer' => $this->tmp.'/composer', 'npm' => $this->tmp.'/npm',
            'require_https' => false, 'max_failures' => 3,
        ], true).';');
    }

    protected function tearDown(): void
    {
        $this->server?->stop(0);
        (new Process(['rm', '-rf', $this->tmp]))->run();
    }

    private function git(string $cwd, string ...$args): string
    {
        $p = new Process(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', ...$args], $cwd);
        $p->mustRun();

        return trim($p->getOutput());
    }

    private function commit(string $dir, string $message): void
    {
        $this->git($dir, 'add', '-A');
        $this->git($dir, 'commit', '-q', '-m', $message);
    }

    /** Push a new commit to the remote, as a developer would. */
    private function pushChange(array $files): string
    {
        $dev = $this->tmp.'/dev';
        if (! is_dir($dev)) {
            $this->git($this->tmp, 'clone', '-q', '-b', self::BRANCH, $this->remote, $dev);
        }
        foreach ($files as $path => $content) {
            is_dir(dirname($dev.'/'.$path)) || mkdir(dirname($dev.'/'.$path), 0777, true);
            file_put_contents($dev.'/'.$path, $content);
        }
        $this->commit($dev, 'change');
        $this->git($dev, 'push', '-q', 'origin', self::BRANCH);

        return $this->git($dev, 'rev-parse', 'HEAD');
    }

    private function deployer(): \Deployer
    {
        return \Deployer::fromPrivateDir($this->project, $this->private);
    }

    private function calls(): string
    {
        return is_file($this->calls) ? file_get_contents($this->calls) : '';
    }

    public function test_pulls_fast_forward_and_runs_the_laravel_steps_without_touching_server_files(): void
    {
        $head = $this->pushChange(['app/Example.php' => '<?php // new', 'composer.lock' => "{\"changed\":true}\n"]);

        $result = $this->deployer()->deploy();

        $this->assertSame('success', $result['status'], $result['message']);
        $this->assertSame($head, $this->git($this->project, 'rev-parse', 'HEAD'));
        $this->assertFileExists($this->project.'/app/Example.php');
        $this->assertSame("APP_KEY=secret-value\n", file_get_contents($this->project.'/.env'));
        $this->assertFileExists($this->project.'/vendor/autoload.php');
        $this->assertFileExists($this->project.'/public/build/manifest.json');

        $calls = $this->calls();
        $this->assertStringContainsString('composer install --no-dev --optimize-autoloader', $calls);
        $this->assertStringContainsString('php artisan optimize:clear', $calls);
        $this->assertStringContainsString('php artisan optimize', $calls);
        $this->assertStringNotContainsString('migrate --force', $calls);
        $this->assertStringNotContainsString('db:seed', $calls);
        $this->assertStringNotContainsString('npm', $calls, 'no front-end changes, so no asset build');
        $this->assertStringContainsString('1 database migration(s) pending', implode(' ', $result['warnings']));
    }

    public function test_skips_composer_when_the_lock_file_is_unchanged_and_builds_assets_when_front_end_changed(): void
    {
        $this->pushChange(['resources/css/app.css' => 'body{}']);

        $result = $this->deployer()->deploy();

        $this->assertSame('success', $result['status'], $result['message']);
        $this->assertContains(['composer install', 'skipped'], $result['steps']);
        $this->assertStringContainsString("npm ci --no-audit --no-fund\nnpm run build", $this->calls());
    }

    public function test_missing_npm_is_a_warning_not_a_failure(): void
    {
        file_put_contents($this->private.'/config.php', '<?php return '.var_export([
            'php' => $this->tmp.'/php', 'composer' => $this->tmp.'/composer', 'npm' => $this->tmp.'/no-such-npm', 'require_https' => false,
        ], true).';');
        $this->pushChange(['resources/js/app.js' => '//']);

        $result = $this->deployer()->deploy();

        $this->assertSame('success', $result['status'], $result['message']);
        $this->assertContains(['Build assets', 'skipped'], $result['steps']);
        $this->assertStringContainsString('npm is not available', implode(' ', $result['warnings']));
    }

    public function test_migrations_and_the_safe_seeder_run_only_when_requested(): void
    {
        $result = $this->deployer()->deploy(['migrate' => true, 'seed_roles' => true]);

        $this->assertSame('success', $result['status'], $result['message']);
        $this->assertStringContainsString('php artisan migrate --force --no-interaction', $this->calls());
        $this->assertStringContainsString('php artisan db:seed --class=RolesAndPermissionsSeeder --force', $this->calls());
        $this->assertStringNotContainsString('migrate:status', $this->calls());
        $this->assertSame('Already up to date — caches rebuilt.', $result['message']);
    }

    public function test_refuses_when_tracked_files_were_modified_on_the_server(): void
    {
        $this->pushChange(['app/Example.php' => '<?php']);
        file_put_contents($this->project.'/composer.lock', "{\"edited on server\":1}\n");

        $result = $this->deployer()->deploy();

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('Tracked files were modified', $result['message']);
        $this->assertSame("{\"edited on server\":1}\n", file_get_contents($this->project.'/composer.lock'));
        $this->assertFileDoesNotExist($this->project.'/app/Example.php');
        $this->assertSame('', $this->calls());
    }

    public function test_refuses_a_diverged_history_instead_of_merging_or_resetting(): void
    {
        $this->pushChange(['app/Remote.php' => '<?php']);
        file_put_contents($this->project.'/local.txt', 'server commit');
        $this->commit($this->project, 'server-side commit');
        $local = $this->git($this->project, 'rev-parse', 'HEAD');

        $result = $this->deployer()->deploy();

        $this->assertSame('failed', $result['status']);
        $this->assertContains(['git pull', 'failed'], $result['steps']);
        $this->assertSame($local, $this->git($this->project, 'rev-parse', 'HEAD'));
    }

    public function test_refuses_the_wrong_branch(): void
    {
        $this->git($this->project, 'checkout', '-q', '-b', 'other');

        $result = $this->deployer()->deploy();

        $this->assertSame('failed', $result['status']);
        $this->assertContains(['Check branch', 'failed'], $result['steps']);
    }

    public function test_a_failing_step_stops_the_deployment(): void
    {
        $this->pushChange(['composer.lock' => "{\"v\":2}\n"]);
        touch($this->tmp.'/fail-install');   // stub exits 3 for "composer install"

        $result = $this->deployer()->deploy();

        $this->assertSame('failed', $result['status']);
        $this->assertContains(['composer install', 'failed'], $result['steps']);
        $this->assertStringNotContainsString('artisan optimize', $this->calls());
        $this->assertStringContainsString('previous commit was', $result['message']);
    }

    public function test_only_one_deployment_runs_at_a_time(): void
    {
        $lock = fopen($this->private.'/deploy.lock', 'c');
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));

        $result = $this->deployer()->deploy();

        $this->assertSame('busy', $result['status']);
        $this->assertSame('', $this->calls());
        flock($lock, LOCK_UN);
    }

    public function test_writes_a_private_log_and_redacts_url_credentials(): void
    {
        $this->deployer()->deploy();

        $logs = glob($this->private.'/logs/deploy-*.log');
        $this->assertCount(1, $logs);
        $this->assertSame('0600', substr(sprintf('%o', fileperms($logs[0])), -4));
        $this->assertStringContainsString('git pull --ff-only origin '.self::BRANCH, file_get_contents($logs[0]));
        $this->assertSame("fatal: https://***@github.com/x", \Deployer::redact('fatal: https://user:ghp_secret@github.com/x'));
    }

    public function test_token_checks_lock_out_repeated_failures(): void
    {
        $d = $this->deployer();
        $this->assertSame('denied', $d->authorize('1.2.3.4', 'wrong'));
        $this->assertSame('denied', $d->authorize('1.2.3.4', ''));
        $this->assertSame('ok', $d->authorize('1.2.3.4', 'correct-token'), 'success clears the counter');
        foreach (range(1, 3) as $_) {
            $this->assertSame('denied', $d->authorize('1.2.3.4', 'wrong'));
        }
        $this->assertSame('locked', $d->authorize('1.2.3.4', 'correct-token'));
        $this->assertSame('ok', $d->authorize('5.6.7.8', 'correct-token'));
        $this->assertStringNotContainsString('correct-token', file_get_contents($this->private.'/token.hash'));
    }

    // ------------------------------------------------------------ HTTP endpoint

    private function serve(): string
    {
        $port = 20000 + random_int(0, 20000);
        $this->server = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $this->project.'/public'], null, ['DEPLOY_PRIVATE_DIR' => $this->private]);
        $this->server->start();
        $deadline = microtime(true) + 5;
        set_error_handler(fn () => true);   // connection refused until the server is up
        try {
            while (microtime(true) < $deadline && ! fsockopen('127.0.0.1', $port)) {
                usleep(50_000);
            }
        } finally {
            restore_error_handler();
        }

        return "http://127.0.0.1:{$port}/deploy.php";
    }

    /** @return array{0: int, 1: string, 2: list<string>} */
    private function request(string $url, string $method = 'GET', array $form = []): array
    {
        $context = stream_context_create(['http' => [
            'method' => $method, 'ignore_errors' => true, 'timeout' => 30,
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($form),
        ]]);
        $body = file_get_contents($url, false, $context);
        preg_match('#HTTP/\S+ (\d+)#', $http_response_header[0], $m);

        return [(int) $m[1], $body, $http_response_header];
    }

    public function test_endpoint_requires_post_and_a_valid_token_and_leaks_nothing(): void
    {
        $url = $this->serve();

        [$code, $body, $headers] = $this->request($url);
        $this->assertSame(200, $code);
        $this->assertStringContainsString('type="password" name="token"', $body);
        $this->assertContains('Cache-Control: no-store, max-age=0', $headers);
        $this->assertContains('X-Frame-Options: DENY', $headers);

        $this->assertSame(405, $this->request($url, 'PUT')[0]);
        $this->assertSame(405, $this->request($url, 'DELETE')[0]);

        [$code, $body] = $this->request($url, 'POST', ['token' => 'wrong']);
        $this->assertSame(403, $code);
        $this->assertSame('', $this->calls());
        $this->assertSame(403, $this->request($url, 'POST', ['token' => ['array']])[0]);

        $this->assertSame(403, $this->request($url, 'POST', ['token' => 'wrong'])[0]);
        $this->assertSame(429, $this->request($url, 'POST', ['token' => 'correct-token'])[0], 'locked out after 3 failures');

        foreach ([$body] as $html) {
            $this->assertStringNotContainsString($this->tmp, $html);
        }
    }

    public function test_endpoint_deploys_with_the_right_token(): void
    {
        $url = $this->serve();
        $head = $this->pushChange(['app/Example.php' => '<?php']);

        [$code, $body] = $this->request($url, 'POST', ['token' => 'correct-token', 'migrate' => '1']);

        $this->assertSame(200, $code, $body);
        $this->assertStringContainsString('Deployment succeeded', $body);
        $this->assertStringContainsString('Deployed '.substr($head, 0, 7), $body);
        $this->assertStringContainsString('artisan migrate', $body);
        $this->assertStringNotContainsString($this->tmp, $body, 'no server paths in the response');
        $this->assertStringNotContainsString('secret-value', $body);
        $this->assertStringContainsString('php artisan migrate --force', $this->calls());
    }

    public function test_endpoint_is_disabled_until_a_token_is_set_and_enforces_https(): void
    {
        unlink($this->private.'/token.hash');
        $url = $this->serve();
        $this->assertSame(503, $this->request($url, 'POST', ['token' => 'anything'])[0]);

        \Deployer::storeToken($this->private, 'correct-token');
        file_put_contents($this->private.'/config.php', '<?php return ["require_https" => true];');
        [$code, $body] = $this->request($url, 'POST', ['token' => 'correct-token']);
        $this->assertSame(403, $code);
        $this->assertStringContainsString('HTTPS required', $body);
        $this->assertSame('', $this->calls());
    }
}
