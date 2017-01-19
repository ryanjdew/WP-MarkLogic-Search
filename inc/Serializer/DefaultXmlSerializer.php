<?php
/*
 * This file is part of marklogic/wp-search
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

namespace MarkLogic\WpSearch\Serializer;

/**
 * Default XML serializer.
 *
 * @since   2015-10-06
 */
final class DefaultXmlSerializer extends AbstractXmlSerializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize($post, $postMeta)
    {
        $doc = $this->createDocument($post, $postMeta);
        return $doc->saveXML();
    }
}
