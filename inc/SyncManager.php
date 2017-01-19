<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

use Psr\Log\LoggerInterface;

/**
 * Wraps up a driver and integrates it with WordPress hooks to make things run.
 *
 * @since   2015-10-06
 */
class SyncManager
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Driver $driver, LoggerInterface $logger)
    {
        $this->driver = $driver;
        $this->logger = $logger;
    }

    public function connect()
    {
        add_action('wp_insert_post', array($this, 'handlePost'), 999, 2);
        add_action('edit_attachment', array($this, 'handleAttachment'), 999);
        add_action('add_attachment', array($this, 'handleAttachment'), 999);
        add_action('before_delete_post', array($this, 'handleDelete'), 999);
        add_action('delete_attachment', array($this, 'handleDelete'), 999);
    }

    public function disconnect()
    {
        remove_action('wp_insert_post', array($this, 'handlePost'), 999, 2);
        remove_action('edit_attachment', array($this, 'handleAttachment'), 999);
        remove_action('add_attachment', array($this, 'handleAttachment'), 999);
        remove_action('deleted_post', array($this, 'handleDelete'), 999);
    }

    public function handlePost($postId, $post)
    {
        if (
            !$this->isPersistablePostType($post)
            || $this->isAutosave()
            || !$this->userCanEdit($post)
            || $this->isAutoDraft($post)
        ) {
            return;
        }

        if ($this->isPersistableStatus($post)) {
            try {
                $this->driver->persist($this->getCurrentBlog(), $post);
                do_action('ml_wpsearch_persisted_post', $post);
            } catch (\Exception $e) {
                $this->logException($e, sprintf('persisting post #%d', $post->ID));
            }
        } else {
            // catches all non-published posts. Like trashed
            // or posts taht moved from published back to draft
            $this->doDelete($post);
        }

    }

    public function handleAttachment($postId)
    {
        return $this->handlePost($postId, get_post($postId));
    }

    public function handleDelete($postId)
    {
        $this->doDelete(get_post($postId));
    }

    private function doDelete($post)
    {
        try {
            $this->driver->delete($this->getCurrentBlog(), $post);
            do_action('ml_wpsearch_deleted_post', $post->ID);
        } catch (\Exception $e) {
            $this->logException($e, sprintf('deleting post #%d', $post->ID));
        }
    }

    private function getCurrentBlog()
    {
        return apply_filters('ml_wpsearch_current_blog', get_current_blog_id());
    }

    private function logException(\Exception $e, $ctx)
    {
        $this->logger->error("Caught {cls}({code}) while {ctx}: {msg}\n{tb}", array(
            'cls'   => get_class($e),
            'code'  => $e->getCode(),
            'ctx'   => $ctx,
            'msg'   => $e->getMessage(),
            'tb'    => $e->getTraceAsString()
        ));
    }

    private function isPersistablePostType($post)
    {
        $type = get_post_type_object($post->post_type);
        return _ml_wpsearch_is_type_persistable($type);
    }

    private function isPersistableStatus($post)
    {
        return apply_filters(
            'ml_wpsearch_persistable_status',
            'publish' === $post->post_status,
            $post->post_status,
            $post
        );
    }

    private function isAutosave()
    {
        return defined('DOING_AUTOSAVE') && DOING_AUTOSAVE;
    }

    private function userCanEdit($post)
    {
        return current_user_can('edit_post', $post->ID);
    }

    private function isAutoDraft($post)
    {
        return 'auto-draft' === $post->post_status;
    }
}
