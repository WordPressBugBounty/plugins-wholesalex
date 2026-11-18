<?php
/**
 * Lookup tools for products, categories, customers and checkout options.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Lookup;
use WHOLESALEX\MCP\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Lets an AI assistant find the IDs the rule and role tools need.
 *
 * Customer results never include email addresses, phone numbers or other
 * contact details; searching by email still works.
 */
final class Lookup_Abilities {

	/**
	 * Most results a lookup returns at once.
	 *
	 * @var int
	 */
	const MAX_RESULTS = 100;

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		return array(
			'wholesalex/search-products'       => array(
				'label'        => __( 'Search products', 'wholesalex' ),
				'description'  => __( 'Finds WooCommerce products by name or SKU and returns their IDs, type, SKU and prices. Variable products also list their variation IDs. Use the IDs in rule product scopes.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object(
					Schema::list_properties( __( 'Product name or SKU. Leave empty to list the newest products.', 'wholesalex' ) ) + array(
						'include_variations' => Schema::boolean( __( 'Also return matching variations as separate results.', 'wholesalex' ) ),
					)
				),
				'callback'     => array( __CLASS__, 'search_products' ),
			),
			'wholesalex/search-categories'     => array(
				'label'        => __( 'Search product categories', 'wholesalex' ),
				'description'  => __( 'Finds WooCommerce product categories by name and returns their IDs, parent and product count.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object( Schema::list_properties( __( 'Category name. Leave empty to list every category.', 'wholesalex' ) ) ),
				'callback'     => array( __CLASS__, 'search_categories' ),
			),
			'wholesalex/search-customers'      => array(
				'label'        => __( 'Search customers', 'wholesalex' ),
				'description'  => __( 'Finds customers by name, username or email and returns their user ID, display name and WholesaleX role. Contact details are not returned. Store staff accounts, such as administrators, shop managers and marketplace vendors, are included with store_staff true; only target them in rules when the store owner asks for it.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object(
					Schema::list_properties( __( 'Name, username or email address.', 'wholesalex' ) ) + array(
						'role_id' => Schema::string( __( 'Only customers with this WholesaleX role ID.', 'wholesalex' ), 100 ),
					)
				),
				'callback'     => array( __CLASS__, 'search_customers' ),
			),
			'wholesalex/list-checkout-options' => array(
				'label'        => __( 'List checkout options', 'wholesalex' ),
				'description'  => __( 'Lists the enabled payment methods, the shipping zones with their enabled shipping methods, and the tax classes. Use these IDs in role settings and in payment, shipping and tax rules.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::no_input(),
				'callback'     => array( __CLASS__, 'checkout_options' ),
			),
		);
	}

	/**
	 * Search products.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function search_products( array $input ): array {
		$search     = trim( (string) ( $input['search'] ?? '' ) );
		$limit      = max( 1, min( self::MAX_RESULTS, (int) ( $input['limit'] ?? 20 ) ) );
		$offset     = max( 0, (int) ( $input['offset'] ?? 0 ) );
		$variations = ! empty( $input['include_variations'] );

		if ( '' === $search ) {
			$query = new \WP_Query(
				array(
					'post_type'      => $variations ? array( 'product', 'product_variation' ) : 'product',
					'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
					'posts_per_page' => $limit,
					'offset'         => $offset,
					'orderby'        => 'ID',
					'order'          => 'DESC',
					'fields'         => 'ids',
					'no_found_rows'  => false,
				)
			);
			$ids   = array_map( 'intval', $query->posts );
			$total = (int) $query->found_posts;
		} else {
			$store = \WC_Data_Store::load( 'product' );
			$found = array_values( array_unique( array_map( 'intval', (array) $store->search_products( $search, '', $variations, true ) ) ) );
			$found = array_values( array_filter( $found ) );
			rsort( $found );
			$total = count( $found );
			$ids   = array_slice( $found, $offset, $limit );
		}

		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$item = array(
				'id'            => $product->get_id(),
				'name'          => wp_strip_all_tags( $product->get_name() ),
				'type'          => $product->get_type(),
				'status'        => $product->get_status(),
				'sku'           => (string) $product->get_sku(),
				'regular_price' => (string) $product->get_regular_price( 'edit' ),
				'sale_price'    => (string) $product->get_sale_price( 'edit' ),
			);
			if ( $product->get_parent_id() ) {
				$item['parent_id'] = $product->get_parent_id();
			}
			if ( $product->is_type( 'variable' ) ) {
				$item['variation_ids'] = array_slice( array_map( 'intval', $product->get_children() ), 0, 50 );
			}
			$products[] = $item;
		}

		return array(
			'total'    => $total,
			'products' => $products,
		);
	}

	/**
	 * Search product categories.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function search_categories( array $input ): array {
		$args   = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
		);
		$search = trim( (string) ( $input['search'] ?? '' ) );
		if ( '' !== $search ) {
			$args['search'] = $search;
		}
		$total = (int) wp_count_terms( $args );

		$args['number'] = max( 1, min( self::MAX_RESULTS, (int) ( $input['limit'] ?? 20 ) ) );
		$args['offset'] = max( 0, (int) ( $input['offset'] ?? 0 ) );

		$categories = array();
		$terms      = get_terms( $args );
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$categories[] = array(
				'id'            => (int) $term->term_id,
				'name'          => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
				'slug'          => $term->slug,
				'parent_id'     => (int) $term->parent,
				'product_count' => (int) $term->count,
			);
		}

		return array(
			'total'      => $total,
			'categories' => $categories,
		);
	}

	/**
	 * Search customers without returning contact details.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function search_customers( array $input ): array {
		$args   = array(
			'number'      => max( 1, min( self::MAX_RESULTS, (int) ( $input['limit'] ?? 20 ) ) ),
			'offset'      => max( 0, (int) ( $input['offset'] ?? 0 ) ),
			'orderby'     => 'ID',
			'order'       => 'DESC',
			'count_total' => true,
		);
		$search = trim( (string) ( $input['search'] ?? '' ) );
		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );
		}
		if ( ! empty( $input['role_id'] ) ) {
			$args['meta_key']   = '__wholesalex_role'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WholesaleX stores the assigned role as user metadata.
			$args['meta_value'] = sanitize_text_field( (string) $input['role_id'] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- WholesaleX stores the assigned role as user metadata.
		}

		$query     = new \WP_User_Query( $args );
		$customers = array();
		foreach ( $query->get_results() as $user ) {
			$role_id     = (string) get_user_meta( $user->ID, '__wholesalex_role', true );
			$role        = '' !== $role_id ? wholesalex()->get_roles( 'by_id', $role_id ) : array();
			$customers[] = array(
				'id'             => (int) $user->ID,
				'name'           => $user->display_name,
				'role_id'        => $role_id,
				'role_title'     => is_array( $role ) ? (string) ( $role['_role_title'] ?? '' ) : '',
				'account_status' => (string) get_user_meta( $user->ID, '__wholesalex_status', true ),
				'store_staff'    => user_can( $user, 'edit_posts' ),
			);
		}

		return array(
			'total'     => (int) $query->get_total(),
			'customers' => $customers,
		);
	}

	/**
	 * List payment methods, shipping zones and tax classes.
	 *
	 * @return array
	 */
	public static function checkout_options(): array {
		$payment_methods = array();
		foreach ( Lookup::enabled_gateways() as $id => $title ) {
			$payment_methods[] = array(
				'id'    => $id,
				'title' => wp_strip_all_tags( $title ),
			);
		}

		$zones = array();
		foreach ( Lookup::shipping_zones() as $zone ) {
			$methods = array();
			foreach ( $zone['methods'] as $id => $title ) {
				$methods[] = array(
					'id'    => (string) $id,
					'title' => wp_strip_all_tags( $title ),
				);
			}
			$zones[] = array(
				'id'      => $zone['id'],
				'name'    => $zone['name'],
				'methods' => $methods,
			);
		}

		$tax_classes = array(
			array(
				'slug' => 'standard',
				'name' => __( 'Standard rate', 'wholesalex' ),
			),
		);
		foreach ( Lookup::tax_classes() as $slug => $name ) {
			$tax_classes[] = array(
				'slug' => $slug,
				'name' => $name,
			);
		}

		return array(
			'payment_methods' => $payment_methods,
			'shipping_zones'  => $zones,
			'tax_classes'     => $tax_classes,
		);
	}
}
