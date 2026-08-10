<?php
/**
 * WholesaleX Wholesale Pricing - Buy X Get Y Discount Rule Handler
 *
 * Adds configured free products to the cart when the rule target quantity is met
 * and renders Buy X Get Y product-page promotional output and badges.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Buy X Get Y free-product wholesale-pricing rules.
 */
class Wholesale_Pricing_Bxgy_Discount {

	const CART_ITEM_FLAG = '_wholesalex_wp_bxgy_free_item';
	const RULE_ID_KEY    = '_wholesalex_wp_bxgy_rule_id';
	const PRODUCT_ID_KEY = '_wholesalex_wp_bxgy_free_product_id';
	const FREE_ITEM_KEY  = '_wholesalex_wp_bxgy_free_item_key';

	/**
	 * Sync free cart lines for the supplied BXGY rules.
	 *
	 * @param \WC_Cart $cart  Cart object.
	 * @param array    $rules Normalized BXGY rule entries.
	 * @return void
	 */
	public function sync_free_cart_items( $cart, array $rules ): void {
		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$desired = $this->get_desired_free_cart_items( $rules );

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_FLAG ] ) ) {
				continue;
			}

			$desired_key = $this->get_desired_item_key_from_cart_item( $cart_item );

			if ( ! isset( $desired[ $desired_key ] ) ) {
				$cart->remove_cart_item( $cart_item_key );
				continue;
			}

			if ( absint( $cart_item['quantity'] ?? 0 ) !== $desired[ $desired_key ]['quantity'] ) {
				$cart->set_quantity( $cart_item_key, $desired[ $desired_key ]['quantity'], false );
			}

			unset( $desired[ $desired_key ] );
		}

		foreach ( $desired as $free_item ) {
			$this->add_free_cart_item( $cart, $free_item );
		}
	}

	/**
	 * Force synced free cart lines to zero price.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function set_free_cart_item_prices( $cart ): void {
		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_FLAG ] ) || empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
				continue;
			}

			$cart_item['data']->set_price( 0 );
		}
	}

	/**
	 * Get products that can be used as Buy X Get Y free items.
	 *
	 * Variable parent products cannot be added directly as a free cart line.
	 * They are returned as informational group rows followed by every selectable,
	 * in-stock variation.
	 *
	 * @param string $search Search keyword.
	 * @param int    $limit  Maximum number of top-level results to return.
	 * @return array
	 */
	public function get_free_product_picker_options( $search = '', $limit = 20 ): array {
		$final                  = array();
		$handled_variable_ids   = array();
		$seen_values            = array();
		$top_level_result_count = 0;
		$limit                  = $this->normalize_picker_result_limit( $limit );
		$search                 = trim( (string) $search );
		$ids                    = $this->get_free_product_picker_candidate_ids( $search, $limit );

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			if ( $product->is_type( 'variable' ) || $product->is_type( 'variation' ) ) {
				$parent = $product->is_type( 'variable' ) ? $product : wc_get_product( $product->get_parent_id() );
				if ( ! $parent instanceof \WC_Product_Variable ) {
					continue;
				}

				$parent_id = $parent->get_id();
				if ( isset( $handled_variable_ids[ $parent_id ] ) ) {
					continue;
				}
				$handled_variable_ids[ $parent_id ] = true;

				$variation_options = $this->get_free_product_picker_variable_options( $parent, $search );
				if ( empty( $variation_options ) ) {
					continue;
				}

				$group = array(
					'value'           => 'variable:' . $parent_id,
					'product_id'      => $parent_id,
					'name'            => $parent->get_name(),
					'is_group'        => true,
					'variation_count' => count( $variation_options ),
				);
				if ( '' !== $search ) {
					$group['suggestion_name'] = $search . ' ' . $group['name'];
				}
				$final[] = $group;

				foreach ( $variation_options as $item ) {
					$value = (string) $item['value'];
					if ( isset( $seen_values[ $value ] ) ) {
						continue;
					}
					$seen_values[ $value ] = true;
					$final[]               = $item;
				}

				++$top_level_result_count;
				if ( $top_level_result_count >= $limit ) {
					break;
				}
				continue;
			}

			if ( ! $this->is_free_product_picker_selectable( $product ) ) {
				continue;
			}

			foreach ( $this->get_free_product_picker_options_for_product( $product ) as $item ) {
				if ( '' !== $search && ! $this->free_product_picker_option_matches_search( $product, $item['name'], $search ) ) {
					continue;
				}

				$value = (string) $item['value'];
				if ( isset( $seen_values[ $value ] ) ) {
					continue;
				}
				$seen_values[ $value ] = true;
				$final[]               = $item;
				++$top_level_result_count;
				break;
			}

			if ( $top_level_result_count >= $limit ) {
				break;
			}
		}

		return $final;
	}

	/**
	 * Get every selectable child of a matched variable product.
	 *
	 * @param \WC_Product_Variable $parent Variable parent product.
	 * @param string               $search Current search term.
	 * @return array
	 */
	private function get_free_product_picker_variable_options( \WC_Product_Variable $parent, string $search ): array {
		$options = array();

		foreach ( $parent->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof \WC_Product_Variation || ! $this->is_free_product_picker_selectable( $variation ) ) {
				continue;
			}

			foreach ( $this->get_free_product_picker_options_for_product( $variation ) as $item ) {
				$item['parent_id']    = $parent->get_id();
				$item['is_variation'] = true;
				if ( '' !== $search ) {
					$item['suggestion_name'] = $search . ' ' . $item['name'];
				}
				$options[] = $item;
			}
		}

		return $options;
	}

	/**
	 * Query candidate product IDs for the free-product picker.
	 *
	 * @param string $search Search keyword.
	 * @param int    $limit  Public response limit.
	 * @return array<int>
	 */
	private function get_free_product_picker_candidate_ids( string $search, int $limit ): array {
		global $wpdb;

		$query_limit = min( max( $limit * 10, 40 ), 200 );
		$author_id   = absint( apply_filters( 'wholesalex_dynamic_rules_product_author', 0 ) );
		$ids         = array();
		$sql         = "SELECT DISTINCT p.ID FROM $wpdb->posts p
			LEFT JOIN $wpdb->posts parent ON p.post_parent = parent.ID
			LEFT JOIN $wpdb->postmeta variation_attr ON variation_attr.post_id = p.ID AND variation_attr.meta_key LIKE 'attribute_%'
			WHERE p.post_type IN ('product', 'product_variation')
			AND p.post_status = %s";
		$args        = array( 'publish' );

		if ( $author_id ) {
			$sql   .= ' AND (p.post_author = %d OR parent.post_author = %d)';
			$args[] = $author_id;
			$args[] = $author_id;
		}

		if ( '' !== $search ) {
			$terms = preg_split( '/[\s,_-]+/', $search );
			$terms = array_values( array_filter( array_map( 'trim', (array) $terms ) ) );

			if ( preg_match( '/^\d+$/', $search ) ) {
				$sql   .= ' AND (p.ID = %d';
				$args[] = absint( $search );

				foreach ( $terms as $term ) {
					$sql   .= ' OR p.post_title LIKE %s OR parent.post_title LIKE %s OR variation_attr.meta_value LIKE %s';
					$like   = '%' . $wpdb->esc_like( $term ) . '%';
					$args[] = $like;
					$args[] = $like;
					$args[] = $like;
				}

				$sql .= ')';
			} elseif ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$sql   .= ' AND (p.post_title LIKE %s OR parent.post_title LIKE %s OR variation_attr.meta_value LIKE %s)';
					$like   = '%' . $wpdb->esc_like( $term ) . '%';
					$args[] = $like;
					$args[] = $like;
					$args[] = $like;
				}
			}
		}

		$sql   .= ' ORDER BY p.post_title ASC LIMIT %d';
		$args[] = $query_limit;

		$results = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( $sql, ...$args )
		);

		foreach ( (array) $results as $id ) {
			$id = absint( $id );
			if ( $id ) {
				$ids[] = $id;
			}
		}

		if ( preg_match( '/^\d+$/', $search ) ) {
			array_unshift( $ids, absint( $search ) );
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Check whether a product can be selected as a BXGY free item.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	private function is_free_product_picker_selectable( $product ): bool {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return false;
		}

		return $product->is_type( 'simple' ) || $product->is_type( 'variation' );
	}

	/**
	 * Return one or more selectable options for a product.
	 *
	 * Variations with "Any ..." attributes are expanded into concrete attribute
	 * combinations so WooCommerce receives every required variation attribute.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	private function get_free_product_picker_options_for_product( $product ): array {
		if ( ! $product->is_type( 'variation' ) ) {
			return array( $this->format_free_product_picker_option( $product ) );
		}

		$options = array();
		foreach ( $this->get_concrete_variation_attributes_for_picker( $product ) as $variation ) {
			$options[] = $this->format_free_product_picker_option( $product, $variation );
		}

		return $options;
	}

	/**
	 * Expand a variation's "Any ..." attributes into concrete combinations.
	 *
	 * @param \WC_Product $product Variation product.
	 * @return array<int, array<string, string>>
	 */
	private function get_concrete_variation_attributes_for_picker( $product ): array {
		$parent = wc_get_product( $product->get_parent_id() );

		if ( ! $parent instanceof \WC_Product_Variable ) {
			return array( $product->get_variation_attributes() );
		}

		$parent_attributes = $parent->get_variation_attributes();
		$variation_attrs   = $product->get_variation_attributes();
		$attribute_options = array();

		foreach ( $parent_attributes as $attribute_name => $options ) {
			$attribute_key = 'attribute_' . sanitize_title( $attribute_name );
			$value         = isset( $variation_attrs[ $attribute_key ] ) ? (string) $variation_attrs[ $attribute_key ] : '';

			if ( '' !== $value ) {
				$attribute_options[ $attribute_key ] = array( $value );
				continue;
			}

			$options = array_values( array_filter( array_map( 'strval', (array) $options ) ) );
			if ( empty( $options ) ) {
				$attribute_options[ $attribute_key ] = array( '' );
				continue;
			}

			$attribute_options[ $attribute_key ] = $options;
		}

		return $this->build_picker_attribute_combinations( $attribute_options );
	}

	/**
	 * Build cartesian product combinations for variation attributes.
	 *
	 * @param array<string, array<int, string>> $attribute_options Attribute values keyed by Woo attribute key.
	 * @return array<int, array<string, string>>
	 */
	private function build_picker_attribute_combinations( array $attribute_options ): array {
		$combinations = array( array() );

		foreach ( $attribute_options as $attribute_key => $options ) {
			$next = array();

			foreach ( $combinations as $combination ) {
				foreach ( $options as $option ) {
					$combination_copy                   = $combination;
					$combination_copy[ $attribute_key ] = $option;
					$next[]                             = $combination_copy;
				}
			}

			$combinations = $next;
		}

		return $combinations;
	}

	/**
	 * Format a product option for the free-product picker.
	 *
	 * @param \WC_Product $product   Product object.
	 * @param array       $variation Concrete variation attributes.
	 * @return array
	 */
	private function format_free_product_picker_option( $product, array $variation = array() ): array {
		$item = array(
			'value'      => $this->get_free_product_picker_option_value( $product, $variation ),
			'product_id' => $product->get_id(),
			'name'       => $this->get_free_product_picker_option_label( $product, $variation ),
		);

		if ( ! empty( $variation ) ) {
			$item['variation'] = $variation;
		}

		$price    = $product->get_price( 'edit' );
		$regular  = $product->get_regular_price( 'edit' );
		$sale     = $product->get_sale_price( 'edit' );
		$image_id = $product->get_image_id();

		if ( ! $image_id && $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent instanceof \WC_Product ) {
				$image_id = $parent->get_image_id();
			}
		}

		$image = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

		if ( '' !== $price ) {
			$item['price'] = (float) $price;
		}
		if ( '' !== $regular ) {
			$item['regular_price'] = (float) $regular;
		}
		if ( '' !== $sale ) {
			$item['sale_price'] = (float) $sale;
		}
		if ( $image ) {
			$item['image'] = esc_url_raw( $image );
		}

		return $item;
	}

	/**
	 * Build a unique option value for a product/attribute combination.
	 *
	 * @param \WC_Product $product   Product object.
	 * @param array       $variation Concrete variation attributes.
	 * @return int|string
	 */
	private function get_free_product_picker_option_value( $product, array $variation = array() ) {
		if ( empty( $variation ) ) {
			return $product->get_id();
		}

		ksort( $variation );
		return $product->get_id() . ':' . md5( wp_json_encode( $variation ) );
	}

	/**
	 * Build a readable label for a picker option.
	 *
	 * @param \WC_Product $product   Product object.
	 * @param array       $variation Concrete variation attributes.
	 * @return string
	 */
	private function get_free_product_picker_option_label( $product, array $variation = array() ): string {
		if ( $product->is_type( 'variation' ) ) {
			$parent_name = '';
			$parent      = wc_get_product( $product->get_parent_id() );

			if ( $parent instanceof \WC_Product ) {
				$parent_name = $parent->get_name();
			}

			$attribute_values = array();
			$attributes       = ! empty( $variation ) ? $variation : $product->get_variation_attributes();
			foreach ( $attributes as $taxonomy => $value ) {
				$value = (string) $value;

				if ( '' === $value ) {
					continue;
				}

				$taxonomy_key = str_replace( 'attribute_', '', $taxonomy );
				if ( taxonomy_exists( $taxonomy_key ) ) {
					$term = get_term_by( 'slug', $value, $taxonomy_key );
					if ( $term && ! is_wp_error( $term ) ) {
						$value = $term->name;
					}
				}

				$attribute_values[] = rawurldecode( wp_strip_all_tags( $value ) );
			}

			$label_parts = array_filter( array_merge( array( $parent_name ), $attribute_values ) );
			$label       = empty( $label_parts ) ? $product->get_name() : implode( ' - ', $label_parts );

			return $label . ' (' . $product->get_id() . ')';
		}

		return $product->get_name() . ' (' . $product->get_id() . ')';
	}

	/**
	 * Match picker search against a normalized label, ID, and SKU.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $label   Formatted option label.
	 * @param string      $search  Search keyword.
	 * @return bool
	 */
	private function free_product_picker_option_matches_search( $product, string $label, string $search ): bool {
		$normalized_label  = $this->normalize_free_product_picker_search_text( $label );
		$normalized_search = $this->normalize_free_product_picker_search_text( $search );

		if ( '' !== $normalized_search && false !== strpos( $normalized_label, $normalized_search ) ) {
			return true;
		}

		$search_terms = preg_split( '/[\s,_-]+/', $search );
		$search_terms = array_values( array_filter( array_map( array( $this, 'normalize_free_product_picker_search_text' ), (array) $search_terms ) ) );

		if ( ! empty( $search_terms ) ) {
			$all_terms_match = true;
			foreach ( $search_terms as $term ) {
				if ( false === strpos( $normalized_label, $term ) ) {
					$all_terms_match = false;
					break;
				}
			}

			if ( $all_terms_match ) {
				return true;
			}
		}

		if ( preg_match( '/^\d+$/', $search ) && absint( $search ) === $product->get_id() ) {
			return true;
		}

		$sku = $product->get_sku();
		return '' !== $normalized_search && '' !== $sku && false !== strpos( $this->normalize_free_product_picker_search_text( $sku ), $normalized_search );
	}

	/**
	 * Normalize text for tolerant picker searching.
	 *
	 * @param string $text Text to normalize.
	 * @return string
	 */
	private function normalize_free_product_picker_search_text( string $text ): string {
		return strtolower( preg_replace( '/[^a-z0-9]+/i', '', rawurldecode( wp_strip_all_tags( $text ) ) ) );
	}

	/**
	 * Normalize AJAX result limits for the free-product picker.
	 *
	 * @param int|string $limit Requested limit.
	 * @return int
	 */
	private function normalize_picker_result_limit( $limit ): int {
		$limit = absint( $limit );

		if ( $limit <= 0 ) {
			return 20;
		}

		return min( $limit, 20 );
	}

	/**
	 * Check whether any BXGY rule wants single-product free-product output.
	 *
	 * @param array $rules Normalized BXGY rule entries.
	 * @return bool
	 */
	public function has_promo_rules( array $rules ): bool {
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['bxgy']['show_free_item'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether any BXGY rule wants badge output.
	 *
	 * @param array $rules Normalized BXGY rule entries.
	 * @return bool
	 */
	public function has_badge_rules( array $rules ): bool {
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['bxgy']['show_badge'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render BXGY promo HTML for a product.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized BXGY rule entries.
	 * @return void
	 */
	public function render_promo_html( $product, array $rules ): void {
		echo $this->get_promo_html( $product, $rules ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build BXGY promo HTML for a product.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized BXGY rule entries.
	 * @return string
	 */
	public function get_promo_html( $product, array $rules ): string {
		if ( ! $product instanceof \WC_Product || empty( $rules ) ) {
			return '';
		}

		$promo_rules = array();

		foreach ( $rules as $rule ) {
			if ( empty( $rule['bxgy']['show_free_item'] ) || ! Wholesale_Pricing_Condition_Engine::is_product_eligible_for_rule( $product, $rule ) ) {
				continue;
			}

			$promo_rules[] = $rule;
		}

		if ( empty( $promo_rules ) ) {
			return '';
		}

		ob_start();
		foreach ( $promo_rules as $rule ) {
			$this->render_free_items_template( $this->get_promo_heading( $rule ), $this->get_free_product_ids( $rule ), $this->get_free_item_count( $rule ) );
		}
		return (string) ob_get_clean();
	}

	/**
	 * Render BXGY badge markup for the current global product.
	 *
	 * @param array       $rules     Normalized BXGY rule entries.
	 * @param bool        $is_single Whether this is for a single product page.
	 * @param \WC_Product $product   Product to render. Defaults to the global product.
	 * @return string
	 */
	public function get_badge_markup( array $rules, bool $is_single = false, $product = null ): string {
		if ( ! $product instanceof \WC_Product ) {
			$product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
		}

		if ( ! $product instanceof \WC_Product || empty( $rules ) ) {
			return '';
		}

		ob_start();

		foreach ( $rules as $rule ) {
			if ( empty( $rule['bxgy']['show_badge'] ) || ! Wholesale_Pricing_Condition_Engine::is_product_eligible_for_rule( $product, $rule ) ) {
				continue;
			}

			$label = $this->get_badge_label( $rule );

			if ( '' === $label ) {
				continue;
			}

			$rule_id    = $this->get_rule_dom_id( $rule );
			$bg_color   = ! empty( $rule['bxgy']['badge_bg_color'] ) ? $rule['bxgy']['badge_bg_color'] : '#5a40e8';
			$text_color = ! empty( $rule['bxgy']['badge_text_color'] ) ? $rule['bxgy']['badge_text_color'] : '#ffffff';
			?>
			<div class="wholesalex-bogo-badge-container wholesalex-bogo-badge-<?php echo esc_attr( $is_single ? 'single' : 'shop' ); ?>">
				<div class="wholesalex-bogo-badge wholesalex-bogo-badge-style-<?php echo esc_attr( $is_single ? 'single-product-' . $rule_id : $rule_id ); ?> wholesalex-bogo-badge-<?php echo esc_attr( $rule_id ); ?>"
					style="background-color: <?php echo esc_attr( $bg_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>;">
					<?php echo esc_html( $label ); ?>
				</div>
			</div>
			<?php
		}

		return (string) ob_get_clean();
	}

	/**
	 * Generate CSS for all configured BXGY badge styles.
	 *
	 * @param array $rules Normalized BXGY rule entries.
	 * @return string
	 */
	public function get_badge_css( array $rules ): string {
		$css = '';

		foreach ( $rules as $rule ) {
			if ( empty( $rule['bxgy']['show_badge'] ) ) {
				continue;
			}

			$rule_id    = $this->get_rule_dom_id( $rule );
			$style      = ! empty( $rule['bxgy']['badge_style'] ) ? sanitize_key( $rule['bxgy']['badge_style'] ) : 'style_one';
			$position   = ! empty( $rule['bxgy']['badge_position'] ) ? sanitize_key( $rule['bxgy']['badge_position'] ) : 'right';
			$bg_color   = ! empty( $rule['bxgy']['badge_bg_color'] ) ? sanitize_hex_color( $rule['bxgy']['badge_bg_color'] ) : '#5a40e8';
			$text_color = ! empty( $rule['bxgy']['badge_text_color'] ) ? sanitize_hex_color( $rule['bxgy']['badge_text_color'] ) : '#ffffff';
			$right      = 'left' === $position ? 'auto' : '0px';
			$left       = 'left' === $position ? '0px' : 'auto';

			if ( ! $bg_color ) {
				$bg_color = '#5a40e8';
			}
			if ( ! $text_color ) {
				$text_color = '#ffffff';
			}

			$selector = '.wholesalex-bogo-badge-' . $rule_id;
			$css     .= $selector . '{right:' . $right . ';left:' . $left . ';}';

			if ( 'style_one' === $style || '' === $style ) {
				$css .= $selector . '{margin-left:' . ( 'left' === $position ? '14px' : '0' ) . ';}';
				$css .= $selector . '::before{content:"";position:absolute;left:-14px;transform:translateY(-1px);border-radius:3px;border-top:16px solid transparent;border-bottom:16px solid transparent;border-right:15px solid ' . $bg_color . ';}';
				$css .= $selector . '::after{content:"";width:7px;height:7px;position:absolute;left:0;top:calc(100% / 2 - 4px);border-radius:50%;background-color:' . $text_color . ';}';
			} elseif ( 'style_two' === $style ) {
				$css .= $selector . '{border-radius:4px;}';
			} elseif ( 'style_three' === $style ) {
				$css .= $selector . '{border-top-left-radius:40px;border-bottom-left-radius:5px;}';
			} elseif ( 'style_four' === $style ) {
				$css .= $selector . '{border-top-left-radius:26px;border-bottom-right-radius:26px;}';
			} elseif ( 'style_five' === $style ) {
				$css .= $selector . '{border-top-right-radius:26px;border-bottom-left-radius:26px;}';
			}
		}

		return $css;
	}

	/**
	 * Add single-product badge markup to the product gallery via inline script.
	 *
	 * @param array       $rules   Normalized BXGY rule entries.
	 * @param \WC_Product $product Product displayed by the single-product template.
	 * @return void
	 */
	public function enqueue_single_badge_script( array $rules, $product = null ): void {
		$markup = $this->get_badge_markup( $rules, true, $product );

		if ( '' === trim( $markup ) ) {
			return;
		}

		$script = $this->get_single_badge_insertion_script( $markup );
		wp_add_inline_script( 'wholesalex', $script, 'after' );
	}

	/**
	 * Build a gallery-compatible badge insertion script.
	 *
	 * Classic templates and WooCommerce block templates use different gallery
	 * wrappers. The observer also covers galleries hydrated after page load.
	 *
	 * @param string $markup Badge HTML.
	 * @return string
	 */
	private function get_single_badge_insertion_script( string $markup ): string {
		$selectors = array(
			'.woocommerce-product-gallery',
			'.wp-block-woocommerce-product-gallery-large-image',
			'.wc-block-product-gallery-large-image',
			'.wp-block-woocommerce-product-gallery',
		);

		$script  = '(function(){var markup=' . wp_json_encode( $markup ) . ',selectors=' . wp_json_encode( $selectors ) . ';';
		$script .= 'function insert(){var gallery=null;for(var i=0;i<selectors.length;i++){gallery=document.querySelector(selectors[i]);if(gallery){break;}}';
		$script .= 'if(!gallery||!markup){return false;}gallery.insertAdjacentHTML("afterbegin",markup);return true;}';
		$script .= 'if(!insert()){var observer=new MutationObserver(function(){if(insert()){observer.disconnect();}});';
		$script .= 'observer.observe(document.documentElement,{childList:true,subtree:true});';
		$script .= 'setTimeout(function(){observer.disconnect();},10000);}})();';

		return $script;
	}

	/**
	 * Return desired free cart items keyed by rule/product pair.
	 *
	 * @param array $rules Normalized BXGY rule entries.
	 * @return array<string, array{rule_id: string, product_id: int, variation_id: int, variation: array, free_id: int, free_key: string, quantity: int}>
	 */
	private function get_desired_free_cart_items( array $rules ): array {
		$desired = array();

		if ( empty( $rules ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $desired;
		}

		foreach ( $rules as $rule ) {
			if ( empty( $rule['filter'] ) || ! Wholesale_Pricing_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
				continue;
			}

			$min_qty = isset( $rule['rule']['_minimum_purchase_count'] ) ? absint( $rule['rule']['_minimum_purchase_count'] ) : 0;

			if ( $min_qty < 1 ) {
				continue;
			}

			$trigger_quantity = $this->get_trigger_quantity( $rule['filter'] );

			if ( $trigger_quantity < $min_qty ) {
				continue;
			}

			$applications = (int) floor( $trigger_quantity / $min_qty );

			if ( ! empty( $rule['rule']['_per_cart_once'] ) ) {
				$applications = min( 1, $applications );
			}

			$applications = (int) apply_filters( 'wholesalex_wholesale_pricing_bxgy_applications', $applications, $rule, $trigger_quantity );

			if ( $applications < 1 ) {
				continue;
			}

			$free_quantity = $applications * $this->get_free_item_count( $rule );

			if ( $free_quantity < 1 ) {
				continue;
			}

			foreach ( $this->get_free_product_items( $rule ) as $free_product ) {
				$free_product_id = absint( $free_product['product_id'] ?? 0 );
				$product = wc_get_product( $free_product_id );

				if (
					! $product instanceof \WC_Product ||
					! $product->is_purchasable() ||
					! $product->is_in_stock() ||
					( ! $product->is_type( 'simple' ) && ! $product->is_type( 'variation' ) )
				) {
					continue;
				}

				$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
				$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
				$variation    = $variation_id ? $this->get_cart_variation_attributes( $product, $free_product['variation'] ?? array() ) : array();

				if ( $variation_id && ! $this->has_required_variation_attributes( $product, $variation ) ) {
					continue;
				}

				$rule_id      = isset( $rule['id'] ) ? (string) $rule['id'] : md5( wp_json_encode( $rule ) );
				$free_item_key = isset( $free_product['key'] ) ? (string) $free_product['key'] : (string) $free_product_id;
				$key          = $this->get_desired_item_key( $rule_id, $free_item_key );

				$desired[ $key ] = array(
					'rule_id'      => $rule_id,
					'product_id'   => absint( $product_id ),
					'variation_id' => absint( $variation_id ),
					'variation'    => $variation,
					'free_id'      => absint( $free_product_id ),
					'free_key'     => $free_item_key,
					'quantity'     => absint( $free_quantity ),
				);
			}
		}

		return $desired;
	}

	/**
	 * Return eligible bought quantity for a BXGY rule.
	 *
	 * @param array $filter Runtime product filter.
	 * @return int
	 */
	private function get_trigger_quantity( array $filter ): int {
		$quantity = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item[ self::CART_ITEM_FLAG ] ) ) {
				continue;
			}

			$product_id   = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
			$variation_id = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;

			if ( ! Wholesale_Pricing_Condition_Engine::product_ids_match_filter( $product_id, $variation_id, $filter ) ) {
				continue;
			}

			$quantity += absint( $cart_item['quantity'] ?? 0 );
		}

		return $quantity;
	}

	/**
	 * Add a new free cart line.
	 *
	 * @param \WC_Cart $cart      Cart object.
	 * @param array    $free_item Desired free item data.
	 * @return void
	 */
	private function add_free_cart_item( \WC_Cart $cart, array $free_item ): void {
		$cart_item_data = array(
			self::CART_ITEM_FLAG => true,
			self::RULE_ID_KEY    => $free_item['rule_id'],
			self::PRODUCT_ID_KEY => $free_item['free_id'],
			self::FREE_ITEM_KEY  => $free_item['free_key'],
			'unique_key'         => md5( 'wholesalex_wp_bxgy_' . $free_item['rule_id'] . '_' . $free_item['free_key'] ),
		);

		$cart->add_to_cart(
			$free_item['product_id'],
			$free_item['quantity'],
			$free_item['variation_id'],
			$free_item['variation'],
			$cart_item_data
		);
	}

	/**
	 * Render free-item promo cards.
	 *
	 * @param string $min_purchase_text Heading text.
	 * @param array  $free_items        Product IDs.
	 * @param int    $free_item_count   Quantity for each free product.
	 * @return void
	 */
	private function render_free_items_template( string $min_purchase_text, array $free_items, int $free_item_count ): void {
		?>
		<div class="wholesalex_free_items wsx-single-product-discount-card wsx-bxgy-free-items">
			<div class="wsx-bxgy-min-purchase-text"> <?php echo esc_html( $min_purchase_text ); ?> </div>
			<?php
			foreach ( $free_items as $free_item_id ) {
				$product = wc_get_product( $free_item_id );

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				$image_id  = $product->get_image_id();
				$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
				$image_url = $image_url ? $image_url : wc_placeholder_img_src( 'thumbnail' );
				?>
				<div class="wsx-bxgy-free-promo-card">
					<div class="wsx-bxgy-free-item-thumb">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_title() ); ?>">
						<?php if ( $free_item_count > 1 ) : ?>
							<span class="wsx-bxgy-free-item-qty-badge"><?php echo esc_html( 'x' . $free_item_count ); ?></span>
						<?php endif; ?>
					</div>
					<div class="wsx-bxgy-free-item-meta">
						<div class="wsx-bxgy-free-item-title"><?php echo esc_html( $product->get_title() ); ?> </div>
						<div class="wsx-bxgy-free-item-price">
							<span class="wsx-bxgy-free-item-regular-price">
								<?php echo wp_kses_post( wc_price( (float) $product->get_price( 'edit' ) * $free_item_count ) ); ?>
							</span>
							<span class="wsx-bxgy-free-item-free-price"><?php echo esc_html__( 'FREE', 'wholesalex' ); ?></span>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Return selected free product IDs from a normalized rule.
	 *
	 * @param array $rule Normalized BXGY rule.
	 * @return array<int>
	 */
	private function get_free_product_ids( array $rule ): array {
		$ids = array();

		foreach ( $this->get_free_product_items( $rule ) as $item ) {
			$ids[] = absint( $item['product_id'] ?? 0 );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Return selected free product entries from a normalized rule.
	 *
	 * @param array $rule Normalized BXGY rule.
	 * @return array<int, array{product_id: int, key: string, variation: array}>
	 */
	private function get_free_product_items( array $rule ): array {
		$items = isset( $rule['rule']['_free_item'] ) && is_array( $rule['rule']['_free_item'] ) ? $rule['rule']['_free_item'] : array();
		$free_items = array();

		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				$product_id = absint( $item['product_id'] ?? $item['value'] ?? 0 );
				$value      = isset( $item['value'] ) ? (string) $item['value'] : (string) $product_id;
				$variation  = isset( $item['variation'] ) && is_array( $item['variation'] ) ? $item['variation'] : array();
			} else {
				$product_id = absint( $item );
				$value      = (string) $product_id;
				$variation  = array();
			}

			if ( ! $product_id ) {
				continue;
			}

			$free_items[] = array(
				'product_id' => $product_id,
				'key'        => '' !== $value ? $value : (string) $product_id,
				'variation'  => $variation,
			);
		}

		return $free_items;
	}

	/**
	 * Return the concrete variation attributes used for cart insertion.
	 *
	 * @param \WC_Product $product              Variation product.
	 * @param array       $configured_variation Saved concrete variation attributes.
	 * @return array
	 */
	private function get_cart_variation_attributes( $product, array $configured_variation ): array {
		$variation = array();

		foreach ( $product->get_variation_attributes() as $attribute_key => $attribute_value ) {
			$attribute_key = sanitize_key( $attribute_key );
			$variation[ $attribute_key ] = (string) $attribute_value;
		}

		foreach ( $configured_variation as $attribute_key => $attribute_value ) {
			$attribute_key = sanitize_key( $attribute_key );

			if ( 0 !== strpos( $attribute_key, 'attribute_' ) ) {
				continue;
			}

			$variation[ $attribute_key ] = (string) $attribute_value;
		}

		return $variation;
	}

	/**
	 * Check whether all parent variation attributes have concrete values.
	 *
	 * @param \WC_Product $product   Variation product.
	 * @param array       $variation Cart variation attributes.
	 * @return bool
	 */
	private function has_required_variation_attributes( $product, array $variation ): bool {
		$parent = wc_get_product( $product->get_parent_id() );

		if ( ! $parent instanceof \WC_Product_Variable ) {
			return true;
		}

		foreach ( $parent->get_variation_attributes() as $attribute_name => $options ) {
			$attribute_key = 'attribute_' . sanitize_title( $attribute_name );

			if ( ! isset( $variation[ $attribute_key ] ) || '' === (string) $variation[ $attribute_key ] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return configured free quantity per selected product.
	 *
	 * @param array $rule Normalized BXGY rule.
	 * @return int
	 */
	private function get_free_item_count( array $rule ): int {
		$count = isset( $rule['rule']['_free_item_count'] ) ? absint( $rule['rule']['_free_item_count'] ) : 1;
		return max( 1, $count );
	}

	/**
	 * Return product-page promo heading.
	 *
	 * @param array $rule Normalized BXGY rule.
	 * @return string
	 */
	private function get_promo_heading( array $rule ): string {
		$min_qty         = isset( $rule['rule']['_minimum_purchase_count'] ) ? absint( $rule['rule']['_minimum_purchase_count'] ) : 1;
		$free_item_count = $this->get_free_item_count( $rule );

		return sprintf(
			/* translators: 1: minimum quantity to buy, 2: free product quantity. */
			__( 'Buy %1$s, Get %2$s Free', 'wholesalex' ),
			$min_qty,
			$free_item_count
		);
	}

	/**
	 * Return badge label for a rule.
	 *
	 * @param array $rule Normalized BXGY rule.
	 * @return string
	 */
	private function get_badge_label( array $rule ): string {
		return isset( $rule['bxgy']['badge_label'] ) ? trim( (string) $rule['bxgy']['badge_label'] ) : '';
	}

	/**
	 * Build a stable desired-item key.
	 *
	 * @param string $rule_id Rule ID.
	 * @param string $free_id Free product key.
	 * @return string
	 */
	private function get_desired_item_key( string $rule_id, string $free_id ): string {
		return $rule_id . ':' . $free_id;
	}

	/**
	 * Build a desired-item key from an existing cart line.
	 *
	 * @param array $cart_item Cart item.
	 * @return string
	 */
	private function get_desired_item_key_from_cart_item( array $cart_item ): string {
		return $this->get_desired_item_key(
			(string) ( $cart_item[ self::RULE_ID_KEY ] ?? '' ),
			(string) ( $cart_item[ self::FREE_ITEM_KEY ] ?? ( $cart_item[ self::PRODUCT_ID_KEY ] ?? 0 ) )
		);
	}

	/**
	 * Return a CSS-safe rule ID.
	 *
	 * @param array $rule Normalized rule.
	 * @return string
	 */
	private function get_rule_dom_id( array $rule ): string {
		$id = isset( $rule['id'] ) ? sanitize_html_class( (string) $rule['id'] ) : '';
		return '' === $id ? 'wp-bxgy-' . md5( wp_json_encode( $rule ) ) : $id;
	}
}
