<?php

namespace Wexample\SymfonyMail\Tests\Fixtures\Log;

use Psr\Log\AbstractLogger;
use Stringable;

class RecordingLogger extends AbstractLogger
{
    /**
     * @var list<string> each record, its message and context serialized
     */
    public array $records = [];

    public function log(
        $level,
        string|Stringable $message,
        array $context = []
    ): void {
        $this->records[] = $level.' '.$message.' '.json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
