<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

class Options extends Hooks
{
    //const SETTING = 'ml_wpsearch_settings';
    const SETTING_CONNECTION = 'ml_wpsearch_connection';
    const SETTING_SEARCH = 'ml_wpsearch_search';
    const SETTING_IMPORT_CONTENT = 'ml_wpsearch_import_content';

    public static function getOptions($category=null)
    {
        if ($category)
            return get_option($category) ?: array();
        else {
            $connectionOptions = get_option(self::SETTING_CONNECTION) ?: array();
            $searchOptions = get_option(self::SETTING_SEARCH) ?: array();
            $importOptions = get_option(self::SETTING_IMPORT_CONTENT) ?: array();

            return array_merge($connectionOptions, $searchOptions, $importOptions);
        }
    }

    public static function replaceSearch()
    {
        $opts = static::getOptions(self::SETTING_SEARCH);
        return !empty($opts['replace_search']) && !empty(self::getSearchPage());
    }

    public static function getSearchPage()
    {
        $opts = static::getOptions(self::SETTING_SEARCH);
        return isset($opts['search_page']) ? $opts['search_page'] : null;
    }

    public function hook()
    {
        add_action('admin_init', array($this, 'register'));
        // see http://codex.wordpress.org/Function_Reference/register_post_type#show_in_menu
        // for why the priority here is so low (early)
        add_action('admin_menu', array($this, 'addPage'), 1);
    }

    public function register()
    {
        register_setting(
            self::SETTING_CONNECTION,
            self::SETTING_CONNECTION,
            array($this, 'cleanConnection')
        );

        register_setting(
            self::SETTING_SEARCH,
            self::SETTING_SEARCH,
            array($this, 'cleanSearch')
        );

        add_settings_section(
            'ml_wpsearch_connection_options',
            __('Connection Options', 'marklogic'),
            '__return_false',
            self::SETTING_CONNECTION
        );

        add_settings_section(
            'ml_wpsearch_search_options',
            __('Search', 'marklogic'),
            '__return_false',
            self::SETTING_SEARCH
        );

        $fields = array(
            'host'      => __('Host', 'marklogic'),
            'port'      => __('Port', 'marklogic'),
            'username'  => __('Username', 'marklogic'),
        );
        foreach ($fields as $fn => $label) {
            $id =  self::idFor(self::SETTING_CONNECTION, $fn);
            add_settings_field($id, $label, array($this, 'textInput'), self::SETTING_CONNECTION, 'ml_wpsearch_connection_options', array(
                'label_for' => $id,
                'field'     => $fn,
            ));
        }
        $pid = self::idFor(self::SETTING_CONNECTION, 'password');
        add_settings_field(
            $pid, 
            __('Password', 'marklogic'),
            array($this, 'passwordInput'),
            self::SETTING_CONNECTION,
            'ml_wpsearch_connection_options',
            array(
                'label_for' => $pid,
                'field'     => 'password',
            )
        );

        add_settings_field(
            $spid = self::idFor(self::SETTING_SEARCH, 'search_page'),
            __('Search Page', 'marklogic'),
            array($this, 'dropdownPages'),
            self::SETTING_SEARCH,
            'ml_wpsearch_search_options',
            array(
                'label_for' => $spid,
                'field' => 'search_page',
            )
        );
        add_settings_field(
            $sid = self::idFor(self::SETTING_SEARCH, 'replace_search'),
            __('Replace WordPress Search', 'marklogic'),
            array($this, 'checkboxInput'),
            self::SETTING_SEARCH,
            'ml_wpsearch_search_options',
            array(
                'label_for' => $sid,
                'field' => 'replace_search',
            )
        );
        add_settings_field(
            $restConfigOption = self::idFor(self::SETTING_SEARCH, 'rest_config_option'),
            __('REST Config Option', 'marklogic'),
            array($this, 'textInput'),
            self::SETTING_SEARCH,
            'ml_wpsearch_search_options',
            array(
                'label_for' => $restConfigOption,
                'field' => 'rest_config_option',
            )
        );
        add_settings_field(
            $restTransform = self::idFor(self::SETTING_SEARCH, 'rest_transform'),
            __('REST Transform', 'marklogic'),
            array($this, 'textInput'),
            self::SETTING_SEARCH,
            'ml_wpsearch_search_options',
            array(
                'label_for' => $restTransform,
                'field' => 'rest_transform',
            )
        );
        add_settings_field(
            $searchExclude = self::idFor(self::SETTING_SEARCH, 'search_exclude'),
            __('Search Exclude', 'marklogic'),
            array($this, 'textAreaInput'),
            self::SETTING_SEARCH,
            'ml_wpsearch_search_options',
            array(
                'label_for' => $searchExclude,
                'field' => 'search_exclude',
            )
        );
    }

