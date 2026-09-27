<?php
/**
 * WholesaleX Uninstaller
 *
 * @link              https://www.wpxpo.com/
 * @since             1.4.3
 * @package           WholesaleX
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove Plugin Data
 *
 * Only runs if user has enabled the data deletion setting.
 *
 * @since 1.4.3
 * @return void
 */
function wholesalex_uninstall_plugin_data_remove() {
	// Get setting directly from database - no plugin dependencies.
	$settings              = get_option( 'wholesalex_settings', array() );
	$is_plugin_data_delete = isset( $settings['_settings_access_delete_wholesalex_plugin_data'] ) ? $settings['_settings_access_delete_wholesalex_plugin_data'] : '';

	// Only delete data if user explicitly enabled this setting.
	if ( 'yes' !== $is_plugin_data_delete ) {
		return;
	}

	global $wpdb;

	$option_keys = array(
		'wholesalex_settings',
		'wholesalex_onboarding_status',
		'wholesalex_onboarding_completed',
		'wholesalex_onboarding_receive_tips',
		'wholesalex_installation_date',
		'_wholesalex_default_admin_role_assigned',
		'_wholesalex_deleted_default_roles',
		'__wholesalex_customer_import_export_stats',
		'wholesalex_notice',
		'__wholesalex_single_product_settings',
		'__wholesalex_single_product_db_update_v2',
		'__wholesalex_category_settings',
		'__wholesalex_dynamic_rules',
		'__wholesalex_pricing_rules',
		'_wholesalex_roles',
		'__wholesalex_registration_form',
		'wholesalex_registration_form',
		'__wholesalex_email_templates',
		'__wholesalex_initial_setup',
		'woocommerce_wholesalex_new_user_approval_required_settings',
		'woocommerce_wholesalex_new_user_approved_settings',
		'woocommerce_wholesalex_new_user_auto_approve_settings',
		'woocommerce_wholesalex_new_user_email_verified_settings',
		'woocommerce_wholesalex_registration_pending_settings',
		'woocommerce_wholesalex_new_user_registered_settings',
		'woocommerce_wholesalex_registration_rejected_settings',
		'woocommerce_wholesalex_new_user_email_verification_settings',
		'woocommerce_wholesalex_user_profile_update_notify_settings',
	);

	foreach ( $option_keys as $option_key ) {
		delete_option( $option_key );
	}

	$post_meta_keys = array(
		'wholesalex_b2b_stock_status',
		'wholesalex_b2b_stock',
		'wholesalex_b2b_backorders',
		'wholesalex_b2b_separate_stock_status',
		'wholesalex_b2b_variable_stock',
		'wholesalex_b2b_variable_backorders',
		'wholesalex_b2b_variable_separate_stock_status',
	);

	foreach ( $post_meta_keys as $post_meta_key ) {
		delete_metadata( 'post', 0, $post_meta_key, '', true );
	}

	$dynamic_prefix = 'wholesalex_';
	$suffixes       = array( '_base_price', '_sale_price' );
	foreach ( $suffixes as $suffix ) {
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall deletes matching metadata in bulk; the metadata API cannot delete keys by prefix, and caching a DELETE is not applicable.
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s",
				$wpdb->esc_like( $dynamic_prefix ) . '%' . $wpdb->esc_like( $suffix )
			)
		);
	}

	// Delete all keys with prefix 'wholesalex_'.
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall deletes matching metadata in bulk; the metadata API cannot delete keys by prefix, and caching a DELETE is not applicable.
		$wpdb->prepare(
			"DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s",
			$wpdb->esc_like( $dynamic_prefix ) . '%'
		)
	);

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall deletes matching metadata in bulk; the metadata API cannot delete keys by prefix, and caching a DELETE is not applicable.
		$wpdb->prepare(
			"DELETE FROM $wpdb->termmeta WHERE meta_key LIKE %s",
			$wpdb->esc_like( $dynamic_prefix ) . '%'
		)
	);

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall deletes matching metadata in bulk; the metadata API cannot delete keys by prefix, and caching a DELETE is not applicable.
		$wpdb->prepare(
			"DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s",
			$wpdb->esc_like( '__wholesalex_' ) . '%'
		)
	);

	$user_meta_keys = array(
		'wholesalex_onboarding_contact_sent',
		'__wholesalex_status',
		'__wholesalex_account_confirmed',
		'wholesalex_notice',
		'__wholesalex_role',
		'__wholesalex_profile_discounts',
		'__wholesalex_profile_settings',
		'__wholesalex_email_confirmation_code',
	);

	foreach ( $user_meta_keys as $user_meta_key ) {
		delete_metadata( 'user', 0, $user_meta_key, '', true );
	}

	$user_option_keys = array(
		'wholesalex_dynamic_rule_import_mapping',
		'wholesalex_role_import_mapping',
		'wholesalex_role_import_error_log',
	);

	foreach ( $user_option_keys as $user_option_key ) {
		delete_metadata( 'user', 0, $user_option_key, '', true );
	}
}

// Run the uninstall process.
wholesalex_uninstall_plugin_data_remove();
