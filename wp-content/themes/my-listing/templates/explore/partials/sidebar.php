<?php
/**
 * Template for displaying explore page sidebar,
 * containing search tabs and filters.
 *
 * @var $explore
 * @since 2.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( empty( $explore->types ) || ! $explore->active_listing_type ) {
	return;
}

// Results page.
$pg = ! empty( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1;
?>

<?php if ( $data['types_template'] === 'dropdown' ): ?>
	<div class="finder-title">
		<h2 class="case27-primary-text"><?php echo esc_html( $data['title'] ) ?></h2>
		<p><?php echo esc_html( $data['subtitle'] ) ?></p>
	</div>
<?php endif ?>

<div class="finder-tabs col-md-12 <?php echo count( $explore->types ) > 1 ? 'with-listing-types' : 'without-listing-types' ?>">

	<?php foreach ( $explore->types as $type ):
		$tabs = $type->get_explore_tabs();
		if ( count( $tabs ) < 2 ) {
			continue;
		} ?>

		<ul class="nav nav-tabs tabs-menu <?php echo 'tab-count-'.count($tabs) ?>" role="tablist" v-show="activeType.slug === '<?php echo esc_attr( $type->get_slug() ) ?>'">
			<?php foreach ( $tabs as $tab ):
				$onclick = $tab['type'] === 'search-form'
					? 'activeType.tab = \'search-form\'; getListings();'
					: 'termsExplore(\''.$tab['type'].'\', \'active\' )'
				?>
				<li :class="activeType.tab == '<?php echo esc_attr( $tab['type'] ) ?>' ? 'active' : ''">
					<a href="#<?php echo esc_attr( $tab['type'] ) ?>" role="tab" class="tab-switch" @click="<?php echo esc_attr( $onclick ) ?>">
						<i class="<?php echo esc_attr( $tab['icon'] ) ?>"></i><p><?php echo esc_html( $tab['label'] ) ?></p>
					</a>
				</li>
			<?php endforeach ?>
		</ul>
	<?php endforeach ?>

	<?php if ( $data['types_template'] === 'dropdown' && count( $explore->types ) > 1 ): ?>
		<div class="types-dropdown-wrapper" v-show="activeType.tab === 'search-form'">
			<?php require locate_template( 'templates/explore/partials/types-dropdown.php' ) ?>
		</div>
	<?php endif ?>

	<?php foreach ( $explore->types as $type ): ?>
		<div class="tab-content" v-show="activeType.slug === '<?php echo esc_attr( $type->get_slug() ) ?>'">

			<div id="search-form" class="listing-type-filters search-tab tab-pane fade" :class="activeType.tab == 'search-form' ? 'in active' : ''">
				<?php $GLOBALS['c27-facets-vue-object'][ $type->get_slug() ] = []; ?>
				<div class="search-filters type-<?php echo esc_attr( $type->get_slug() ) ?>">
					<div class="light-forms filter-wrapper">

						<?php foreach ((array) $type->get_search_filters() as $facet): ?>
							<?php if ( $facet['type'] == 'order' ): ?>
								<?php continue; ?>
							<?php endif ?>

							<?php c27()->get_partial( "facets/{$facet['type']}", [
								'facet' => $facet,
								'listing_type' => $type->get_slug(),
								'type' => $type,
							] ) ?>
						<?php endforeach ?>
						<?php $GLOBALS['c27-facets-vue-object'][ $type->get_slug() ]['page'] = ( $pg >= 1 ? $pg - 1 : 0 ); ?>
					</div>
					<div class="form-group fc-search">
						<a href="#" class="buttons button-2 full-width c27-explore-search-button"
						   @click.prevent="state.mobileTab = 'results'; mobile.matches ? _getListings() : getListings(); _resultsScrollTop();"
						><i class="mi search"></i><?php _e( 'Search', 'my-listing' ) ?></a>
						<a href="#" class="reset-results-27 full-width" @click.prevent="resetFilters($event); getListings();">
							<i class="mi refresh"></i><?php _ex( 'Reset Filters', 'Explore page', 'my-listing' ) ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach ?>

	<div id="explore-taxonomy-tab" class="listing-cat-tab tab-pane fade c27-explore-categories" :class="activeType.tab !== 'search-form' ? 'in active' : ''">
		<div v-if="currentTax">
			<transition-group name="vfade-down">
				<div v-if="currentTax.activeTerm" class="active-term" :key="currentTax.activeTerm.term_id">
					<a href="#" class="taxonomy-back-btn" @click.prevent="termsGoBack( currentTax.activeTerm )" v-if="currentTax.activeTermId !== 0">
						<i class="mi keyboard_backspace"></i><?php _ex( 'Back', 'Explore page', 'my-listing' ) ?>
					</a>

					<div class="active-taxonomy-container" :class="currentTax.activeTerm.background ? 'with-bg' : 'no-bg'">
						<div
							class="category-background" style="height: 200px; background-size: cover;"
							:style="currentTax.activeTerm.background ? 'background-image: url(\''+currentTax.activeTerm.background+'\');' : ''"
						></div>
						<span class="cat-icon" :style="'background-color:'+currentTax.activeTerm.color" v-html="currentTax.activeTerm.single_icon"></span>
						<h1 class="category-name">{{ currentTax.activeTerm.name }}</h1>
						<p class="category-description">{{ currentTax.activeTerm.description }}</p>
					</div>
				</div>

				<div v-show="currentTax.termsLoading && currentTax.activeTermId !== 0 && ! currentTax.activeTerm" class="loader-bg" :key="'single-term-loading-indicator'">
					<div class="listing-cat listing-cat-loading bg-loading-animation"></div>
					<div class="listing-cat-line bg-loading-animation"></div>
					<div class="listing-cat-line bg-loading-animation"></div>
					<div class="listing-cat-line bg-loading-animation"></div>
				</div>
			</transition-group>

			<transition-group :name="currentTax.activeTermId === 0 ? 'vfade-up' : 'vfade-down'">
				<a href="#" class="taxonomy-back-btn" @click.prevent="activeType.tab = 'search-form'; getListings();" v-if="currentTax.activeTermId === 0 && showBackToFilters" :key="'back-to-filters'">
					<i class="mi keyboard_backspace"></i><?php _ex( 'Back to filters', 'Explore page', 'my-listing' ) ?>
				</a>

				<div v-if="Object.keys(currentTax.terms).length && currentTax.activeTermId !== 0" :key="'subterms-loaded-indicator-'+currentTax.activeTermId">
					<h4 class="browse-subcategories"><i class="mi bookmark_border"></i><?php _ex( 'Browse sub-categories', 'Explore page', 'my-listing' ) ?></h4>
				</div>

				<div v-if="currentTax.terms" v-for="term in currentTax.terms" class="listing-cat" :class="term.term_id == currentTax.active_term ? 'active' : ''" :key="term.term_id">
					<a href="#" @click.prevent="termsExplore( activeType.tab, term )">
						<div
							class="overlay <?php echo $explore->data['categories_overlay']['type'] == 'gradient' ? esc_attr( $explore->data['categories_overlay']['gradient'] ) : '' ?>"
							style="<?php echo $explore->data['categories_overlay']['type'] == 'solid_color' ? 'background-color: ' . esc_attr( $explore->data['categories_overlay']['solid_color'] ) . '; ' : '' ?>"
						></div>
						<div class="lc-background" :style="term.background ? 'background-image: url(\''+term.background+'\');' : ''"></div>

						<div class="lc-info">
							<h4 class="case27-secondary-text">{{ term.name }}</h4>
							<h6>{{ term.count }}</h6>
						</div>
						<div class="lc-icon" v-html="term.icon"></div>
					</a>
				</div>

				<div v-if="currentTax.terms && currentTax.hasMore && !currentTax.termsLoading" :key="'load-more-terms'">
					<a href="#" class="buttons button-2" @click.prevent="currentTax.termsPage += 1; termsExplore( activeType.tab, currentTax.activeTerm, true );">
						<?php _ex( 'Load More', 'Explore page', 'my-listing' ) ?>
					</a>
				</div>

				<div v-show="currentTax.termsLoading && currentTax.activeTermId === 0" class="loader-bg" :key="'terms-loading-indicator'">
					<div class="listing-cat listing-cat-loading bg-loading-animation"></div>
					<div class="listing-cat listing-cat-loading bg-loading-animation"></div>
					<div class="listing-cat listing-cat-loading bg-loading-animation"></div>
					<div class="listing-cat listing-cat-loading bg-loading-animation"></div>
				</div>
			</transition-group>
		</div>
	</div>
</div>
