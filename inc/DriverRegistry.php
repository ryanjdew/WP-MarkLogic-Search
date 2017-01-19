<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

/**
 * Keeps track of `SyncManager` instances so they can be retrieved by other
 * plugins. We use a "singleton" here, but the uniqueness isn't enforced.
 *
 * @since   2015-10-06
 */
class DriverRegistry
{
    private static $instance = null;

    /**
     * @var SyncManager[]
     */
    private $managers;

    /**
     * @var Driver[]
     */
    private $drivers;

    public function add($name, Driver $driver)
    {
        $this->drivers[$name] = $driver;
        $this->managers[$name] = new SyncManager($driver, ml_wpsearch_get_logger());
        $this->managers[$name]->connect();
    }

    public function has($name)
    {
        return isset($this->drivers[$name]);
    }

    public function get($name)
    {
        return $this->has($name) ? $this->drivers[$name] : null;
    }

    public function remove($name)
    {
        if ($this->has($name)) {
            $this->managers[$name]->disconnect();
            unset($this->managers[$name]);
            unset($this->drivers[$name]);
            return true;
        }

        return false;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
