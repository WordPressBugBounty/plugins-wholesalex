<?php
/**
 * WholesaleX Wholesale Pricing - BOGO Discount Rule Handler
 *
 * Applies saved wholesale-pricing BOGO rules as WooCommerce negative cart fees
 * and renders product-page promotional text and product badges.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Buy X Get One Free wholesale-pricing discounts.
 */
class Wholesale_Pricing_Bogo_Discount {

	/**
	 * Calculate BOGO fee entries for the supplied rules.
	 *
	 * @param array $rules Normalized BOGO rule entries.
	 * @return array<string, array{discount: float, name: string, rule_id: string}>
	 */
	public function calculate( array $rules ): array {
		$fees = array();

		if ( ! function_exists( 'WC' ) || ! WC()->cart || null === WC()->cart->get_cart() ) {
			return $fees;
		}

		foreach ( $rules as $rule ) {
			if ( empty( $rule['filter'] ) || ! Wholesale_Pricing_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
				continue;
			}

			$min_qty = isset( $rule['rule']['_minimum_purchase_count'] ) ? absint( $rule['rule']['_minimum_purchase_count'] ) : 0;

			if ( $min_qty < 1 ) {
				continue;
			}

			foreach ( $this->get_eligible_product_groups( $rule['filter'] ) as $group ) {
				if ( $group['quantity'] < $min_qty || $group['unit_price'] <= 0 ) {
					continue;
				}

				$free_quantity = (int) floor( $group['quantity'] / $min_qty );

				if ( ! empty( $rule['rule']['_per_cart_once'] ) ) {
					$free_quantity = min( 1, $free_quantity );
				}

				$free_quantity = (int) apply_filters( 'wholesalex_wholesale_pricing_bogo_free_quantity', $free_quantity, $rule, $group );

				if ( $free_quantity < 1 ) {
					continue;
				}

				$discount_amount = $this->get_discount_amount( $group['product'], $group['unit_price'], $free_quantity );

				if ( $discount_amount <= 0 ) {
					continue;
				}

				$fees[ $this->get_hash_key( $rule, $group['group_id'] ) ] = array(
					'discount' => $discount_amount,
					'name'     => $this->get_discount_label( $rule, $group['product'], $min_qty, $free_quantity ),
					'rule_id'  => isset( $rule['id'] ) ? (string) $rule['id'] : '',
				);
			}
		}

		return $fees;
	}

