<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * A logger implementation that simple calls `error_log`
 *
 * @since   2015-10-06
 */
final class SimpleLogger extends AbstractLogger
{
    private static $levels = array(
        LogLevel::EMERGENCY => 800,
        LogLevel::ALERT     => 700,
        LogLevel::CRITICAL  => 600,
        LogLevel::ERROR     => 500,
        LogLevel::WARNING   => 400,
        LogLevel::NOTICE    => 300,
        LogLevel::INFO      => 200,
        LogLevel::DEBUG     => 100,
    );

    private $level;

    public function __construct($level)
    {
        if (!isset(self::$levels[$level])) {
            $level = LogLEvel::WARNING;
        }

        $this->level = self::$levels[$level];
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $ctx=array())
    {
        if (self::integerLevelFor($level) < $this->level) {
            return;
        }

        error_log(sprintf(
            '[%s] %s',
            $level,
            strtr($message, $this->makeReplacements($ctx))
        ));
    }

    private function makeReplacements(array $ctx)
    {
        $out = array();
        foreach ($ctx as $key => $val) {
            $out[sprintf('{%s}', $key)] = $this->isStringy($val) ? (string) $val : json_encode($val);
        }

        return $out;
    }

    private function isStringy($val)
    {
        return is_scalar($val) || (is_object($val) && method_exists($val, '__toString'));
    }

    private static function integerLevelFor($level)
    {
        return isset(self::$levels[$level]) ? self::$levels[$level] : 1000;
    }
}
