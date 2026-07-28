<?php
/**
 * WholesaleX Wholesale Pricing - Tiered Discount Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles quantity-based wholesale-pricing tiers and table rendering.
 */
class Wholesale_Pricing_Tiered_Discount {

	/**
	 * Calculate the active tier price.
	 *
	 * @param array       $rule       Normalized wholesale-pricing rule entry.
	 * @param \WC_Product $product    Current product.
	 * @param float       $base_price Price before this rule is applied.
	 * @param int         $quantity   Quantity context for tier selection.
	 * @return array{price: float|false, tier: array|false}
	 */
	public function calculate( array $rule, \WC_Product $product, float $base_price, int $quantity ): array {
		$active_tier = $this->get_active_tier( $rule, $quantity );

		if ( empty( $active_tier ) || $base_price <= 0 ) {
			return array(
				'price' => false,
				'tier'  => false,
			);
		}

		$price = Wholesale_Pricing_Rule_Engine::calculate_discounted_price( $active_tier, $base_price );

		return array(
			'price' => false === $price ? false : (float) $price,
			'tier'  => $active_tier,
		);
	}

	/**
	 * Return the tier matching the provided quantity.
	 *
	 * @param array $rule     Normalized wholesale-pricing rule entry.
	 * @param int   $quantity Quantity to match.
	 * @return array|false
	 */
	public function get_active_tier( array $rule, int $quantity ) {
		if ( $quantity < 1 ) {
			return false;
		}

		$tiers       = $this->get_tiers( $rule );
		$active_tier = false;
		$matched_min = 0;

		foreach ( $tiers as $tier ) {
			$min_qty = isset( $tier['_min_quantity'] ) ? absint( $tier['_min_quantity'] ) : 0;
			$max_qty = isset( $tier['_max_quantity'] ) ? absint( $tier['_max_quantity'] ) : 0;

			if ( $min_qty < 1 || $quantity < $min_qty ) {
				continue;
			}

			if ( $max_qty > 0 && $quantity > $max_qty ) {
				continue;
			}

			if ( $min_qty >= $matched_min ) {
				$active_tier = $tier;
				$matched_min = $min_qty;
			}
		}

		return $active_tier;
	}

	/**
	 * Normalize UI tier data into the discount shape used by the pricing engine.
	 *
	 * @param array $rule Normalized wholesale-pricing rule entry.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_tiers( array $rule ): array {
		$tiers = isset( $rule['rule']['tiers'] ) && is_array( $rule['rule']['tiers'] ) ? $rule['rule']['tiers'] : array();
		$out   = array();

		foreach ( $tiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}

			$min_qty = isset( $tier['min_qty'] ) ? absint( $tier['min_qty'] ) : 0;
			$amount  = isset( $tier['amount'] ) ? $tier['amount'] : '';

			if ( $min_qty < 1 || '' === $amount || ! is_numeric( $amount ) ) {
				continue;
			}

			$max_qty = isset( $tier['max_qty'] ) && null !== $tier['max_qty'] && '' !== $tier['max_qty'] ? absint( $tier['max_qty'] ) : 0;

			$out[] = array(
				'_id'              => isset( $tier['id'] ) && '' !== $tier['id'] ? sanitize_text_field( $tier['id'] ) : wp_unique_id( 'wsx_tier_' ),
				'_min_quantity'    => $min_qty,
				'_max_quantity'    => $max_qty,
				'_discount_type'   => isset( $tier['amount_type'] ) ? sanitize_text_field( $tier['amount_type'] ) : 'percentage',
				'_discount_amount' => $amount,
			);
		}

		usort(
			$out,
			function ( $a, $b ) {
				return absint( $a['_min_quantity'] ) <=> absint( $b['_min_quantity'] );
			}
		);

		return $out;
	}

	/**
	 * Render the tiered pricing table using the rule's design settings.
	 *
	 * @param array       $rule          Normalized wholesale-pricing rule entry.
	 * @param \WC_Product $product       Current product.
	 * @param float       $base_price    Price before this rule is applied.
	 * @param int         $cart_quantity Quantity currently in cart.
	 * @return string
	 */
	public function render_table( array $rule, \WC_Product $product, float $base_price, int $cart_quantity ): string {
		$tiers = $this->get_tiers( $rule );

		if ( empty( $tiers ) || $base_price <= 0 ) {
			return '';
		}

		$design      = isset( $rule['design'] ) && is_array( $rule['design'] ) ? $rule['design'] : array();
		$table_style = isset( $design['table_style'] ) ? $design['table_style'] : 'table_style';
		$is_vertical = ! empty( $design['vertical_style'] );
		$layout      = ( 'classic_style' === $table_style ) ? ( $is_vertical ? 'layout_three' : 'layout_two' ) : ( $is_vertical ? 'layout_six' : 'layout_one' );
		$active_tier = $this->get_active_tier( $rule, $cart_quantity );
		$columns     = $this->get_columns( $design );
		$heading     = isset( $design['table_heading'] ) && '' !== $design['table_heading'] ? $design['table_heading'] : __( 'Buy More, Save More', 'wholesalex' );

		ob_start();
		$this->render_css( $rule, $design, $columns );

		if ( in_array( $layout, array( 'layout_one', 'layout_six' ), true ) ) {
			$this->render_table_layout( $rule, $product, $tiers, $active_tier, $base_price, $columns, $heading, $layout, $cart_quantity );
		} else {
			$this->render_classic_layout( $rule, $product, $tiers, $active_tier, $base_price, $heading, $layout, $cart_quantity );
		}

		return (string) ob_get_clean();
	}

