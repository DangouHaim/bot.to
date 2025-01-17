<?php

namespace MyListing\Src;

class Term {

	private $data;

	public static $instances = [];

	/**
	 * Get a new term instance (Multiton pattern). When called the first time, the term
	 * will be fetched from database. Otherwise, it will return the previous instance.
	 *
	 * @since 2.1
	 * @param $term int or \WP_Term
	 */
	public static function get( $term ) {
		if ( is_numeric( $term ) ) {
			$term = get_term( $term );
		}

		if ( ! $term instanceof \WP_Term ) {
			return false;
		}

		if ( ! array_key_exists( $term->term_id, self::$instances ) ) {
			self::$instances[ $term->term_id ] = new self( $term );
		}

		return self::$instances[ $term->term_id ];
	}

	public static function get_by_slug( $slug, $taxonomy ) {
		return self::get( get_term_by( 'slug', $slug, $taxonomy ) );
	}

	public function __construct( \WP_Term $term ) {
		$this->data = $term;
	}

	public function get_id() {
		return $this->data->term_id;
	}

	public function get_name() {
		return wp_specialchars_decode( $this->data->name );
	}

	public function get_description() {
		return $this->data->description;
	}

	public function get_slug() {
		return $this->data->slug;
	}

	public function get_parent_id() {
		return $this->data->parent;
	}

	public function get_full_name( $object = null, $name = null ) {
		if ( ! $object ) {
			$object = $this->data;
		}

		if ( ! $name ) {
			$name = $object->name;
		}

		if ( $object->parent && ( $parent = get_term( $object->parent, $object->taxonomy ) ) ) {
			return $this->get_full_name( $parent, "{$parent->name} &#9656; {$name}" );
		}

		return $name;
	}

	public function get_data( $key = null ) {
		if ( $key ) {
			return isset( $this->data->$key ) ? $this->data->$key : false;
		}

		return $this->data;
	}

	public function get_taxonomy() {
		return $this->data->taxonomy;
	}

	/**
	 * Get Icon
	 *
	 * @param array $args Options to modify the icon output.
	 * @return string HTML output.
	 */
	public function get_icon( $args = [] ) {
		$args = c27()->merge_options( [
			'color' => true,
			'background' => true,
			], $args );

		$styles = '';

		// filter: drop-shadow(red 0px -200px 0px)
		if ( $args['color'] ) {
			$styles .= 'color: ' . esc_attr( $this->get_text_color() ) . '; ';
		}

		if ( $args['background'] ) {
			$styles .= 'background: ' . esc_attr( $this->get_color() ) . '; ';
		}

		// Image Icon.
		if ( 'image' === $this->get_icon_type() && $url = $this->get_icon_image_url() ) {
			ob_start(); ?>
				<div class="term-icon image-icon" style="<?php echo esc_attr( $styles ) ?>">
					<img src="<?php echo esc_url( $url ) ?>">
				</div>
			<?php return ob_get_clean();
		}

		// Font Icon.
		ob_start(); ?>
			<i class="<?php echo esc_attr( $this->get_icon_font() ) ?>" style="<?php echo esc_attr( $styles ) ?>"></i>
		<?php return ob_get_clean();
	}

	/**
	 * Get Icon Font
	 */
	public function get_icon_font() {
		if ( $icon = $this->get_recursive_field( 'icon' ) ) {
			return $icon;
		}

		if ( $this->data->taxonomy == 'region' ) {
			$default_icon = 'icon-location-pin-4';
		} else {
			$default_icon = 'mi bookmark_border';
		}

		return apply_filters( 'case27\classes\term\default_icon', $default_icon, $this );
	}

	/**
	 * Get Icon Image
	 */
	public function get_icon_image() {
		if ( $icon = $this->get_recursive_field( 'icon_image' ) ) {
			return $icon;
		}

		return apply_filters( 'case27\classes\term\default_icon_image', '' );
	}

	/**
	 * Get Icon Image URL.
	 */
	public function get_icon_image_url() {
		$image = $this->get_icon_image();
		$image_url = $image && is_array( $image ) && isset( $image['sizes']['large'] ) ? $image['sizes']['large'] : '';
		return esc_url( $image_url );
	}

	/**
	 * Get Icon Type
	 */
	public function get_icon_type() {
		$type = $this->get_recursive_field( 'icon_type' );
		return 'image' === $type ? 'image' : 'icon';
	}

	public function get_image() {
		if ( $image = $this->get_recursive_field( 'image' ) ) {
			return $image;
		}

		return apply_filters( 'case27\classes\term\default_image', '' );
	}

	public function get_color() {
		if ( $color = $this->get_recursive_field( 'color' ) ) {
			return $color;
		}

		return apply_filters( 'case27\classes\term\default_color', c27()->get_setting( 'general_brand_color', '#f24286' ) );
	}

	public function get_text_color() {
		if ( $text_color = $this->get_recursive_field( 'text_color' ) ) {
			return $text_color;
		}

		return apply_filters( 'case27\classes\term\default_text_color', '#fff' );
	}

	public function get_link() {
		return get_term_link( $this->data );
	}

	public function get_count() {
		if ( ! $count = get_term_meta( $this->data->term_id, 'listings_full_count', true ) ) {
			$count = $this->data->count;
		}

		if ( $count ) {
			return sprintf(
				_n( '%s listing', '%s listings', $count, 'my-listing' ),
				number_format_i18n( $count )
			);
		}

		return __( 'No listings', 'my-listing' );
	}


	/*
	 * Check if field value exists for this term.
	 * If not, recursively check the term parent, until a value is found.
	 * Otherwise, return false.
	 */
	public function get_recursive_field( $field_name, $term = null ) {
		if ( ! $term ) {
			$term = $this->data;
		}

		if ( $field = get_field( $field_name, $term->taxonomy . '_' . $term->term_id ) ) {
			return $field;
		}

		if ( $term->parent && ( $parent = get_term( $term->parent, $term->taxonomy ) ) ) {
			return $this->get_recursive_field( $field_name, $parent );
		}

		return false;
	}

	/**
	 * Get the list of listing types this term belongs to.
	 *
	 * @since  2.1
	 * @return array of listing type ids
	 */
	public function get_listing_types() {
		return array_filter( array_map( 'absint',
			(array) get_term_meta( $this->get_id(), 'listing_type', true )
		) );
	}

	public static function get_term_tree( $terms = [], $parent = 0 ) {
		$result = [];

		foreach( $terms as $term ) {
			if ( $parent == $term->parent ) {
				$term->children = self::get_term_tree( $terms, $term->term_id );
				$result[] = $term;
			}
		}

		return $result;
	}

	public static function iterate_recursively( $callback, $terms, $depth = 0 ) {
		$depth++;
		foreach ( $terms as $term ) {
			$callback( $term, $depth );

			if ( ! empty( $term->children ) && is_array( $term->children ) ) {
				self::iterate_recursively( $callback, $term->children, $depth );
			}
		}
	}
}