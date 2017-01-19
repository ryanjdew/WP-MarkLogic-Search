<?php
/*
 * This file is part of marklogic/wp-search.
 *
 * Used to display the form on the search page itself.
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

!defined('ABSPATH') && exit;
?>

<form action="<?php echo esc_url(ml_wpsearch_url()); ?>" method="GET" class="search-form ml-search form-inline" role="search">
    <button type="submit" class="btn btn-default btn-soft-magic">
        <span class="hidden-sm hidden-xs"><?php _e('Search', 'marklogic'); ?></span>
        <i class="hidden-md hidden-lg glyphicon glyphicon-search"></i>
    </button>
    <div class="search-container">
        <input type="text" name="s" class="form-control" placeholder="<?php esc_attr_e('Search', 'marklogic'); ?>" value="<?php the_search_query(); ?>" />
    </div>
</form>
