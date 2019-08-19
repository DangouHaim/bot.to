<?php

namespace MyListing\Int\Elementor;

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Elementor {
	use \MyListing\Src\Traits\Instantiatable;

	public
		$widgets,
		$controls;

	public function __construct() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		$this->widgets = [
			'Page_Heading',
			'Title_Bar',
			'Featured_Section',
			'Section_Heading',
			'Listing_Categories',
			'Listing_Feed',
			'Add_Listing',
			'Info_Cards',
			'Featured_Service',
			'Testimonials',
			'Team',
			'Image',
			'Clients_Slider',
			'Map',
			'Package_Selection',
			'Explore',
			'Blog_Feed',

			// Block Elements
			'Content_Block',
			'Gallery_Block',
			'Countdown_Block',
			'List_Block',
			'Table_Block',
			'Accordion_Block',
			'Tabs_Block',
			'Video_Block',
		];

		$this->controls = [
			'icon',
		];

		add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
		add_action( 'elementor/init', array( $this, 'controls_registered' ) );

		add_action('elementor/documents/register_controls', [$this, 'elementor_page_settings_controls']);

		add_action( 'elementor/element/column/layout/before_section_end', function( $column ) {
			$column->add_control(
				'mylisting_link_to',
				[
					'label' => _x( 'Link to url', 'Elementor column settings', 'my-listing' ),
					'type' => Controls_Manager::URL,
					'show_external' => true,
					'default' => [
						'url' => '',
						'is_external' => false,
						'nofollow' => false,
					],
				]
			);
		} );

		add_action( 'elementor/element/after_add_attributes', function( $element ) {
			$link_to = $element->get_settings('mylisting_link_to');
			if ( ! is_array( $link_to ) || empty( trim( $link_to['url'] ) ) ) {
				return;
			}

			$element->add_render_attribute( '_wrapper', 'data-mylisting-link-to', wp_json_encode( $link_to ) );
		} );

		add_action('elementor/element/section/section_background/before_section_end', function( $section, $tab ) {
			$section->add_control(
				'c27_use_parallax',
				[
					'label' => __( 'Use Parallax Effect?', 'my-listing' ),
					'type' => Controls_Manager::SWITCHER,
					'default' => '',
					'label_on' => __( 'Yes', 'my-listing' ),
					'label_off' => __( 'No', 'my-listing' ),
					'condition' => [
						'background_background' => [ 'classic' ],
					],
					'prefix_class' => 'elementor-section-use-parallax-',
				]
			);
		}, 10, 2);

		add_action( 'wp_enqueue_scripts', [ $this, 'load_custom_fonts' ], 10 );

		add_action( 'wp_enqueue_scripts', function() {
			if ( class_exists( '\Elementor\Frontend' ) ) {
				\Elementor\Plugin::instance()->frontend->enqueue_styles();
			}
		} );

		// add support for Elementor Pro custom headers & footers
		add_action( 'elementor/theme/register_locations', [ $this, 'register_locations' ] );

		add_filter( 'mylisting/header-config', [ $this, 'theme_header_config' ] );
	}

	public function widgets_registered() {
		if ( ! defined( 'ELEMENTOR_PATH' ) || ! class_exists( '\Elementor\Widget_Base' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::instance();


		foreach ( $this->widgets as $widget ) {
			$classname = sprintf( '\MyListing\Int\Elementor\Widgets\%s', $widget );
			if ( class_exists( $classname ) ) {
				\Elementor\Plugin::instance()->widgets_manager->register_widget_type(
					new $classname()
				);
			}
		}
	}

	public function controls_registered() {
		if ( ! defined( 'ELEMENTOR_PATH' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		if ( ! class_exists( 'Elementor\Base_Data_Control' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		foreach ($this->controls as $control) {
			$template_file = CASE27_INTEGRATIONS_DIR . "/elementor/controls/{$control}.php";
			if ( file_exists( $template_file ) ) {
				require_once $template_file;
			}
		}
	}

	public function elementor_page_settings_controls( $page ) {
		$page->start_controls_section(
			'mylisting_page_header_settings',
			[
				'label' => __( 'Header', 'my-listing' ),
				'tab' => Controls_Manager::TAB_SETTINGS,
			]
		);

		$page->add_control(
			'c27_hide_header',
			[
				'label' => __( 'Hide Header?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => __( 'Hide', 'my-listing' ),
				'label_off' => __( 'Show', 'my-listing' ),
			]
		);

		$page->add_control(
			'c27_header_blend_to_next_section',
			[
				'label' => __( 'Blend header to the next section?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => __( 'Yes', 'my-listing' ),
				'label_off' => __( 'No', 'my-listing' ),
			]
		);

		$page->add_control(
			'c27_show_title_bar',
			[
				'label' => __( 'Show Title Bar?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => c27()->get_setting('header_show_title_bar', false) ? 'yes' : '',
				'label_on' => __( 'Show', 'my-listing' ),
				'label_off' => __( 'Hide', 'my-listing' ),
				'condition' => ['c27_hide_header' => ''],
			]
		);

		$page->add_control(
			'c27_customize_header',
			[
				'label' => __( 'Customize Header?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => __( 'Yes', 'my-listing' ),
				'label_off' => __( 'No', 'my-listing' ),
				'condition' => ['c27_hide_header' => ''],
			]
		);

		$page->add_control(
			'c27_header_style',
			[
				'label' => __( 'Height', 'my-listing' ),
				'type' => Controls_Manager::SELECT,
				'default' => c27()->get_setting('header_style', 'default'),
				'options' => [
					'default' => __( 'Normal', 'my-listing' ),
					'alternate' => __( 'Extended', 'my-listing' ),
				],
				'condition' => ['c27_customize_header' => 'yes'],
			]
		);

		$page->add_control(
			'c27_header_skin',
			[
				'label' => __( 'Text Color', 'my-listing' ),
				'type' => Controls_Manager::SELECT,
				'default' => c27()->get_setting('header_skin', 'dark'),
				'options' => [
					'dark' => __( 'Light', 'my-listing' ),
					'light' => __( 'Dark', 'my-listing' ),
				],
				'condition' => ['c27_customize_header' => 'yes'],
			]
		);

		$page->add_control(
			'c27_header_position',
			[
				'label' => __( 'Sticky header on scroll?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => c27()->get_setting('header_fixed', true) == true ? 'yes' : '',
				'label_on' => __( 'Yes', 'my-listing' ),
				'label_off' => __( 'No', 'my-listing' ),
				'return_value' => 'yes',
				'condition' => ['c27_customize_header' => 'yes'],
			]
		);

		$page->add_control(
		    'c27_header_background',
		    [
		        'label' => __( 'Background Color', 'my-listing' ),
		        'type' => Controls_Manager::COLOR,
		        'default' => c27()->get_setting('header_background_color', 'rgba(29, 29, 31, 0.95)'),
				'condition' => ['c27_customize_header' => 'yes'],
		    ]
		);

		$page->add_control(
		    'c27_header_border_color',
		    [
		        'label' => __( 'Border Color', 'my-listing' ),
		        'type' => Controls_Manager::COLOR,
		        'default' => c27()->get_setting('header_border_color', 'rgba(29, 29, 31, 0.95)'),
				'condition' => ['c27_customize_header' => 'yes'],
		    ]
		);

		$page->add_control(
			'c27_header_show_search_form',
			[
				'label' => __( 'Show Search Form?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => c27()->get_setting('header_show_search_form', true) == true ? 'yes' : '',
				'label_on' => __( 'Show', 'my-listing' ),
				'label_off' => __( 'Hide', 'my-listing' ),
				'return_value' => 'yes',
				'condition' => ['c27_customize_header' => 'yes'],
			]
		);

		$page->add_control(
			'c27_header_menu_location',
			[
				'label' => __( 'Main Menu Location', 'my-listing' ),
				'type' => Controls_Manager::SELECT,
				'default' => c27()->get_setting('header_menu_location', 'right'),
				'options' => [
					'left' => __( 'Left', 'my-listing' ),
					'right' => __( 'Right', 'my-listing' ),
				],
				'condition' => ['c27_customize_header' => 'yes'],
			]
		);

		$page->add_control(
			'c27_header_show_call_to_action',
			[
				'label' => __( 'Show Call to Action button?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => c27()->get_setting('header_show_call_to_action_button', false) == true ? 'yes' : '',
				'label_on' => __( 'Show', 'my-listing' ),
				'label_off' => __( 'Hide', 'my-listing' ),
				'return_value' => 'yes',
				'condition' => ['c27_customize_header' => 'yes'],
			]
		);

		$page->end_controls_section();

		$page->start_controls_section(
			'mylisting_page_footer_settings',
			[
				'label' => __( 'Footer', 'my-listing' ),
				'tab' => Controls_Manager::TAB_SETTINGS,
			]
		);

		$page->add_control(
			'c27_hide_footer',
			[
				'label' => __( 'Hide footer?', 'my-listing' ),
				'description' => __( 'Useful when you want to add a custom footer.', 'my-listing'),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => __( 'Hide', 'my-listing' ),
				'label_off' => __( 'Show', 'my-listing' ),
			]
		);

		$page->add_control(
			'c27_customize_footer',
			[
				'label' => __( 'Customize Footer?', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => __( 'Yes', 'my-listing' ),
				'label_off' => __( 'No', 'my-listing' ),
				'condition' => ['c27_hide_footer' => ''],
			]
		);

		$page->add_control(
			'c27_footer_show_widgets',
			[
				'label' => __( 'Show Widgets', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'label_on' => __( 'Show', 'my-listing' ),
				'label_off' => __( 'Hide', 'my-listing' ),
				'return_value' => 'yes',
				'condition' => ['c27_customize_footer' => 'yes'],
			]
		);

		$page->add_control(
			'c27_footer_show_footer_menu',
			[
				'label' => __( 'Show Footer Menu', 'my-listing' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'label_on' => __( 'Show', 'my-listing' ),
				'label_off' => __( 'Hide', 'my-listing' ),
				'return_value' => 'yes',
				'condition' => ['c27_customize_footer' => 'yes'],
			]
		);

		$page->end_controls_section();
	}

	public function load_custom_fonts() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$elementor = \Elementor\Plugin::instance();
		$typography = $elementor->schemes_manager->get_scheme( 'typography' )->get_scheme_value();
		$styles = [];
		$typo_classes = [
			'primary' => \Elementor\Scheme_Typography::TYPOGRAPHY_1,
			'secondary' => \Elementor\Scheme_Typography::TYPOGRAPHY_2,
			'body' => \Elementor\Scheme_Typography::TYPOGRAPHY_3,
			'accent' => \Elementor\Scheme_Typography::TYPOGRAPHY_4,
		];

		foreach ( $typo_classes as $typo_class => $typo_id ) {
			if ( ! is_array( $typography[ $typo_id ] ) || empty( $typography[ $typo_id ] ) ) {
				continue;
			}

			$font_family = '';
			$font_weight = '';

			if ( ! empty( $typography[ $typo_id ][ 'font_family' ] ) ) {
				$font_family = "font-family: '{$typography[ $typo_id ][ 'font_family' ]}', GlacialIndifference, sans-serif !important;";
			}

			if ( ! empty( $typography[ $typo_id ][ 'font_weight' ] ) ) {
				$font_weight = "font-weight: {$typography[ $typo_id ][ 'font_weight' ]} !important;";
			}

			if ( ! ( $font_family || $font_weight ) ) {
				continue;
			}

			$styles[] = ".case27-{$typo_class}-text { $font_family $font_weight } ";

			if ( $typo_class == 'primary' ) {
				$styles[] = ".featured-section .fc-description h1, .featured-section .fc-description h2, .featured-section .fc-description h3, .featured-section .fc-description h4, .featured-section .fc-description h5, .featured-section .fc-description h6 { $font_family $font_weight } ";
			}

			if ( $typo_class == 'secondary' ) {
				$styles[] = ".title-style-1 h5 { $font_family $font_weight } ";
			}

			if ( $typo_class == 'body' ) {
				if ( $font_family ) {
					// if a custom body font has been set, don't load the default theme fonts.
					add_filter( 'mylisting/assets/load-default-font', '__return_false' );
					$styles[] = "body, p { $font_family } ";
				}

				if ( $font_weight ) {
					$styles[] = "p { $font_weight }";
				}
			}
		}

		$stylestring = join( ' ', $styles );

		add_action( 'wp_enqueue_scripts', function() use( $stylestring ) {
			wp_add_inline_style( 'theme-styles-default', $stylestring );
		}, 50 );
	}

	public function register_locations( $location_manager ) {
		$location_manager->register_location( 'header' );
		$location_manager->register_location( 'footer' );
	}

	public function theme_header_config( $config ) {
		// Get the page settings manager
		$page_settings_manager = \Elementor\Core\Settings\Manager::get_settings_managers( 'page' );

		// Get the settings model for current post
		$page_settings_model = $page_settings_manager->get_model( get_queried_object_id() );

        $GLOBALS['c27_elementor_page'] = $page_settings_model; // @todo: rewrite

        $config['header']['show'] = false;
        $config['title-bar']['show'] = false;

        if ( $page_settings_model->get_settings( 'c27_hide_header' ) !== 'yes' ) {
            $config['header']['show'] = true;
            $config['title-bar']['show'] = $page_settings_model->get_settings( 'c27_show_title_bar' ) === 'yes';
            $config['header']['args'] = [];
            $config['header']['args']['blend_to_next_section'] = $page_settings_model->get_settings( 'c27_header_blend_to_next_section' ) === 'yes';

            if ( $page_settings_model->get_settings( 'c27_customize_header' ) === 'yes' ) {
            	$config['header']['args']['fixed']               = $page_settings_model->get_settings( 'c27_header_position' );
            	$config['header']['args']['style']               = $page_settings_model->get_settings( 'c27_header_style' );
            	$config['header']['args']['skin']                = $page_settings_model->get_settings( 'c27_header_skin' );
            	$config['header']['args']['menu_location']       = $page_settings_model->get_settings( 'c27_header_menu_location' );
            	$config['header']['args']['background_color']    = $page_settings_model->get_settings( 'c27_header_background' );
            	$config['header']['args']['border_color']        = $page_settings_model->get_settings( 'c27_header_border_color' );
            	$config['header']['args']['show_search_form']    = $page_settings_model->get_settings( 'c27_header_show_search_form' );
            	$config['header']['args']['show_call_to_action'] = $page_settings_model->get_settings( 'c27_header_show_call_to_action' );
            	$config['header']['args']['is_edit_mode']        = \Elementor\Plugin::$instance->editor->is_edit_mode();
            }
        }

	    $is_buddypress_profile = function_exists( 'bp_is_user' ) ? bp_is_user() : false;

	    if ( is_singular('job_listing') || is_page_template('templates/content-featured-image.php') || ( is_singular('post') && has_post_thumbnail() ) || $is_buddypress_profile ) {
	        $config['header']['show'] = true;
	        $config['title-bar']['show'] = false;
	        $config['header']['args']['style'] = c27()->get_setting('single_listing_header_style', 'default');
	        $config['header']['args']['skin'] = c27()->get_setting('single_listing_header_skin', 'dark');
	        $config['header']['args']['background_color'] = c27()->get_setting('single_listing_header_background_color', 'rgba(29, 29, 31, 0.95)');
	        $config['header']['args']['border_color'] = c27()->get_setting('single_listing_header_border_color', 'rgba(29, 29, 31, 0.95)');
	        $config['header']['args']['fixed'] = true;
	        $config['header']['args']['blend_to_next_section'] = true;
	    }

	    return $config;
	}
}
