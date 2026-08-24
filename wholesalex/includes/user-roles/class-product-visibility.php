<?php
/**
 * WholesaleX User Roles - Product Visibility Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level product visibility restrictions.
 */
class User_Roles_Product_Visibility {

	/**
	 * Cached hidden product IDs for the current request.
	 *
	 * @var array|null
	 */
	private $hidden_product_ids = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'pre_get_posts', array( $this, 'filter_product_queries' ) );
		add_filter( 'woocommerce_product_query', array( $this, 'filter_product_queries' ) );
		add_filter( 'woocommerce_shortcode_products_query', array( $this, 'filter_product_query_args' ) );
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'filter_product_query_args' ) );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_product_visibility' ), 10, 2 );
		add_filter( 'woocommerce_variation_is_visible', array( $this, 'filter_variation_visibility' ), 10, 2 );
		add_filter( 'woocommerce_related_products', array( $this, 'filter_related_products' ) );
		add_action( 'template_redirect', array( $this, 'redirect_from_hidden_products' ), 8 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'prevent_checkout_hidden_products' ), 8 );
	}

	/**
	 * Exclude role-hidden products from product archive queries.
	 *
	 * @param \WP_Query $query Query object.
	 * @return \WP_Query
	 */
	public function filter_product_queries( $query ) {
		if ( $this->is_admin_request() || ! is_object( $query ) || ! method_exists( $query, 'get' ) ) {
			return $query;
		}

		$post_type        = $query->get( 'post_type' );
		$product_cat      = $query->get( 'product_cat' );
		$is_product_query = 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );

		if ( ! $is_product_query && empty( $product_cat ) ) {
			return $query;
		}

		$hidden_ids = $this->get_hidden_product_ids();
		if ( empty( $hidden_ids ) ) {
			return $query;
		}

		$existing_ids = (array) $query->get( 'post__not_in' );
		$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing_ids, $hidden_ids ) ) ) );

		return $query;
	}

	/**
	 * Exclude role-hidden products from array-based product queries.
	 *
	 * @param array $query_args Query arguments.
	 * @return array
	 */
	public function filter_product_query_args( $query_args ) {
		if ( $this->is_admin_request() || ! is_array( $query_args ) ) {
			return $query_args;
		}

		$hidden_ids = $this->get_hidden_product_ids();
		if ( empty( $hidden_ids ) ) {
			return $query_args;
		}

		$existing_ids                = isset( $query_args['post__not_in'] ) ? (array) $query_args['post__not_in'] : array();
		$query_args['post__not_in'] = array_values( array_unique( array_merge( $existing_ids, $hidden_ids ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Role-hidden products must be excluded from third-party product queries.

		return $query_args;
	}

	/**
	 * Hide role-hidden products from WooCommerce visibility checks.
	 *
	 * @param bool $visible Product visibility status.
	 * @param int  $product_id Product ID.
	 * @return bool
	 */
	public function filter_product_visibility( $visible, $product_id ) {
		if ( $this->is_product_hidden( $product_id ) ) {
			return false;
		}

		return $visible;
	}

	/**
	 * Hide an individually selected variation without hiding its parent product.
	 *
	 * @param bool $visible      Variation visibility status.
	 * @param int  $variation_id Variation ID.
	 * @return bool
	 */
	public function filter_variation_visibility( $visible, $variation_id ) {
		return $this->is_product_hidden( $variation_id ) ? false : $visible;
	}

	/**
	 * Remove role-hidden products from related product lists.
	 *
	 * @param array $product_ids Product IDs.
	 * @return array
	 */
	public function filter_related_products( $product_ids ) {
		return array_values( array_diff( (array) $product_ids, $this->get_hidden_product_ids() ) );
	}

	/**
	 * Redirect users away from role-hidden single product pages.
	 *
	 * @return void
	 */
	public function redirect_from_hidden_products() {
		if ( ! is_product() ) {
			return;
		}

		$product_id = (int) get_the_ID();
		if ( ! $this->is_product_hidden( $product_id ) ) {
			return;
		}

		/* translators: %s: Product name. */
		wc_add_notice( sprintf( __( 'Sorry, you are not allowed to see %s product.', 'wholesalex' ), get_the_title( $product_id ) ), 'notice' );

		$previous_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$redirect_url = ! empty( $previous_url ) ? $previous_url : home_url();
		wp_safe_redirect( $redirect_url );
		exit();
	}

	/**
	 * Remove role-hidden products from cart before checkout.
	 *
	 * @return void
	 */
	public function prevent_checkout_hidden_products() {
		if ( ! isset( WC()->cart ) || empty( WC()->cart ) ) {
			return;
		}

		if ( apply_filters( 'wholesalex_allow_hidden_product_to_checkout', false ) ) {
			return;
		}

		$hidden_ids = $this->get_hidden_product_ids();
		if ( empty( $hidden_ids ) ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product_id = (int) $product_id;

			if ( $this->is_product_hidden( $product_id ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );
				/* translators: %s: Product name. */
				wc_add_notice( sprintf( __( 'Sorry, you are not allowed to checkout %s product.', 'wholesalex' ), get_the_title( $product_id ) ), 'error' );
			}
		}
	}

	/**
	 * Get role-hidden product IDs for the current user.
	 *
	 * @return array
	 */
	private function get_hidden_product_ids() {
		if ( null !== $this->hidden_product_ids ) {
			return $this->hidden_product_ids;
		}

		$this->hidden_product_ids = array();
		$restrictions             = $this->get_current_role_restrictions();

		if ( empty( $restrictions ) || ! $this->is_restriction_enabled( $restrictions, '_visibility_enabled' ) || $this->is_current_user_excluded( $restrictions, '_visibility' ) ) {
			return $this->hidden_product_ids;
		}

		$this->hidden_product_ids = $this->get_products_for_rule(
			$this->normalize_product_filter( isset( $restrictions['_visibility_filter'] ) ? $restrictions['_visibility_filter'] : 'specific_products' ),
			isset( $restrictions['_visibility_products'] ) ? $restrictions['_visibility_products'] : array(),
			isset( $restrictions['_visibility_exclude_products'] ) ? $restrictions['_visibility_exclude_products'] : array()
		);

		return $this->hidden_product_ids;
	}

	/**
	 * Get current role restrictions.
	 *
	 * @return array
	 */
	private function get_current_role_restrictions() {
		$role_id = wholesalex()->get_current_user_role();
		if ( empty( $role_id ) ) {
			return array();
		}

		$role_content = wholesalex()->get_roles( 'by_id', $role_id );
		if ( empty( $role_content['_restrictions'] ) || ! is_array( $role_content['_restrictions'] ) ) {
			return array();
		}

		return $role_content['_restrictions'];
	}

	/**
	 * Check whether current user is excluded from a restriction group.
	 *
	 * @param array  $restrictions Role restrictions.
	 * @param string $prefix Restriction prefix.
	 * @return bool
	 */
	private function is_current_user_excluded( $restrictions, $prefix ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$enabled_key = $prefix . '_exclude_users';
		$list_key    = $prefix . '_exclude_users_list';

		if ( ! $this->is_truthy( isset( $restrictions[ $enabled_key ] ) ? $restrictions[ $enabled_key ] : false ) ) {
			return false;
		}

		$user_id = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		return in_array( $user_id, $this->get_selected_ids( isset( $restrictions[ $list_key ] ) ? $restrictions[ $list_key ] : array() ), true );
	}

	/**
	 * Get product IDs selected by a rule.
	 *
	 * @param string $filter Product filter.
	 * @param array  $selected Selected values.
	 * @param array  $excluded_products Excluded products.
	 * @return array
	 */
	private function get_products_for_rule( $filter, $selected, $excluded_products = array() ) {
		$selected_ids = $this->get_selected_ids( $selected );
		$excluded_ids = $this->get_selected_ids( $excluded_products );
		$product_ids  = array();

		switch ( $filter ) {
			case 'all_products':
				$product_ids = $this->query_product_ids();
				break;
			case 'specific_products':
				$product_ids = $selected_ids;
				break;
			case 'specific_variations':
				$product_ids = array_values(
					array_filter(
						$selected_ids,
						function ( $product_id ) {
							return 'product_variation' === get_post_type( $product_id );
						}
					)
				);
				break;
			case 'categories':
				$product_ids = empty( $selected_ids ) ? array() : $this->query_product_ids(
					array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $selected_ids,
						),
					)
				);
				break;
			case 'brands':
				$brand_taxonomy = $this->get_brand_taxonomy();
				if ( $brand_taxonomy && ! empty( $selected_ids ) ) {
					$product_ids = $this->query_product_ids(
						array(
							array(
								'taxonomy' => $brand_taxonomy,
								'field'    => 'term_id',
								'terms'    => $selected_ids,
							),
						)
					);
				}
				break;
		}

		return array_values( array_diff( array_unique( array_map( 'absint', $product_ids ) ), $excluded_ids ) );
	}

	/**
	 * Query product IDs.
	 *
	 * @param array $tax_query Tax query.
	 * @return array
	 */
	private function query_product_ids( $tax_query = array() ) {
		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return get_posts( $args );
	}

	/**
	 * Check whether a product or its parent is hidden.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return bool
	 */
	private function is_product_hidden( $product_id ) {
		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return false;
		}

		$hidden_ids = $this->get_hidden_product_ids();
		if ( in_array( $product_id, $hidden_ids, true ) ) {
			return true;
		}

		if ( 'product_variation' === get_post_type( $product_id ) ) {
			$parent_id = wp_get_post_parent_id( $product_id );
			return $parent_id && in_array( (int) $parent_id, $hidden_ids, true );
		}

		return false;
	}

	/**
	 * Get the active WooCommerce brand taxonomy used by the selector.
	 *
	 * @return string
	 */
	private function get_brand_taxonomy() {
		foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand' ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				return $taxonomy;
			}
		}

		return '';
	}

	/**
	 * Normalize product filter keys from UI/legacy values.
	 *
	 * @param string $filter Product filter.
	 * @return string
	 */
	private function normalize_product_filter( $filter ) {
		$map = array(
			'all'               => 'all_products',
			'cat_in_list'       => 'categories',
			'brand_in_list'     => 'brands',
			'products_in_list'  => 'specific_products',
			'attribute_in_list' => 'specific_variations',
		);

		if ( isset( $map[ $filter ] ) ) {
			return $map[ $filter ];
		}

		return in_array( $filter, array( 'all_products', 'specific_products', 'specific_variations', 'categories', 'brands' ), true ) ? $filter : 'specific_products';
	}

	/**
	 * Extract ids from multiselect values.
	 *
	 * @param array $items Multiselect items.
	 * @return array
	 */
	private function get_selected_ids( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return array();
		}

		$ids = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$ids[] = (int) preg_replace( '/[^0-9]/', '', (string) $item['value'] );
			} elseif ( is_numeric( $item ) ) {
				$ids[] = (int) $item;
			}
		}

		return array_values( array_filter( array_unique( $ids ) ) );
	}

	/**
	 * Normalize bool-like UI values.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_truthy( $value ) {
		return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
	}

	/**
	 * Check whether a role restriction section is enabled.
	 *
	 * Missing keys are treated as enabled for backward compatibility with
	 * existing saved roles that predate section-level toggles.
	 *
	 * @param array  $restrictions Role restrictions.
	 * @param string $key Enabled flag key.
	 * @return bool
	 */
	private function is_restriction_enabled( $restrictions, $key ) {
		return ! array_key_exists( $key, $restrictions ) || $this->is_truthy( $restrictions[ $key ] );
	}

	/**
	 * Check whether current request is an admin-only request.
	 *
	 * @return bool
	 */
	private function is_admin_request() {
		return is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
	}
}