    public function cleanConnection($dirty)
    {
        $clean = array();
        foreach (array('host', 'username', 'password') as $fn) {
            $clean[$fn] = isset($dirty[$fn]) ? sanitize_text_field($dirty[$fn]) : null;
        }
        $clean['port'] = isset($dirty['port']) ? filter_var($dirty['port'], FILTER_VALIDATE_INT) : null;
        
        return $clean;
    }

    public function cleanSearch($dirty)
    {
        $clean = array();
        foreach (array('rest_config_option','rest_transform') as $fn) {
            $clean[$fn] = isset($dirty[$fn]) ? sanitize_text_field($dirty[$fn]) : null;
        }
        $clean['replace_search'] = empty($dirty['replace_search']) ? false : true;
        $clean['search_page'] = empty($dirty['search_page']) ? null : absint($dirty['search_page']);
        $clean['search_exclude'] = isset($dirty['search_exclude']) ? implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $dirty['search_exclude'] ) ) ) : null;
        
        return $clean;
    }

    public function addPage()
    {
        $page = add_options_page(
            __('MarkLogic Search', 'marklogic'),
            __('MarkLogic Search', 'marklogic'),
            'manage_options',
            'ml-wpsearch',
            array($this, 'showPage')
        );

        add_action("load-{$page}", array($this, 'loadPage'));
    }

    public function loadPage()
    {
        do_action('ml_wpsearch_load_settings_page');
    }

    public function showPage()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('MarkLogic Search Settings', 'marklogic'); ?></h1>

            <?php  
                $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'connection_options';  
            ?>  

            <h2 class="nav-tab-wrapper">  
                <a href="?page=ml-wpsearch&tab=connection_options" class="nav-tab <?php echo $active_tab == 'connection_options' ? 'nav-tab-active' : ''; ?>">Connection Options</a>  
                <a href="?page=ml-wpsearch&tab=search_options" class="nav-tab <?php echo $active_tab == 'search_options' ? 'nav-tab-active' : ''; ?>">Search Options</a>  
                <a href="?page=ml-wpsearch&tab=import_content" class="nav-tab <?php echo $active_tab == 'import_content' ? 'nav-tab-active' : ''; ?>">Import Content</a>  
            </h2>  

            <?php do_action('ml_wpsearch_before_settings_form'); ?>
            <form method="POST" action="<?php echo admin_url('options.php'); ?>">
                <?php 
                if( $active_tab == 'connection_options' ) {  
                    settings_fields(self::SETTING_CONNECTION);
                    do_settings_sections(self::SETTING_CONNECTION);
                    submit_button(__('Save', 'marklogic'));
                } else if( $active_tab == 'search_options' ) {
                    settings_fields(self::SETTING_SEARCH);
                    do_settings_sections(self::SETTING_SEARCH);
                    submit_button(__('Save', 'marklogic'));
                } else if( $active_tab == 'import_content' ) {
                     do_action('ml_wpsearch_after_settings_form');
                } 
                ?> 
            </form>
            
        </div>
        <?php
    }

    public function textInput(array $args)
    {
        $opts = self::getOptions();
        self::showInput(
            'text',
            $args['label_for'],
            isset($opts[$args['field']]) ? $opts[$args['field']] : null,
            array('class' => 'regular-text')
        );
    }

    public function textAreaInput(array $args)
    {
        $opts = self::getOptions();
        self::showInput(
            'textarea',
            $args['label_for'],
            isset($opts[$args['field']]) ? $opts[$args['field']] : null,
            array('class' => 'regular-text')
        );
    }

    public function passwordInput(array $args)
    {
        $opts = self::getOptions();
        self::showInput(
            'password',
            $args['label_for'],
            isset($opts[$args['field']]) ? $opts[$args['field']] : null,
            array('class' => 'regular-text')
        );
    }

    public function checkboxInput(array $args)
    {
        $opts = self::getOptions();
        self::showInput(
            'checkbox',
            $args['label_for'],
            1,
            empty($opts[$args['field']]) ? array() : array('checked' => 'checked')
        );
    }

    public static function dropdownPages(array $args)
    {
        $opts = self::getOptions();
        wp_dropdown_pages(array(
            'selected'  => isset($opts['search_page']) ? $opts['search_page'] : null,
            'name' => $args['label_for'],
            'id' => $args['label_for'],
            'show_option_none' => 'None',
        ));
    }

    private static function showInput($type, $id, $value, array $attributes=[])
    {
        $attr = array();
        foreach ($attributes as $k => $v) {
            $attr[] = sprintf('%s="%s"', tag_escape($k), esc_attr($v));
        }

        if ($type == 'textarea') {
            printf(
                '<textarea id="%1$s" name="%1$s" rows="10" cols="75" %3$s>%2$s</textarea>',
                esc_attr($id),
                esc_attr($value),
                implode(' ', $attr)
            );
        } else {
            printf(
                '<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" %4$s />',
                esc_attr($type),
                esc_attr($id),
                esc_attr($value),
                implode(' ', $attr)
            );
        }
    }

    private static function idFor($section, $fn)
    {
        return sprintf('%s[%s]', $section, $fn);
    }
}
