<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SecurityCheckCommand extends Command
{
    protected $signature = 'app:security-check';

    protected $description = 'Verify PHP runtime version against PHP2Shell (CVE-2026-109618/109619) & PHPAfterShell (CVE-2026-126714) and check session hardening';

    /**
     * Minimum patched PHP versions per branch (November 19, 2026 security releases).
     */
    private const MIN_PATCHED_VERSIONS = [
        '8.2' => '8.2.36',
        '8.3' => '8.3.37',
        '8.4' => '8.4.28',
        '8.5' => '8.5.13',
        '8.6' => '8.6.0',
    ];

    public function handle(): int
    {
        $version = PHP_VERSION;
        $majorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $this->info("Checking PHP Runtime & Security Configuration...");
        $this->line("Current PHP Version : <comment>{$version}</comment>");
        $this->line("Session Driver      : <comment>" . config('session.driver') . "</comment>");
        $this->line("Upload Progress     : <comment>" . (ini_get('session.upload_progress.enabled') ? 'On' : 'Off') . "</comment>");

        if (version_compare($version, '8.2.0', '<')) {
            $this->error("FAIL: PHP {$version} is EOL and vulnerable to PHP2Shell & PHPAfterShell. Upgrade to >= 8.2.36, 8.3.37, 8.4.28, 8.5.13, or 8.6.0.");
            return self::FAILURE;
        }

        if (isset(self::MIN_PATCHED_VERSIONS[$majorMinor])) {
            $minRequired = self::MIN_PATCHED_VERSIONS[$majorMinor];
            if (version_compare($version, $minRequired, '<')) {
                $this->error("FAIL: PHP {$version} is vulnerable to PHP2Shell / PHPAfterShell. Minimum patched version for {$majorMinor}.x is {$minRequired}.");
                return self::FAILURE;
            }
        }

        if (ini_get('session.upload_progress.enabled')) {
            $this->warn("WARN: php.ini session.upload_progress.enabled is On. Recommend setting session.upload_progress.enabled = Off.");
        }

        $this->info("PASS: PHP {$version} meets November 2026 security patch requirements (PHP2Shell & PHPAfterShell mitigated).");
        return self::SUCCESS;
    }
}
