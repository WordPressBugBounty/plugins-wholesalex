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
 * Temporary customer CSV secrets are not retained merchant configuration.
 *
 * @return void
 */
function wholesalex_uninstall_customer_imports() {
	require_once __DIR__ . '/includes/import/class-customer-import-jobs.php';
	require_once __DIR__ . '/includes/import/class-customer-import-cleanup.php';

	$remove = static function () {
		( new \WHOLESALEX\Customer_Import_Jobs() )->cleanup_all();
		\WHOLESALEX\Customer_Import_Cleanup::legacy();
		delete_option( '__wholesalex_customer_import_private_v1' );
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Enumerate only private temporary dynamic-import options.
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( '__wholesalex_dynamic_import_' ) . '%' ) );
		foreach ( $keys as $key ) {
			delete_option( $key );
		}
		wp_clear_scheduled_hook( 'wholesalex_dynamic_import_expire' );
	};
	if ( ! is_multisite() ) {
		$remove();
		return;
	}
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $site_id ) {
		switch_to_blog( (int) $site_id );
		try {
			$remove();
		} finally {
			restore_current_blog();
		}
	}
}

wholesalex_uninstall_customer_imports();

/**
 * Remove Plugin Data
 *
 * Only runs if user has enabled the data deletion setting.
 *
 * @since 1.4.3
 * @param bool $delete_shared Whether all sites permit deleting shared user data.
 * @return void
 */
function wholesalex_uninstall_plugin_data_remove( $delete_shared = true ) {
	// Get setting directly from database - no plugin dependencies.
	$settings              = get_option( 'wholesalex_settings', array() );
	$is_plugin_data_delete = isset( $settings['_settings_access_delete_wholesalex_plugin_data'] ) ? $settings['_settings_access_delete_wholesalex_plugin_data'] : '';

	// Only delete data if user explicitly enabled this setting.
	if ( 'yes' !== $is_plugin_data_delete ) {
		return;
	}

	global $wpdb;

	// Role definitions and capabilities are site-local even on a shared user table.
	$roles = get_option( '_wholesalex_roles', array() );
	foreach ( is_array( $roles ) ? array_keys( $roles ) : array() as $role_id ) {
		if ( ! is_string( $role_id ) || 0 !== strpos( $role_id, 'wholesalex_' ) || in_array( $role_id, array( 'wholesalex_guest', 'wholesalex_b2c_users' ), true ) ) {
			continue;
		}
		foreach ( get_users(
			array(
				'blog_id' => get_current_blog_id(),
				'role'    => $role_id,
			)
		) as $user ) {
			$user->remove_role( $role_id );
			$user->remove_cap( $role_id );
		}
		remove_role( $role_id );
	}
	$page_id = absint( get_option( 'wholesalex_registration_page_id', 0 ) );
	$page    = $page_id ? get_post( $page_id ) : null;
	if ( $page && 'page' === $page->post_type && 'wholesale-register' === $page->post_name ) {
		$blocks = array_values(
			array_filter(
				parse_blocks( $page->post_content ),
				static function ( $block ) {
					return ! empty( $block['blockName'] ) || ! empty( trim( $block['innerHTML'] ?? '' ) );
				}
			)
		);
		// Older generated pages have no ownership marker. Recognize only their exact original content.
		$generated_html = "<div class=\"wp-block-wholesalex-forms wholesalex-form\">[wholesalex_login_registration registration_role='all_b2b']</div>";
		$owned          = 'yes' === get_post_meta( $page_id, '_wholesalex_generated_registration_page', true );
		if ( ! $owned ) {
			$original_content = serialize_block(
				array(
					'blockName'    => 'wholesalex/forms',
					'attrs'        => array(
						'registration_role' => 'all_b2b',
						'tag'               => 'wholesalex_login_registration',
					),
					'innerBlocks'  => array(),
					'innerHTML'    => $generated_html,
					'innerContent' => array( $generated_html ),
				)
			);
			$owned            = $original_content === $page->post_content;
		}
		// Retain merchant-authored pages or additional content; only the generated form is owned.
		if ( $owned && 1 === count( $blocks ) && 'wholesalex/forms' === $blocks[0]['blockName'] ) {
			wp_delete_post( $page_id, true );
		}
	}

	$option_keys = array(
		'wholesalex_settings',
		'wholesalex_registration_page_id',
		'overview_complete_steps',
		'overview_steps_collapsed',
		'wholesalex_onboarding_status',
		'wholesalex_onboarding_completed',
		'wholesalex_onboarding_receive_tips',
		'wholesalex_installation_date',
		'wholesalex_legacy_dynamic_rules_access',
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
		'_custom_page_visibility',
		'_wholesalex_generated_registration_page',
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

	if ( $delete_shared ) {
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
	}

	$user_option_keys = array(
		'wholesalex_dynamic_rule_import_mapping',
		'wholesalex_role_import_mapping',
		'wholesalex_role_import_error_log',
	);

	foreach ( $user_option_keys as $user_option_key ) {
		delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . $user_option_key, '', true );
	}
}

// Shared user metadata is retained whenever any site retains its configuration.
if ( ! is_multisite() ) {
	wholesalex_uninstall_plugin_data_remove();
} else {
	$wholesalex_site_ids      = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	$wholesalex_delete_shared = true;
	foreach ( $wholesalex_site_ids as $wholesalex_site_id ) {
		switch_to_blog( (int) $wholesalex_site_id );
		try {
			$wholesalex_settings = get_option( 'wholesalex_settings', array() );
			if ( 'yes' !== ( $wholesalex_settings['_settings_access_delete_wholesalex_plugin_data'] ?? '' ) ) {
				$wholesalex_delete_shared = false;
			}
		} finally {
			restore_current_blog();
		}
	}
	foreach ( $wholesalex_site_ids as $wholesalex_site_id ) {
		switch_to_blog( (int) $wholesalex_site_id );
		try {
			wholesalex_uninstall_plugin_data_remove( $wholesalex_delete_shared );
		} finally {
			restore_current_blog();
		}
	}
}
