<?php
/**
 * WholesaleX Wholesale Pricing - Cart Discount Rule Handler
 *
 * Applies saved wholesale-pricing cart discount rules as WooCommerce negative
 * cart fees. Rules are evaluated with the same role targeting, product
 * targeting, schedule, and advanced-condition behavior used by regular and
 * tiered wholesale pricing rules.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles cart-level wholesale-pricing discounts.
 */
class Wholesale_Pricing_Cart_Discount {

	/**
	 * Rule-data bucket used for wholesale-pricing cart discount promos.
	 */
	private const PROMO_RULE_DATA_TYPE = 'wholesale_pricing_cart_discount';

	/**
	 * Calculate cart discount fee entries for the supplied rules.
	 *
	 * @param array $rules Normalized cart discount rule entries.
	 * @return array<string, array{discount: float, name: string, rule_id: string}>
	 */
	public function calculate( array $rules ): array {
		$fees = array();

		if ( ! function_exists( 'WC' ) || ! WC()->cart || null === WC()->cart->get_cart() ) {
			return $fees;
		}

		foreach ( $rules as $rule ) {
			if ( ! isset( $rule['conditions'], $rule['filter'] ) ) {
				continue;
			}

			if ( ! Wholesale_Pricing_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
				continue;
			}

			$discount_base = $this->get_discount_base_amount( $rule['filter'] );

			if ( $discount_base <= 0 ) {
				continue;
			}

			$discount_amount = $this->get_discount_amount( $rule, $discount_base );

			if ( $discount_amount <= 0 ) {
				continue;
			}

			$fees[ $this->get_hash_key( $rule ) ] = array(
				'discount' => $discount_amount,
				'name'     => $this->get_discount_label( $rule ),
				'rule_id'  => isset( $rule['id'] ) ? (string) $rule['id'] : '',
			);
		}

		return $fees;
	}

