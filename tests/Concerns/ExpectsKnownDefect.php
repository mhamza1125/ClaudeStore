<?php

namespace Tests\Concerns;

use PHPUnit\Framework\AssertionFailedError;

/**
 * Characterization of a known defect from docs/AUDIT_2026-11-25.md.
 *
 * While the defect exists the assertions fail and the test is reported as INCOMPLETE
 * ("EXPECTED-FAIL <id>"), so the suite stays green. Once the defect is fixed the assertions
 * pass and the test FAILS on purpose, telling whoever fixed it to remove the marker and
 * turn it into a normal regression test.
 */
trait ExpectsKnownDefect
{
    protected function expectKnownDefect(string $findingId, callable $assertions): void
    {
        try {
            $assertions();
        } catch (AssertionFailedError $e) {
            $this->markTestIncomplete("EXPECTED-FAIL {$findingId}: ".$e->getMessage());
        }

        $this->fail("{$findingId} now passes: remove the expected-fail marker and keep this as a regression test.");
    }
}
