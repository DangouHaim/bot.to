<?php

require_once("bookmark-counter.php");

$data = c27()->merge_options( [
    'listing' => '',
    'options' => [],
    'wrap_in' => '',
], $data );

if ( ! $data['listing'] ) {
    return;
}

$listing = \MyListing\Src\Listing::get( $data['listing'] );
if ( ! ( $listing && $listing->type ) ) {
    return;
}

// Get the preview template options for the listing type of the current listing.
$options = $listing->get_preview_options();

// Finally, in case custom options have been provided through the c27()->get_partial() method,
// then give those the highest priority, by overwriting the listing type options with those.
$options = c27()->merge_options( $options, (array) $data['options'] );

$classes = [
    'default' => '',
    'alternate' => 'lf-type-2',
    'list-view' => 'lf-list-view',
];

// Categories.
$categories = $listing->get_field( 'job_category' );
// $categories = array_filter( (array) wp_get_object_terms($listing->ID, 'job_listing_category', ['orderby' => 'term_order', 'order' => 'ASC']) );

$first_category = $categories ? new MyListing\Src\Term( $categories[0] ) : false;
$listing_thumbnail = $listing->get_logo( 'thumbnail' ) ?: c27()->image( 'marker.jpg' );
$latitude = false;
$longitude = false;

if ( is_numeric( $listing->get_data('geolocation_lat') ) ) {
    $latitude = $listing->get_data('geolocation_lat');
}

if ( is_numeric( $listing->get_data('geolocation_long') ) ) {
    $longitude = $listing->get_data('geolocation_long');
}

$data['listing']->_c27_marker_data = [
    'lat' => $latitude,
    'lng' => $longitude,
    'thumbnail' => $listing_thumbnail,
    'category_icon' => $first_category ? $first_category->get_icon() : null,
    'category_color' => $first_category ? $first_category->get_color() : null,
    'category_text_color' => $first_category ? $first_category->get_text_color() : null,
];

// Tagline.
if ( $listing->has_field( 'tagline' ) ) {
    $tagline = $listing->get_field( 'tagline' );
} elseif ( $listing->has_field( 'description' ) ) {
    $tagline = c27()->the_text_excerpt( wp_kses( $listing->get_field( 'description' ), [] ), 114, '&hellip;', false );
} else {
    $tagline = false;
}

// Info fields (fields below title)
$info_fields = $listing->get_info_fields();

// Get the number of details, so the height of the listing preview
// can be reduced if there are many details.
$detailsCount = 0;
foreach ((array) $options['footer']['sections'] as $section) {
    if ( $section['type'] == 'details' ) $detailsCount = count( $section['details'] );
}

$isPromoted = apply_filters( 'mylisting/preview-card/show-badge', false, $listing, $data );

$wrapper_classes = get_post_class( [
    'lf-item-container',
    'listing-preview',
    'type-' . $listing->type->get_slug(),
    $classes[ $options['template'] ],
], $listing->get_id() );

if ( $detailsCount > 2 ) {
    $wrapper_classes[] = 'lf-small-height';
}

if ( $listing->get_data( '_claimed' ) ) {
    $wrapper_classes[] = 'c27-verified';
}

$wrapper_classes[] = $listing->get_logo() ? 'has-logo' : 'no-logo';
$wrapper_classes[] = $tagline ? 'has-tagline' : 'no-tagline';
$wrapper_classes[] = ! empty( $info_fields ) ? 'has-info-fields' : 'no-info-fields';

if ( $listing->get_priority() >= 2 ) {
    $wrapper_classes[] = 'level-promoted';
    $promotion_tooltip = _x( 'Promoted', 'Listing Preview Card: Promoted Tooltip Title', 'my-listing' );
} elseif ( $listing->get_priority() === 1 ) {
    $wrapper_classes[] = 'level-featured';
    $promotion_tooltip = _x( 'Featured', 'Listing Preview Card: Promoted Tooltip Title', 'my-listing' );
} else {
    $wrapper_classes[] = 'level-normal';
    $promotion_tooltip = '';
}

$wrapper_classes[] = sprintf( 'priority-%d', $listing->get_priority() );

?>

<!-- LISTING ITEM PREVIEW -->
<div class="<?php echo $data['wrap_in'] ? esc_attr( $data['wrap_in'] ) : '' ?>">
<div
    class="<?php echo esc_attr( join( ' ', $wrapper_classes ) ) ?>"
    data-count="<? echo esc_attr( get_bookmarks_count($listing->get_id()) ) ?>"
    data-id="listing-id-<?php echo esc_attr( $listing->get_id() ); ?>"
    data-latitude="<?php echo esc_attr( $latitude ); ?>"
    data-longitude="<?php echo esc_attr( $longitude ); ?>"
    data-category-icon="<?php echo esc_attr( $first_category ? $first_category->get_icon() : '' ) ?>"
    data-category-color="<?php echo esc_attr( $first_category ? $first_category->get_color() : '' ) ?>"
    data-category-text-color="<?php echo esc_attr( $first_category ? $first_category->get_text_color() : '' ) ?>"
    data-thumbnail="<?php echo esc_url( $listing_thumbnail ) ?>"
    data-template="<?php echo esc_attr( $options['template'] ) ?>"
>
<?php
if ( $preview_template = locate_template( sprintf( 'templates/single-listing/previews/%s.php', $options['template'] ) ) ) {
    require $preview_template;
} else {
    require locate_template( 'templates/single-listing/previews/default.php' );
}
?>
</div>
</div>