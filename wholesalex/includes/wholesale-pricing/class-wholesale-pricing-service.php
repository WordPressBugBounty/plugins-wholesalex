<?php
/**
 * Wholesale pricing rule write operations shared by every entry point.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Saves, deletes and toggles wholesale pricing rules.
 *
 * The admin REST route and the AI tools both call this class, so every write
 * runs the same ownership checks, input validation, sanitization, provider
 * dispatch and schedule rules. The logic was moved here unchanged from
 * `Wholesale_Pricing_Rest_Api`; the REST route still shapes its responses
 * exactly as before.
 */
final class Wholesale_Pricing_Service {

	/**
	 * Error data key marking validation messages that the REST route reports
	 * as a `success: false` response instead of an HTTP error.
	 *
	 * @var string
	 */
	const SOFT_ERROR = 'wholesalex_soft_error';

	/**
	 * Create or update a rule.
	 *
	 * @param string $id   Rule ID.
	 * @param array  $rule Submitted rule fields in the stored format.
	 * @return array|\WP_Error On success, `rule` holds the saved rule and
	 *                         `delegated` is true when an extension or a
	 *                         title-only edit handled the save.
	 */
	public static function save( string $id, array $rule ) {
		$api      = new Wholesale_Pricing_Rest_Api( false );
		$existing = Wholesale_Pricing::get_rule( $id );
		if ( is_array( $existing ) && ! Wholesale_Pricing::current_manager_owns_rule( $existing ) ) {
			return self::forbidden();
		}

		$provider = Wholesale_Pricing::provider_operation( 'save', $id, $rule, $existing );
		if ( null !== $provider ) {
			return is_wp_error( $provider ) ? $provider : self::saved( $provider, true );
		}
		if ( is_array( $existing ) && Wholesale_Pricing::has_provider_record( $existing ) ) {
			$saved = Wholesale_Pricing::save_rule( $id, array( 'title' => sanitize_text_field( $rule['title'] ?? $existing['title'] ?? '' ) ) );
			return is_wp_error( $saved ) ? $saved : self::saved( $saved, true );
		}
		$ownership = Wholesale_Pricing::validate_provider_write( $rule, $existing );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}
		$input_error = $api->validate_input( $rule, is_array( $existing ) ? $existing : array() );
		if ( is_wp_error( $input_error ) ) {
			return $input_error;
		}
		$candidate   = array_replace( is_array( $existing ) ? $existing : array(), $rule );
		$unavailable = Wholesale_Pricing_Rule_Registry::unavailable_feature( $candidate );
		if ( '' !== $unavailable ) {
			$free_fields     = array( 'id', 'title', 'status', 'regular', 'product_discount', 'cart', 'bogo', 'products', 'categories', 'exclude_products', 'user_role_filter', 'user_roles', 'specific_users', 'schedule' );
			$provider_fields = array_diff_key( $rule, array_flip( $free_fields ) );
			$unchanged       = is_array( $existing );
			foreach ( $provider_fields as $key => $value ) {
				if ( ! is_array( $existing ) || ! array_key_exists( $key, $existing ) || $value !== $existing[ $key ] ) {
					$unchanged = false;
					break;
				}
			}
			if ( ! $unchanged ) {
				return new \WP_Error(
					'wholesalex_provider_unavailable',
					__( 'The provider for this pricing operation is unavailable.', 'wholesalex' ),
					array(
						'status'  => 409,
						'feature' => $unavailable,
					)
				);
			}
		}
		$sanitized = $api->sanitize_rule( $candidate, is_array( $existing ) ? $existing : array() );
		if ( '' !== $unavailable && is_array( $existing ) ) {
			foreach ( array( 'rule_type', 'product_filter', 'discount_type', 'conditions' ) as $key ) {
				if ( array_key_exists( $key, $existing ) && ( ! array_key_exists( $key, $rule ) || $rule[ $key ] === $existing[ $key ] ) ) {
					$sanitized[ $key ] = $existing[ $key ];
				}
			}
		}

		// Only authoritative unknown fields survive a Free save.
		if ( is_array( $existing ) ) {
			$sanitized = array_replace( $existing, $sanitized );
		}

		$context = Wholesale_Pricing::get_manager_context();

