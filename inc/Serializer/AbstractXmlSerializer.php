<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch\Serializer;

use MarkLogic\WpSearch\PostSerializer;

/**
 * ABC for XML based serializers.
 *
 * @since   2015-10-06
 */
abstract class AbstractXmlSerializer implements PostSerializer
{
    const XMLNS = 'http://developer.marklogic.com/site/internal';

    protected function createDocument($post, $postMeta=null)
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElementNS(self::XMLNS, 'ml:Post');
        $root->setAttribute('status', $this->filter('status', $post->post_status, $post));
        $root->setAttribute('post-type', $this->filter('type', $post->post_type, $post));
        $root->setAttribute('url', $this->filter('url', get_permalink($post), $post));
        $root->appendChild($title = $doc->createElementNS(
            self::XMLNS,
            'ml:title'
        ));
        $title->appendChild($doc->createCDATASection(
            $this->filter('title', strip_tags($post->post_title), $post)
        ));
        if ($created = $this->formatDate($post->post_date_gmt)) {
            $root->appendChild($doc->createElementNS(
                self::XMLNS,
                'ml:created',
                $this->filter('created', $created, $post)
            ));
        }
        if ($updated = $this->formatDate($post->post_modified_gmt)) {
            $root->appendChild($doc->createElementNS(
                self::XMLNS,
                'ml:last-updated',
                $this->filter('updated', $updated, $post)
            ));
        }
        $root->appendChild($body = $doc->createElementNS(
            self::XMLNS,
            'ml:body'
        ));
        $body->appendChild($doc->createCDATASection(
            $this->filter('content', strip_tags($post->post_content), $post)
        ));
        $root->appendChild($excerpt = $doc->createElementNS(
            self::XMLNS,
            'ml:short-description'
        ));
        $excerpt->appendChild($doc->createCDATASection(
            $this->filter('excerpt', $post->post_excerpt, $post)
        ));
        if ($author = $this->buildAuthor($post, $doc)) {
            $root->appendChild($author);
        }

        $terms = $doc->createElementNS(self::XMLNS, 'ml:terms');
        $root->appendChild($terms);
        $taxonomies = $this->filter('taxonomies', get_object_taxonomies($post), $post);
        foreach ($taxonomies as $tax) {
            $this->addTerms($post, $tax, $terms, $doc);
        }

        if (in_array('post_tag', $taxonomies, true) && ($elem = $this->buildTags($post, $doc))) {
            $root->appendChild($elem);
        }

        $metadata = $doc->createElementNS(self::XMLNS, 'ml:metadata');
        $root->appendChild($metadata);
        
        foreach ($postMeta as $key => $value) {

            if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})$/', $value[0], $matches)) {
                $value[0] = $matches[1] . "T" . $matches[2] . ":00+00:00"; //2016-11-09T20:34:11+00:00
            }

            $metadata->appendChild($doc->createElementNS(self::XMLNS, 'ml:'.$key, $value[0]));
        }

        $doc->appendChild($root);

        do_action('ml_wpsearch_serialize_document', $post, $doc, $root);

        return $doc;
    }

    protected function buildAuthor($post, \DOMDocument $doc)
    {
        $author = get_user_by('id', $post->post_author);
        if (!$author || !$author->first_name || !$author->last_name) {
            return null;
        }

        return $doc->createElementNS(self::XMLNS, 'ml:author', $this->filter('author', sprintf(
            '%s %s',
            $author->first_name,
            $author->last_name
        ), $post));
    }

    protected function addTerms($post, $taxonomy, \DOMElement $elem, \DOMDocument $doc)
    {
        $terms = get_the_terms($post->ID, $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return;
        }

        foreach ($this->filter($taxonomy.'_terms', $terms, $post) as $term) {
            $elem->appendChild($te = $doc->createElementNS(self::XMLNS, 'ml:term', $term->name));
            $te->setAttribute('taxonomy', $taxonomy);
            $te->setAttribute('slug', $term->slug);
        }
    }

    protected function buildTags($post, \DOMDocument $doc)
    {
        $tags = get_the_tags($post->ID);
        if (!$tags) {
            return null;
        }

        $elem = $doc->createElementNS(self::XMLNS, 'ml:tags');
        foreach ($tags as $tag) {
            $elem->appendChild($doc->createElementNS(self::XMLNS, 'ml:tag', $tag->name));
        }

        return $elem;
    }

    protected function filter($part, $value, $post)
    {
        return apply_filters(sprintf('ml_wpsearch_serialized_%s', $part), $value, $post);
    }

    protected function formatDate($date)
    {
        try {
            $d = new \DateTime($date, new \DateTimeZone('UTC'));
            return $d->format(\DateTime::ATOM);
        } catch (\Exception $e) {
            return null;
        }
    }
}
