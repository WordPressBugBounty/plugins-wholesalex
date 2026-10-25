<?php
/**
 * Opaque provider storage ownership, without commercial definitions.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX\Admin;

defined( 'ABSPATH' ) || exit;
/** Own the focused opaque provider storage adapter. */
final class Provider_Settings_Preservation {
	/**
	 * Storage keys retained for absent providers; no defaults, controls or validation.
	 */
	private const OPAQUE_KEYS = array(
		'_settings_show_bxgy_free_products_on_single_product_page',
		'_settings_show_bxgy_free_products_badge',
		'_language_wallet_request_amount_text',
		'_language_wallet_request_submit_text',
		'_language_wallet_request_balance_text',
		'_bulkorder_clear_session_after_save',
		'_bulkorder_clear_cart_after_save',
		'_bulkorder_filter_on_order_form',
		'bulk_order_table_or_form',
		'_bulkorder_my_account_endpoint',
		'_bulkorder_my_account_endpoint_title',
		'_purchase_lists_myaccount_endpoint',
		'_purchase_lists_myaccount_endpoint_title',
		'wsx_addon_bulkorder',
		'_settings_show_conversation_my_account_page',
		'_conversation_my_account_endpoint',
		'_conversation_my_account_endpoint_title',
		'_conversation_admin_emails',
		'_request_quote_admin_emails',
		'_language_conversation_go_back',
		'_language_conversation_x_time_ago',
		'_language_conversation_reply',
		'_language_conversation_error',
		'_language_conversation_heading',
		'_language_new_conversation',
		'_language_conversations_list_title',
		'_language_conversations_list_type',
		'_language_conversations_list_type_inquiry',
		'_language_conversations_list_type_message',
		'_language_conversations_list_type_quote',
		'_language_conversations_last_reply_time',
		'_language_conversations_action',
		'_language_start_conversation',
		'wsx_addon_conversation',
		'_subaccount_my_account_endpoint',
		'_subaccount_my_account_endpoint_title',
		'subaccount_order_approval_need_status_label',
		'_language_subaccount_review_the_order_text',
		'_language_subaccount_approve_order_text',
		'_language_subaccount_reject_order_text',
		'wsx_addon_subaccount',
		'wholesalex_wallet_refund_for_all_gateway',
		'wholesalex_wallet_partial_payment_status',
		'_language_wallet_menu_item_text',
		'_language_wallet_menu_item_endpoint_text',
		'_language_wholesalex_payment_gateway_icon_text',
		'_language_wallet_partial_pay_text',
		'_language_wallet_partial_paid_checkout_text',
		'_language_credit_limit',
		'_language_wholesalex_add_credit',
		'_language_wholesalex_credit_balance',
		'_language_balance',
		'_language_add_balance',
		'_language_transaction_history',
		'_language_topup_amount',
		'_language_add_balance_submit_btn',
		'_language_transaction_table_transaction_id',
		'_language_transaction_table_transction_type',
		'_language_transaction_table_amount',
		'_language_transaction_table_date',
		'_language_transaction_table_details',
		'_language_pagination_label_prev',
		'_language_pagination_label_next',
		'_language_wallet_pending_request_from_subaccount',
		'_language_wallet_balance_request_table_subaccount_name_text',
		'_language_wallet_balance_request_table_requested_credit_text',
		'_language_wallet_balance_request_table_requested_action_text',
		'wsx_addon_wallet',
		'plugin_menu_name',
		'plugin_menu_slug',
		'free_plugin_name',
		'free_plugin_author_name',
		'free_plugin_author_url',
		'free_plugin_description',
		'pro_plugin_name',
		'pro_plugin_author_name',
		'pro_plugin_author_url',
		'pro_plugin_description',
		'plugin_logo',
		'plugin_icon',
		'bulkorder_form_shortcode',
		'wallet_payment_gateway_title',
		'wallet_payment_gateway_description',
		'plugin_wsx_wcfm',
		'plugin_wsx_dokan',
		'wsx_addon_whitelabel',
		'_raq_clear_cart_after_request',
		'_language_hidden_price_request_quote_btn_text',
		'wsx_addon_raq',
	);
	/** Refresh the authoritative option and remove keys the Free form does not own.
	 *
	 * @param array $changes Legacy full-form changes.
	 * @return array Owned changes; omitted opaque values survive unchanged.
	 */
	public static function prepare( array $changes ): array {
		foreach ( array( 'wholesalex_settings', 'alloptions', 'notoptions' ) as $key ) {
			wp_cache_delete( $key, 'options' ); }
		$GLOBALS['wholesalex_settings'] = (array) get_option( 'wholesalex_settings', array() );
		return array_diff_key( $changes, array_fill_keys( self::OPAQUE_KEYS, true ) );
	}
}
