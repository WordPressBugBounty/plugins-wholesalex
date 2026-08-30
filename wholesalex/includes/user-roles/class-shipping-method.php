<?php
/**
 * WholesaleX User Roles - Shipping Method Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level shipping method rules.
 */
class User_Roles_Shipping_Method {

	/**
	 * Free shipping rule group key.
	 *
	 * @var string
	 */
	const FREE_SHIPPING_RULE = 'free_shipping';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'add_role_shipping_hash_to_packages' ) );
		add_filter( 'woocommerce_package_rates', array( $this, 'apply_role_shipping_methods' ), 5, 2 );
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_simple_product_shipping_promo' ), 12 );
		add_filter( 'woocommerce_available_variation', array( $this, 'render_variation_shipping_promo' ), 12, 3 );
		add_action( 'wp_footer', array( $this, 'render_shipping_promo_script' ) );
	}

	/**
	 * Add role shipping config to WooCommerce shipping package hashes.
	 *
	 * WooCommerce stores calculated package rates in the customer session. If
	 * role shipping permissions change without cart/address changes, this hash
	 * forces checkout to recalculate the methods.
	 *
	 * @param array $packages Shipping packages.
	 * @return array
	 */
	public function add_role_shipping_hash_to_packages( $packages ) {
		if ( empty( $packages ) || ! is_array( $packages ) ) {
			return $packages;
		}

		$config = $this->get_current_role_config();
		$hash   = md5( wp_json_encode( $config ) );

		foreach ( $packages as $package_key => $package ) {
			$packages[ $package_key ]['wholesalex_role_shipping_hash'] = $hash;
		}

		return $packages;
	}

	/**
	 * Apply role-level shipping method permissions to package rates.
	 *
	 * Free shipping is only filtered here. WooCommerce remains responsible for
	 * making the rate available after evaluating its minimum amount, coupon, and
	 * other native requirements.
	 *
	 * @param array $package_rates Package rates.
	 * @param array $package Package data.
	 * @return array
	 */
	public function apply_role_shipping_methods( $package_rates, $package ) {
		$config = $this->get_current_role_config();
		if ( empty( $config['methods'] ) ) {
			return $package_rates;
		}

		$selected_methods = $this->get_package_selected_shipping_methods( $package, $config['methods'] );
		if ( empty( $selected_methods ) ) {
			return $package_rates;
		}

		$matched_methods = array();
		foreach ( $selected_methods as $instance_id => $method ) {
			if ( 'free_shipping' !== $method->id ) {
				$matched_methods[ $instance_id ] = $method;
				continue;
			}

			$rule = $this->normalize_rule( isset( $config['rules'][ $instance_id ] ) ? $config['rules'][ $instance_id ] : array() );
			if ( $this->package_matches_rule( $package, $rule ) ) {
				$matched_methods[ $instance_id ] = $method;
			}
		}

		foreach ( $package_rates as $rate_key => $rate ) {
			if ( ! is_object( $rate ) || 'free_shipping' !== $rate->method_id ) {
				continue;
			}

			$instance_id = (string) $rate->instance_id;
			if ( isset( $selected_methods[ $instance_id ] ) && ! isset( $matched_methods[ $instance_id ] ) ) {
				unset( $package_rates[ $rate_key ] );
			}
		}

		foreach ( $matched_methods as $instance_id => $method ) {
			if ( $this->package_has_shipping_rate( $package_rates, $method->id, $instance_id ) ) {
				continue;
			}

			// Never recreate a free shipping rate rejected by WooCommerce.
			if ( 'free_shipping' === $method->id ) {
				continue;
			}

			$package_rates = $this->merge_calculated_method_rates( $package_rates, $method, $package );
		}

		return $package_rates;
	}

	/**
	 * Merge calculated rates for a selected role shipping method.
	 *
	 * @param array  $package_rates Package rates.
	 * @param object $method Shipping method instance.
	 * @param array  $package Package data.
	 * @return array
	 */
	private function merge_calculated_method_rates( $package_rates, $method, $package ) {
		if ( ! method_exists( $method, 'calculate_shipping' ) ) {
			return $package_rates;
		}

		$method->rates = array();
		$method->calculate_shipping( $package );

		if ( empty( $method->rates ) || ! is_array( $method->rates ) ) {
			return $package_rates;
		}

		foreach ( $method->rates as $rate_id => $rate ) {
			if ( ! is_object( $rate ) ) {
				continue;
			}

			$package_rates[ $rate_id ] = $rate;
		}

		return $package_rates;
	}

	/**
	 * Render role shipping promo for simple products.
	 *
	 * @return void
	 */
	public function render_simple_product_shipping_promo() {
		global $product;

		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo $this->get_product_shipping_promo_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Add role shipping promo to variation availability HTML.
	 *
	 * @param array       $variation_array Variation data.
	 * @param \WC_Product $product Parent product.
	 * @param \WC_Product $variation Variation product.
	 * @return array
	 */
	public function render_variation_shipping_promo( $variation_array, $product, $variation ) {
		if ( ! is_object( $variation ) || ! is_a( $variation, 'WC_Product' ) ) {
			return $variation_array;
		}

		$variation_array['availability_html'] .= $this->get_product_shipping_promo_html( $variation );

		return $variation_array;
	}

	/**
	 * Build role shipping promo HTML for an eligible product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function get_product_shipping_promo_html( $product ) {
		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return '';
		}

		$config = $this->get_current_role_config();
		if ( empty( $config['methods'] ) || empty( $config['promo'] ) ) {
			return '';
		}

		$show_promo_text = isset( $config['promo']['show_promo_text'] ) && 'yes' === $config['promo']['show_promo_text'];
		$show_popup      = isset( $config['promo']['show_popup'] ) && 'yes' === $config['promo']['show_popup'];

		if ( ! $show_promo_text && ! $show_popup ) {
			return '';
		}

		if ( ! $this->product_has_role_free_shipping( $product, $config ) ) {
			return '';
		}

		$html = '';

		if ( $show_popup ) {
			$html .= $this->get_shipping_promo_popup_html( $product, $config['promo'] );
		}

		if ( $show_promo_text ) {
			$html .= $this->get_shipping_promo_text_html( $config['promo'] );
		}

		return $html;
	}

	/**
	 * Check whether a product matches any role-selected free shipping method.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $config Role shipping config.
	 * @return bool
	 */
	private function product_has_role_free_shipping( $product, $config ) {
		foreach ( $config['methods'] as $instance_id ) {
			$method = $this->get_shipping_method_by_instance_id( $instance_id );
			if ( ! is_object( $method ) || 'free_shipping' !== $method->id ) {
				continue;
			}

			$rule = $this->normalize_rule( isset( $config['rules'][ $instance_id ] ) ? $config['rules'][ $instance_id ] : array() );
			if ( $this->product_matches_rule( $product, $rule ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the inline role shipping promo text card.
	 *
	 * @param array $promo Promo settings.
	 * @return string
	 */
	private function get_shipping_promo_text_html( $promo ) {
		$shipping_text = $this->get_promo_text( $promo, 'promo_text' );

		ob_start();
		?>
		<div class="wsx-single-product-discount-card wsx-mt-10 wsx-mb-8 wsx-free-shipping-sp-card wsx-role-free-shipping-sp-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
			<?php echo esc_html( $shipping_text ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the role shipping promo popup.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $promo Promo settings.
	 * @return string
	 */
	private function get_shipping_promo_popup_html( $product, $promo ) {
		$product_id  = $product->get_id();
		$popup_text  = $this->get_promo_text( $promo, 'popup_text' );
		$button_text = wholesalex()->get_setting( 'promo_button_text_on_sp', __( 'Get exclusive offers', 'wholesalex' ) );

		ob_start();
		?>
		<div class="wsx-d-flex wsx-item-center wsx-gap-12 wsx-mb-24 wsx-role-free-shipping-promo">
			<div class="wsx-font-14"><?php esc_html_e( 'Promotions:', 'wholesalex' ); ?></div>
			<div class="wsx-relative">
				<div class="wsx-font-12 wsx-bg-secondary wsx-br-md wsx-pt-4 wsx-pb-6 wsx-plr-10 wsx-color-text-reverse wsx-curser-pointer wsx-btn-icon wsx-role-free-shipping-promo-toggle" data-product-id="<?php echo esc_attr( $product_id ); ?>">
					<?php echo esc_html( $button_text ); ?>
					<div class="wsx-icon wsx-role-free-shipping-promo-icon" style="margin-bottom: -4px; transition: all 0.3s;">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none">
							<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m5 7.5 5 5 5-5" />
						</svg>
					</div>
				</div>

				<div class="wsx-role-free-shipping-promo-modal wsx-absolute wsx-down-4 wsx-z-999 wsx-card wsx-plr-8 wsx-pt-10 wsx-pb-10 wsx-width-70v wsx-width-250 wsx-shadow-primary" data-product-id="<?php echo esc_attr( $product_id ); ?>" style="display:none;">
					<div class="wsx-font-14 wsx-font-bold wsx-text-center wsx-color-text-medium wsx-mb-8">
						<?php esc_html_e( 'Conditional Discount Offers', 'wholesalex' ); ?>
					</div>
					<div class="wsx-free-shipping-discounts">
						<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
							<div class="wsx-single-product-discount-card wsx-sp-free-shipping-cart wsx-role-free-shipping-popup-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
								<div class="wsx-font-18"><?php echo wp_kses_post( $popup_text ); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the role shipping promo popup interaction script.
	 *
	 * @return void
	 */
	public function render_shipping_promo_script() {
		if ( ! is_product() ) {
			return;
		}

		$config = $this->get_current_role_config();
		if ( empty( $config['methods'] ) || empty( $config['promo']['show_popup'] ) || 'yes' !== $config['promo']['show_popup'] ) {
			return;
		}

		?>
		<script type="text/javascript">
			(function($) {
				'use strict';

				$(document).off('click.wholesalexRoleShippingPromo').on('click.wholesalexRoleShippingPromo', '.wsx-role-free-shipping-promo-toggle', function(e) {
					e.preventDefault();

					const wrapper = $(this).closest('.wsx-role-free-shipping-promo');
					const modal = wrapper.find('.wsx-role-free-shipping-promo-modal');
					const icon = wrapper.find('.wsx-role-free-shipping-promo-icon');

					$('.wsx-role-free-shipping-promo-modal').not(modal).hide(100);
					$('.wsx-role-free-shipping-promo-icon').not(icon).removeClass('rotated').css('transform', 'rotate(0deg)');

					modal.slideToggle(100);
					if (icon.hasClass('rotated')) {
						icon.removeClass('rotated').css('transform', 'rotate(0deg)');
					} else {
						icon.addClass('rotated').css('transform', 'rotate(180deg)');
					}
				});

				$(document).off('click.wholesalexRoleShippingPromoOutside').on('click.wholesalexRoleShippingPromoOutside', function(e) {
					if ($(e.target).closest('.wsx-role-free-shipping-promo-modal').length !== 0) return;
					if ($(e.target).closest('.wsx-role-free-shipping-promo-toggle').length !== 0) return;
					$('.wsx-role-free-shipping-promo-modal').hide(100);
					$('.wsx-role-free-shipping-promo-icon').removeClass('rotated').css('transform', 'rotate(0deg)');
				});
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Get promo text value.
	 *
	 * @param array  $promo Promo settings.
	 * @param string $key Promo text key.
	 * @return string
	 */
	private function get_promo_text( $promo, $key ) {
		return isset( $promo[ $key ] ) && '' !== $promo[ $key ] ? $promo[ $key ] : __( 'Free Shipping', 'wholesalex' );
	}

	/**
	 * Get current role shipping methods and method rules.
	 *
	 * @return array
	 */
	private function get_current_role_config() {
		$role_id = wholesalex()->get_current_user_role();
		if ( empty( $role_id ) ) {
			return array(
				'methods' => array(),
				'rules'   => array(),
				'promo'   => array(),
			);
		}

		$role_content = wholesalex()->get_roles( 'by_id', $role_id );
		if ( is_array( $role_content ) ) {
			$role_content = WHOLESALEX_Role::get_role_with_wtrs_shipping_methods( $role_content );
		}
		$methods      = isset( $role_content['_shipping_methods'] ) && is_array( $role_content['_shipping_methods'] ) ? $role_content['_shipping_methods'] : array();
		$rules        = isset( $role_content['_shipping_method_rules'] ) && is_array( $role_content['_shipping_method_rules'] ) ? $role_content['_shipping_method_rules'] : array();
		$promo        = $this->normalize_promo( $role_content );

		return array(
			'methods' => array_values( array_unique( array_map( 'strval', array_filter( $methods ) ) ) ),
			'rules'   => $rules,
			'promo'   => $promo,
		);
	}

	/**
	 * Get selected shipping methods available to the current package zone.
	 *
	 * @param array $package Package data.
	 * @param array $selected_methods Selected method instance ids.
	 * @return array
	 */
	private function get_package_selected_shipping_methods( $package, $selected_methods ) {
		if ( ! function_exists( 'wc_get_shipping_zone' ) || ! is_array( $package ) ) {
			return array();
		}

		$zone = wc_get_shipping_zone( $package );
		if ( ! is_object( $zone ) || ! method_exists( $zone, 'get_shipping_methods' ) ) {
			return array();
		}

		$selected_methods = array_map( 'strval', $selected_methods );
		$methods          = array();

		foreach ( $zone->get_shipping_methods() as $method ) {
			if ( ! is_object( $method ) || ! $method->is_enabled() ) {
				continue;
			}

			$instance_id = (string) $method->get_instance_id();
			if ( in_array( $instance_id, $selected_methods, true ) ) {
				$methods[ $instance_id ] = $method;
			}
		}

		return $methods;
	}

	/**
	 * Check if package rates already contain a shipping rate for an instance.
	 *
	 * @param array  $package_rates Package rates.
	 * @param string $method_id Shipping method id.
	 * @param string $instance_id Shipping method instance id.
	 * @return bool
	 */
	private function package_has_shipping_rate( $package_rates, $method_id, $instance_id ) {
		foreach ( $package_rates as $rate ) {
			if ( is_object( $rate ) && (string) $rate->method_id === (string) $method_id && (string) $rate->instance_id === (string) $instance_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize role free shipping rules.
	 *
	 * Supports the current nested schema and previous flat keys.
	 *
	 * @param array $rules Method rules.
	 * @return array
	 */
	private function normalize_rule( $rules ) {
		$free_shipping = isset( $rules[ self::FREE_SHIPPING_RULE ] ) && is_array( $rules[ self::FREE_SHIPPING_RULE ] ) ? $rules[ self::FREE_SHIPPING_RULE ] : array();

		return array(
			'product_filter'   => $this->normalize_product_filter( isset( $free_shipping['product_filter'] ) ? $free_shipping['product_filter'] : ( isset( $rules['_filter_type'] ) ? $rules['_filter_type'] : 'all_products' ) ),
			'products'         => isset( $free_shipping['products'] ) && is_array( $free_shipping['products'] ) ? $free_shipping['products'] : ( isset( $rules['_products'] ) && is_array( $rules['_products'] ) ? $rules['_products'] : array() ),
			'categories'       => isset( $free_shipping['categories'] ) && is_array( $free_shipping['categories'] ) ? $free_shipping['categories'] : ( isset( $rules['_categories'] ) && is_array( $rules['_categories'] ) ? $rules['_categories'] : array() ),
			'brands'           => isset( $free_shipping['brands'] ) && is_array( $free_shipping['brands'] ) ? $free_shipping['brands'] : ( isset( $rules['_brands'] ) && is_array( $rules['_brands'] ) ? $rules['_brands'] : array() ),
			'attributes'       => isset( $free_shipping['attributes'] ) && is_array( $free_shipping['attributes'] ) ? $free_shipping['attributes'] : ( isset( $rules['_attributes'] ) && is_array( $rules['_attributes'] ) ? $rules['_attributes'] : array() ),
			'exclude_products' => isset( $free_shipping['exclude_products'] ) && is_array( $free_shipping['exclude_products'] ) ? $free_shipping['exclude_products'] : ( isset( $rules['_exclude_products'] ) && is_array( $rules['_exclude_products'] ) ? $rules['_exclude_products'] : array() ),
		);
	}

	/**
	 * Normalize role-level shipping promo settings.
	 *
	 * @param array $role_content Role data.
	 * @return array
	 */
	private function normalize_promo( $role_content ) {
		$promo = array();
		if ( isset( $role_content['_shipping_method_promo'][ self::FREE_SHIPPING_RULE ] ) && is_array( $role_content['_shipping_method_promo'][ self::FREE_SHIPPING_RULE ] ) ) {
			$promo = $role_content['_shipping_method_promo'][ self::FREE_SHIPPING_RULE ];
		}

		return array(
			'show_promo_text' => isset( $promo['show_promo_text'] ) ? $promo['show_promo_text'] : $this->get_legacy_promo_value( $role_content, '_show_shipping_promo_texts', 'no' ),
			'promo_text'      => isset( $promo['promo_text'] ) ? $promo['promo_text'] : $this->get_legacy_promo_value( $role_content, '_free_shipping_promo_text', __( 'Free Shipping', 'wholesalex' ) ),
			'show_popup'      => isset( $promo['show_popup'] ) ? $promo['show_popup'] : $this->get_legacy_promo_value( $role_content, '_show_free_shipping_promo_on_popup', 'no' ),
			'popup_text'      => isset( $promo['popup_text'] ) ? $promo['popup_text'] : $this->get_legacy_promo_value( $role_content, '_free_shipping_promo_popup_text', __( 'Free Shipping', 'wholesalex' ) ),
		);
	}

	/**
	 * Get a legacy root-level promo value.
	 *
	 * @param array  $role_content Role data.
	 * @param string $key Legacy field key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_legacy_promo_value( $role_content, $key, $default ) {
		return isset( $role_content[ $key ] ) ? $role_content[ $key ] : $default;
	}

	/**
	 * Normalize shipping product filter.
	 *
	 * @param string $product_filter Product filter.
	 * @return string
	 */
	private function normalize_product_filter( $product_filter ) {
		$filter_map = array(
			'all'              => 'all_products',
			'products_in_list' => 'specific_products',
			'cat_in_list'      => 'categories',
			'brand_in_list'    => 'brands',
			'att_in_list'      => 'attributes',
		);

		if ( isset( $filter_map[ $product_filter ] ) ) {
			return $filter_map[ $product_filter ];
		}

		return in_array( $product_filter, array( 'specific_products', 'categories', 'brands', 'attributes' ), true ) ? $product_filter : 'all_products';
	}

	/**
	 * Check whether a package contains an item eligible for a free shipping rule.
	 *
	 * @param array $package Package data.
	 * @param array $rule Normalized rule.
	 * @return bool
	 */
	private function package_matches_rule( $package, $rule ) {
		if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
			return 'all_products' === $rule['product_filter'];
		}

		foreach ( $package['contents'] as $cart_item ) {
			if ( $this->cart_item_matches_rule( $cart_item, $rule ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether a cart item is eligible for a free shipping rule.
	 *
	 * @param array $cart_item Cart item.
	 * @param array $rule Normalized rule.
	 * @return bool
	 */
	private function cart_item_matches_rule( $cart_item, $rule ) {
		$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;

		return $this->ids_match_rule( $product_id, $variation_id, $rule );
	}

	/**
	 * Check whether a product is eligible for a free shipping rule.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule Normalized rule.
	 * @return bool
	 */
	private function product_matches_rule( $product, $rule ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		return $this->ids_match_rule( $product_id, $variation_id, $rule );
	}

	/**
	 * Check product ids against a normalized free shipping rule.
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $variation_id Variation ID.
	 * @param array $rule Normalized rule.
	 * @return bool
	 */
	private function ids_match_rule( $product_id, $variation_id, $rule ) {
		$product_ids       = array_filter( array( $product_id, $variation_id ) );
		$excluded_products = $this->get_selected_ids( $rule['exclude_products'] );

		if ( ! empty( $excluded_products ) && ! empty( array_intersect( $product_ids, $excluded_products ) ) ) {
			return false;
		}

		if ( 'all_products' === $rule['product_filter'] ) {
			return true;
		}

		$filter_values = $this->get_filter_values( $rule );
		if ( empty( $filter_values ) ) {
			return false;
		}

		switch ( $rule['product_filter'] ) {
			case 'specific_products':
				return ! empty( array_intersect( $product_ids, $filter_values ) );
			case 'categories':
				return ! empty( array_intersect( wc_get_product_term_ids( $product_id, 'product_cat' ), $filter_values ) );
			case 'brands':
				$brand_taxonomy = $this->get_brand_taxonomy();
				return $brand_taxonomy && ! empty( array_intersect( wc_get_product_term_ids( $product_id, $brand_taxonomy ), $filter_values ) );
			case 'attributes':
				return ! empty( array_intersect( $this->get_product_attribute_ids( $product_id ), $filter_values ) );
			default:
				return false;
		}
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
	 * Get selected ids for the active product filter.
	 *
	 * @param array $rule Normalized rule.
	 * @return array
	 */
	private function get_filter_values( $rule ) {
		switch ( $rule['product_filter'] ) {
			case 'specific_products':
				return $this->get_selected_ids( $rule['products'] );
			case 'categories':
				return $this->get_selected_ids( $rule['categories'] );
			case 'brands':
				return $this->get_selected_ids( $rule['brands'] );
			case 'attributes':
				return $this->get_selected_ids( $rule['attributes'] );
			default:
				return array();
		}
	}

	/**
	 * Extract ids from a multiselect value list.
	 *
	 * @param array $items Multiselect data.
	 * @return array
	 */
	private function get_selected_ids( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return array();
		}

		$ids = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$ids[] = (int) $item['value'];
			} elseif ( is_numeric( $item ) ) {
				$ids[] = (int) $item;
			}
		}

		return array_values( array_filter( array_unique( $ids ) ) );
	}

	/**
	 * Get all registered attribute ids used by a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function get_product_attribute_ids( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$attribute_ids         = array();
		$registered_attributes = wc_get_attribute_taxonomies();
		$taxonomy_to_id_map    = array();

		foreach ( $registered_attributes as $registered_attr ) {
			$taxonomy_name                        = wc_attribute_taxonomy_name( $registered_attr->attribute_name );
			$taxonomy_to_id_map[ $taxonomy_name ] = (int) $registered_attr->attribute_id;
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_object( $attribute ) || ! method_exists( $attribute, 'is_taxonomy' ) || ! $attribute->is_taxonomy() ) {
				continue;
			}

			$taxonomy = $attribute->get_taxonomy();
			if ( isset( $taxonomy_to_id_map[ $taxonomy ] ) ) {
				$attribute_ids[] = $taxonomy_to_id_map[ $taxonomy ];
			}
		}

		return array_values( array_unique( $attribute_ids ) );
	}

	/**
	 * Get a WooCommerce shipping method by instance id.
	 *
	 * @param string|int $instance_id Shipping method instance id.
	 * @return object|null
	 */
	private function get_shipping_method_by_instance_id( $instance_id ) {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return null;
		}

		return \WC_Shipping_Zones::get_shipping_method( (int) $instance_id );
	}
}
