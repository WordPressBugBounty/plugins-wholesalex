<?php
/**
 * WholesaleX Wholesale Pricing – REST API
 *
 * Registers all REST routes used by the React WholesalePricingContext and
 * dispatches each request to the appropriate handler.
 *
 * Routes
 * ------
 *   POST /wholesalex/v1/wholesale_pricing_action
 *       action = wholesale_pricing_action  (type=get) → list all rules
 *       action = save_wholesale_pricing_rule           → create / update
 *       action = delete_wholesale_pricing_rule         → delete single
 *       action = bulk_delete_wholesale_pricing_rules   → bulk delete
 *       action = bulk_set_wholesale_pricing_status     → bulk enable/disable
 *
 *   GET  /wholesalex/v1/wholesale_pricing/{id}         → fetch single rule
 *
 * Security
 * --------
 *   • Permission callback: administrators, or a marketplace vendor explicitly
 *     authorized by a WholesaleX integration adapter.
 *   • All incoming data is sanitised before being passed to the storage layer.
 *   • Enum fields are validated against an allowlist.
 *   • IDs are forced through `sanitize_text_field`.
 *   • Numeric quantities use `absint`.
 *   • Colours use `sanitize_hex_color`.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wholesale_Pricing_Rest_Api
 */
class Wholesale_Pricing_Rest_Api
{

    // ── Constructor ───────────────────────────────────────────────────────────

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    // ── Route registration ────────────────────────────────────────────────────

