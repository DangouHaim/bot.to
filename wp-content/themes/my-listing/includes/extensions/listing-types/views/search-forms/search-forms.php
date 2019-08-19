<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>
<div class="sub-tabs">
	<ul>
		<li :class="currentTab == 'search-page' && currentSubTab == 'advanced' ? 'active' : ''" class="search-page-tab-advanced">
			<a @click.prevent="setTab('search-page', 'advanced')">Advanced Form</a>
		</li>
		<li :class="currentTab == 'search-page' && currentSubTab == 'basic' ? 'active' : ''" class="search-page-tab-basic">
			<a @click.prevent="setTab('search-page', 'basic')">Basic Form</a>
		</li>
		<li :class="currentTab == 'search-page' && currentSubTab == 'order' ? 'active' : ''" class="search-page-tab-order">
			<a @click.prevent="setTab('search-page', 'order')">Listing Order</a>
		</li>
		<li :class="currentTab == 'search-page' && currentSubTab == 'explore-tabs' ? 'active' : ''" class="search-page-tab-explore-tabs">
			<a @click.prevent="setTab('search-page', 'explore-tabs')">Explore Tabs</a>
		</li>
	</ul>
</div>

<div class="tab-content">
	<input type="hidden" v-model="search_page_json_string" name="case27_listing_type_search_page">

	<div class="search-page-tab-advanced-content" v-show="currentSubTab == 'advanced' || currentSubTab == 'basic'">
		<?php require_once locate_template( 'includes/extensions/listing-types/views/search-forms/forms.php' ) ?>
	</div>

	<div class="search-page-tab-order-content" v-show="currentSubTab == 'order'">
		<?php require_once locate_template( 'includes/extensions/listing-types/views/search-forms/listing-order.php' ) ?>
	</div>

	<div class="search-page-tab-explore-tabs-content" v-show="currentSubTab == 'explore-tabs'">
		<?php require_once locate_template( 'includes/extensions/listing-types/views/search-forms/explore-tabs.php' ) ?>
	</div>
</div>

<!-- <pre>{{ search.order }}</pre> -->
