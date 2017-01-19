<?php
/*
 * This file is part of marklogic/wp-search.
 *
 * This is the default "view" for search, pretty much bootstrap based.
 *
 * @package     MarkLogic\WpSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 */

!defined('ABSPATH') && exit;


// eventually the values passed into `getFacet` will need to be dynamic
$facetType = 'type';
$facetActive = false !== stripos($querytext, $facetType);
$facetValues = null;
if ($results->hasFacets() && ($facet = $results->getFacet($facetType))) {
    $facetValues = $facet->getFacetValues();
}

?>
<div class="marklogic-search-results">
    <div class="row">

        <?php if ($facetValues): ?>
        <div class="col-md-3 marklogic-search-facets">
            <h4>
                <?php _e('Filter By:', 'marklogic'); ?>
                <i class="toggle-icon pull-right hidden-md hidden-lg glyphicon glyphicon-triangle-bottom"></i>
            </h4>
            <ul class="marklogic-search-facet-list">
                <?php foreach ($facetValues as $facetValue): ?>
                <li>
                <a href="<?php echo esc_url(_ml_wpsearch_build_facet_url_query($querytext, sprintf(
                    '%s:"%s"',
                    $facetType,
                    $facetValue->getName()
                ))); ?>" class="<?php if ($facetActive): ?>active<?php endif; ?>">
                        <?php echo esc_html($facetValue->getName()); ?>
                        <?php if ($facetActive): ?>
                            <span class="glyphicon glyphicon-remove-circle"></span>
                        <?php else: ?>
                            <span class="count"><?php echo absint($facetValue->getCount()); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="col-md-9 marklogic-search-item-container">
            <?php foreach ($results->getResults() as $row): ?>
                <?php
                $title = $row->getMetadataQName("title", "http://developer.marklogic.com/site/internal");
                $title = $title && isset($title[0]) ? $title[0] : '[ title missing ]';

                $url = $row->getMetadataQName("url", "http://developer.marklogic.com/site/internal");
                $url = $url && isset($url[0]) ? preg_replace('#^//#', '', $url[0]) : null;

                // Description
                $description = "";
                foreach ($row->getMatches() as $match) {
                    $description = $description . $match->getContent();
                }
                ?>

                <div class="marklogic-search-item">
                    <h3>
                        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
                    </h3>
                    <div class="marklogic-search-item-body">
                        <p class="marklogic-search-item-link">
                            <?php echo esc_html($url); ?>
                        </p>
                        <div class="search-snippet">
                            <?php echo strip_tags($description, '<span>'); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<div class="marklogic-search-pagination">
    <?php if ($prevLink): ?>
        <span class="prev">
            <a href="<?php echo esc_url($prevLink); ?>">&laquo;</a>
        </span>
    <?php endif; ?>
    
    <span class="pages">
        <?php echo absint($results->getStart()); ?>
        &ndash;
        <?php echo absint($results->getEnd());  ?>
        of <?php echo absint($results->getTotal()); ?>
    </span> 

    <?php if ($nextLink): ?>
        <span class="next">
            <a href="<?php echo esc_url($nextLink); ?>">&raquo;</a>
        </span>
    <?php endif; ?>
</div>
