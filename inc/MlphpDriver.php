<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch;

use MarkLogic\MLPHP\RESTClient;
use MarkLogic\MLPHP\Search;
use MarkLogic\MLPHP\Document;
use MarkLogic\WpSearch\Serializer\DefaultXmlSerializer;

/**
 * A driver backed by MarkLogic's PHP library.
 *
 * @since   2015-10-06
 */
final class MlphpDriver implements Driver
{
    /**
     * @var RESTClient
     */
    private $client;

    /**
     * @var PostSerializer
     */
    private $serializer;

    public function __construct(RESTClient $client, PostSerializer $serializer=null)
    {
        $this->client = $client;
        $this->serializer = $serializer ?: new DefaultXmlSerializer();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($blogId, $post)
    {
        $postMeta = get_post_meta($post->ID);
        $xml = $this->serializer->serialize($post, $postMeta);
        $doc = $this->createDocument($blogId, $post, $postMeta);
        $doc->setContent($xml);
        $doc->setContentType('application/xml');
        $doc->write(null, array(
            'collection'    => $this->buildCollections($post),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function bulkPersist($blogId, array $posts)
    {
        $errors = array();
        $count = 0;
        foreach ($posts as $post) {
            try {
                $this->persist($blogId, $post);
                $count++;
            } catch (\Exception $e) {
                $errors[$post->ID] = $this->formatException($e);
            }
        }

        return new BulkResult($count, $errors);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($blogId, $post)
    {
        $doc = $this->createDocument($blogId, $post);
        $doc->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete($blogId, array $posts)
    {
        $errors = array();
        $count = 0;
        foreach ($posts as $post) {
            try {
                $this->delete($blogId, $post);
                $count++;
            } catch (\Exception $e) {
                $errors[$post->ID] = $this->formatException($e);
            }
        }

        return new BulkResult($count, $errors);
    }

    private function createDocument($blogId, $post, $postMeta)
    {
        return new Document($this->client, sprintf('/%s.xml', apply_filters(
            'ml_wpsearch_document_uri',
            $this->createURIwithPostID($blogId, $post),
            $post
        )));
    }
    private function createURIwithPostID($blogId, $post)
    {
        $host = parse_url(home_url(), PHP_URL_HOST);
        return sprintf('%s/%s/%s', $host, $blogId, $post->ID);
    }


    private function buildCollections($post)
    {
        $hostName = parse_url(home_url(), PHP_URL_HOST);
        return apply_filters('ml_wpsearch_document_collections', array(
            $hostName,
            sprintf('%s/%s', $hostName, $post->post_type),
        ), $post);
    }

    private function formatException(\Exception $e)
    {
        return sprintf(
            '%s(%s) at %s:%d: %s',
            get_class($e),
            $e->getCode(),
            $e->getFile(),
            $e->getLine(),
            $e->getMessage()
        );
    }

    public function search($querytext, $params)
    {
        $search = new Search($this->client);
        return $search->retrieve($querytext, array_filter($params));
    }
}
