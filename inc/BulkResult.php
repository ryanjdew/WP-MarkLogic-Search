<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

/**
 * A value object encapsulating results from bulk operations.
 *
 * @since   1.0
 */
final class BulkResult implements \Countable, \IteratorAggregate
{
    /**
     * The total number of posts indexed.
     *
     * @var int
     */
    private $count;

    /**
     * Errors from the request. This should be an array of $postId => $error
     * pairs.
     *
     * @var array
     */
    private $errors;

    public function __construct($count, array $errors)
    {
        $this->count = $count;
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Used to encode this thing to JSON since we can't rely on having
     * `JsonSerializable` everywhere.
     *
     * @return  array
     */
    public function asArray()
    {
        return array(
            'count'     => $this->count,
            'errors'    => $this->errors,
        );
    }
}
