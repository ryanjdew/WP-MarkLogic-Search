<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

/**
 * ABC for classes that hook into WP.
 *
 * @since   2015-10-07
 */
abstract class Hooks
{
    private static $reg = array();

    public static function instance()
    {
        $cls = get_called_class();
        if (!isset(self::$reg[$cls])) {
            self::$reg[$cls] = new $cls();
        }

        return self::$reg[$cls];
    }

    public static function init()
    {
        return static::instance()->hook();
    }

    abstract public function hook();
}