	/**
	 * Check whether any BOGO rule wants single-product promo output.
	 *
	 * @param array $rules Normalized BOGO rule entries.
	 * @return bool
	 */
	public function has_promo_rules( array $rules ): bool {
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['bogo']['show_promo_text'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether any BOGO rule wants badge output.
	 *
	 * @param array $rules Normalized BOGO rule entries.
	 * @return bool
	 */
	public function has_badge_rules( array $rules ): bool {
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['bogo']['show_badge'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render BOGO promo popup for a product.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized BOGO rule entries.
	 * @return void
	 */
	public function render_promo_html( $product, array $rules ): void {
		echo $this->get_promo_html( $product, $rules ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build BOGO promo popup HTML for a product.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized BOGO rule entries.
	 * @return string
	 */
	public function get_promo_html( $product, array $rules ): string {
		if ( ! $product instanceof \WC_Product || empty( $rules ) ) {
			return '';
		}

		$promo_rules = $this->get_product_promo_rules( $product, $rules );

		if ( empty( $promo_rules ) ) {
			return '';
		}

		usort( $promo_rules, array( $this, 'compare_rules' ) );

		ob_start();
		$this->render_promo_modal( $product, $promo_rules );
		return (string) ob_get_clean();
	}

	/**
	 * Render BOGO badge markup for the current global product.
	 *
	 * @param array       $rules     Normalized BOGO rule entries.
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
			if ( empty( $rule['bogo']['show_badge'] ) || ! Wholesale_Pricing_Condition_Engine::is_product_eligible_for_rule( $product, $rule ) ) {
				continue;
			}

			$label = $this->get_badge_label( $rule );

			if ( '' === $label ) {
				continue;
			}

			$rule_id    = $this->get_rule_dom_id( $rule );
			$bg_color   = ! empty( $rule['bogo']['badge_bg_color'] ) ? $rule['bogo']['badge_bg_color'] : '#5a40e8';
			$text_color = ! empty( $rule['bogo']['badge_text_color'] ) ? $rule['bogo']['badge_text_color'] : '#ffffff';
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
	 * Generate CSS for all configured BOGO badge styles.
	 *
	 * @param array $rules Normalized BOGO rule entries.
	 * @return string
	 */
	public function get_badge_css( array $rules ): string {
		$css = '';

		foreach ( $rules as $rule ) {
			if ( empty( $rule['bogo']['show_badge'] ) ) {
				continue;
			}

			$rule_id    = $this->get_rule_dom_id( $rule );
			$style      = ! empty( $rule['bogo']['badge_style'] ) ? sanitize_key( $rule['bogo']['badge_style'] ) : 'style_one';
			$position   = ! empty( $rule['bogo']['badge_position'] ) ? sanitize_key( $rule['bogo']['badge_position'] ) : 'right';
			$bg_color   = ! empty( $rule['bogo']['badge_bg_color'] ) ? sanitize_hex_color( $rule['bogo']['badge_bg_color'] ) : '#5a40e8';
			$text_color = ! empty( $rule['bogo']['badge_text_color'] ) ? sanitize_hex_color( $rule['bogo']['badge_text_color'] ) : '#ffffff';
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
	 * @param array       $rules   Normalized BOGO rule entries.
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
	 * Return cart product groups eligible for a BOGO rule.
	 *
	 * @param array $filter Runtime product filter.
	 * @return array<int, array{group_id: int, product: \WC_Product, quantity: int, unit_price: float}>
	 */
	private function get_eligible_product_groups( array $filter ): array {
		$groups = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
				continue;
			}

			$product_id   = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
			$variation_id = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;

			if ( ! Wholesale_Pricing_Condition_Engine::product_ids_match_filter( $product_id, $variation_id, $filter ) ) {
				continue;
			}

			$product   = $cart_item['data'];
			$group_id  = $variation_id ? $product_id : $product->get_id();
			$quantity  = absint( $cart_item['quantity'] ?? 0 );
			$unit_price = (float) $product->get_price();

			if ( ! isset( $groups[ $group_id ] ) ) {
				$groups[ $group_id ] = array(
					'group_id'   => $group_id,
					'product'    => $product,
					'quantity'   => 0,
					'unit_price' => $unit_price,
				);
			}

			$groups[ $group_id ]['quantity'] += $quantity;

			if ( $unit_price > 0 && ( $groups[ $group_id ]['unit_price'] <= 0 || $unit_price < $groups[ $group_id ]['unit_price'] ) ) {
				$groups[ $group_id ]['unit_price'] = $unit_price;
				$groups[ $group_id ]['product']    = $product;
			}
		}

		return array_values( $groups );
	}

	/**
	 * Calculate the cart fee amount for free units.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param float       $unit_price    Unit price.
	 * @param int         $free_quantity Free quantity.
	 * @return float
	 */
	private function get_discount_amount( \WC_Product $product, float $unit_price, int $free_quantity ): float {
		// WooCommerce stores fee totals excluding tax.
		// Negative BOGO fees then reduce the taxable base correctly.
		return (float) wc_get_price_excluding_tax(
			$product,
			array(
				'qty'   => $free_quantity,
				'price' => $unit_price,
			)
		);
	}

	/**
	 * Return product-specific promo rules.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized BOGO rule entries.
	 * @return array
	 */
	private function get_product_promo_rules( \WC_Product $product, array $rules ): array {
		$promo_rules = array();

		foreach ( $rules as $rule ) {
			if ( empty( $rule['bogo']['show_promo_text'] ) || ! Wholesale_Pricing_Condition_Engine::is_product_eligible_for_rule( $product, $rule ) ) {
				continue;
			}

			$promo_rules[] = $rule;
		}

		return $promo_rules;
	}

	/**
	 * Render the complete promo trigger and modal.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Product-scoped BOGO rules.
	 * @return void
	 */
	private function render_promo_modal( \WC_Product $product, array $rules ): void {
		ob_start();
		$this->render_promo_cards( $product, $rules );
		$modal_content = trim( (string) ob_get_clean() );

		if ( '' === $modal_content ) {
			return;
		}

		$product_id = (string) $product->get_id();
		$button_id  = 'wsx-wp-bogo-discount-view-more-' . $product_id;
		$modal_id   = 'wsx-wp-bogo-discount-modal-' . $product_id;
		$icon_id    = 'wsx-wp-bogo-discount-icon-' . $product_id;
		?>
		<div class="wsx-d-flex wsx-item-center wsx-gap-12 wsx-mb-24 wsx-wp-bogo-discount-promo">
			<div class="wsx-font-14"><?php echo esc_html__( 'Promotions:', 'wholesalex' ); ?></div>
			<div class="wsx-relative">
				<div class="wsx-font-12 wsx-bg-secondary wsx-br-md wsx-pt-4 wsx-pb-6 wsx-plr-10 wsx-color-text-reverse wsx-curser-pointer wsx-btn-icon"
					id="<?php echo esc_attr( $button_id ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
					<?php echo esc_html( apply_filters( 'wholesalex_wholesale_pricing_bogo_promo_button_text', __( 'Get exclusive offers', 'wholesalex' ), $product, $rules ) ); ?>
					<div class="wsx-icon" id="<?php echo esc_attr( $icon_id ); ?>" style="margin-bottom: -4px; transition: all 0.3s;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m5 7.5 5 5 5-5" /></svg></div>
				</div>

				<div class="wsx-dr-single-product-discounts-modal wsx-absolute wsx-down-4 wsx-z-999 wsx-card wsx-plr-8 wsx-pt-10 wsx-pb-10 wsx-width-70v wsx-width-250 wsx-shadow-primary"
					id="<?php echo esc_attr( $modal_id ); ?>" style="display:none;">
					<div class="wsx-font-14 wsx-font-bold wsx-text-center wsx-color-text-medium wsx-mb-8">
						<?php echo esc_html__( 'Conditional Discount Offers', 'wholesalex' ); ?>
					</div>
					<?php echo wp_kses_post( $modal_content ); ?>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			(function($) {
				'use strict';
				const button = $('#<?php echo esc_js( $button_id ); ?>');
				const modal = $('#<?php echo esc_js( $modal_id ); ?>');
				const icon = $('#<?php echo esc_js( $icon_id ); ?>');

				button.on('click', function(e) {
					e.preventDefault();
					modal.slideToggle(100);
					icon.toggleClass('rotated').css('transform', icon.hasClass('rotated') ? 'rotate(180deg)' : 'rotate(0deg)');
				});

				$(document).on('click', function(e) {
					if ($(e.target).closest('#<?php echo esc_js( $modal_id ); ?>, #<?php echo esc_js( $button_id ); ?>').length) {
						return;
					}
					modal.hide(100);
					icon.removeClass('rotated').css('transform', 'rotate(0deg)');
				});
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Render BOGO promo cards inside the popup modal.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Product-scoped BOGO rules.
	 * @return void
	 */
	private function render_promo_cards( \WC_Product $product, array $rules ): void {
		?>
		<div class="wsx-sp-bogo-discounts">
			<div class="wsx-sp-rule-info">
				<div class="wsx-font-14 wsx-font-medium">
					<?php echo esc_html__( 'Buy X Get 1 Discounted', 'wholesalex' ); ?>
				</div>
			</div>

			<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
				<?php foreach ( $rules as $rule ) : ?>
					<?php
					$min_qty      = isset( $rule['rule']['_minimum_purchase_count'] ) ? absint( $rule['rule']['_minimum_purchase_count'] ) : 1;
					$heading_text = ! empty( $rule['bogo']['offer_text'] ) ? (string) $rule['bogo']['offer_text'] : __( '100% Off on 1 Product', 'wholesalex' );
					$desc_text    = ! empty( $rule['bogo']['promo_text_popup'] ) ? (string) $rule['bogo']['promo_text_popup'] : __( 'Buy at least {required_quantity} products', 'wholesalex' );
					$desc         = $this->restore_smart_tags(
						array(
							'{required_quantity}' => $min_qty,
							'{product_title}'     => $product->get_title(),
						),
						$desc_text
					);
					?>
					<div class="wsx-single-product-discount-card wsx-sp-bogo-discount-cart wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
						<div class="wsx-font-18"><?php echo esc_html( $heading_text ); ?></div>
						<div class="wsx-font-14"><?php echo esc_html( $desc ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Return the fee label shown on cart and checkout totals.
	 *
	 * @param array       $rule          Normalized BOGO rule.
	 * @param \WC_Product $product       Discounted product.
	 * @param int         $min_qty       Required quantity.
	 * @param int         $free_quantity Free quantity.
	 * @return string
	 */
	private function get_discount_label( array $rule, \WC_Product $product, int $min_qty, int $free_quantity ): string {
		$template = ! empty( $rule['bogo']['promo_text_cart'] ) ? (string) $rule['bogo']['promo_text_cart'] : __( '{product_title} (Buy X Get 1 Discounted)', 'wholesalex' );
		$label    = $this->restore_smart_tags(
			array(
				'{product_title}'     => $product->get_title(),
				'{required_quantity}' => $min_qty,
				'{x}'                 => $min_qty,
				'{y}'                 => $free_quantity,
			),
			$template
		);

		return apply_filters( 'wholesalex_wholesale_pricing_bogo_discount_text', $label, $rule, $product, $free_quantity );
	}

	/**
	 * Return badge label for a rule.
	 *
	 * @param array $rule Normalized BOGO rule.
	 * @return string
	 */
	private function get_badge_label( array $rule ): string {
		return isset( $rule['bogo']['badge_label'] ) ? trim( (string) $rule['bogo']['badge_label'] ) : '';
	}

	/**
	 * Replace smart tags in a text template.
	 *
	 * @param array  $smart_tags Replacement map.
	 * @param string $text       Text template.
	 * @return string
	 */
	private function restore_smart_tags( array $smart_tags, string $text ): string {
		return strtr( $text, $smart_tags );
	}

	/**
	 * Compare rules by the same priority dimensions used at runtime.
	 *
	 * @param array $a First rule.
	 * @param array $b Second rule.
	 * @return int
	 */
	private function compare_rules( array $a, array $b ): int {
		$a_applied = isset( $a['applied_on_priority'] ) ? (int) $a['applied_on_priority'] : 0;
		$b_applied = isset( $b['applied_on_priority'] ) ? (int) $b['applied_on_priority'] : 0;

		if ( $a_applied === $b_applied ) {
			return ( isset( $b['who_priority'] ) ? (int) $b['who_priority'] : 0 ) <=> ( isset( $a['who_priority'] ) ? (int) $a['who_priority'] : 0 );
		}

		return $b_applied <=> $a_applied;
	}

	/**
	 * Build a stable hash for a calculated fee entry.
	 *
	 * @param array $rule     Normalized BOGO rule.
	 * @param int   $group_id Product group ID.
	 * @return string
	 */
	private function get_hash_key( array $rule, int $group_id ): string {
		return md5(
			serialize(
				array(
					'id'       => $rule['id'] ?? '',
					'group_id' => $group_id,
					'filter'   => $rule['filter'] ?? array(),
				)
			)
		);
	}

	/**
	 * Return a CSS-safe rule ID.
	 *
	 * @param array $rule Normalized BOGO rule.
	 * @return string
	 */
	private function get_rule_dom_id( array $rule ): string {
		$id = isset( $rule['id'] ) ? sanitize_html_class( (string) $rule['id'] ) : '';
		return '' === $id ? 'wp-bogo-' . md5( wp_json_encode( $rule ) ) : $id;
	}
}
