<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>
<div class="advanced-search-form">
	<h3 class="section-title" v-show="currentSubTab == 'advanced'">
		Customize the advanced search form for this listing type
		<span class="subtitle">Not sure what's this? <a href="https://docs.mylistingtheme.com/article/configuring-search-forms/" target="_blank">View the docs</a>.</span>
	</h3>

	<h3 class="section-title" v-show="currentSubTab == 'basic'">
		Customize the basic search form for this listing type
		<span class="subtitle">Not sure what's this? <a href="https://docs.mylistingtheme.com/article/configuring-search-forms/" target="_blank">View the docs</a>.</span>
	</h3>

	<div class="fields-wrapper">
		<div class="fields-column left">
			<h4>Filters <span>Click on a filter to edit. Drag & Drop to reorder.</span></h4>

			<draggable v-model="search[state.search.active_form].facets" :options="{group: 'facet-types', animation: 100, handle: 'h5'}" @start="drag=true" @end="drag=false" class="fields-draggable" :class="drag ? 'active' : ''">
				<div v-for="facet in search[state.search.active_form].facets">
					<div class="facet field" v-if="facet">
						<h5>
							<span class="prefix">+</span>
							{{facet.label}}
							<small>({{ facet.type }})</small>
							<span class="actions">
								<span title="Delete this field" @click.prevent="searchTab().deleteFacet(facet, state.search.active_form)"><i class="mi delete"></i></span>
							</span>
						</h5>
						<div class="options edit">
							<?php foreach ( $designer->get_filters() as $filter ): ?>
								<?php echo $filter->print_options() ?>
							<?php endforeach ?>
						</div>
					</div>
				</div>
			</draggable>
			<div class="placeholder-fields">
				<div class="field-wrapper">
					<div class="field"><h5>Placeholder field</h5></div>
					<div class="field"><h5>Placeholder field</h5></div>
					<div class="field"><h5>Placeholder field</h5></div>
					<div class="field"><h5>Placeholder field</h5></div>
				</div>
			</div>
		</div>
		<div class="fields-column right">
			<h4>Add a new filter</h4>

			<div class="form-group field add-new-field">
				<label>Select filter</label>
				<div class="select-wrapper">
					<select v-model="state.search.new_facet_type">
						<option v-for="facet in blueprints.facet_types" v-if="!facet.form || facet.form === state.search.active_form" :value="facet.type">{{ facet.label }}</option>
					</select>
				</div>

				<button class="btn btn-primary pull-right" @click.prevent="searchTab().addFacet(state.search.active_form)">Add</button>
			</div>
		</div>
	</div>
</div>