		if ( $context['is_vendor'] ) {
			if ( ! self::vendor_rule_type_is_allowed( $sanitized ) ) {
				return self::soft_error( __( 'Vendors may only create wholesale pricing rules.', 'wholesalex' ) );
			}

			$ownership_error = self::validate_vendor_product_ownership( $sanitized, $context['vendor_id'] );
			if ( is_wp_error( $ownership_error ) ) {
				return self::soft_error( $ownership_error->get_error_message() );
			}

			$sanitized['owner_id']   = $context['vendor_id'];
			$sanitized['owner_type'] = $context['owner_type'];
			$sanitized['created_by'] = $context['vendor_id'];
		} elseif ( is_array( $existing ) ) {
			foreach ( array( 'owner_id', 'owner_type', 'created_by' ) as $ownership_key ) {
				if ( isset( $existing[ $ownership_key ] ) ) {
					$sanitized[ $ownership_key ] = $existing[ $ownership_key ];
				}
			}
		} else {
			$sanitized['owner_id']   = 0;
			$sanitized['owner_type'] = 'admin';
			$sanitized['created_by'] = get_current_user_id();
		}

		$schedule_error = $api->validate_schedule( $rule, $sanitized );
		if ( is_wp_error( $schedule_error ) ) {
			return self::soft_error( $schedule_error->get_error_message() );
		}

		if (
			'active' === $sanitized['status'] &&
			'product_discount' === ( $sanitized['rule_type'] ?? '' ) &&
			(
				! is_numeric( $sanitized['product_discount']['amount'] ?? null ) ||
				(float) $sanitized['product_discount']['amount'] <= 0
			)
		) {
			return self::soft_error( __( 'Please enter a product discount greater than zero before saving.', 'wholesalex' ) );
		}

