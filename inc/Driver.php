<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

/**
 * Defines a "driver" -- something that can perform indexation and deletion of
 * posts as well as handle search.
 *
 * @since   2015-10-06
 */
interface Driver
{
    /**
     * Add a new post to the backend.
     *
     * @param   int $blogId The blog to which the post belongs.
     * @param   object $post The post to index.
     * @throws  Exception if anything goes wrong.
     * @return  boolean
     */
    public function persist($blogId, $post);

    /**
     * Add several posts to the storage backend.
     *
     * @param   int $blogId The blog to which the posts belong.
     * @param   object[] $posts The posts to add
     * @return  BulkResult
     */
    public function bulkPersist($blogId, array $posts);

    /**
     * Remove a post from from the storage backend.
     *
     * @param   int $blogId The blog to which the post belongs
     * @param   object $post The post to remove. Generally this method is called
     *          before any deletion is done in the database.
     * @throws  Exception if anything goes wrong
     * @return  boolean
     */
    public function delete($blogId, $post);

    /**
     * Remove several posts from the storage backend.
     *
     * @param   int $blogId The blog to which the 
     * @param   object[] $posts The posts to remove
     * @return  BulkResult
     */
    public function bulkDelete($blogId, array $posts);
}
