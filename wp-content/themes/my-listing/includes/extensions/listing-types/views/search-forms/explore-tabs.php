<?php
if ( ! defined('ABSPATH') ) {
	exit;
}

$explore_tab_limit = absint( apply_filters( 'mylisting/type-editor/explore-tabs/limit', 3 ) );
?>

<h3 class="section-title">
	Explore Tabs
	<p>Set what tabs should be shown in the Explore page sidebar for this listing type.</p>
</h3>

<div class="fields-wrapper">
	<div class="fields-column left">
		<h4>Tabs</h4>
		<draggable v-model="search.explore_tabs" :options="{group: 'search-explore-tabs', animation: 100, handle: 'h5'}" @start="drag=true" @end="drag=false" class="fields-draggable" :class="drag ? 'active' : ''">
			<div v-for="tab in search.explore_tabs" class="field explore-tab">
				<h5>
					<span class="prefix">+</span>
					{{ tab.label }}
					<small>({{capitalize( tab.type )}})</small>
					<span class="actions">
						<span title="Remove" @click.prevent="searchTab().removeTab(tab)"><i class="mi delete"></i></span>
					</span>
				</h5>
				<div class="edit">
					<div class="form-group">
						<label>Label</label>
						<input type="text" v-model="tab.label">
					</div>

					<div class="form-group">
						<label>Icon</label>
						<iconpicker v-model="tab.icon"></iconpicker>
					</div>

					<div style="clear: both;"></div>

					<div v-show="tab.type !== 'search-form'">
						<div class="form-group">
							<label>Order by</label>
							<div class="select-wrapper">
								<select v-model="tab.orderby">
									<option value="name">Name</option>
									<option value="count">Count</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label>Order</label>
							<div class="select-wrapper">
								<select v-model="tab.order">
									<option value="ASC">Ascending</option>
									<option value="DESC">Descending</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label>
								<input type="checkbox" v-model="tab.hide_empty">
								Hide empty terms?
							</label>
							<p class="form-description">If checked, terms that won't retrieve any results will not be shown.</p>
						</div>
					</div>
					<div style="clear: both;"></div>
				</div>
			</div>
		</draggable>
		<div v-if="search.explore_tabs.length >= <?php echo $explore_tab_limit ?>" class="ml-empty">
			<i class="mi error_outline"></i>
			<p>You've reached the maximum number of tabs allowed (<?php echo $explore_tab_limit ?>).</p>
		</div>
		<div v-else-if="!search.explore_tabs.length" class="ml-empty">
			<i class="mi playlist_add"></i>
			<p>No tabs added yet.</p>
		</div>
		<div v-else class="placeholder-fields">
			<div class="field-wrapper">
				<div class="field"><h5>&nbsp;</h5></div>
				<div class="field"><h5>&nbsp;</h5></div>
			</div>
		</div>
	</div>

	<div class="fields-column right">
		<h4>Add new tab</h4>
		<div class="form-group field add-new-field text-center add-new-order" :class="search.explore_tabs.length >= <?php echo $explore_tab_limit ?> ? 'ml-overlay-disabled' : ''">
			<button
				v-for="preset in blueprints.explore_tabs"
				class="btn btn-secondary block"
				@click.prevent="searchTab().addTab( preset )"
				v-if="search.explore_tabs.filter( function(t) { return t.type === preset.type } ).length === 0"
			>{{ preset.label }}</button>
		</div>
	</div>
</div>

<!-- <pre>{{ search.explore_tabs }}</pre> -->