    /**
     * Register REST routes.
     */
    public function register_routes(): void
    {

        // Main CRUD endpoint (list, save, delete, bulk ops).
        register_rest_route(
            'wholesalex/v1',
            '/wholesale_pricing_action',
            array(
                'methods' => \WP_REST_Server::CREATABLE, // POST
                'callback' => array($this, 'handle_action'),
                'permission_callback' => array($this, 'permission_check'),
            )
        );

        // Fetch a single rule by ID.
        register_rest_route(
            'wholesalex/v1',
            '/wholesale_pricing/(?P<id>[\w-]+)',
            array(
                'methods' => \WP_REST_Server::READABLE, // GET
                'callback' => array($this, 'get_single_rule'),
                'permission_callback' => array($this, 'permission_check'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Product and customer data used by the live rule-builder preview.
        register_rest_route(
            'wholesalex/v1',
            '/wholesale_pricing_preview',
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'get_builder_preview_data'),
                'permission_callback' => array($this, 'permission_check'),
            )
        );
    }

    /**
     * Return safely scoped data for the builder preview.
     *
     * Product searches are constrained on the server so targeted rules can
     * never leak unrelated results into the preview picker.
     *
     * @param \WP_REST_Request $request REST request.
     * @return \WP_REST_Response
     */
    public function get_builder_preview_data(\WP_REST_Request $request)
    {
        $resource = sanitize_key((string) $request->get_param('resource'));
        $search = sanitize_text_field((string) $request->get_param('search'));

        if ('users' === $resource) {
            $user_ids = $this->normalize_preview_ids($request->get_param('user_ids'));
            return rest_ensure_response(
                array(
                    'success' => true,
                    'data' => $this->get_preview_users($search, $user_ids),
                )
            );
        }

        $filter = sanitize_key((string) $request->get_param('product_filter'));
        $allowed_filters = array('all_products', 'specific_products', 'specific_categories', 'brands', 'attributes', 'sku');
        if (!in_array($filter, $allowed_filters, true)) {
            $filter = 'all_products';
        }

        $product_ids = $this->normalize_preview_ids($request->get_param('product_ids'));
        $category_ids = $this->normalize_preview_ids($request->get_param('category_ids'));
        $brand_ids = $this->normalize_preview_ids($request->get_param('brand_ids'));
        $attribute_ids = $this->normalize_preview_ids($request->get_param('attribute_ids'));
        $skus = $this->normalize_preview_strings($request->get_param('skus'), 'sku:');
        $exclude_ids = $this->normalize_preview_ids($request->get_param('exclude_ids'));
        $role_id = sanitize_key((string) $request->get_param('role_id'));

        return rest_ensure_response(
            array(
                'success' => true,
                'data' => $this->get_preview_products(
                    $filter,
                    $search,
                    $product_ids,
                    $category_ids,
                    $brand_ids,
                    $attribute_ids,
                    $skus,
                    $exclude_ids,
                    $role_id
                ),
            )
        );
    }

    /**
     * Search users and include their effective WholesaleX role.
     *
     * @param string     $search   Search term.
     * @param array<int> $user_ids Preferred initial users.
     * @return array<int, array<string, mixed>>
     */
    private function get_preview_users(string $search, array $user_ids = array()): array
    {
        $args = array(
            'number' => 20,
            'orderby' => 'display_name',
            'order' => 'ASC',
        );

        if ('' !== $search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        } elseif (!empty($user_ids)) {
            $args['include'] = $user_ids;
            $args['orderby'] = 'include';
        }

        $users = get_users($args);

        return array_map(
            static function ($user) {
                $role_id = wholesalex()->get_user_role($user->ID);
                $role = wholesalex()->get_roles('by_id', $role_id);

                return array(
                    'value' => (int) $user->ID,
                    'name' => $user->display_name . ' (' . $user->user_email . ')',
                    'role_id' => sanitize_text_field((string) $role_id),
                    'role_name' => isset($role['_role_title'])
                        ? sanitize_text_field($role['_role_title'])
                        : '',
                );
            },
            $users
        );
    }

    /**
     * Get products for a preview while retaining the configured target order.
     *
     * @param string     $filter       Product filter.
     * @param string     $search       Search term.
     * @param array<int> $product_ids  Selected product IDs.
     * @param array<int> $category_ids Selected category IDs.
     * @param array<int> $brand_ids    Selected brand term IDs.
     * @param array<int> $attribute_ids Selected attribute taxonomy IDs.
     * @param array<string> $skus      Selected product SKUs.
     * @param array<int> $exclude_ids  Excluded product IDs.
     * @param string     $role_id      Preview customer role.
     * @return array<int, array<string, mixed>>
     */
    private function get_preview_products(
        string $filter,
        string $search,
        array $product_ids,
        array $category_ids,
        array $brand_ids,
        array $attribute_ids,
        array $skus,
        array $exclude_ids,
        string $role_id
    ): array {
        if ('specific_products' === $filter && empty($product_ids)) {
            return array();
        }

        if ('specific_categories' === $filter && empty($category_ids)) {
            return array();
        }

        if ('brands' === $filter && empty($brand_ids)) {
            return array();
        }

        if ('attributes' === $filter && empty($attribute_ids)) {
            return array();
        }

        if ('sku' === $filter && empty($skus)) {
            return array();
        }

        $base_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'fields' => 'ids',
            'no_found_rows' => true,
        );

        if ('' !== $search) {
            $base_args['s'] = $search;
        }
        if (!empty($exclude_ids)) {
            $base_args['post__not_in'] = $exclude_ids;
        }

        $ids = array();
        if ('specific_products' === $filter) {
            $base_args['post__in'] = $product_ids;
            $base_args['orderby'] = 'post__in';
            $ids = (new \WP_Query($base_args))->posts;
        } elseif ('specific_categories' === $filter) {
            // Query each category in selection order. This makes the initial
            // product come from the first selected category, as the UI promises.
            foreach ($category_ids as $category_id) {
                $args = $base_args;
                $args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => array($category_id),
                    ),
                );
                foreach ((new \WP_Query($args))->posts as $product_id) {
                    if (!in_array((int) $product_id, $ids, true)) {
                        $ids[] = (int) $product_id;
                    }
                    if (count($ids) >= 20) {
                        break 2;
                    }
                }
            }
        } elseif ('brands' === $filter) {
            $supported_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand');
            foreach ($brand_ids as $brand_id) {
                $term = get_term($brand_id);
                if (!$term instanceof \WP_Term || !in_array($term->taxonomy, $supported_taxonomies, true)) {
                    continue;
                }

                $args = $base_args;
                $args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    array(
                        'taxonomy' => $term->taxonomy,
                        'field' => 'term_id',
                        'terms' => array($brand_id),
                    ),
                );
                $this->append_preview_product_ids($ids, (new \WP_Query($args))->posts);
                if (count($ids) >= 20) {
                    break;
                }
            }
        } elseif ('attributes' === $filter) {
            $attribute_taxonomies = array();
            foreach (wc_get_attribute_taxonomies() as $attribute) {
                $attribute_taxonomies[absint($attribute->attribute_id)] = wc_attribute_taxonomy_name($attribute->attribute_name);
            }

            foreach ($attribute_ids as $attribute_id) {
                $taxonomy = $attribute_taxonomies[$attribute_id] ?? '';
                if (!$taxonomy || !taxonomy_exists($taxonomy)) {
                    continue;
                }

                $args = $base_args;
                $args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    array(
                        'taxonomy' => $taxonomy,
                        'operator' => 'EXISTS',
                    ),
                );
                $this->append_preview_product_ids($ids, (new \WP_Query($args))->posts);
                if (count($ids) >= 20) {
                    break;
                }
            }
        } elseif ('sku' === $filter) {
            foreach ($skus as $sku) {
                $product_id = wc_get_product_id_by_sku($sku);
                $product = $product_id ? wc_get_product($product_id) : false;
                if (!$product) {
                    continue;
                }

                $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
                if ($parent_id && !in_array($parent_id, $exclude_ids, true)) {
                    $ids[] = $parent_id;
                }
            }

            $ids = array_values(array_unique(array_map('absint', $ids)));
            if ('' !== $search && !empty($ids)) {
                $args = $base_args;
                $args['post__in'] = $ids;
                $args['orderby'] = 'post__in';
                $ids = (new \WP_Query($args))->posts;
            }
        } else {
            $base_args['orderby'] = array('menu_order' => 'ASC', 'date' => 'DESC');
            $ids = (new \WP_Query($base_args))->posts;
        }

        $products = array();
        foreach (array_slice($ids, 0, 20) as $product_id) {
            $product = $this->format_preview_product($product_id, $role_id);
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * Append unique product IDs without exceeding the preview result limit.
     *
     * @param array<int> $target Product IDs collected so far.
     * @param array<int> $source Product IDs returned by a scoped query.
     * @return void
     */
    private function append_preview_product_ids(array &$target, array $source): void
    {
        foreach ($source as $product_id) {
            $product_id = absint($product_id);
            if ($product_id && !in_array($product_id, $target, true)) {
                $target[] = $product_id;
            }
            if (count($target) >= 20) {
                return;
            }
        }
    }

    /**
     * Format one WooCommerce product for the preview UI.
     *
     * @param int    $product_id Product ID.
     * @param string $role_id    Preview customer role.
     * @return array<string, mixed>|null
     */
    private function format_preview_product($product_id, string $role_id = ''): ?array
    {
        $product = wc_get_product(absint($product_id));
        if (!$product) {
            return null;
        }

        $regular_price = $product->is_type('variable')
            ? $product->get_variation_regular_price('min', false)
            : $product->get_regular_price('edit');
        $price = $product->is_type('variable')
            ? $product->get_variation_price('min', false)
            : $product->get_price('edit');
        $role_regular = $role_id ? get_post_meta($product->get_id(), $role_id . '_base_price', true) : '';
        $role_sale = $role_id ? get_post_meta($product->get_id(), $role_id . '_sale_price', true) : '';
        $price_source = wholesalex()->get_setting('_is_sale_or_regular_Price', 'is_regular_price');
        if ('is_sale_price' === $price_source) {
            $base_price = (float) $role_sale > 0
                ? (float) $role_sale
                : ((float) $role_regular > 0 ? (float) $role_regular : (float) $price);
        } else {
            $base_price = (float) $role_regular > 0 ? (float) $role_regular : (float) $regular_price;
        }
        $description = $product->get_short_description();
        if ('' === trim(wp_strip_all_tags($description))) {
            $description = $product->get_description();
        }

        $image_id = $product->get_image_id();
        $image = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_single') : '';
        $gallery_images = array();
        foreach ($product->get_gallery_image_ids() as $gallery_image_id) {
            $gallery_image = wp_get_attachment_image_url($gallery_image_id, 'woocommerce_single');
            if (!$gallery_image) {
                continue;
            }
            $gallery_thumbnail = wp_get_attachment_image_url($gallery_image_id, 'woocommerce_thumbnail');
            $gallery_images[] = array(
                'image' => esc_url_raw($gallery_image),
                'thumbnail' => esc_url_raw($gallery_thumbnail ? $gallery_thumbnail : $gallery_image),
            );
        }

        return array(
            'value' => $product->get_id(),
            'name' => $product->get_name(),
            'description' => wp_trim_words(wp_strip_all_tags($description), 24, '&hellip;'),
            'regular_price' => '' === $regular_price ? null : (float) $regular_price,
            'price' => '' === $price ? null : (float) $price,
            'role_regular_price' => '' === $role_regular ? null : (float) $role_regular,
            'role_sale_price' => '' === $role_sale ? null : (float) $role_sale,
            'base_price' => $base_price,
            'image' => $image ? esc_url_raw($image) : esc_url_raw(wc_placeholder_img_src('woocommerce_thumbnail')),
            'image_thumbnail' => $image_id
                ? esc_url_raw(wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail'))
                : esc_url_raw(wc_placeholder_img_src('woocommerce_thumbnail')),
            'gallery_images' => $gallery_images,
        );
    }

    /**
     * Normalize IDs received as scalars or select-option objects.
     *
     * @param mixed $items Raw IDs.
     * @return array<int>
     */
    private function normalize_preview_ids($items): array
    {
        if (!is_array($items)) {
            return array();
        }

        $ids = array();
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['value'] ?? $item['id'] ?? 0;
            }
            $id = absint($item);
            if ($id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Normalize string select values received as scalars or option objects.
     *
     * @param mixed  $items  Raw values.
     * @param string $prefix Optional stored-value prefix to remove.
     * @return array<string>
     */
    private function normalize_preview_strings($items, string $prefix = ''): array
    {
        if (!is_array($items)) {
            return array();
        }

        $values = array();
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['value'] ?? $item['id'] ?? '';
            }
            $value = sanitize_text_field((string) $item);
            if ('' !== $prefix && 0 === strpos($value, $prefix)) {
                $value = substr($value, strlen($prefix));
            }
            $value = trim($value);
            if ('' !== $value) {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    // ── Permission ────────────────────────────────────────────────────────────

    /**
     * Administrators and authorized marketplace vendors may manage rules.
     *
     * @return bool|\WP_Error
     */
    public function permission_check()
    {
        $context = Wholesale_Pricing::get_manager_context();

        if (!$context['can_manage']) {
            return new \WP_Error(
                'wholesalex_forbidden',
                __('You do not have permission to manage wholesale pricing rules.', 'wholesalex'),
                array('status' => 403)
            );
        }
        return true;
    }

    // ── Main dispatcher

    /**
     * Dispatch POST requests to the appropriate handler.
     *
     * @param \WP_REST_Request $request Full REST request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_action(\WP_REST_Request $request)
    {
        $params = $request->get_params();
        $action = isset($params['action']) ? sanitize_text_field($params['action']) : '';
        $type = isset($params['type']) ? sanitize_text_field($params['type']) : '';

        // List all rules.
        if ('get' === $type) {
            return rest_ensure_response(
                array(
                    'success' => true,
                    'data' => Wholesale_Pricing::get_rules_for_current_manager(),
                )
            );
        }

        switch ($action) {
            case 'save_wholesale_pricing_rule':
                return $this->handle_save($params);

            case 'delete_wholesale_pricing_rule':
                return $this->handle_delete($params);

            case 'bulk_delete_wholesale_pricing_rules':
                return $this->handle_bulk_delete($params);

            case 'bulk_set_wholesale_pricing_status':
                return $this->handle_bulk_set_status($params);

            default:
                return rest_ensure_response(
                    array(
                        'success' => false,
                        'message' => __('Unknown action.', 'wholesalex'),
                    )
                );
        }
    }

    // ── GET single rule ───────────────────────────────────────────────────────

    /**
     * Return a single rule by its ID.
     *
     * @param \WP_REST_Request $request REST request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_single_rule(\WP_REST_Request $request)
    {
        $id = sanitize_text_field($request->get_param('id'));
        $rule = Wholesale_Pricing::get_rule($id);

        if (null === $rule || !Wholesale_Pricing::current_manager_owns_rule($rule)) {
            return new \WP_Error(
                'wholesalex_not_found',
                __('Wholesale pricing rule not found.', 'wholesalex'),
                array('status' => 404)
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data' => $rule,
            )
        );
    }

    // ── Action handlers ───────────────────────────────────────────────────────

    /**
     * Save (create or update) a rule.
     *
     * @param array $params Raw request params.
     * @return \WP_REST_Response
     */
    private function handle_save(array $params)
    {
        $id = isset($params['id']) ? sanitize_text_field($params['id']) : '';
        $rule_json = isset($params['rule']) ? wp_unslash($params['rule']) : '';

        if (empty($id)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Missing rule ID.', 'wholesalex'),
                )
            );
        }

        if (empty($rule_json)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Missing rule data.', 'wholesalex'),
                )
            );
        }

        $rule = json_decode($rule_json, true);

        if (!is_array($rule)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Invalid rule JSON.', 'wholesalex'),
                )
            );
        }

        $existing = Wholesale_Pricing::get_rule($id);
        if (is_array($existing) && !Wholesale_Pricing::current_manager_owns_rule($existing)) {
            return $this->forbidden_rule_response();
        }

        $sanitized = $this->sanitize_rule($rule);
        $context = Wholesale_Pricing::get_manager_context();

        if ($context['is_vendor']) {
            if (!$this->vendor_rule_type_is_allowed($sanitized)) {
                return rest_ensure_response(
                    array(
                        'success' => false,
                        'message' => __('Vendors may only create wholesale pricing rules.', 'wholesalex'),
                    )
                );
            }

            $ownership_error = $this->validate_vendor_product_ownership($sanitized, $context['vendor_id']);
            if (is_wp_error($ownership_error)) {
                return rest_ensure_response(
                    array(
                        'success' => false,
                        'message' => $ownership_error->get_error_message(),
                    )
                );
            }

            $sanitized['owner_id'] = $context['vendor_id'];
            $sanitized['owner_type'] = $context['owner_type'];
            $sanitized['created_by'] = $context['vendor_id'];
        } elseif (is_array($existing)) {
            foreach (array('owner_id', 'owner_type', 'created_by') as $ownership_key) {
                if (isset($existing[$ownership_key])) {
                    $sanitized[$ownership_key] = $existing[$ownership_key];
                }
            }
        } else {
            $sanitized['owner_id'] = 0;
            $sanitized['owner_type'] = 'admin';
            $sanitized['created_by'] = get_current_user_id();
        }

        $schedule_error = $this->validate_schedule($rule, $sanitized);
        if (is_wp_error($schedule_error)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $schedule_error->get_error_message(),
                )
            );
        }

        if (
            'product_discount' === ($sanitized['rule_type'] ?? '') &&
            (
                !is_numeric($sanitized['product_discount']['amount'] ?? null) ||
                (float) $sanitized['product_discount']['amount'] <= 0
            )
        ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Please enter a product discount greater than zero before saving.', 'wholesalex'),
                )
            );
        }

        if (!$this->can_save_rule_for_current_license($sanitized)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Please activate WholesaleX Pro to save this active Pro wholesale pricing rule.', 'wholesalex'),
                )
            );
        }

        $saved = Wholesale_Pricing::save_rule($id, $sanitized);

        return rest_ensure_response(
            array(
                'success' => true,
                'data' => $saved,
                'message' => __('Rule saved successfully.', 'wholesalex'),
            )
        );
    }

    /**
     * Delete a single rule.
     *
     * @param array $params Raw request params.
     * @return \WP_REST_Response
     */
    private function handle_delete(array $params)
    {
        $id = isset($params['id']) ? sanitize_text_field($params['id']) : '';

        if (empty($id)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Missing rule ID.', 'wholesalex'),
                )
            );
        }

        $rule = Wholesale_Pricing::get_rule($id);
        if (!is_array($rule) || !Wholesale_Pricing::current_manager_owns_rule($rule)) {
            return $this->forbidden_rule_response();
        }

        Wholesale_Pricing::delete_rule($id);

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __('Rule deleted.', 'wholesalex'),
            )
        );
    }

    /**
     * Delete multiple rules in one request.
     *
     * @param array $params Raw request params.
     * @return \WP_REST_Response
     */
    private function handle_bulk_delete(array $params)
    {
        $ids = $this->decode_ids_param($params);

        if (is_wp_error($ids)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $ids->get_error_message(),
                )
            );
        }

        foreach ($ids as $id) {
            $rule = Wholesale_Pricing::get_rule($id);
            if (!is_array($rule) || !Wholesale_Pricing::current_manager_owns_rule($rule)) {
                return $this->forbidden_rule_response();
            }
        }

        foreach ($ids as $id) {
            Wholesale_Pricing::delete_rule($id);
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __('Rules deleted.', 'wholesalex'),
            )
        );
    }

    /**
     * Enable or disable multiple rules at once.
     *
     * @param array $params Raw request params.
     * @return \WP_REST_Response
     */
    private function handle_bulk_set_status(array $params)
    {
        $ids = $this->decode_ids_param($params);
        $next_status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

        if (is_wp_error($ids)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $ids->get_error_message(),
                )
            );
        }

        if (!in_array($next_status, array('active', 'inactive'), true)) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __('Invalid status value.', 'wholesalex'),
                )
            );
        }

        foreach ($ids as $id) {
            $rule = Wholesale_Pricing::get_rule($id);
            if (!is_array($rule) || !Wholesale_Pricing::current_manager_owns_rule($rule)) {
                return $this->forbidden_rule_response();
            }
        }

        if ('active' === $next_status && !$this->is_pro_active()) {
            foreach ($ids as $id) {
                $rule = Wholesale_Pricing::get_rule($id);
                if (
                    is_array($rule) &&
                    Wholesale_Pricing::current_manager_owns_rule($rule) &&
                    $this->is_pro_rule($rule)
                ) {
                    return rest_ensure_response(
                        array(
                            'success' => false,
                            'message' => __('Please activate WholesaleX Pro to activate this Pro wholesale pricing rule.', 'wholesalex'),
                        )
                    );
                }
            }
        }

        foreach ($ids as $id) {
            $rule = Wholesale_Pricing::get_rule($id);
            if (is_array($rule) && Wholesale_Pricing::current_manager_owns_rule($rule)) {
                $rule['status'] = $next_status;
                Wholesale_Pricing::save_rule($id, $rule);
            }
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __('Status updated.', 'wholesalex'),
            )
        );
    }

    /**
     * Check whether the current license allows the supplied rule to be saved.
     *
     * Pro-only rules may remain stored as drafts/inactive records, but they
     * cannot be saved as active campaigns without a valid Pro license.
     *
     * @param array $rule Sanitised rule.
     * @return bool
     */
    private function can_save_rule_for_current_license(array $rule): bool
    {
        if ($this->is_pro_active() || 'active' !== ($rule['status'] ?? '')) {
            return true;
        }

        return !$this->is_pro_rule($rule);
    }

    /**
     * Determine whether a saved rule uses Pro-only wholesale-pricing features.
     *
     * @param array $rule Rule data.
     * @return bool
     */
    private function is_pro_rule(array $rule): bool
    {
        if (in_array($rule['product_filter'] ?? '', array('brands', 'attributes', 'sku'), true)) {
            return true;
        }

        if ('buy_x_get_y' === ($rule['rule_type'] ?? '')) {
            return true;
        }

        if ('wholesale_pricing' === ($rule['rule_type'] ?? '') && 'tiered' === ($rule['discount_type'] ?? '')) {
            return true;
        }

        $restrictions = isset($rule['restrictions']) && is_array($rule['restrictions']) ? $rule['restrictions'] : array();

        return (
            'wholesale_pricing' === ($rule['rule_type'] ?? '') &&
            (
                !empty($restrictions['enable_quantity_limits']) ||
                !empty($restrictions['enable_value_limits']) ||
                !empty($restrictions['enable_quantity_step']) ||
                !empty($restrictions['tiered_combined_variations'])
            )
        );
    }

    /**
     * Check whether WholesaleX Pro is active with a valid license.
     *
     * @return bool
     */
    private function is_pro_active(): bool
    {
        return method_exists(wholesalex(), 'is_pro_active') && wholesalex()->is_pro_active();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Decode the `ids` JSON param used by bulk operations.
     *
     * @param array $params Raw request params.
     * @return string[]|\WP_Error Array of sanitised ID strings, or WP_Error.
     */
    private function decode_ids_param(array $params)
    {
        $ids_raw = isset($params['ids']) ? wp_unslash($params['ids']) : '[]';
        $ids = json_decode($ids_raw, true);

        if (!is_array($ids)) {
            return new \WP_Error('invalid_ids', __('Invalid IDs parameter.', 'wholesalex'));
        }

        return array_map('sanitize_text_field', $ids);
    }

    /**
     * Return a consistent response when a rule is outside the current manager scope.
     *
     * @return \WP_REST_Response
     */
    private function forbidden_rule_response()
    {
        return new \WP_Error(
            'wholesalex_forbidden_rule',
            __('You do not have permission to manage this pricing rule.', 'wholesalex'),
            array('status' => 403)
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
    private function vendor_rule_type_is_allowed(array $rule): bool
    {
        $allowed_types = apply_filters(
            'wholesalex_vendor_wholesale_pricing_rule_types',
            array('wholesale_pricing')
        );

        return in_array($rule['rule_type'] ?? '', $allowed_types, true);
    }

    /**
     * Ensure explicitly selected products belong to the current vendor.
     *
     * @param array $rule      Sanitized rule.
     * @param int   $vendor_id Vendor user ID.
     * @return true|\WP_Error
     */
    private function validate_vendor_product_ownership(array $rule, int $vendor_id)
    {
        $product_fields = array('products', 'exclude_products');

        if (isset($rule['bxgy']['free_products'])) {
            $product_fields[] = array('bxgy', 'free_products');
        }

        foreach ($product_fields as $field) {
            $items = is_array($field)
                ? ($rule[$field[0]][$field[1]] ?? array())
                : ($rule[$field] ?? array());

            foreach ($items as $item) {
                $product_id = absint(is_array($item) ? ($item['value'] ?? 0) : $item);
                if (!$product_id) {
                    continue;
                }

                $parent_id = wp_get_post_parent_id($product_id);
                $owner_product_id = $parent_id ? $parent_id : $product_id;
                if ($vendor_id !== absint(get_post_field('post_author', $owner_product_id))) {
                    return new \WP_Error(
                        'wholesalex_invalid_vendor_product',
                        __('A selected product does not belong to your store.', 'wholesalex')
                    );
                }
            }
        }

        return true;
    }

    // ── Sanitisation ──────────────────────────────────────────────────────────

    /**
     * Deeply sanitise an incoming rule array.
     *
     * Every field is validated/cast so only clean data ever reaches wp_options.
     *
     * @param array $rule Raw rule from the JS client.
     * @return array Sanitised rule.
     */
    private function sanitize_rule(array $rule): array
    {
        $out = array();

        // ── Shared scalars ─────────────────────────────────────────
        $out['id'] = isset($rule['id']) ? sanitize_text_field($rule['id']) : '';
        $out['title'] = isset($rule['title']) ? sanitize_text_field($rule['title']) : '';
        $out['status'] = in_array($rule['status'] ?? '', array('active', 'inactive'), true)
            ? $rule['status']
            : 'inactive';

        $allowed_rule_types = array('wholesale_pricing', 'product_discount', 'cart_discount', 'bogo_discount', 'buy_x_get_y');
        $out['rule_type'] = in_array($rule['rule_type'] ?? '', $allowed_rule_types, true)
            ? $rule['rule_type']
            : 'wholesale_pricing';

        // ── Shared targeting ───────────────────────────────────────
        $allowed_product_filters = array('all_products', 'specific_products', 'specific_categories', 'brands', 'attributes', 'sku');
        $out['product_filter'] = in_array($rule['product_filter'] ?? '', $allowed_product_filters, true)
            ? $rule['product_filter']
            : 'all_products';
        // Products/categories/brands/attributes/SKUs come from the MultiSelect AJAX
        // component as {name, value} objects. We must preserve that shape so the
        // component can re-render the labels correctly on reload.
        $out['products'] = isset($rule['products']) ? $this->sanitize_select_items($rule['products'], 'int') : array();
        $out['categories'] = isset($rule['categories']) ? $this->sanitize_select_items($rule['categories'], 'int') : array();
        $out['brands'] = isset($rule['brands']) ? $this->sanitize_select_items($rule['brands'], 'int') : array();
        $out['attributes'] = isset($rule['attributes']) ? $this->sanitize_select_items($rule['attributes'], 'string') : array();
        $out['skus'] = isset($rule['skus']) ? $this->sanitize_select_items($rule['skus'], 'string') : array();
        $out['exclude_products'] = isset($rule['exclude_products']) ? $this->sanitize_select_items($rule['exclude_products'], 'int') : array();

        $allowed_user_filters = array('all', 'all_users', 'all_b2b', 'specific_users', 'specific_roles');
        $out['user_role_filter'] = in_array($rule['user_role_filter'] ?? '', $allowed_user_filters, true)
            ? $rule['user_role_filter']
            : 'all_b2b';
        // user_roles: {name, value} where value is a role slug (string).
        $out['user_roles'] = isset($rule['user_roles'])
            ? $this->sanitize_select_items($rule['user_roles'], 'string')
            : array();
        // specific_users: {name, value} where value is a user ID. Keep the
        // value as a string to support both current IDs and legacy user_{ID} values.
        $out['specific_users'] = isset($rule['specific_users'])
            ? $this->sanitize_select_items($rule['specific_users'], 'string')
            : array();

        // ── Wholesale pricing restrictions ─────────────────────────
        // Cart discount has no restriction panel. BOGO/BxGy restriction flags
        // live in their own type-specific objects.
        $r = (
            'wholesale_pricing' === $out['rule_type'] &&
            is_array($rule['restrictions'] ?? null)
        ) ? $rule['restrictions'] : array();
        $out['restrictions'] = array(
            'enable_quantity_limits' => !empty($r['enable_quantity_limits']),
            'min_quantity' => isset($r['min_quantity']) ? sanitize_text_field($r['min_quantity']) : '',
            'max_quantity' => isset($r['max_quantity']) ? sanitize_text_field($r['max_quantity']) : '',
            'min_quantity_message' => isset($r['min_quantity_message']) ? sanitize_text_field($r['min_quantity_message']) : '',
            'max_quantity_message' => isset($r['max_quantity_message']) ? sanitize_text_field($r['max_quantity_message']) : '',
            'enable_quantity_step' => !empty($r['enable_quantity_step']),
            'quantity_step' => isset($r['quantity_step']) ? sanitize_text_field($r['quantity_step']) : '',
            'enable_value_limits' => !empty($r['enable_value_limits']),
            'min_amount' => isset($r['min_amount']) ? sanitize_text_field($r['min_amount']) : '',
            'max_amount' => isset($r['max_amount']) ? sanitize_text_field($r['max_amount']) : '',
            'min_amount_message' => isset($r['min_amount_message']) ? sanitize_text_field($r['min_amount_message']) : '',
            'max_amount_message' => isset($r['max_amount_message']) ? sanitize_text_field($r['max_amount_message']) : '',
            'tiered_combined_variations' => !empty($r['tiered_combined_variations']),
        );

        // ── Shared schedule ────────────────────────────────────────
        $s = is_array($rule['schedule'] ?? null) ? $rule['schedule'] : array();
        $out['schedule'] = array(
            'start_date' => isset($s['start_date']) ? $this->sanitize_schedule_date($s['start_date']) : '',
            'end_date' => isset($s['end_date']) ? $this->sanitize_schedule_date($s['end_date']) : '',
        );

        $out['conditions'] = $this->sanitize_conditions($rule);

        // ── Type-specific fields ───────────────────────────────────
        switch ($out['rule_type']) {
            case 'product_discount':
                $out = array_merge($out, $this->sanitize_product_discount_rule($rule));
                // Product discounts never accept pricing restrictions, even
                // when an older client submits a stale restrictions object.
                $out['restrictions'] = array();
                break;
            case 'cart_discount':
                $out = array_merge($out, $this->sanitize_cart_rule($rule));
                break;
            case 'bogo_discount':
                $out = array_merge($out, $this->sanitize_bogo_rule($rule));
                break;
            case 'buy_x_get_y':
                $out = array_merge($out, $this->sanitize_bxgy_rule($rule));
                break;
            default:
                $out = array_merge($out, $this->sanitize_pricing_rule($rule));
                if ('tiered' === $out['discount_type']) {
                    $out['restrictions']['enable_quantity_limits'] = false;
                    $out['restrictions']['min_quantity'] = '';
                    $out['restrictions']['max_quantity'] = '';
                    $out['restrictions']['min_quantity_message'] = '';
                    $out['restrictions']['max_quantity_message'] = '';
                    $out['restrictions']['enable_quantity_step'] = false;
                    $out['restrictions']['quantity_step'] = '';
                }
                break;
        }

        return $out;
    }

    /**
     * Normalize a schedule value to a calendar date in the WordPress timezone.
     *
     * @param mixed $value Raw date value.
     * @return string
     */
    private function sanitize_schedule_date($value): string
    {
        $value = trim(sanitize_text_field($value));
        if ('' === $value) {
            return '';
        }

        $timezone = wp_timezone();

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);
            $errors = \DateTimeImmutable::getLastErrors();

            if (
                false === $date ||
                (is_array($errors) && (0 < $errors['warning_count'] || 0 < $errors['error_count'])) ||
                $value !== $date->format('Y-m-d')
            ) {
                return '';
            }

            return $date->format('Y-m-d');
        }

        try {
            return (new \DateTimeImmutable($value, $timezone))
                ->setTimezone($timezone)
                ->format('Y-m-d');
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Reject invalid or reversed schedule dates before persisting a rule.
     *
     * @param array $raw_rule  Raw rule data.
     * @param array $sanitized Sanitized rule data.
     * @return true|\WP_Error
     */
    private function validate_schedule(array $raw_rule, array $sanitized)
    {
        $raw_schedule = is_array($raw_rule['schedule'] ?? null) ? $raw_rule['schedule'] : array();
        $schedule = $sanitized['schedule'];

        foreach (array('start_date', 'end_date') as $field) {
            if (!empty($raw_schedule[$field]) && empty($schedule[$field])) {
                return new \WP_Error(
                    'wholesalex_invalid_schedule_date',
                    __('Please enter a valid schedule date.', 'wholesalex')
                );
            }
        }

        if (
            !empty($schedule['start_date']) &&
            !empty($schedule['end_date']) &&
            $schedule['start_date'] > $schedule['end_date']
        ) {
            return new \WP_Error(
                'wholesalex_invalid_schedule_range',
                __('The start date cannot be later than the end date. Please correct the schedule before saving this rule.', 'wholesalex')
            );
        }

        return true;
    }

    // ── Type-specific sanitisers ──────────────────────────────────────────────

    /**
     * Sanitise wholesale_pricing specific fields.
     */
    private function sanitize_pricing_rule(array $rule): array
    {
        $out = array();

        $out['discount_type'] = in_array($rule['discount_type'] ?? '', array('regular', 'tiered'), true)
            ? $rule['discount_type']
            : 'regular';

        $regular = is_array($rule['regular'] ?? null) ? $rule['regular'] : array();
        $out['regular'] = array(
            'amount' => isset($regular['amount']) ? sanitize_text_field($regular['amount']) : '',
            'amount_type' => in_array($regular['amount_type'] ?? '', array('percentage', 'amount', 'fixed'), true)
                ? $regular['amount_type']
                : 'percentage',
            'label' => isset($regular['label']) ? sanitize_text_field($regular['label']) : '',
        );

        $out['tiers'] = array();
        if (isset($rule['tiers']) && is_array($rule['tiers'])) {
            foreach ($rule['tiers'] as $tier) {
                if (!is_array($tier)) {
                    continue;
                }
                $out['tiers'][] = array(
                    'id' => isset($tier['id']) ? sanitize_text_field($tier['id']) : '',
                    'min_qty' => isset($tier['min_qty']) ? absint($tier['min_qty']) : 0,
                    'max_qty' => (isset($tier['max_qty']) && !is_null($tier['max_qty']))
                        ? absint($tier['max_qty'])
                        : null,
                    'amount' => isset($tier['amount']) ? sanitize_text_field($tier['amount']) : '',
                    'amount_type' => in_array($tier['amount_type'] ?? '', array('percentage', 'amount', 'fixed'), true)
                        ? $tier['amount_type']
                        : 'percentage',
                );
            }
        }

        $out['design'] = $this->sanitize_design(
            is_array($rule['design'] ?? null) ? $rule['design'] : array()
        );

        return $out;
    }

    /**
     * Sanitise product_discount specific fields.
     */
    private function sanitize_product_discount_rule(array $rule): array
    {
        $discount = is_array($rule['product_discount'] ?? null)
            ? $rule['product_discount']
            : array();

        return array(
            'discount_type' => 'regular',
            'product_discount' => array(
                'amount' => isset($discount['amount']) && is_numeric($discount['amount'])
                    ? sanitize_text_field($discount['amount'])
                    : '',
                'amount_type' => in_array($discount['amount_type'] ?? '', array('percentage', 'amount', 'fixed'), true)
                    ? $discount['amount_type']
                    : 'percentage',
                'label' => isset($discount['label'])
                    ? sanitize_text_field($discount['label'])
                    : __('Product discount', 'wholesalex'),
            ),
        );
    }

    /**
     * Sanitise cart_discount specific fields.
     */
    private function sanitize_cart_rule(array $rule): array
    {
        $src = is_array($rule['cart'] ?? null) ? $rule['cart'] : array();
        $allowed_types = array('percentage', 'amount', 'fixed');

        return array(
            'cart' => array(
                'discount_type' => in_array($src['discount_type'] ?? '', $allowed_types, true)
                    ? $src['discount_type']
                    : 'percentage',
                'discount_amount' => isset($src['discount_amount']) ? sanitize_text_field($src['discount_amount']) : '',
                'discount_name' => isset($src['discount_name']) ? sanitize_text_field($src['discount_name']) : '',
                'show_cart_discount_conditions' => array_key_exists('show_cart_discount_conditions', $src)
                    ? !empty($src['show_cart_discount_conditions'])
                    : true,
                'show_label_before_promo' => !empty($src['show_label_before_promo']),
                'label_text' => isset($src['label_text']) ? sanitize_text_field($src['label_text']) : 'Cart Discount',
                'promo_desc_text' => isset($src['promo_desc_text']) ? sanitize_text_field($src['promo_desc_text']) : 'After adding to the cart',
                'condition_texts' => $this->sanitize_condition_texts(
                    is_array($src['condition_texts'] ?? null) ? $src['condition_texts'] : array()
                ),
            ),
        );
    }

    /**
     * Sanitise bogo_discount specific fields.
     */
    private function sanitize_bogo_rule(array $rule): array
    {
        $src = is_array($rule['bogo'] ?? null) ? $rule['bogo'] : array();
        $allowed_styles = array('style_one', 'style_two', 'style_three', 'style_four', 'style_five');
        $allowed_pos = array('', 'left', 'right');

        return array(
            'bogo' => array(
                'buy_x_qty' => isset($src['buy_x_qty']) ? absint($src['buy_x_qty']) : 1,
                'per_cart_once' => !empty($src['per_cart_once']),
                'show_promo_text' => array_key_exists('show_promo_text', $src)
                    ? !empty($src['show_promo_text'])
                    : true,
                'offer_text' => isset($src['offer_text']) ? sanitize_text_field($src['offer_text']) : '100% Off on 1 Product',
                'promo_text_popup' => isset($src['promo_text_popup']) ? sanitize_text_field($src['promo_text_popup']) : 'Buy at least {required_quantity} products',
                'promo_text_cart' => isset($src['promo_text_cart']) ? sanitize_text_field($src['promo_text_cart']) : '{product_title} (Buy X Get 1 Discounted)',
                'show_badge' => !empty($src['show_badge']),
                'badge_label' => isset($src['badge_label']) ? sanitize_text_field($src['badge_label']) : 'Buy X Get 1 Discounted',
                'badge_style' => in_array($src['badge_style'] ?? '', $allowed_styles, true)
                    ? $src['badge_style']
                    : 'style_one',
                'badge_position' => in_array($src['badge_position'] ?? '', $allowed_pos, true)
                    ? $src['badge_position']
                    : '',
                'badge_bg_color' => sanitize_hex_color($src['badge_bg_color'] ?? '') ?? '#5a40e8',
                'badge_text_color' => sanitize_hex_color($src['badge_text_color'] ?? '') ?? '#ffffff',
            ),
        );
    }

    /**
     * Sanitise buy_x_get_y specific fields.
     */
    private function sanitize_bxgy_rule(array $rule): array
    {
        $src = is_array($rule['bxgy'] ?? null) ? $rule['bxgy'] : array();
        $allowed_styles = array('style_one', 'style_two', 'style_three', 'style_four', 'style_five');
        $allowed_pos = array('', 'left', 'right');

        $free_products = array();
        if (isset($src['free_products']) && is_array($src['free_products'])) {
            $free_products = $this->sanitize_bxgy_free_product_items($src['free_products']);
        }

        return array(
            'bxgy' => array(
                'min_qty' => isset($src['min_qty']) ? absint($src['min_qty']) : 1,
                'free_products' => $free_products,
                'free_item_count' => isset($src['free_item_count']) ? absint($src['free_item_count']) : 1,
                'per_cart_once' => !empty($src['per_cart_once']),
                'badge_label' => isset($src['badge_label']) ? sanitize_text_field($src['badge_label']) : 'Buy X Get Y Free',
                'show_free_item' => array_key_exists('show_free_item', $src) ? !empty($src['show_free_item']) : true,
                'show_badge' => array_key_exists('show_badge', $src) ? !empty($src['show_badge']) : true,
                'badge_style' => in_array($src['badge_style'] ?? '', $allowed_styles, true)
                    ? $src['badge_style']
                    : 'style_one',
                'badge_position' => in_array($src['badge_position'] ?? '', $allowed_pos, true)
                    ? $src['badge_position']
                    : '',
                'badge_bg_color' => sanitize_hex_color($src['badge_bg_color'] ?? '') ?? '#5a40e8',
                'badge_text_color' => sanitize_hex_color($src['badge_text_color'] ?? '') ?? '#ffffff',
            ),
        );
    }

    /**
     * Sanitise a list of MultiSelect items.
     *
     * The MultiSelect AJAX component emits items as {name, value} objects.
     * We must keep that shape intact so labels are available when the form
     * is re-opened.  Plain scalar fallback handles legacy/simple-mode lists.
     *
     * @param array  $items      Raw list from the JS client.
     * @param string $value_type 'int' or 'string' – how to sanitize the value key.
     * @return array
     */
    private function sanitize_select_items(array $items, string $value_type = 'string'): array
    {
        $out = array();
        foreach ($items as $item) {
            if (is_array($item)) {
                // Object shape emitted by MultiSelect AJAX mode.
                $value = $value_type === 'int'
                    ? absint($item['value'] ?? 0)
                    : sanitize_text_field($item['value'] ?? '');
                $out[] = array(
                    'value' => $value,
                    'name' => sanitize_text_field($item['name'] ?? ''),
                );
            } else {
                // Plain scalar (legacy / simple-mode).
                $out[] = $value_type === 'int'
                    ? absint($item)
                    : sanitize_text_field((string) $item);
            }
        }
        return $out;
    }

    /**
     * Sanitise Buy X Get Y free-product items.
     *
     * BXGY variation options can include concrete variation attributes. Preserve
     * that metadata so WooCommerce add_to_cart receives every required field.
     *
     * @param array $items Raw MultiSelect items.
     * @return array
     */
    private function sanitize_bxgy_free_product_items(array $items): array
    {
        $out = array();

        foreach ($items as $item) {
            if (is_array($item)) {
                $product_id = absint($item['product_id'] ?? $item['value'] ?? 0);
                $value = sanitize_text_field((string) ($item['value'] ?? $product_id));

                if (!$product_id) {
                    continue;
                }

                $sanitized = array(
                    'value' => '' !== $value ? $value : $product_id,
                    'product_id' => $product_id,
                    'name' => sanitize_text_field($item['name'] ?? ''),
                );

                if (isset($item['variation']) && is_array($item['variation'])) {
                    $variation = array();

                    foreach ($item['variation'] as $attribute_key => $attribute_value) {
                        $attribute_key = sanitize_key($attribute_key);

                        if (0 !== strpos($attribute_key, 'attribute_')) {
                            continue;
                        }

                        $variation[$attribute_key] = sanitize_text_field((string) $attribute_value);
                    }

                    if (!empty($variation)) {
                        $sanitized['variation'] = $variation;
                    }
                }

                $out[] = $sanitized;
            } else {
                $product_id = absint($item);
                if ($product_id) {
                    $out[] = $product_id;
                }
            }
        }

        return $out;
    }

    /**
     * Sanitise advanced condition tiers using the dynamic-rules condition shape.
     *
     * @param array $rule Raw rule from the JS client.
     * @return array{tiers: array<int, array<string, string>>}
     */
    private function sanitize_conditions(array $rule): array
    {
        $conditions = is_array($rule['conditions'] ?? null) ? $rule['conditions'] : array();
        $tiers = is_array($conditions['tiers'] ?? null) ? $conditions['tiers'] : array();

        // Backward compatibility for the first cart-discount UI draft.
        if (empty($tiers) && is_array($rule['cart']['conditions'] ?? null)) {
            $tiers = $rule['cart']['conditions'];
        }

        $allowed_fields = array(
            'cart_total_qty',
            'cart_total_value',
            'cart_total_weight',
            'pro_order_count',
            'pro_total_purchase',
            'order_count',
            'total_purchase',
        );
        $allowed_operators = array('less', 'less_equal', 'greater_equal', 'greater', 'equal', 'not_equal');
        $legacy_fields = array(
            'cart_total' => 'cart_total_value',
            'item_count' => 'cart_total_qty',
            'lifetime_order_count' => 'pro_order_count',
            'lifetime_orders' => 'pro_order_count',
            'user_order_count' => 'pro_order_count',
            'lifetime_purchase' => 'pro_total_purchase',
            'lifetime_spend' => 'pro_total_purchase',
            'lifetime_total_spent' => 'pro_total_purchase',
            'user_total_purchase' => 'pro_total_purchase',
        );
        $legacy_operators = array(
            '<' => 'less',
            '>' => 'greater',
            '=' => 'equal',
        );

        $out = array();
        foreach ($tiers as $tier) {
            if (!is_array($tier)) {
                continue;
            }

            $field = sanitize_text_field($tier['_conditions_for'] ?? $tier['field'] ?? '');
            $field = $legacy_fields[$field] ?? $field;

            $operator = sanitize_text_field($tier['_conditions_operator'] ?? $tier['operator'] ?? '');
            $operator = $legacy_operators[$operator] ?? $operator;

            $value = isset($tier['_conditions_value']) ? $tier['_conditions_value'] : ($tier['value'] ?? '');
            $value = is_scalar($value) ? sanitize_text_field((string) $value) : '';

            if (
                !in_array($field, $allowed_fields, true) ||
                !in_array($operator, $allowed_operators, true) ||
                '' === $value ||
                !is_numeric($value)
            ) {
                continue;
            }

            $out[] = array(
                '_conditions_for' => $field,
                '_conditions_operator' => $operator,
                '_conditions_value' => $value,
            );
        }

        return array('tiers' => $out);
    }

    /**
     * Sanitise custom condition text overrides for cart discount rules.
     *
     * @param array $texts Raw condition text values.
     * @return array
     */
    private function sanitize_condition_texts(array $texts): array
    {
        $out = $this->get_default_condition_texts();
        $legacy_defaults = $this->get_legacy_default_condition_texts();

        foreach ($this->get_legacy_condition_text_map() as $legacy_key => $current_keys) {
            if (!isset($texts[$legacy_key]) || !is_scalar($texts[$legacy_key])) {
                continue;
            }

            $legacy_value = sanitize_text_field((string) $texts[$legacy_key]);
            if (isset($legacy_defaults[$legacy_key]) && $legacy_defaults[$legacy_key] === $legacy_value) {
                continue;
            }

            foreach ($current_keys as $current_key) {
                if (!array_key_exists($current_key, $texts)) {
                    $out[$current_key] = $legacy_value;
                }
            }
        }

        foreach ($texts as $key => $value) {
            $key = sanitize_key($key);
            if (!array_key_exists($key, $out) || !is_scalar($value)) {
                continue;
            }
            $out[$key] = sanitize_text_field((string) $value);
        }
        return $out;
    }

    /**
     * Return all cart discount condition text keys with their default values.
     *
     * @return array<string, string>
     */
    private function get_default_condition_texts(): array
    {
        return array(
            'cart_total_qty_less_conditions_text' => __('Keep your items below {max_value} to qualify.', 'wholesalex'),
            'cart_total_qty_less_equal_conditions_text' => __('Keep your items at {max_value} or less to qualify.', 'wholesalex'),
            'cart_total_qty_greater_conditions_text' => __('Add more than {min_value} items to qualify.', 'wholesalex'),
            'cart_total_qty_greater_equal_conditions_text' => __('Add {min_value} or more items to qualify.', 'wholesalex'),
            'cart_total_qty_greater_less_conditions_text' => __('Add more than {min_value} but less than {max_value} items to unlock this offer.', 'wholesalex'),
            'cart_total_qty_greater_equal_less_equal_conditions_text' => __('Add {min_value} to {max_value} items to unlock this offer.', 'wholesalex'),
            'cart_total_qty_greater_less_equal_conditions_text' => __('Add more than {min_value} to {max_value} items to unlock this offer.', 'wholesalex'),
            'cart_total_qty_greater_equal_less_conditions_text' => __('Add {min_value} to less than {max_value} items to unlock this offer.', 'wholesalex'),
            'cart_total_weight_less_conditions_text' => __('Keep your weight below {max_value} to qualify.', 'wholesalex'),
            'cart_total_weight_less_equal_conditions_text' => __('Keep your total weight at {max_value} or less to qualify.', 'wholesalex'),
            'cart_total_weight_greater_conditions_text' => __('Add more than {min_value} in weight to qualify.', 'wholesalex'),
            'cart_total_weight_greater_equal_conditions_text' => __('Add {min_value} or more in weight to qualify.', 'wholesalex'),
            'cart_total_weight_greater_less_conditions_text' => __('Add more than {min_value} but less than {max_value} in weight to unlock this offer.', 'wholesalex'),
            'cart_total_weight_greater_equal_less_equal_conditions_text' => __('Add {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex'),
            'cart_total_weight_greater_less_equal_conditions_text' => __('Add more than {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex'),
            'cart_total_weight_greater_equal_less_conditions_text' => __('Add {min_value} to less than {max_value} in weight to unlock this offer.', 'wholesalex'),
            'cart_total_value_less_conditions_text' => __('Keep your spend below ${max_value} to qualify.', 'wholesalex'),
            'cart_total_value_less_equal_conditions_text' => __('Keep your spend at ${max_value} or less to qualify.', 'wholesalex'),
            'cart_total_value_greater_conditions_text' => __('Spend more than ${min_value} to qualify.', 'wholesalex'),
            'cart_total_value_greater_equal_conditions_text' => __('Spend ${min_value} or more to qualify.', 'wholesalex'),
            'cart_total_value_greater_less_conditions_text' => __('Spend more than ${min_value} but less than ${max_value} to unlock this offer.', 'wholesalex'),
            'cart_total_value_greater_equal_less_equal_conditions_text' => __('Spend between ${min_value} and ${max_value} to unlock this offer.', 'wholesalex'),
            'cart_total_value_greater_less_equal_conditions_text' => __('Spend more than ${min_value} and up to ${max_value} to unlock this offer.', 'wholesalex'),
            'cart_total_value_greater_equal_less_conditions_text' => __('Spend ${min_value} or more but less than ${max_value} to unlock this offer.', 'wholesalex'),
        );
    }

    /**
     * Return legacy cart condition text defaults.
     *
     * @return array<string, string>
     */
    private function get_legacy_default_condition_texts(): array
    {
        return array(
            'cart_total_value_max_conditions_text' => 'Spend upto {min_value}',
            'cart_total_value_min_conditions_text' => 'Spend min {max_value}',
            'cart_total_value_min_max_conditions_text' => 'Spend {min_value} to {max_value}',
            'cart_total_qty_min_max_conditions_text' => 'Add {min_value} to {max_value} product(s) to cart',
            'cart_total_qty_min_conditions_text' => 'Add min {min_value} product(s) to cart',
            'cart_total_qty_max_conditions_text' => 'Add {max_value} or more product(s) to cart',
            'cart_total_weight_min_max_conditions_text' => 'Add {min_value} to {max_value} {unit} to cart',
            'cart_total_weight_min_conditions_text' => 'Add min {min_value} {unit} to cart',
            'cart_total_weight_max_conditions_text' => 'Add up to {max_value} {unit} to cart',
        );
    }

    /**
     * Map legacy min/max keys to operator-aware keys.
     *
     * @return array<string, array<int, string>>
     */
    private function get_legacy_condition_text_map(): array
    {
        return array(
            'cart_total_value_max_conditions_text' => array('cart_total_value_less_conditions_text', 'cart_total_value_less_equal_conditions_text'),
            'cart_total_value_min_conditions_text' => array('cart_total_value_greater_conditions_text', 'cart_total_value_greater_equal_conditions_text'),
            'cart_total_value_min_max_conditions_text' => array('cart_total_value_greater_less_conditions_text', 'cart_total_value_greater_equal_less_equal_conditions_text', 'cart_total_value_greater_less_equal_conditions_text', 'cart_total_value_greater_equal_less_conditions_text'),
            'cart_total_qty_max_conditions_text' => array('cart_total_qty_less_conditions_text', 'cart_total_qty_less_equal_conditions_text'),
            'cart_total_qty_min_conditions_text' => array('cart_total_qty_greater_conditions_text', 'cart_total_qty_greater_equal_conditions_text'),
            'cart_total_qty_min_max_conditions_text' => array('cart_total_qty_greater_less_conditions_text', 'cart_total_qty_greater_equal_less_equal_conditions_text', 'cart_total_qty_greater_less_equal_conditions_text', 'cart_total_qty_greater_equal_less_conditions_text'),
            'cart_total_weight_max_conditions_text' => array('cart_total_weight_less_conditions_text', 'cart_total_weight_less_equal_conditions_text'),
            'cart_total_weight_min_conditions_text' => array('cart_total_weight_greater_conditions_text', 'cart_total_weight_greater_equal_conditions_text'),
            'cart_total_weight_min_max_conditions_text' => array('cart_total_weight_greater_less_conditions_text', 'cart_total_weight_greater_equal_less_equal_conditions_text', 'cart_total_weight_greater_less_equal_conditions_text', 'cart_total_weight_greater_equal_less_conditions_text'),
        );
    }

    /**
     * Sanitise the design sub-object.
     *
     * @param array $design Raw design from the JS client.
     * @return array
     */
    private function sanitize_design(array $design): array
    {
        return array(
            'table_style' => in_array($design['table_style'] ?? '', array('table_style', 'classic_style'), true)
                ? $design['table_style']
                : 'table_style',
            'vertical_style' => !empty($design['vertical_style']),
            'border_radius' => !empty($design['border_radius']),
            'table_heading' => isset($design['table_heading'])
                ? sanitize_text_field($design['table_heading'])
                : 'Buy More, Save More',
            'header_bg_color' => sanitize_hex_color($design['header_bg_color'] ?? '') ?? '#F7F7F7',
            'header_text_color' => sanitize_hex_color($design['header_text_color'] ?? '') ?? '#3A3A3A',
            'border_color' => sanitize_hex_color($design['border_color'] ?? '') ?? '#E5E5E5',
            'active_row_bg_color' => sanitize_hex_color($design['active_row_bg_color'] ?? '') ?? '#6C6CFF',
            'active_row_text_color' => sanitize_hex_color($design['active_row_text_color'] ?? '') ?? '#FFFFFF',
            'text_color' => sanitize_hex_color($design['text_color'] ?? '') ?? '#494949',
            'font_size' => isset($design['font_size']) ? absint($design['font_size']) : 14,
            'price_display' => in_array($design['price_display'] ?? '', array('fixed', 'discount'), true)
                ? $design['price_display']
                : 'fixed',
            'discount_text_color' => sanitize_hex_color($design['discount_text_color'] ?? '') ?? '#ffffff',
            'discount_bg_color' => sanitize_hex_color($design['discount_bg_color'] ?? '') ?? '#070707',
            'column_priority' => isset($design['column_priority']) && is_array($design['column_priority'])
                ? array_map(array($this, 'sanitize_column_item'), $design['column_priority'])
                : array(),
        );
    }

    /**
     * Sanitise a single column-priority item.
     *
     * @param mixed $item Raw item.
     * @return array
     */
    private function sanitize_column_item($item): array
    {
        if (!is_array($item)) {
            return array('label' => '', 'value' => '', 'status' => true);
        }
        return array(
            'label' => isset($item['label']) ? sanitize_text_field($item['label']) : '',
            'value' => isset($item['value']) ? sanitize_text_field($item['value']) : '',
            'status' => isset($item['status']) ? (bool) $item['status'] : true,
        );
    }
}
