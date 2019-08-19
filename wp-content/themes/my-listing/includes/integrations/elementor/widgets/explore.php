<?php

namespace MyListing\Int\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Elementor\Plugin;
use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

class Explore extends Widget_Base {

	public function get_name() {
		return 'case27-explore-widget';
	}

	public function get_title() {
		return __( '<strong>27</strong> > Explore Listings', 'my-listing' );
	}

	public function get_icon() {
		return 'eicon-post';
	}

	public function get_script_depends() {
		return ['mylisting-explore'];
	}

	protected function _register_controls() {
		$traits = new \MyListing\Int\Elementor\Traits( $this );

		$this->start_controls_section( 'section_content_block', [
			'label' => esc_html__( 'Content', 'my-listing' ),
		] );

		$this->add_control( '27_title', [
			'label' => __( 'Title', 'my-listing' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( 'What are you looking for?', 'my-listing' ),
		] );

		$this->add_control( '27_subtitle', [
				'label' => __( 'Subtitle', 'my-listing' ),
				'type' => Controls_Manager::TEXT,
		] );

		$this->add_control( '27_template', [
			'label' => __( 'Template', 'my-listing' ),
			'type' => Controls_Manager::SELECT2,
			'default' => 'explore-1',
			'options' => [
				'explore-1' => __( 'Template 1', 'my-listing' ),
				'explore-2' => __( 'Template 2', 'my-listing' ),
				'explore-no-map' => __( 'Template 3', 'my-listing' ),
			],
		] );

		$this->add_control( '27_finder_columns', [
			'label' => __( 'Columns by default', 'my-listing' ),
			'type' => Controls_Manager::SELECT2,
			'default' => 'finder-one-columns',
			'options' => [
				'finder-one-columns' => __( 'One', 'my-listing' ),
				'finder-two-columns' => __( 'Two', 'my-listing' ),
				'finder-three-columns' => __( 'Three', 'my-listing' ),
			],
			'condition' => ['27_template' => ['explore-1', 'explore-2']],
		] );


		$this->add_control( '27_scroll_to_results', [
			'label' => __( 'Automatically scroll to results?', 'my-listing' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => ['27_template' => ['explore-2']],
		] );

		$this->add_control( '27_disable_live_url_update', [
			'label' => __( 'Disable live url update?', 'my-listing' ),
			'description' => __( 'When listing filters are used in Explore page, the url in the browser\'s address-bar is updated to reflect their new values. You can use this option to disable that behavior.', 'my-listing' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
		] );

		$this->add_control( '27_listing_types', [
			'label' => __( 'Listing Types', 'my-listing' ),
			'type' => Controls_Manager::REPEATER,
			'fields' => [ [
				'name' => 'type',
				'label' => __( 'Select Listing Type', 'my-listing' ),
				'type' => Controls_Manager::SELECT2,
				'options' => c27()->get_posts_dropdown_array([
					'post_type' => 'case27_listing_type',
					'posts_per_page' => -1,
				], 'post_name'),
				'default' => '',
			] ],
			'title_field' => '{{{ type.toUpperCase() }}}',
		] );

		$this->add_control( 'types_template', [
			'label' => _x( 'Display listing types as', 'Elementor > Explore widget settings', 'my-listing' ),
			'type' => Controls_Manager::SELECT2,
			'default' => 'topbar',
			'options' => [
				'topbar' => _x( 'Navbar', 'Elementor > Explore widget settings', 'my-listing' ),
				'dropdown' => _x( 'Dropdown', 'Elementor > Explore widget settings', 'my-listing' ),
			],
		] );

		$this->add_control( 'cts_map_settings', [
	        'label' => __( 'Map', 'my-listing' ),
	        'type' => Controls_Manager::HEADING,
	        'separator' => 'before',
	    ] );

		$this->add_control( '27_map_skin', [
			'label' => __( 'Map Skin', 'my-listing' ),
			'type' => Controls_Manager::SELECT,
			'default' => 'skin1',
			'options' => c27()->get_map_skins(),
		] );

		$this->add_control( '27_scroll_wheel', [
			'label' => __( 'Zoom map using mouse scroll?', 'my-listing' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => '',
			'label_on' => __( 'Yes', 'my-listing' ),
			'label_off' => __( 'No', 'my-listing' ),
			'return_value' => 'yes',
			'condition' => ['27_template' => ['explore-1', 'explore-2']],
		] );

		$this->add_control( 'cts_map_default_lat', [
			'label'   => _x( 'Default latitude', 'Explore map', 'my-listing' ),
			'description' => _x( 'When there are no listings to show on the map, this will be used as the default location.', 'Explore map', 'my-listing' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 51.492,
			'min'     => -90,
			'max'     => 90,
		] );

		$this->add_control( 'cts_map_default_lng', [
			'label'   => _x( 'Default longitude', 'Explore map', 'my-listing' ),
			'description' => _x( 'When there are no listings to show on the map, this will be used as the default location.', 'Explore map', 'my-listing' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => -0.130,
			'min'     => -180,
			'max'     => 180,
		] );

		$this->add_control( 'cts_map_default_zoom', [
			'label'   => _x( 'Default zoom level', 'Explore map', 'my-listing' ),
			'description' => _x( 'Set the map zoom level when there are no map markers to show.', 'Explore map', 'my-listing' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 11,
			'min'     => 0,
			'max'     => 30,
		] );

		$this->add_control( 'cts_map_min_zoom', [
			'label'   => _x( 'Minimum zoom level', 'Explore map', 'my-listing' ),
			'description' => _x( 'Set the minimum zoom level allowed.', 'Explore map', 'my-listing' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 2,
			'min'     => 0,
			'max'     => 30,
		] );

		$this->add_control( 'cts_map_max_zoom', [
			'label'   => _x( 'Maximum zoom level', 'Explore map', 'my-listing' ),
			'description' => _x( 'Set the maximum zoom level allowed.', 'Explore map', 'my-listing' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 18,
			'min'     => 0,
			'max'     => 30,
		] );

		$this->add_control( 'categories_tab_heading', [
	        'label' => __( 'Categories/Taxonomies Tab', 'my-listing' ),
	        'type' => Controls_Manager::HEADING,
	        'separator' => 'before',
	    ] );

		$this->add_control( 'categories_count', [
			'label'   => __( 'Item Count', 'my-listing' ),
			'description'   => __( 'Set the amount of terms to show in taxonomy tabs. Leave blank to show all.', 'my-listing' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 10,
			'min'     => 0,
		] );

		$traits->choose_overlay( __( 'Set an overlay for taxonomy terms', 'my-listing' ), '27_categories_overlay' );

		$this->end_controls_section();
	}

	protected function render( $instance = [] ) {
		c27()->get_section( 'explore', [
			'title' => $this->get_settings('27_title'),
			'subtitle' => $this->get_settings('27_subtitle'),
			'listing_types' => $this->get_settings('27_listing_types'),
			'types_template' => $this->get_settings('types_template'),
			'categories' => [
				'count'      => $this->get_settings( 'categories_count' ),
			],
			'scroll_to_results' => 'yes' == $this->get_settings( '27_scroll_to_results' ),
			'disable_live_url_update' => 'yes' === $this->get_settings( '27_disable_live_url_update' ),
			'template' => $this->get_settings('27_template'),
			'finder_columns' => $this->get_settings('27_finder_columns'),
			'is_edit_mode' => Plugin::$instance->editor->is_edit_mode(),
			'categories_overlay' => [
				'type' => $this->get_settings('27_categories_overlay'),
				'gradient' => $this->get_settings('27_categories_overlay__gradient'),
				'solid_color' => $this->get_settings('27_categories_overlay__solid_color'),
			],
			'map' => [
				'default_lat' => $this->get_settings( 'cts_map_default_lat' ),
				'default_lng' => $this->get_settings( 'cts_map_default_lng' ),
				'default_zoom' => $this->get_settings( 'cts_map_default_zoom' ),
				'min_zoom' => $this->get_settings( 'cts_map_min_zoom' ),
				'max_zoom' => $this->get_settings( 'cts_map_max_zoom' ),
				'skin' => $this->get_settings( '27_map_skin' ),
				'scrollwheel' => $this->get_settings( '27_scroll_wheel' ),
			],
		] );
	}

	protected function content_template() {}
	public function render_plain_content( $instance = [] ) {}
}
