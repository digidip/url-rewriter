<?php

namespace digidip\Loggers;

use DateTime;
use Psr\Log\LoggerInterface;

class StdioLogger implements LoggerInterface
{
    private function getTimestamp(): string
    {
        return (new \DateTime())->format(DateTime::ISO8601);
    }

    public function emergency($message, array $context = array())
    {
        fwrite(STDERR, "[EMER] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function alert($message, array $context = array())
    {
        fwrite(STDERR, "[ALER] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function critical($message, array $context = array())
    {
        fwrite(STDERR, "[CRIT] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function error($message, array $context = array())
    {
        fwrite(STDERR, "[ERRO] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function warning($message, array $context = array())
    {
        fwrite(STDOUT, "[WARN] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function notice($message, array $context = array())
    {
        fwrite(STDOUT, "[NOTE] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function info($message, array $context = array())
    {
        fwrite(STDOUT, "[INFO] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function debug($message, array $context = array())
    {
        fwrite(STDOUT, "[DBUG] {$this->getTimestamp()}: {$message}" . PHP_EOL);
    }

    public function log($level, $message, array $context = array())
    {
    }
}