	/**
	 * Check whether any cart discount rule wants single-product promo output.
	 *
	 * @param array $rules Normalized cart discount rule entries.
	 * @return bool
	 */
	public function has_promo_rules( array $rules ): bool {
		foreach ( $rules as $rule ) {
			if ( $this->should_show_cart_discount_conditions( $rule['cart'] ?? array() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render cart discount promo popup for a product.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized cart discount rule entries.
	 * @return void
	 */
	public function render_promo_html( $product, array $rules ): void {
		echo $this->get_promo_html( $product, $rules ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build cart discount promo popup HTML for a product.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized cart discount rule entries.
	 * @return string
	 */
	public function get_promo_html( $product, array $rules ): string {
		if ( ! $product instanceof \WC_Product || empty( $rules ) ) {
			return '';
		}

		$this->register_promo_data( $product, $rules );

		$cart_discounts = wholesalex()->get_rule_data( $product->get_id(), self::PROMO_RULE_DATA_TYPE );

		if ( empty( $cart_discounts ) ) {
			return '';
		}

		usort( $cart_discounts, array( $this, 'compare_promo_rules' ) );

		ob_start();
		$this->render_promo_modal( $product, $cart_discounts );
		return (string) ob_get_clean();
	}

	/**
	 * Register eligible promo data using the same product-scoped cache pattern
	 * used by Dynamic Rules, but with wholesale-pricing rule settings.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rules   Normalized cart discount rule entries.
	 * @return void
	 */
	private function register_promo_data( \WC_Product $product, array $rules ): void {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		$pid          = $variation_id ? $variation_id : $product_id;

		foreach ( $rules as $rule ) {
			if ( ! $this->should_show_cart_discount_conditions( $rule['cart'] ?? array() ) || empty( $rule['id'] ) ) {
				continue;
			}

			if ( ! empty( wholesalex()->get_rule_data( $pid, self::PROMO_RULE_DATA_TYPE, $rule['id'] ) ) ) {
				continue;
			}

			if ( ! Wholesale_Pricing_Condition_Engine::product_ids_match_filter( (int) $product_id, (int) $variation_id, $rule['filter'] ) ) {
				continue;
			}

			wholesalex()->set_rule_data(
				$rule['id'],
				$pid,
				self::PROMO_RULE_DATA_TYPE,
				array(
					'type'                => $rule['rule']['_discount_type'],
					'value'               => $rule['rule']['_discount_amount'],
					'conditions'          => $rule['conditions'],
					'cart'                => isset( $rule['cart'] ) && is_array( $rule['cart'] ) ? $rule['cart'] : array(),
					'who_priority'        => $rule['who_priority'],
					'applied_on_priority' => $rule['applied_on_priority'],
					'end_date'            => $rule['schedule']['end_date'] ?? '',
				)
			);
		}
	}

	/**
	 * Render the complete popup trigger and modal.
	 *
	 * @param \WC_Product $product        Current product.
	 * @param array       $cart_discounts Product-scoped cart discount promo data.
	 * @return void
	 */
	private function render_promo_modal( \WC_Product $product, array $cart_discounts ): void {
		ob_start();
		$this->render_promo_cards( $product, $cart_discounts );
		$modal_content = trim( (string) ob_get_clean() );

		if ( '' === $modal_content ) {
			return;
		}

		$product_id = (string) $product->get_id();
		$button_id  = 'wsx-wp-cart-discount-view-more-' . $product_id;
		$modal_id   = 'wsx-wp-cart-discount-modal-' . $product_id;
		$icon_id    = 'wsx-wp-cart-discount-icon-' . $product_id;
		?>
		<div class="wsx-d-flex wsx-item-center wsx-gap-12 wsx-mb-24 wsx-wp-cart-discount-promo">
			<div class="wsx-font-14"> <?php echo esc_html__( 'Promotions:', 'wholesalex' ); ?></div>
			<div class="wsx-relative">
				<div class="wsx-font-12 wsx-bg-secondary wsx-br-md wsx-pt-4 wsx-pb-6 wsx-plr-10 wsx-color-text-reverse wsx-curser-pointer wsx-btn-icon"
					id="<?php echo esc_attr( $button_id ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
					<?php echo esc_html( apply_filters( 'wholesalex_wholesale_pricing_cart_discount_promo_button_text', __( 'Get exclusive offers', 'wholesalex' ), $product, $cart_discounts ) ); ?>
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
					if (icon.hasClass('rotated')) {
						icon.removeClass('rotated').css('transform', 'rotate(0deg)');
					} else {
						icon.addClass('rotated').css('transform', 'rotate(180deg)');
					}
				});

				$(document).on('click', function(e) {
					if ($(e.target).closest('#<?php echo esc_js( $modal_id ); ?>').length) {
						return;
					}
					if ($(e.target).closest('#<?php echo esc_js( $button_id ); ?>').length) {
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
	 * Render cart discount cards inside the popup modal.
	 *
	 * @param \WC_Product $product        Current product.
	 * @param array       $cart_discounts Product-scoped cart discount promo data.
	 * @return void
	 */
	private function render_promo_cards( \WC_Product $product, array $cart_discounts ): void {
		$info_rule = $this->get_first_rule_with_label( $cart_discounts );
		?>
		<div class="wsx-sp-cart-discounts">
			<?php if ( ! empty( $info_rule ) ) : ?>
				<div class="wsx-sp-rule-info">
					<div class="wsx-font-14 wsx-font-medium">
						<?php echo esc_html( $this->get_cart_setting( $info_rule, 'label_text', __( 'Cart Discount', 'wholesalex' ) ) ); ?>
					</div>
					<?php
					$promo_desc = $this->get_cart_setting( $info_rule, 'promo_desc_text', __( 'After adding to the cart', 'wholesalex' ) );
					if ( '' !== trim( (string) $promo_desc ) ) :
						?>
						<div class="wsx-font-14"><?php echo esc_html( $promo_desc ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
				<?php foreach ( $cart_discounts as $cart_discount ) : ?>
					<?php
					$heading_text = $this->get_promo_heading_text( $product, $cart_discount );
					$conditions   = '';

					if ( $this->should_show_cart_discount_conditions( $cart_discount['cart'] ?? array() ) && isset( $cart_discount['conditions']['tiers'] ) ) {
						$conditions = $this->generate_rule_conditions_markup( $cart_discount['conditions']['tiers'], $cart_discount['cart']['condition_texts'] ?? array() );
					}
					?>
					<div class="wsx-single-product-discount-card wsx-cart-discount-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
						<div class="wsx-font-18 wsx-font-medium" style="color: var(--color-notice)"> <?php echo wp_kses_post( $heading_text ); ?></div>
						<div class="wsx-font-14"><?php echo wp_kses_post( $conditions ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Check whether cart discount conditions should be shown on product pages.
	 *
	 * @param array $cart Cart discount settings.
	 * @return bool
	 */
	private function should_show_cart_discount_conditions( array $cart ): bool {
		return ! empty( $cart['show_cart_discount_conditions'] );
	}

	/**
	 * Build condition text markup from rule-specific text templates.
	 *
	 * @param array $conditions Condition tiers.
	 * @param array $texts      Rule-specific condition text templates.
	 * @return string
	 */
	private function generate_rule_conditions_markup( array $conditions, array $texts ): string {
		$data   = array();
		$markup = '<div>';
		$texts  = $this->normalize_condition_texts( $texts );

		foreach ( $conditions as $condition ) {
			if ( ! isset( $condition['_conditions_for'], $condition['_conditions_operator'], $condition['_conditions_value'] ) ) {
				continue;
			}

			if ( ! is_numeric( $condition['_conditions_value'] ) ) {
				continue;
			}

			$con_value = (float) $condition['_conditions_value'];
			$con_name  = sanitize_key( $condition['_conditions_for'] );
			$operator  = sanitize_key( $condition['_conditions_operator'] );

			if ( ! in_array( $con_name, array( 'cart_total_qty', 'cart_total_value', 'cart_total_weight' ), true ) ) {
				continue;
			}

			if ( ! isset( $data[ $con_name ] ) ) {
				$data[ $con_name ] = array();
			}

			$this->add_condition_boundary( $data[ $con_name ], $operator, $con_value );
		}

		foreach ( $data as $con_name => $cons ) {
			$markup .= $this->get_condition_markup( $con_name, $cons, $texts );
		}

		$markup .= '</div>';
		return $markup;
	}

	/**
	 * Merge saved condition text templates with defaults.
	 *
	 * @param array $texts Saved condition text templates.
	 * @return array<string, string>
	 */
	private function normalize_condition_texts( array $texts ): array {
		$normalized = $this->get_default_condition_texts();
		$legacy_map = $this->get_legacy_condition_text_map();
		$legacy_defaults = $this->get_legacy_default_condition_texts();

		foreach ( $legacy_map as $legacy_key => $current_keys ) {
			if ( ! isset( $texts[ $legacy_key ] ) || ! is_scalar( $texts[ $legacy_key ] ) ) {
				continue;
			}

			$legacy_value = (string) $texts[ $legacy_key ];
			if ( isset( $legacy_defaults[ $legacy_key ] ) && $legacy_defaults[ $legacy_key ] === $legacy_value ) {
				continue;
			}

			foreach ( $current_keys as $current_key ) {
				if ( ! array_key_exists( $current_key, $texts ) ) {
					$normalized[ $current_key ] = $legacy_value;
				}
			}
		}

		foreach ( $texts as $key => $value ) {
			if ( isset( $normalized[ $key ] ) && is_scalar( $value ) ) {
				$normalized[ $key ] = (string) $value;
			}
		}

		return $normalized;
	}

	/**
	 * Add an operator while retaining the most restrictive boundary.
	 *
	 * @param array  $boundaries Parsed condition boundaries.
	 * @param string $operator   Condition operator.
	 * @param float  $value      Condition value.
	 * @return void
	 */
	private function add_condition_boundary( array &$boundaries, string $operator, float $value ): void {
		if ( in_array( $operator, array( 'greater', 'greater_equal' ), true ) ) {
			$direction = 'lower';
		} elseif ( in_array( $operator, array( 'less', 'less_equal' ), true ) ) {
			$direction = 'upper';
		} else {
			return;
		}

		$candidate = array(
			'operator' => $operator,
			'value'    => $value,
		);

		if ( ! isset( $boundaries[ $direction ] ) ) {
			$boundaries[ $direction ] = $candidate;
			return;
		}

		$current     = $boundaries[ $direction ];
		$replace     = 'lower' === $direction ? $value > $current['value'] : $value < $current['value'];
		$same_value  = $value === $current['value'];
		$is_strict   = in_array( $operator, array( 'greater', 'less' ), true );
		$was_strict  = in_array( $current['operator'], array( 'greater', 'less' ), true );

		if ( $replace || ( $same_value && $is_strict && ! $was_strict ) ) {
			$boundaries[ $direction ] = $candidate;
		}
	}

	/**
	 * Render the active operator-aware condition text.
	 *
	 * @param string $condition_for Condition field name.
	 * @param array  $boundaries    Effective lower and upper boundaries.
	 * @param array  $texts         Condition text templates.
	 * @return string
	 */
	private function get_condition_markup( string $condition_for, array $boundaries, array $texts ): string {
		$signature_parts = array();
		if ( isset( $boundaries['lower']['operator'] ) ) {
			$signature_parts[] = $boundaries['lower']['operator'];
		}
		if ( isset( $boundaries['upper']['operator'] ) ) {
			$signature_parts[] = $boundaries['upper']['operator'];
		}

		if ( empty( $signature_parts ) ) {
			return '';
		}

		$key = $condition_for . '_' . implode( '_', $signature_parts ) . '_conditions_text';
		if ( ! isset( $texts[ $key ] ) ) {
			return '';
		}

		$min_value = isset( $boundaries['lower']['value'] ) ? (string) $boundaries['lower']['value'] : '';
		$max_value = isset( $boundaries['upper']['value'] ) ? (string) $boundaries['upper']['value'] : '';

		if ( 'cart_total_value' === $condition_for ) {
			$min_value = '' !== $min_value ? wc_price( (float) $min_value ) : '';
			$max_value = '' !== $max_value ? wc_price( (float) $max_value ) : '';
		}

		return '<div>' . $this->restore_smart_tags(
			array(
				'${min_value}' => $min_value,
				'${max_value}' => $max_value,
				'{min_value}'  => $min_value,
				'{max_value}'  => $max_value,
				'{unit}'       => 'cart_total_weight' === $condition_for ? (string) get_option( 'woocommerce_weight_unit' ) : '',
			),
			$texts[ $key ]
		) . '</div>';
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
	 * Return default condition text templates for saved cart-discount rules.
	 *
	 * @return array<string, string>
	 */
	private function get_default_condition_texts(): array {
		return array(
			'cart_total_qty_less_conditions_text'                   => __( 'Keep your items below {max_value} to qualify.', 'wholesalex' ),
			'cart_total_qty_less_equal_conditions_text'             => __( 'Keep your items at {max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_conditions_text'                => __( 'Add more than {min_value} items to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_equal_conditions_text'          => __( 'Add {min_value} or more items to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_less_conditions_text'           => __( 'Add more than {min_value} but less than {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_equal_less_equal_conditions_text' => __( 'Add {min_value} to {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_less_equal_conditions_text'     => __( 'Add more than {min_value} to {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_equal_less_conditions_text'     => __( 'Add {min_value} to less than {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_less_conditions_text'                => __( 'Keep your weight below {max_value} to qualify.', 'wholesalex' ),
			'cart_total_weight_less_equal_conditions_text'          => __( 'Keep your total weight at {max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_conditions_text'             => __( 'Add more than {min_value} in weight to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_equal_conditions_text'       => __( 'Add {min_value} or more in weight to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_less_conditions_text'        => __( 'Add more than {min_value} but less than {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_equal_less_equal_conditions_text' => __( 'Add {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_less_equal_conditions_text'  => __( 'Add more than {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_equal_less_conditions_text'  => __( 'Add {min_value} to less than {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_value_less_conditions_text'                 => __( 'Keep your spend below ${max_value} to qualify.', 'wholesalex' ),
			'cart_total_value_less_equal_conditions_text'           => __( 'Keep your spend at ${max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_value_greater_conditions_text'              => __( 'Spend more than ${min_value} to qualify.', 'wholesalex' ),
			'cart_total_value_greater_equal_conditions_text'        => __( 'Spend ${min_value} or more to qualify.', 'wholesalex' ),
			'cart_total_value_greater_less_conditions_text'         => __( 'Spend more than ${min_value} but less than ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_equal_less_equal_conditions_text' => __( 'Spend between ${min_value} and ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_less_equal_conditions_text'   => __( 'Spend more than ${min_value} and up to ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_equal_less_conditions_text'   => __( 'Spend ${min_value} or more but less than ${max_value} to unlock this offer.', 'wholesalex' ),
		);
	}

	/**
	 * Return legacy templates used to distinguish defaults from custom text.
	 *
	 * @return array<string, string>
	 */
	private function get_legacy_default_condition_texts(): array {
		return array(
			'cart_total_value_max_conditions_text'        => 'Spend upto {min_value}',
			'cart_total_value_min_conditions_text'        => 'Spend min {max_value}',
			'cart_total_value_min_max_conditions_text'    => 'Spend {min_value} to {max_value}',
			'cart_total_qty_min_max_conditions_text'      => 'Add {min_value} to {max_value} product(s) to cart',
			'cart_total_qty_min_conditions_text'          => 'Add min {min_value} product(s) to cart',
			'cart_total_qty_max_conditions_text'          => 'Add {max_value} or more product(s) to cart',
			'cart_total_weight_min_max_conditions_text'   => 'Add {min_value} to {max_value} {unit} to cart',
			'cart_total_weight_min_conditions_text'       => 'Add min {min_value} {unit} to cart',
			'cart_total_weight_max_conditions_text'       => 'Add up to {max_value} {unit} to cart',
		);
	}

	/**
	 * Map legacy min/max template keys to operator-aware keys.
	 *
	 * @return array<string, array<int, string>>
	 */
	private function get_legacy_condition_text_map(): array {
		return array(
			'cart_total_value_max_conditions_text' => array( 'cart_total_value_less_conditions_text', 'cart_total_value_less_equal_conditions_text' ),
			'cart_total_value_min_conditions_text' => array( 'cart_total_value_greater_conditions_text', 'cart_total_value_greater_equal_conditions_text' ),
			'cart_total_value_min_max_conditions_text' => array( 'cart_total_value_greater_less_conditions_text', 'cart_total_value_greater_equal_less_equal_conditions_text', 'cart_total_value_greater_less_equal_conditions_text', 'cart_total_value_greater_equal_less_conditions_text' ),
			'cart_total_qty_max_conditions_text' => array( 'cart_total_qty_less_conditions_text', 'cart_total_qty_less_equal_conditions_text' ),
			'cart_total_qty_min_conditions_text' => array( 'cart_total_qty_greater_conditions_text', 'cart_total_qty_greater_equal_conditions_text' ),
			'cart_total_qty_min_max_conditions_text' => array( 'cart_total_qty_greater_less_conditions_text', 'cart_total_qty_greater_equal_less_equal_conditions_text', 'cart_total_qty_greater_less_equal_conditions_text', 'cart_total_qty_greater_equal_less_conditions_text' ),
			'cart_total_weight_max_conditions_text' => array( 'cart_total_weight_less_conditions_text', 'cart_total_weight_less_equal_conditions_text' ),
			'cart_total_weight_min_conditions_text' => array( 'cart_total_weight_greater_conditions_text', 'cart_total_weight_greater_equal_conditions_text' ),
			'cart_total_weight_min_max_conditions_text' => array( 'cart_total_weight_greater_less_conditions_text', 'cart_total_weight_greater_equal_less_equal_conditions_text', 'cart_total_weight_greater_less_equal_conditions_text', 'cart_total_weight_greater_equal_less_conditions_text' ),
		);
	}

	/**
	 * Return the first promo rule that has its label block enabled.
	 *
	 * @param array $cart_discounts Product-scoped cart discount promo data.
	 * @return array
	 */
	private function get_first_rule_with_label( array $cart_discounts ): array {
		foreach ( $cart_discounts as $cart_discount ) {
			if ( ! empty( $cart_discount['cart']['show_label_before_promo'] ) ) {
				return $cart_discount;
			}
		}

		return array();
	}

	/**
	 * Read a cart promo setting from a registered promo rule.
	 *
	 * @param array  $rule    Registered promo rule data.
	 * @param string $key     Cart setting key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_cart_setting( array $rule, string $key, string $default ): string {
		if ( isset( $rule['cart'][ $key ] ) && is_scalar( $rule['cart'][ $key ] ) ) {
			return (string) $rule['cart'][ $key ];
		}

		return $default;
	}

	/**
	 * Build the card heading text for a promo rule.
	 *
	 * @param \WC_Product $product Current product.
	 * @param array       $rule    Registered promo rule data.
	 * @return string
	 */
	private function get_promo_heading_text( \WC_Product $product, array $rule ): string {
		$type  = isset( $rule['type'] ) ? sanitize_key( $rule['type'] ) : 'percentage';
		$value = isset( $rule['value'] ) ? (float) $rule['value'] : 0.0;

		if ( 'percentage' === $type ) {
			return $value . __( ' % OFF', 'wholesalex' );
		}

		if ( 'fixed' === $type ) {
			return '<del>' . wc_price( $product->get_price() ) . '</del>. to <ins>' . wc_price( $value ) . '</ins>';
		}

		return wc_price( $value ) . __( ' OFF', 'wholesalex' );
	}

	/**
	 * Compare promo rules by the same priority dimensions used at runtime.
	 *
	 * @param array $a First rule.
	 * @param array $b Second rule.
	 * @return int
	 */
	private function compare_promo_rules( array $a, array $b ): int {
		$a_applied = isset( $a['applied_on_priority'] ) ? (int) $a['applied_on_priority'] : 0;
		$b_applied = isset( $b['applied_on_priority'] ) ? (int) $b['applied_on_priority'] : 0;

		if ( $a_applied === $b_applied ) {
			return ( isset( $b['who_priority'] ) ? (int) $b['who_priority'] : 0 ) <=> ( isset( $a['who_priority'] ) ? (int) $a['who_priority'] : 0 );
		}

		return $b_applied <=> $a_applied;
	}

	/**
	 * Return the cart total that a rule can discount.
	 *
	 * All-products rules use the cart total unless specific products are
	 * excluded. Once exclusions exist, only eligible cart lines can contribute
	 * to the discount base.
	 *
	 * @param array $filter Runtime product filter.
	 * @return float
	 */
	private function get_discount_base_amount( array $filter ): float {
		if ( ! empty( $filter['is_all_products'] ) && empty( $filter['exclude_products'] ) ) {
			return (float) wholesalex()->get_cart_total();
		}

		$total    = 0.0;
		$with_tax = (bool) apply_filters( 'wholesalex_dr_cart_discount_on_tax', false );

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;

			if ( ! Wholesale_Pricing_Condition_Engine::product_ids_match_filter( $product_id, $variation_id, $filter ) ) {
				continue;
			}

			$total += isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0.0;

			if ( $with_tax && isset( $cart_item['line_tax'] ) ) {
				$total += (float) $cart_item['line_tax'];
			}
		}

		return $total;
	}

	/**
	 * Calculate a rule's cart discount amount.
	 *
	 * @param array $rule          Normalized cart discount rule.
	 * @param float $discount_base Cart amount eligible for discount.
	 * @return float
	 */
	private function get_discount_amount( array $rule, float $discount_base ): float {
		$discount = isset( $rule['rule'] ) && is_array( $rule['rule'] ) ? $rule['rule'] : array();
		$type     = isset( $discount['_discount_type'] ) ? sanitize_key( $discount['_discount_type'] ) : 'percentage';
		$amount   = isset( $discount['_discount_amount'] ) ? (float) $discount['_discount_amount'] : 0.0;

		if ( $amount <= 0 ) {
			return 0.0;
		}

		if ( 'percentage' === $type ) {
			$discount_amount = ( $discount_base * $amount ) / 100;
		} else {
			$discount_amount = $amount;
		}

		return (float) min( max( 0, $discount_amount ), $discount_base );
	}

	/**
	 * Return the fee label shown on cart and checkout totals.
	 *
	 * @param array $rule Normalized cart discount rule.
	 * @return string
	 */
	private function get_discount_label( array $rule ): string {
		$discount = isset( $rule['rule'] ) && is_array( $rule['rule'] ) ? $rule['rule'] : array();
		$label    = '';

		if ( ! empty( $discount['_discount_name'] ) ) {
			$label = (string) $discount['_discount_name'];
		} elseif ( ! empty( $discount['_discount_label'] ) ) {
			$label = (string) $discount['_discount_label'];
		}

		if ( '' === trim( $label ) ) {
			$label = __( 'Cart Discount', 'wholesalex' );
		}

		return apply_filters( 'wholesalex_cart_discount_title', $label, $rule );
	}

	/**
	 * Build a stable hash for a calculated fee entry.
	 *
	 * @param array $rule Normalized cart discount rule.
	 * @return string
	 */
	private function get_hash_key( array $rule ): string {
		return md5(
			serialize(
				array(
					'id'     => $rule['id'] ?? '',
					'filter' => $rule['filter'] ?? array(),
				)
			)
		);
	}
}