	/**
	 * Return enabled column definitions.
	 *
	 * @param array $design Rule design settings.
	 * @return array
	 */
	private function get_columns( array $design ): array {
		$columns = isset( $design['column_priority'] ) && is_array( $design['column_priority'] ) ? $design['column_priority'] : array();

		if ( empty( $columns ) ) {
			$columns = array(
				array(
					'label'  => 'Quantity_Range',
					'value'  => __( 'Quantity Range', 'wholesalex' ),
					'status' => true,
				),
				array(
					'label'  => 'Discount',
					'value'  => __( 'Discount', 'wholesalex' ),
					'status' => true,
				),
				array(
					'label'  => 'Price_Per_Unit',
					'value'  => __( 'Price Per Unit', 'wholesalex' ),
					'status' => true,
				),
			);
		}

		return array_values(
			array_filter(
				$columns,
				function ( $column ) {
					return ! empty( $column['status'] );
				}
			)
		);
	}

	/**
	 * Render table layout markup.
	 *
	 * @param array       $rule        Rule entry.
	 * @param \WC_Product $product     Product object.
	 * @param array       $tiers       Normalized tiers.
	 * @param array|false $active_tier Active tier.
	 * @param float       $base_price  Base price.
	 * @param array       $columns     Enabled columns.
	 * @param string      $heading     Table heading.
	 * @param string      $layout      Layout key.
	 * @param int         $cart_quantity Quantity currently in cart.
	 * @return void
	 */
	private function render_table_layout( array $rule, \WC_Product $product, array $tiers, $active_tier, float $base_price, array $columns, string $heading, string $layout, int $cart_quantity ): void {
		?>
		<div class="wsx-price-container-title"><?php echo esc_html( $heading ); ?></div>
		<div class="wsx-price-table-container wsx-scrollbar wsx-table-overflow <?php echo 'layout_six' === $layout ? 'layout-vertical' : ''; ?>" data-cart-quantity="<?php echo esc_attr( $cart_quantity ); ?>">
			<div class="wsx-price-table-header"><div class="wsx-price-table-row">
				<?php foreach ( $columns as $column ) : ?>
					<div class="wsx-tooltip wsx-tooltip-global wsx-price-table-cell">
						<div class="wsx-ellipsis"><?php echo esc_html( $column['value'] ); ?></div>
						<div class="wsx-tooltip-content wsx-font-regular top wsx-text-center" style="margin-bottom: -16px;"><?php echo esc_html( $column['value'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div></div>
			<div class="wsx-price-table-body">
				<?php foreach ( $tiers as $tier ) : ?>
					<?php
					$sale_price = Wholesale_Pricing_Rule_Engine::calculate_discounted_price( $tier, $base_price );
					$discount   = max( 0, $base_price - (float) $sale_price );
					$is_active  = $active_tier && $active_tier['_id'] === $tier['_id'];
					$price_html = $this->format_product_price_html( $product, $base_price, (float) $sale_price );
					?>
					<div data-min="<?php echo esc_attr( $tier['_min_quantity'] ); ?>" data-max="<?php echo esc_attr( $tier['_max_quantity'] ); ?>" data-price-html="<?php echo esc_attr( $price_html ); ?>" class="wsx-price-table-row <?php echo $is_active ? 'active' : ''; ?>">
						<?php foreach ( $columns as $column ) : ?>
							<?php if ( 'Discount' === $column['label'] ) : ?>
								<div class="wsx-price-table-cell"><?php echo wp_kses_post( $this->format_discount( $discount ) ); ?></div>
							<?php elseif ( 'Quantity_Range' === $column['label'] ) : ?>
								<div class="wsx-price-table-cell"><?php echo esc_html( $this->format_quantity_range( $tier ) ); ?></div>
							<?php elseif ( 'Price_Per_Unit' === $column['label'] ) : ?>
								<div class="wsx-price-table-cell"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product, array( 'price' => $sale_price ) ) ) ); ?></div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render classic layout markup.
	 *
	 * @param array       $rule        Rule entry.
	 * @param \WC_Product $product     Product object.
	 * @param array       $tiers       Normalized tiers.
	 * @param array|false $active_tier Active tier.
	 * @param float       $base_price  Base price.
	 * @param string      $heading     Table heading.
	 * @param string      $layout      Layout key.
	 * @param int         $cart_quantity Quantity currently in cart.
	 * @return void
	 */
	private function render_classic_layout( array $rule, \WC_Product $product, array $tiers, $active_tier, float $base_price, string $heading, string $layout, int $cart_quantity ): void {
		?>
		<div class="wsx-price-container-title"><?php echo esc_html( $heading ); ?></div>
		<div class="wsx-price-classical-container <?php echo 'layout_three' === $layout ? 'layout-vertical' : ''; ?>" data-cart-quantity="<?php echo esc_attr( $cart_quantity ); ?>">
			<?php foreach ( $tiers as $tier ) : ?>
				<?php
				$sale_price = Wholesale_Pricing_Rule_Engine::calculate_discounted_price( $tier, $base_price );
				$discount   = max( 0, $base_price - (float) $sale_price );
				$is_active  = $active_tier && $active_tier['_id'] === $tier['_id'];
				$price_html = $this->format_product_price_html( $product, $base_price, (float) $sale_price );
				?>
				<div data-min="<?php echo esc_attr( $tier['_min_quantity'] ); ?>" data-max="<?php echo esc_attr( $tier['_max_quantity'] ); ?>" data-price-html="<?php echo esc_attr( $price_html ); ?>" class="wsx-price-classical-item <?php echo $is_active ? 'active' : ''; ?>">
					<div class="wsx-price-classical-content">
						<div class="wsx-price-classical-price">
							<div class="wsx-price-classical-text"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product, array( 'price' => $sale_price ) ) ) ); ?></div>
							<?php if ( 'layout_three' !== $layout ) : ?>
								<div class="wsx-price-classical-tag"><?php echo wp_kses_post( $this->format_discount( $discount ) ); ?></div>
							<?php endif; ?>
						</div>
						<?php if ( 'layout_three' === $layout ) : ?>
							<div class="wsx-price-classical-divider">/</div>
						<?php endif; ?>
						<div class="wsx-price-classical-quantity">
							<span class="wsx-quantities"><?php echo esc_html( $this->format_quantity_range( $tier ) ); ?></span>
							<span class="wsx-quantity-text"><?php esc_html_e( 'Pieces', 'wholesalex' ); ?></span>
						</div>
						<?php if ( 'layout_three' !== $layout ) : ?>
							<div class="wsx-price-classical-overlay"></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render scoped CSS for a tier table.
	 *
	 * @param array $rule    Rule entry.
	 * @param array $design  Rule design settings.
	 * @param array $columns Enabled columns.
	 * @return void
	 */
	private function render_css( array $rule, array $design, array $columns ): void {
		$column_count = max( 1, count( $columns ) );
		$scope        = '.wsx-wholesale-pricing-rule-' . sanitize_html_class( $rule['id'] );
		$radius       = ! empty( $design['border_radius'] ) ? '8px' : '0';
		$radius_sm    = ! empty( $design['border_radius'] ) ? '2px' : '0';
		$radius_header = ! empty( $design['border_radius'] ) ? '8px 8px 0 0' : '0';
		$radius_vertical_header = ! empty( $design['border_radius'] ) ? '8px 0 0 8px' : '0';
		$font_size    = isset( $design['font_size'] ) ? absint( $design['font_size'] ) : 14;

		?>
		<style>
			<?php echo esc_html( $scope ); ?> .wsx-price-container-title{font-size:20px;font-weight:500;margin-bottom:12px}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-container{width:100%;border:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>;margin-bottom:40px;font-size:<?php echo esc_attr( $font_size ); ?>px;color:<?php echo esc_attr( $design['text_color'] ?? '#494949' ); ?>;border-radius:<?php echo esc_attr( $radius ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-header{width:100%;font-weight:600;border-bottom:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>;color:<?php echo esc_attr( $design['header_text_color'] ?? '#3A3A3A' ); ?>;background-color:<?php echo esc_attr( $design['header_bg_color'] ?? '#F7F7F7' ); ?>;border-radius:<?php echo esc_attr( $radius_header ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-row{width:100%;display:grid;grid-template-columns:repeat(<?php echo esc_attr( $column_count ); ?>,minmax(155px,1fr));border-bottom:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-row.active{color:<?php echo esc_attr( $design['active_row_text_color'] ?? '#FFFFFF' ); ?>;background-color:<?php echo esc_attr( $design['active_row_bg_color'] ?? '#6C6CFF' ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-row:last-child{border-bottom:none}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-cell{box-sizing:border-box;min-width:0;width:100%;padding:12px 16px;text-align:left;border-right:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>;white-space:nowrap}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-cell:last-child{border-right:0}
			<?php echo esc_html( $scope ); ?> .wsx-ellipsis{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:24rem}
			<?php echo esc_html( $scope ); ?> .wsx-price-table-container.layout-vertical{display:flex}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-table-body{display:flex;width:100%}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-table-header{border-right:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>;border-bottom:0;border-radius:<?php echo esc_attr( $radius_vertical_header ); ?>}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-table-row{grid-template-columns:minmax(120px,1fr);border-right:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>;border-bottom:0}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-table-row:last-child{border-right:0}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-table-cell{border-right:0;border-bottom:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-table-cell:last-child{border-bottom:0}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-container{display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:12px;background-color:<?php echo esc_attr( $design['header_bg_color'] ?? '#F7F7F7' ); ?>;border-radius:<?php echo esc_attr( $radius ); ?>;margin-bottom:40px;width:fit-content}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-content{border-right:1px solid <?php echo esc_attr( $design['border_color'] ?? '#E5E5E5' ); ?>;padding-right:20px;position:relative;z-index:1}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-price{display:flex;align-items:center;gap:6px;margin-bottom:8px}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-text{font-weight:500;font-size:<?php echo esc_attr( $font_size ); ?>px;color:<?php echo esc_attr( $design['header_text_color'] ?? '#3A3A3A' ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-tag{padding:0 6px;font-size:<?php echo esc_attr( max( 10, $font_size - 4 ) ); ?>px;color:<?php echo esc_attr( $design['discount_text_color'] ?? '#FFFFFF' ); ?>;background-color:<?php echo esc_attr( $design['discount_bg_color'] ?? '#070707' ); ?>;border-radius:<?php echo esc_attr( $radius_sm ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-overlay{display:none;position:absolute;z-index:-1;content:'';top:-8px;bottom:-8px;left:-8px;right:12px;background-color:<?php echo esc_attr( $design['active_row_bg_color'] ?? '#6C6CFF' ); ?>;border-radius:<?php echo esc_attr( $radius ); ?>}
			<?php echo esc_html( $scope ); ?> .wsx-price-classical-item.active .wsx-price-classical-overlay{display:block}
			<?php echo esc_html( $scope ); ?> .layout-vertical.wsx-price-classical-container{display:block;width:fit-content;padding:20px}
			<?php echo esc_html( $scope ); ?> .layout-vertical .wsx-price-classical-content{border:0;padding-right:0;display:flex;align-items:center;gap:12px}
		</style>
		<?php
	}

	/**
	 * Format a tier quantity range.
	 *
	 * @param array $tier Normalized tier.
	 * @return string
	 */
	private function format_quantity_range( array $tier ): string {
		$min = absint( $tier['_min_quantity'] );
		$max = isset( $tier['_max_quantity'] ) ? absint( $tier['_max_quantity'] ) : 0;

		return $max > 0 ? $min . '-' . $max : $min . '+';
	}

	/**
	 * Format the tier discount column.
	 *
	 * @param float $discount_amount Calculated discount amount.
	 * @return string
	 */
	private function format_discount( float $discount_amount ): string {
		return wc_price( $discount_amount );
	}

	/**
	 * Format price HTML for the main WooCommerce product price node.
	 *
	 * @param \WC_Product $product    Product object.
	 * @param float       $base_price Base price before the tier.
	 * @param float       $sale_price Tier price.
	 * @return string
	 */
	private function format_product_price_html( \WC_Product $product, float $base_price, float $sale_price ): string {
		$sale_html = wc_price( wc_get_price_to_display( $product, array( 'price' => $sale_price ) ) );

		if ( $base_price <= 0 || $sale_price <= 0 || $sale_price >= $base_price ) {
			return $sale_html;
		}

		$base_html = wc_price( wc_get_price_to_display( $product, array( 'price' => $base_price ) ) );

		return '<ins>' . wp_kses_post( $sale_html ) . '</ins> <del aria-hidden="true">' . wp_kses_post( $base_html ) . '</del>';
	}
}
