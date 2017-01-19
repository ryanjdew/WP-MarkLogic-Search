<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

class BulkImports extends Hooks
{
    const TYPE    = 'ml_wpsearch_import';
    const AJAX    = 'ml_wpsearch_import_type';

    public function hook()
    {
        add_action('init', array($this, 'registerTypes'));
        add_action('ml_wpsearch_after_settings_form', array($this, 'showFields'));
        add_action('ml_wpsearch_load_settings_page', array($this, 'loadPage'));
        add_action('ml_wpsearch_bulk_import', array($this, 'doImport'), 10, 2);
        add_action('wp_ajax_'.self::AJAX, array($this, 'scheduleImport'));
    }

    public function registerTypes()
    {
        register_post_type(self::TYPE, array(
            'label'         => __('Bulk Import', 'marklogic'),
            'labels'        => array(
                'menu_name'     => __('Imports', 'marklogic'),
                'add_new'       => __('Schedule Import', 'marklogic'),
                'add_new_item'  => __('Schedule Import', 'marklogic'),
            ),
            'public'        => false,
            'supports'      => array(''), // hack to hide the title, etc.
            'show_in_menu'  => 'ml-wpsearch',
        ));
    }

    public function loadPage()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    public function enqueue()
    {
        wp_enqueue_style(
            'ml-wpsearch-imports',
            plugins_url('assets/css/imports.css', __DIR__),
            array(),
            ML_WPSEARCH_VER
        );

        wp_enqueue_script(
            'ml-wpsearch-imports',
            plugins_url('assets/js/imports.js', __DIR__),
            array('jquery'),
            ML_WPSEARCH_VER
        );
    }

    public function showFields()
    {
        echo '<h3>', __('Import Content', 'marklogic'), '</h3>';

        echo '<div class="ml-wpsearch-import-messages"></div>';

        echo '<div class="ml-wpsearch-import-wrap">';

        echo '<div class="ml-wpsearch-import-form">';
        foreach ($this->getPublicTypes() as $name => $label) {
            printf(
                '<label for="%1$s[%2$s]"><input type="radio" name="%1$s" id="%1$s[%2$s]" value="%2$s" />%3$s</label>',
                'ml_wpsearch_import_type',
                esc_attr($name),
                esc_html($label)
            );
        }
        printf(
            '<button type="button" id="ml-wpsearch-import-submit" class="button">%s</button>',
            __('Import', 'marklogic')
        );
        wp_nonce_field(self::AJAX, 'ml_wpsearch_import_nonce', false);
        printf('<input type="hidden" name="ml_wpsearch_import_action" value="%s" />', self::AJAX);
        echo '</div>';

        echo '<div class="ml-wpsearch-import-imports">';
        $this->showImports();
        echo '</div>';

        echo '<div class="clear"></div>';

        echo '</div>';
    }

    public function scheduleImport()
    {
        header('Content-Type: application/json');

        if (!check_ajax_referer(self::AJAX, 'nonce', false)) {
            return $this->ajaxError(__('Invalid XSRF token.', 'marklogic'));
        }

        $type = isset($_POST['post_type']) ? $_POST['post_type'] : null;
        if (!$type) {
            return $this->ajaxError(__('Missing post_type parameter.', 'marklogic'));
        }

        $typeObject = get_post_type_object($type);
        if (!$typeObject) {
            return $this->ajaxError(__('Invalid post type.', 'marklogic'));
        }

        if (!apply_filters('ml_wpsearch_can_import', true, $type, $typeObject)) {
            return $this->ajaxError(sprintf(
                __('Importing the %s post type is not allowed.', 'marklogic'),
                $type
            ));
        }

        $postId = wp_insert_post(array(
            'post_type'     => self::TYPE,
            'post_title'    => $type,
        ));

        if (is_wp_error($postId)) {
            return $this->ajaxError(__('Could not create import.', 'marklogic'), 500);
        }

        Cron::scheduleImport($postId, $type);

        status_header(200);
        echo json_encode(array(
            'code'      => 200,
            'message'   => __('Import scheduled.', 'marklogic'),
        ));
        wp_die();
    }

    public function doImport($postId, $postType)
    {
        $logger = ml_wpsearch_get_logger();
        $results = array();
        $driver = DriverRegistry::getInstance()->get('marklogic');
        $blogId = get_current_blog_id();

        $logger->debug('starting import of {typ} {id}', array('typ' => $postType, 'id' => $postId));
        $page = 0;
        do {
            $page++;
            $query = new \WP_Query(apply_filters('ml_wpsearch_bulk_import_args', array(
                'post_type'         => $postType,
                'posts_per_page'    => 100,
                'paged'             => $page,
            ), $postType, $postId));
            $results[] = $driver->bulkPersist($blogId, $query->posts);
        } while ($page < $query->max_num_pages);
        $logger->debug('finished import of {typ} {id}', array('typ' => $postType, 'id' => $postId));

        wp_update_post(array(
            'ID'            => $postId,
            'post_content'  => json_encode($this->flattenResults($results)),
            'post_status'   => 'publish',
        ));
    }

    private function ajaxError($msg, $status=400)
    {
        status_header($status);
        echo json_encode(array(
            'code'  => $status,
            'error' => $msg,
        ));
        wp_die();
    }

    private function getPublicTypes()
    {
        $types = get_post_types(array('public' => true), 'objects');

        $out = array();
        foreach ($types as $name => $type) {
            if (_ml_wpsearch_is_type_persistable($type)) {
                $out[$name] = $type->label;
            }
        }

        return apply_filters('ml_wpsearch_importable_types', $out, $types);
    }

    private function flattenResults(array $results)
    {
        $count = 0;
        $errors = array();
        foreach ($results as $r) {
            $count += count($r);
            $errors = array_merge($errors, $r->getErrors());
        }

        return array(
            'count'     => $count,
            'errors'    => $errors ?: new \stdClass,
        );
    }

    private function showImports()
    {
        $imports = get_posts(array(
            'post_type'         => self::TYPE,
            'post_status'       => array('publish', 'draft'),
            'posts_per_page'    => 10,
        ));

        ?>
        <table class="wp-list-table widefat striped fixed posts">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php _e('Post Type', 'marklogic'); ?></th>
                    <th><?php _e('Status', 'marklogic'); ?></th>
                    <th><?php _e('Stats', 'marklogic'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($imports) {
                    foreach ($imports as $import) {
                        $this->showImport($import);
                    }
                } else {
                    echo '<tr><td colspan="4">', __('No Imports', 'marklogic'), '</td></tr>';
                }?>
            </tbody>
        </table>
        <?php
    }

    private function showImport($import)
    {
        $type = get_post_type_object($import->post_title);
        if (!$type) {
            return;
        }

        $isComplete = 'publish' === $import->post_status;
        ?>
        <tr>
            <td><?php echo absint($import->ID); ?></td>
            <td><?php echo esc_html($type->label); ?></td>
            <td><?php $isComplete ? _e('Complete', 'marklogic') : _e('Pending', 'marklogic'); ?></td>
            <td><?php if ($isComplete) {
                $res = json_decode($import->post_content, true);
                echo __('Total:', 'marklogic'), ' ', (isset($res['count']) ? absint($res['count']) : 0);
                echo '<br />';
                echo __('Errors:', 'marklogic'), ' ', (isset($res['errors']) ? count($res['errors']) : 0);
            } else {
                echo '-';
            } ?></td>
        </tr>
        <?php
    }
}
