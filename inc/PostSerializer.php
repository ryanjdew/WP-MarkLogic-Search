<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

/**
 * Transform post object into something to be indexed.
 *
 * @since   2015-10-06
 */
interface PostSerializer
{
    /**
     * Serialize a post into.
     *
     * @param   object $post
     * @return  string
     */
    public function serialize($post, $postMeta);
}
