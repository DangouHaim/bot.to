<?php
/**
 * Parallax cover image template for single listing page.
 *
 * @since 1.6.0
 */

// Use the empty template if listing cover image isn't available.
$image = "https://bot.to/wp-content/themes/my-listing/assets/images/marketplace.jpg";
$listing_logo = "https://bot.to/wp-content/themes/my-listing/assets/images/cart.png";

// Overlay options.
$overlay_opacity = c27()->get_setting( 'single_listing_cover_overlay_opacity', '0.5' );
$overlay_color   = c27()->get_setting( 'single_listing_cover_overlay_color', '#242429' );
?>

<section class="marketplace-header featured-section profile-cover parallax-bg profile-cover-image" style="background-image: url('<?php echo esc_url( $image ) ?>');">
    <div class="overlay"
        style="background-color: <?php echo esc_attr( $overlay_color ); ?>;
                opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
        >
    </div>

    <div class="market-wrap">

        <div class="market-search fixed hidden-sm hidden-xs hidden-md">
            <? echo do_shortcode( '[yith_woocommerce_ajax_search template="" class=""]' ) ?>
        </div>

        <div class="container listing-main-info">
            <div class="col-md-6">
                <div class="profile-name <?php echo esc_attr($tagline ? 'has-tagline' : 'no-tagline') ?>">
                    <?php if ($listing_logo) : ?>
                        <a class="profile-avatar" href="/marketplace/" style="background: transparent;background-image: url('<?php echo esc_url($listing_logo) ?>'); background-size: 80%; background-position: center; background-repeat: no-repeat;"></a>
                    <?php endif ?>

                    <h1 class="case27-primary-text">
                        <a href="/marketplace/" style="color: #fff !important">Marketplace</a>
                        <span class="verified-badge" data-toggle="tooltip" data-title="<?php echo esc_attr(_x('Verified listing', 'Single listing', 'my-listing')) ?>">
                            <i class="fa fa-check"></i>
                        </span>
                    </h1>
                    <div class="pa-below-title">
                        Marketplace for your requirements
                    </div>
                </div>
                <div class="market-search hidden-lg hidden-xl">
                    <? echo do_shortcode( '[yith_woocommerce_ajax_search template="" class=""]' ) ?>
                </div>
            </div>
        </div>
    </div>
</section>