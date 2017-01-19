<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

/**
 * Hooks into the search page load to display the results as well as perform
 * the actual query.
 *
 * @since 2016-03-08
 */
class SearchPage extends Hooks
{
    private $searchId;
    private $results = null;
    private $nextUrl = null;
    private $prevUrl = null;

    public function hook()
    {
        add_action('parse_query', array($this, 'maybeReplaceSearch'));
        add_filter('posts_search', array ($this, 'removeSearchSql'), 10, 2);
        add_action('template_redirect', array($this, 'catchSearchPage'));
    }

    public function unhookSearch()
    {
        remove_filter('parse_query', array($this, 'maybeReplaceSearch'));
        remove_filter('posts_search', array ($this, 'removeSearchSql'), 10, 2);
    }

    /**
     * Checks to see if a normal search query is being performed (eg. example.com/?s=search)
     * and, if so, will replace the query with one that loads our search page.
     *
     * `handleSearchQueryVar`, hooked into `pre_get_posts` later on, will take
     * care of ensuring the `s` query var doesn't change how the query is loaded.
     * This is done in two steps because when search is not being replaced
     * the query should still work on the search page itself.
     */
    public function maybeReplaceSearch(\WP_Query $query)
    {
        if ($query->is_main_query() && $query->is_search() && Options::replaceSearch()) {
            // All of the `is_*` stuff is setup in WP_Query::parse_query which
            // has already ran by this point. So we need to reset it up for what
            // our new page.
            $query->is_search = false;
            $query->is_singular = $query->is_page = true;

            // change the query to point at our search page.
            $query->set('p', Options::getSearchPage());
            $query->set('post_type', 'page');

            // finally we don't need to redirect to the canonical URL for
            // our search page.
            remove_action('template_redirect', 'redirect_canonical');
        }
    }

    /**
     * When a query comes into a page with the `s` in the URL, WP tries to do a
     * query for both the page and the search string which, most of of the time,
     * results in a not found. This looks if the query is on the search page and,
     * if so, removes the search SQL bits.
     */
    public function removeSearchSql($search, \WP_Query $query)
    {
        return $this->onSearchPage($query) ? '' : $search;
    }

    public function catchSearchPage()
    {
        global $wp_query;

        if (!is_page()) {
            return;
        }

        $id = get_queried_object_id();
        if (Options::getSearchPage() != $id) {
            return;
        }

        $this->searchId = $id;
        try {
            list($this->results, $this->nextUrl, $this->prevUrl) = ml_wpsearch_search($wp_query->get('s'));
            add_filter('the_content', array($this, 'appendSearchContent'), 100);
        } catch (\Exception $e) {
            $this->logException($e);
            $this->resetSearchQuery();
        }
    }

    public function appendSearchContent($content)
    {
        global $wp_query;

        if (get_the_ID() != $this->searchId) {
            return $content;
        }

        $querytext = $wp_query->get('s');
        $form = locate_template('marklogic-search/form.php') ?: __DIR__.'/../views/form.php';

        ob_start();
        require $form;
        $this->renderResults();
        $searchContent = ob_get_clean();

        return $content.$searchContent;
    }

    private function onSearchPage(\WP_Query $query)
    {
        $id = isset($query->queried_object_id) ? $query->queried_object_id : $query->get('p');
        return $query->is_main_query()
            && $id
            && Options::getSearchPage() == $id;
    }

    private function renderResults()
    {
        global $wp_query;

        if (!$this->results) {
            return;
        }

        $results = $this->results;
        $nextLink = $this->nextUrl;
        $prevLink = $this->prevUrl;
        $querytext = $wp_query->get('s');

        if ($results->getTotal() < 1) {
            $template = locate_template('marklogic-search/no-results.php') ?: __DIR__.'/../views/no-results.php';
        } else {
            $template = locate_template('marklogic-search/results.php') ?: __DIR__.'/../views/results.php';
        }

        require $template;
    }

    private function logException(\Exception $e)
    {
        ml_wpsearch_get_logger()->error("Caught {cls}({code}) conducting search: {msg}\n{tb}", array(
            'cls'   => get_class($e),
            'code'  => $e->getCode(),
            'msg'   => $e->getMessage(),
            'tb'    => $e->getTraceAsString()
        ));
    }

    private function resetSearchQuery()
    {
        global $wp_the_query;
        if (!$wp_the_query->get('s')) {
            return;
        }

        $this->unhookSearch();
        $wp_the_query = new \WP_Query(array(
            's' => $wp_the_query->get('s'),
        ));
        wp_reset_query();
    }
}
