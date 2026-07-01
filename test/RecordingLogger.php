<?php

namespace Ubermuda\FeatureFlagsBundle\Test;

use Psr\Log\AbstractLogger;
use Stringable;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string}> */
    public array $records = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message];
    }

    public function hasErrorRecords(): bool
    {
        foreach ($this->records as $record) {
            if ('error' === $record['level']) {
                return true;
            }
        }

        return false;
    }
}
