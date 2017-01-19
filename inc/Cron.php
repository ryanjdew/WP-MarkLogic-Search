<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

class Cron extends Hooks
{
    const BULK_IMPORT   = 'ml_wpsearch_bulk_import_job';
    const BULK_DELETE   = 'ml_wpsearch_bulk_delete_job';

    public function hook()
    {
        add_action(self::BULK_IMPORT, array($this, 'doImport'), 10, 2);
        add_action(self::BULK_DELETE, array($this, 'doDelete'), 10, 2);
    }

    /**
     * Schedule a new import for a given post type.
     *
     * @param   int $logId The ID of the post used to track logging
     * @param   string $postType the type to import
     * @return  void
     */
    public static function scheduleImport($logId, $postType)
    {
        wp_schedule_single_event(
            time(),
            self::BULK_IMPORT,
            array($logId, $postType)
        );
    }

    /**
     * Schedule a new delete of a given post type.
     *
     * @param   int $logId The ID of the post used to track logging
     * @param   string $postType the post type to delete
     * @return  void
     */
    public static function scheduleDelete($logId, $postType)
    {
        wp_schedule_single_event(
            time(),
            self::BULK_DELETE,
            array($logId, $postType)
        );
    }

    public function doImport($logId, $postType)
    {
        $this->disableTimeLimit();
        do_action('ml_wpsearch_bulk_import', $logId, $postType);
    }

    public function doDelete($logId, $postType)
    {
        $this->disableTimeLimit();
        do_action('ml_wpsearch_bulk_delete', $logId, $postType);
    }

    private function disableTimeLimit()
    {
        set_time_limit(0);
    }
}