		$saved = Wholesale_Pricing::save_rule( $id, $sanitized );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return self::saved( $saved, false );
	}

	/**
	 * Delete one rule, optionally keeping a recoverable trash copy.
	 *
	 * @param string $id    Rule ID.
	 * @param bool   $trash Whether to archive the rule before deleting it.
	 * @return true|\WP_Error
	 */
	public static function delete( string $id, bool $trash = false ) {
		$rule = Wholesale_Pricing::get_rule( $id );
		if ( ! is_array( $rule ) || ! Wholesale_Pricing::current_manager_owns_rule( $rule ) ) {
			return self::forbidden();
		}

		$check = Wholesale_Pricing::provider_operation( 'validate_delete', $id, array(), $rule );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		if ( $trash ) {
			// Keep a recoverable copy outside the rules used for storefront pricing.
			$trash_key = '__wholesalex_trashed_pricing_rule_' . $id;
			$archived  = array(
				'rule'       => $rule,
				'trashed_at' => time(),
			);
			if ( ! update_option( $trash_key, $archived, false ) && get_option( $trash_key ) !== $archived ) {
				return self::soft_error( __( 'Failed to move pricing rule to trash.', 'wholesalex' ) );
			}
		}

		$deleted = Wholesale_Pricing::delete_rule( $id );
		return is_wp_error( $deleted ) ? $deleted : true;
	}

	/**
	 * Delete several rules after validating the whole operation.
	 *
	 * @param string[] $ids Rule IDs.
	 * @return true|\WP_Error
	 */
	public static function delete_many( array $ids ) {
		foreach ( $ids as $id ) {
			$rule = Wholesale_Pricing::get_rule( $id );
			if ( ! is_array( $rule ) || ! Wholesale_Pricing::current_manager_owns_rule( $rule ) ) {
				return self::forbidden();
			}
			$check = Wholesale_Pricing::provider_operation( 'validate_delete', $id, array(), $rule );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
		}

		foreach ( $ids as $id ) {
			$deleted = Wholesale_Pricing::delete_rule( $id );
			if ( is_wp_error( $deleted ) ) {
				return $deleted;
			}
		}

		return true;
	}

	/**
	 * Activate or deactivate several rules after validating the whole operation.
	 *
	 * @param string[] $ids         Rule IDs.
	 * @param string   $next_status "active" or "inactive".
	 * @return true|\WP_Error
	 */
	public static function set_status( array $ids, string $next_status ) {
		if ( ! in_array( $next_status, array( 'active', 'inactive' ), true ) ) {
			return self::soft_error( __( 'Invalid status value.', 'wholesalex' ) );
		}

		foreach ( $ids as $id ) {
			$rule = Wholesale_Pricing::get_rule( $id );
			if ( ! is_array( $rule ) || ! Wholesale_Pricing::current_manager_owns_rule( $rule ) ) {
				return self::forbidden();
			}
			$check = Wholesale_Pricing::provider_operation( 'validate_status', $id, array(), $rule );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
		}

		foreach ( $ids as $id ) {
			$rule = Wholesale_Pricing::get_rule( $id );
			if ( is_array( $rule ) && Wholesale_Pricing::current_manager_owns_rule( $rule ) ) {
				$rule['status'] = $next_status;
				$saved          = Wholesale_Pricing::save_rule( $id, $rule );
				if ( is_wp_error( $saved ) ) {
					return $saved;
				}
			}
		}

		return true;
	}

	/**
	 * Whether an error is a validation message the REST route reports softly.
	 *
	 * @param mixed $error Value to test.
	 * @return bool
	 */
	public static function is_soft_error( $error ): bool {
		if ( ! is_wp_error( $error ) ) {
			return false;
		}
		$data = $error->get_error_data();
		return is_array( $data ) && ! empty( $data[ self::SOFT_ERROR ] );
	}

	/**
	 * Build a successful save result.
	 *
	 * @param array $rule      Saved rule.
	 * @param bool  $delegated Whether an extension or a title-only edit handled the save.
	 * @return array
	 */
	private static function saved( $rule, bool $delegated ): array {
		return array(
			'rule'      => $rule,
			'delegated' => $delegated,
		);
	}

	/**
	 * Build a validation error the REST route reports as `success: false`.
	 *
	 * @param string $message Message.
	 * @return \WP_Error
	 */
	private static function soft_error( string $message ): \WP_Error {
		return new \WP_Error(
			'wholesalex_invalid_pricing_rule',
			$message,
			array(
				'status'         => 400,
				self::SOFT_ERROR => true,
			)
		);
	}

	/**
	 * Return the error used when a rule is outside the current manager scope.
	 *
	 * @return \WP_Error
	 */
	private static function forbidden(): \WP_Error {
		return new \WP_Error(
			'wholesalex_forbidden_rule',
			__( 'You do not have permission to manage this pricing rule.', 'wholesalex' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Vendors only receive the product-level wholesale pricing rule builder.
	 *
	 * Cart-wide campaigns and free-product campaigns can affect products owned
	 * by other sellers, so they remain administrator-only.
	 *
	 * @param array $rule Sanitized rule.
	 * @return bool
	 */
	private static function vendor_rule_type_is_allowed( array $rule ): bool {
		$allowed_types = apply_filters(
			'wholesalex_vendor_wholesale_pricing_rule_types',
			array( 'wholesale_pricing' )
		);

		return in_array( $rule['rule_type'] ?? '', $allowed_types, true );
	}

	/**
	 * Ensure explicitly selected products belong to the current vendor.
	 *
	 * @param array $rule      Sanitized rule.
	 * @param int   $vendor_id Vendor user ID.
	 * @return true|\WP_Error
	 */
	private static function validate_vendor_product_ownership( array $rule, int $vendor_id ) {
		$product_fields = array( 'products', 'exclude_products' );

		$product_fields = apply_filters( 'wholesalex_pricing_vendor_product_fields', $product_fields, $rule );

		foreach ( $product_fields as $field ) {
			$items = is_array( $field )
				? ( $rule[ $field[0] ][ $field[1] ] ?? array() )
				: ( $rule[ $field ] ?? array() );

			foreach ( $items as $item ) {
				$product_id = absint( is_array( $item ) ? ( $item['value'] ?? 0 ) : $item );
				if ( ! $product_id ) {
					continue;
				}

				$parent_id        = wp_get_post_parent_id( $product_id );
				$owner_product_id = $parent_id ? $parent_id : $product_id;
				if ( absint( get_post_field( 'post_author', $owner_product_id ) ) !== $vendor_id ) {
					return new \WP_Error(
						'wholesalex_invalid_vendor_product',
						__( 'A selected product does not belong to your store.', 'wholesalex' )
					);
				}
			}
		}

		return true;
	}
}
