<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rtcamp.com/nginx-helper/
 * @since      2.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     rtCamp
 */
class Nginx_Helper_Admin
{
    /**
     * WP-CLI Command.
     *
     * @since    2.0.0
     *
     * @var string WP-CLI Command.
     */
    public const WP_CLI_COMMAND = 'nginx-helper';

    /**
     * Purge options.
     *
     * @since    2.0.0
     *
     * @var string[] Purge options.
     */
    public $options;

    /**
     * The ID of this plugin.
     *
     * @since    2.0.0
     *
     * @var string The ID of this plugin.
     */
    private $plugin_name;

    /**
     * Various settings tabs.
     *
     * @since    2.0.0
     *
     * @var string Various settings tabs.
     */
    private $settings_tabs;

    /**
     * The version of this plugin.
     *
     * @since    2.0.0
     *
     * @var string The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     *
     * @param  string  $plugin_name  The name of this plugin.
     * @param  string  $version  The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->options = $this->nginx_helper_settings();
    }

    /**
     * Add time stamps in html.
     */
    public function add_timestamps()
    {

        global $pagenow;

        if (is_admin() || (int) $this->options['enable_purge'] !== 1 || (int) $this->options['enable_stamp'] !== 1) {
            return;
        }

        if (! empty($pagenow) && $pagenow === 'wp-login.php') {
            return;
        }

        foreach (headers_list() as $header) {
            [$key, $value] = explode(':', $header, 2);
            $key = mb_strtolower($key);
            if ($key === 'content-type' && mb_strpos(mb_trim($value), 'text/html') !== 0) {
                return;
            }
            if ($key === 'content-type') {
                break;
            }
        }

        /**
         * Don't add timestamp if run from ajax, cron or wpcli.
         */
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        $timestamps = "\n<!--".
            'Cached using Nginx-Helper on '.current_time('mysql').'. '.
            'It took '.get_num_queries().' queries executed in '.timer_stop().' seconds.'.
            "-->\n".
            '<!--Visit http://wordpress.org/extend/plugins/nginx-helper/faq/ for more details-->';

        echo wp_kses($timestamps, []);

    }

    /**
     * Dismisses the "suggest purge" admin notice when the user clicks the dismiss link.
     */
    public function dismiss_suggest_purge_after_update()
    {

        if (! isset($_GET['nginx_helper_dismiss']) || ! isset($_GET['_wpnonce'])) {
            return;
        }

        $dismiss = sanitize_text_field(wp_unslash($_GET['nginx_helper_dismiss']));
        $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

        // Verify the correct nonce depending on whether this is a purge+dismiss or dismiss-only request.
        $has_purge_params = isset($_GET['nginx_helper_action'], $_GET['nginx_helper_urls']);
        $nonce_verified = $has_purge_params ? wp_verify_nonce($nonce, 'nginx_helper-purge_all') : wp_verify_nonce($nonce, 'nginx_helper_dismiss_notice');

        if ($dismiss && $nonce_verified) {

            delete_transient('rt_wp_nginx_helper_suggest_purge_notice');
            wp_safe_redirect(remove_query_arg(['nginx_helper_dismiss', '_wpnonce']));
            exit;
        }
    }

    /**
     * Dispay plugin notices.
     */
    public function display_notices()
    {
        echo '<div class="updated"><p>'.esc_html__('Purge initiated', 'nginx-helper').'</p></div>';
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    2.0.0
     *
     * @param  string  $hook  The current admin page.
     */
    public function enqueue_scripts($hook)
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Nginx_Helper_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Nginx_Helper_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        if ($hook !== 'settings_page_nginx') {
            return;
        }

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__).'js/nginx-helper-admin.js', ['jquery'], $this->version, false);

        $do_localize = [
            'purge_confirm_string' => esc_html__('Purging entire cache is not recommended. Would you like to continue?', 'nginx-helper'),
        ];
        wp_localize_script($this->plugin_name, 'nginx_helper', $do_localize);

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    2.0.0
     *
     * @param  string  $hook  The current admin page.
     */
    public function enqueue_styles($hook)
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Nginx_Helper_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Nginx_Helper_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        if ($hook !== 'settings_page_nginx') {
            return;
        }

        wp_enqueue_style($this->plugin_name.'-icons', plugin_dir_url(__FILE__).'icons/css/nginx-fontello.css', [], $this->version, 'all');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__).'css/nginx-helper-admin.css', [], $this->version, 'all');

    }

    /**
     * Retrieve the asset path.
     *
     * @since     2.0.0
     *
     * @return string asset path of the plugin.
     */
    public function functional_asset_path()
    {

        $log_path = WP_CONTENT_DIR.'/uploads/nginx-helper/';

        return apply_filters('nginx_asset_path', $log_path);

    }

    /**
     * Retrieve the asset url.
     *
     * @since     2.0.0
     *
     * @return string asset url of the plugin.
     */
    public function functional_asset_url()
    {

        $log_url = WP_CONTENT_URL.'/uploads/nginx-helper/';

        return apply_filters('nginx_asset_url', $log_url);

    }

    /**
     * Get map
     *
     * @global object $wpdb
     *
     * @return string
     */
    public function get_map()
    {

        if (! $this->options['enable_map']) {
            return;
        }

        if (is_multisite()) {

            global $wpdb;

            $rt_all_blogs = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT blog_id, domain, path FROM '.$wpdb->blogs." WHERE site_id = %d AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'",
                    $wpdb->siteid
                )
            );

            $wpdb->dmtable = $wpdb->base_prefix.'domain_mapping';

            $rt_domain_map_sites = '';

            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->dmtable}'") === $wpdb->dmtable) { // phpcs:ignore
                $rt_domain_map_sites = $wpdb->get_results("SELECT blog_id, domain FROM {$wpdb->dmtable} ORDER BY id DESC");
            }

            $rt_nginx_map = '';
            $rt_nginx_map_array = [];

            if ($rt_all_blogs) {

                foreach ($rt_all_blogs as $blog) {

                    if (true === SUBDOMAIN_INSTALL) {
                        $rt_nginx_map_array[$blog->domain] = $blog->blog_id;
                    } else {

                        if ($blog->blog_id !== 1) {
                            $rt_nginx_map_array[$blog->path] = $blog->blog_id;
                        }
                    }
                }
            }

            if ($rt_domain_map_sites) {

                foreach ($rt_domain_map_sites as $site) {
                    $rt_nginx_map_array[$site->domain] = $site->blog_id;
                }
            }

            foreach ($rt_nginx_map_array as $domain => $domain_id) {
                $rt_nginx_map .= "\t".$domain."\t".$domain_id.";\n";
            }

            return $rt_nginx_map;

        }

    }

    /**
     * Initialize WooCommerce hooks if enabled.
     *
     * @since 2.3.5
     */
    public function init_woocommerce_hooks()
    {
        if (! is_plugin_active('woocommerce/woocommerce.php') || empty($this->options['purge_woo_products'])) {
            return;
        }

        add_action('woocommerce_reduce_order_stock', [$this, 'purge_product_cache_on_purchase'], 10, 1);
        add_action('woocommerce_update_product', [$this, 'purge_product_cache_on_update'], 10, 1);
    }

    /**
     * Initialize the settings tab.
     * Required since i18n is used in the settings tab which can be invoked only after init hook since WordPress 6.7
     */
    public function initialize_setting_tab()
    {

        /**
         * Define settings tabs
         */
        $this->settings_tabs = apply_filters(
            'rt_nginx_helper_settings_tabs',
            [
                'general' => [
                    'menu_title' => __('General', 'nginx-helper'),
                    'menu_slug' => 'general',
                ],
                'support' => [
                    'menu_title' => __('Support', 'nginx-helper'),
                    'menu_slug' => 'support',
                ],
            ]
        );
    }

    /**
     * Determines if the current request is for importing Posts/ WordPress content.
     *
     * @return bool True if the request is for importing, false otherwise.
     */
    public function is_import_request()
    {
        $import_query_var = sanitize_text_field(wp_unslash($_GET['import'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is already in the admin dashboard.
        $has_import_started = did_action('import_start');

        return (defined('WP_IMPORTING') && true === WP_IMPORTING)
            || $has_import_started !== 0
            || ! empty($import_query_var);
    }

    /**
     * Check if the nginx log is enabled.
     *
     * @since 2.2.4
     *
     * @return bool
     */
    public function is_nginx_log_enabled()
    {

        $options = get_site_option('rt_wp_nginx_helper_options', []);

        if (! empty($options['enable_log']) && (int) $options['enable_log'] === 1) {
            return true;
        }

        if (defined('ENCODIA_NGINX_HELPER_LOG_ENABLED') && true === ENCODIA_NGINX_HELPER_LOG_ENABLED) {
            return true;
        }

        return false;
    }

    /**
     * Add admin menu.
     *
     * @since    2.0.0
     */
    public function nginx_helper_admin_menu()
    {

        if (is_multisite()) {

            add_submenu_page(
                'settings.php',
                __('Nginx Helper', 'nginx-helper'),
                __('Nginx Helper', 'nginx-helper'),
                'manage_options',
                'nginx',
                [&$this, 'nginx_helper_setting_page']
            );

        } else {

            add_submenu_page(
                'options-general.php',
                __('Nginx Helper', 'nginx-helper'),
                __('Nginx Helper', 'nginx-helper'),
                'manage_options',
                'nginx',
                [&$this, 'nginx_helper_setting_page']
            );

        }

    }

    /**
     * Automatically purges Nginx cache on any WordPress core, plugin, or theme update if enabled.
     *
     * @param  WP_Upgrader  $upgrader_object  WP_Upgrader instance.
     * @param  array  $options  Array of bulk item update data.
     */
    public function nginx_helper_auto_purge_on_any_update($upgrader_object, $options)
    {

        if (! isset($options['action'], $options['type'])
            || $options['action'] !== 'update'
            || ! in_array($options['type'], ['core', 'plugin', 'theme'], true)) {
            return;
        }
        if (! defined('NGINX_HELPER_AUTO_PURGE_ON_ANY_UPDATE') || ! NGINX_HELPER_AUTO_PURGE_ON_ANY_UPDATE) {
            set_transient('rt_wp_nginx_helper_suggest_purge_notice', true, HOUR_IN_SECONDS);

            return;
        }
        global $nginx_purger;

        $nginx_purger->purge_all();
    }

    /**
     * Default settings.
     *
     * @since    2.0.0
     *
     * @return array
     */
    public function nginx_helper_default_settings()
    {

        return [
            'enable_purge' => 0,
            'cache_method' => 'enable_fastcgi',
            'purge_method' => 'get_request',
            'enable_map' => 0,
            'enable_log' => 0,
            'log_level' => 'INFO',
            'log_filesize' => '5',
            'enable_stamp' => 0,
            'purge_homepage_on_edit' => 1,
            'purge_homepage_on_del' => 1,
            'purge_archive_on_edit' => 1,
            'purge_archive_on_del' => 1,
            'purge_archive_on_new_comment' => 0,
            'purge_archive_on_deleted_comment' => 0,
            'purge_page_on_mod' => 1,
            'purge_page_on_new_comment' => 1,
            'purge_page_on_deleted_comment' => 1,
            'purge_feeds' => 1,
            'redis_hostname' => '127.0.0.1',
            'redis_port' => '6379',
            'redis_prefix' => 'nginx-cache:',
            'redis_unix_socket' => '',
            'redis_database' => 0,
            'redis_username' => '',
            'redis_password' => '',
            'purge_url' => '',
            'redis_enabled_by_constant' => 0,
            'redis_socket_enabled_by_constant' => 0,
            'redis_acl_enabled_by_constant' => 0,
            'preload_cache' => 0,
            'is_cache_preloaded' => 0,
            'roles_with_purge_cap' => [],
            'purge_woo_products' => 0,
        ];

    }

    /**
     * Get latest news.
     *
     * @since     2.0.0
     */
    public function nginx_helper_get_feeds()
    {

        // Get RSS Feed(s).
        require_once ABSPATH.WPINC.'/feed.php';

        $maxitems = 0;
        $rss_items = [];

        // Get a SimplePie feed object from the specified feed source.
        $rss = fetch_feed('https://rtcamp.com/blog/feed/');

        if (! is_wp_error($rss)) { // Checks that the object is created correctly.

            // Figure out how many total items there are, but limit it to 5.
            $maxitems = $rss->get_item_quantity(5);
            // Build an array of all the items, starting with element 0 (first element).
            $rss_items = $rss->get_items(0, $maxitems);

        }
        ?>
		<ul role="list">
			<?php
            if ($maxitems === 0) {
                echo '<li role="listitem">'.esc_html_e('No items', 'nginx-helper').'.</li>';
            } else {

                // Loop through each feed item and display each item as a hyperlink.
                foreach ($rss_items as $item) {
                    ?>
						<li role="listitem">
							<?php
                                printf(
                                    '<a href="%s" title="%s">%s</a>',
                                    esc_url($item->get_permalink()),
                                    esc_attr(
                                        sprintf(
                                            /* translators: %s: date/time the feed item as been posted */
                                            __('Posted %s', 'nginx-helper'),
                                            $item->get_date('j F Y | g:i a')
                                        )
                                    ),
                                    esc_html($item->get_title())
                                );
                    ?>
						</li>
					<?php
                }
            }
        ?>
		</ul>
		<?php
        exit();

    }

    /**
     * Display settings.
     *
     * @global $string $pagenow Contain current admin page.
     *
     * @since    2.0.0
     */
    public function nginx_helper_setting_page()
    {
        include plugin_dir_path(__FILE__).'partials/nginx-helper-admin-display.php';
    }

    /**
     * Get settings.
     *
     * @since    2.0.0
     */
    public function nginx_helper_settings()
    {

        $options = get_site_option(
            'rt_wp_nginx_helper_options',
            [
                'redis_hostname' => '127.0.0.1',
                'redis_port' => '6379',
                'redis_prefix' => 'nginx-cache:',
                'redis_database' => 0,
            ]
        );

        $data = wp_parse_args(
            $options,
            $this->nginx_helper_default_settings()
        );

        $is_redis_enabled = (
            defined('RT_WP_NGINX_HELPER_REDIS_HOSTNAME') &&
            defined('RT_WP_NGINX_HELPER_REDIS_PORT') &&
            defined('RT_WP_NGINX_HELPER_REDIS_PREFIX')
        );

        $data['redis_acl_enabled_by_constant'] = defined('RT_WP_NGINX_HELPER_REDIS_USERNAME') && defined('RT_WP_NGINX_HELPER_REDIS_PASSWORD');
        $data['redis_socket_enabled_by_constant'] = defined('RT_WP_NGINX_HELPER_REDIS_UNIX_SOCKET');
        $data['redis_unix_socket'] = $data['redis_socket_enabled_by_constant'] ? RT_WP_NGINX_HELPER_REDIS_UNIX_SOCKET : $data['redis_unix_socket'];
        $data['redis_username'] = $data['redis_acl_enabled_by_constant'] ? RT_WP_NGINX_HELPER_REDIS_USERNAME : $data['redis_username'];
        $data['redis_password'] = $data['redis_acl_enabled_by_constant'] ? RT_WP_NGINX_HELPER_REDIS_PASSWORD : $data['redis_password'];

        if (! $is_redis_enabled) {
            return $data;
        }

        $data['redis_enabled_by_constant'] = $is_redis_enabled;
        $data['enable_purge'] = $is_redis_enabled;
        $data['cache_method'] = 'enable_redis';
        $data['redis_hostname'] = RT_WP_NGINX_HELPER_REDIS_HOSTNAME;
        $data['redis_port'] = RT_WP_NGINX_HELPER_REDIS_PORT;
        $data['redis_prefix'] = RT_WP_NGINX_HELPER_REDIS_PREFIX;
        $data['redis_database'] = defined('RT_WP_NGINX_HELPER_REDIS_DATABASE') ? RT_WP_NGINX_HELPER_REDIS_DATABASE : 0;

        return $data;

    }

    /**
     * Nginx helper setting link function.
     *
     * @param  array  $links  links.
     */
    public function nginx_helper_settings_link($links)
    {

        if (is_network_admin()) {
            $setting_page = 'settings.php';
        } else {
            $setting_page = 'options-general.php';
        }

        $settings_link = '<a href="'.network_admin_url($setting_page.'?page=nginx').'">'.__('Settings', 'nginx-helper').'</a>';
        array_unshift($links, $settings_link);

        return $links;

    }

    /**
     * Function to add toolbar purge link.
     *
     * @param  object  $wp_admin_bar  Admin bar object.
     */
    public function nginx_helper_toolbar_purge_link($wp_admin_bar)
    {

        if (! current_user_can('Nginx Helper | Purge cache')) {
            return;
        }

        if (is_admin()) {
            $nginx_helper_urls = 'all';
            $link_title = __('Purge Cache', 'nginx-helper');
        } else {
            $nginx_helper_urls = 'current-url';
            $link_title = __('Purge Current Page', 'nginx-helper');
        }

        $purge_url = add_query_arg(
            [
                'nginx_helper_action' => 'purge',
                'nginx_helper_urls' => $nginx_helper_urls,
                'nginx_helper_dismiss' => get_transient('rt_wp_nginx_helper_suggest_purge_notice'),
            ]
        );

        $nonced_url = wp_nonce_url($purge_url, 'nginx_helper-purge_all');

        $wp_admin_bar->add_menu(
            [
                'id' => 'nginx-helper-purge-all',
                'title' => $link_title,
                'href' => $nonced_url,
                'meta' => ['title' => $link_title],
            ]
        );

    }

    /**
     * Sync purge capability with selected roles.
     */
    public function nginx_helper_update_role_caps()
    {
        $purge_cap = 'Nginx Helper | Purge cache';

        // Get all available roles.
        $all_roles = wp_roles()->get_names();
        $site_options = get_site_option('rt_wp_nginx_helper_options', []);

        // Roles selected in settings.
        $selected_roles = isset($site_options['roles_with_purge_cap']) && is_array($site_options['roles_with_purge_cap'])
            ? $site_options['roles_with_purge_cap']
            : [];

        foreach ($all_roles as $role_key => $role_name) {
            $role = get_role($role_key);

            if (! $role || $role_key === 'administrator') {
                continue;
            }

            // If role is NOT selected, remove cap and continue.
            if (! isset($selected_roles[$role_key])) {
                $role->remove_cap($purge_cap);

                continue;
            }

            // If selected, make sure cap is added.
            $role->add_cap($purge_cap);
        }
    }

    /**
     * Preloads the cache for the website.
     *
     * @return void
     */
    public function preload_cache()
    {
        $is_cache_preloaded = $this->options['is_cache_preloaded'];
        $preload_cache_enabled = $this->options['preload_cache'];

        if ($preload_cache_enabled && (bool) $is_cache_preloaded === false) {
            $this->options['is_cache_preloaded'] = true;

            update_site_option('rt_wp_nginx_helper_options', $this->options);
            $this->preload_cache_from_sitemap();
        }
    }

    /**
     * Purge all urls.
     * Purge current page cache when purging is requested from front
     * and all urls when requested from admin dashboard.
     *
     * @global object $nginx_purger
     */
    public function purge_all()
    {

        if ($this->is_import_request()) {
            return;
        }

        global $nginx_purger, $wp;

        $method = null;
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = wp_strip_all_tags($_SERVER['REQUEST_METHOD']);
        }

        $action = '';
        if ($method === 'POST') {
            if (isset($_POST['nginx_helper_action'])) {
                $action = wp_strip_all_tags($_POST['nginx_helper_action']);
            }
        } else {
            if (isset($_GET['nginx_helper_action'])) {
                $action = wp_strip_all_tags($_GET['nginx_helper_action']);
            }
        }

        if (empty($action)) {
            return;
        }

        if (! current_user_can('Nginx Helper | Purge cache')) {
            wp_die('Sorry, you do not have the necessary privileges to edit these options.');
        }

        if ($action === 'done') {

            add_action('admin_notices', [&$this, 'display_notices']);
            add_action('network_admin_notices', [&$this, 'display_notices']);

            return;

        }

        check_admin_referer('nginx_helper-purge_all');

        $current_url = user_trailingslashit(home_url($wp->request));

        if (! is_admin()) {
            $action = 'purge_current_page';
            $redirect_url = $current_url;
        } else {
            $redirect_url = add_query_arg(['nginx_helper_action' => 'done']);
        }

        switch ($action) {
            case 'purge':
                $nginx_purger->purge_all();
                break;
            case 'purge_current_page':
                $post_id = url_to_postid($current_url);

                if ($post_id > 0) {
                    $nginx_purger->purge_post_url_with_variants($post_id);
                } else {
                    $nginx_purger->purge_url($current_url);
                }
                break;
        }

        if ($action === 'purge') {

            /**
             * Fire an action after the entire cache has been purged whatever caching type is used.
             *
             * @since 2.2.2
             */
            do_action('rt_nginx_helper_after_purge_all');

        }

        wp_redirect(esc_url_raw($redirect_url));
        exit();

    }

    /**
     * Svuota la cache di tutti i prodotti quando le opzioni dei prodotti vengono salvate
     *
     * @since 9.1.0
     *
     * @param  mixed  $old_value  Previous option value.
     * @param  mixed  $value  New option value.
     * @param  string  $option_name  Option being updated.
     */
    public function purge_product_cache_on_product_options_save($option_name, $old_value, $value)
    {
        static $already_purged = false;

        if ($already_purged) {
            return;
        }

        if (! is_admin() || ! isset($_GET['page']) || $_GET['page'] !== 'options-product') {
            return;
        }

        // il purge è abilitato e se esiste il cpt products
        if (! $this->options['enable_purge'] || ! post_type_exists('product')) {
            return;
        }

        global $nginx_purger;

        if (empty($nginx_purger)) {
            return;
        }

        $already_purged = true;

        $total_purged = 0;

        $nginx_purger->log('Options-product settings updated - purging cache for every published product.');

        $query = new WP_Query(
            [
                'post_type' => 'product',
                'post_status' => 'publish',
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'posts_per_page' => -1,
                'no_found_rows' => true,
            ]
        );

        if ($query->have_posts()) {
            foreach ($query->posts as $product_id) {
                // svuota la cache del singolo prodotto e le sue varianti
                $nginx_purger->purge_post_url_with_variants($product_id);
                $total_purged++;
            }
        }

        if ($total_purged === 0) {
            $nginx_purger->log('No published products detected while purging after options-product save.', 'WARNING');

            return;
        }

        $nginx_purger->log(sprintf('Purged cache for %1$d product posts after options-product save.', $total_purged));
    }

    /**
     * Purge product cache when order stock is reduced (purchase).
     *
     * @since  2.3.5
     *
     * @global object $nginx_purger Nginx purger object.
     *
     * @param  object  $order  Order object.
     */
    public function purge_product_cache_on_purchase($order)
    {

        global $nginx_purger;

        if (! $order instanceof WC_Order) {
            return;
        }

        if (! $this->options['enable_purge']) {
            return;
        }

        $nginx_purger->log('WooCommerce order stock reduction - purging product caches');

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (! $product) {
                continue;
            }

            $product_id = $product->get_id();
            $nginx_purger->log('Purging cache for product ID: '.$product_id.' due to purchase');

            $nginx_purger->purge_post_url_with_variants($product_id);
        }
    }

    /**
     * Purge product cache when a product is updated via REST API.
     *
     * @since 2.3.5
     *
     * @global object $nginx_purger Nginx purger object.
     *
     * @param  int  $product_id  Product ID.
     */
    public function purge_product_cache_on_update($product_id)
    {
        global $nginx_purger;

        if (empty($nginx_purger)) {
            return;
        }

        if (! $this->options['enable_purge']) {
            return;
        }

        $nginx_purger->log('WooCommerce product update - purging cache for product ID: '.$product_id);

        $nginx_purger->purge_post_url_with_variants($product_id);
    }

    /**
     * Purge url when post status is changed.
     *
     * @global string $blog_id Blog id.
     * @global object $nginx_purger Nginx purger variable.
     *
     * @param  string  $new_status  New status.
     * @param  string  $old_status  Old status.
     * @param  object  $post  Post object.
     */
    public function set_future_post_option_on_future_status($new_status, $old_status, $post)
    {

        global $blog_id, $nginx_purger;

        $exclude_post_types = ['nav_menu_item'];

        if (in_array($post->post_type, $exclude_post_types, true)) {
            return;
        }

        if (! $this->options['enable_purge'] || $this->is_import_request()) {
            return;
        }

        $purge_status = ['publish', 'future'];

        if (in_array($old_status, $purge_status, true) || in_array($new_status, $purge_status, true)) {

            $nginx_purger->log('Purge post on transition post STATUS from '.$old_status.' to '.$new_status);
            $nginx_purger->purge_post($post->ID);

        }

        if (
            $new_status === 'future' && $post && $post->post_status === 'future' &&
            (
                ($post->post_type === 'post' || $post->post_type === 'page') ||
                (
                    isset($this->options['custom_post_types_recognized']) &&
                    in_array($post->post_type, $this->options['custom_post_types_recognized'], true)
                )
            )
        ) {

            $nginx_purger->log('Set/update future_posts option ( post id = '.$post->ID.' and blog id = '.$blog_id.' )');
            $this->options['future_posts'][$blog_id][$post->ID] = strtotime($post->post_date_gmt) + 60;
            update_site_option('rt_wp_nginx_helper_options', $this->options);

        }

    }

    public function store_default_options()
    {
        $options = get_site_option('rt_wp_nginx_helper_options', []);
        $default_settings = $this->nginx_helper_default_settings();

        $removable_default_settings = [
            'redis_port',
            'redis_prefix',
            'redis_hostname',
            'redis_database',
            'redis_unix_socket',
        ];

        // Remove all the keys that are not to be stored by default.
        foreach ($removable_default_settings as $removable_key) {
            unset($default_settings[$removable_key]);
        }

        $diffed_options = wp_parse_args($options, $default_settings);

        add_site_option('rt_wp_nginx_helper_options', $diffed_options);
    }

    /**
     * Displays an admin notice suggesting the user to purge cache after a WordPress update.
     */
    public function suggest_purge_after_update()
    {

        if (! get_transient('rt_wp_nginx_helper_suggest_purge_notice')) {
            return;
        }

        $setting_page = is_network_admin() ? 'settings.php' : 'options-general.php';
        $settings_link = network_admin_url($setting_page.'?page=nginx');
        $dismiss_url = wp_nonce_url(add_query_arg('nginx_helper_dismiss', 'true'), 'nginx_helper_dismiss_notice');
        ?>
		<div class="notice notice-info">
			<p>
				<?php
                esc_html_e('A WordPress update was detected. It is recommended to purge the cache to ensure your site displays the latest changes.', 'nginx-helper');
        ?>
				<a href="<?php echo esc_url($settings_link); ?>"><?php esc_html_e('Go & Purge Cache', 'nginx-helper'); ?></a>
				|
				<a href="<?php echo esc_url($dismiss_url); ?>">
				<?php esc_html_e('Dismiss', 'nginx-helper'); ?>
				</a>
			</p>
		</div>
		<?php
    }

    /**
     * Unset future post option on delete
     *
     * @global string $blog_id Blog id.
     * @global object $nginx_purger Nginx helper object.
     *
     * @param  int  $post_id  Post id.
     */
    public function unset_future_post_option_on_delete($post_id)
    {

        global $blog_id, $nginx_purger;

        if (
            ! $this->options['enable_purge'] ||
            empty($this->options['future_posts']) ||
            empty($this->options['future_posts'][$blog_id]) ||
            isset($this->options['future_posts'][$blog_id][$post_id]) ||
            wp_is_post_revision($post_id)
        ) {
            return;
        }

        $nginx_purger->log('Unset future_posts option ( post id = '.$post_id.' and blog id = '.$blog_id.' )');

        unset($this->options['future_posts'][$blog_id][$post_id]);

        if (! count($this->options['future_posts'][$blog_id])) {
            unset($this->options['future_posts'][$blog_id]);
        }

        update_site_option('rt_wp_nginx_helper_options', $this->options);
    }

    /**
     * Update map
     */
    public function update_map()
    {

        if (is_multisite()) {

            $rt_nginx_map = $this->get_map();

            $fp = fopen($this->functional_asset_path().'map.conf', 'w+');
            if ($fp) {
                fwrite($fp, $rt_nginx_map);
                fclose($fp);
            }
        }

    }

    /**
     * Update map when new blog added in multisite.
     *
     * @global object $nginx_purger Nginx purger class object.
     *
     * @param  string  $blog_id  blog id.
     */
    public function update_new_blog_options($blog_id)
    {

        global $nginx_purger;

        $nginx_purger->log("New site added ( id $blog_id )");
        $this->update_map();
        $nginx_purger->log("New site added to nginx map ( id $blog_id )");
        $helper_options = $this->nginx_helper_default_settings();
        update_blog_option($blog_id, 'rt_wp_nginx_helper_options', $helper_options);
        $nginx_purger->log("Default options updated for the new blog ( id $blog_id )");

    }

    /**
     * Parse sitemap content and extract all URLs.
     *
     * @param  string  $sitemap_url  The URL of the sitemap.
     * @return array|WP_Error An array of URLs or WP_Error on failure.
     */
    private function extract_sitemap_urls($sitemap_url)
    {
        $response = wp_remote_get($sitemap_url);

        $urls = [];

        if (is_wp_error($response)) {
            return $urls;
        }

        $sitemap_content = wp_remote_retrieve_body($response);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($sitemap_content);

        if ($xml === false) {
            return new WP_Error('sitemap_parse_error', esc_html__('Failed to parse the sitemap XML', 'nginx-helper'));
        }

        $urls = [];

        if ($xml === false) {
            return $urls;
        }

        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }

        return $urls;
    }

    /**
     * Fetches all the sitemap urls for the site.
     *
     * @return array
     */
    private function get_index_sitemap_urls()
    {
        $sitemaps = wp_sitemaps_get_server()->index->get_sitemap_list();
        $urls = [];
        foreach ($sitemaps as $sitemap) {
            $urls[] = $sitemap['loc'];
        }

        return $urls;
    }

    /**
     * This function preloads the cache from sitemap url.
     *
     * @return void
     */
    private function preload_cache_from_sitemap()
    {

        $sitemap_urls = $this->get_index_sitemap_urls();
        $all_urls = [];

        foreach ($sitemap_urls as $sitemap_url) {
            $urls = $this->extract_sitemap_urls($sitemap_url);
            $all_urls = array_merge($all_urls, $urls);
        }

        $args = [
            'timeout' => 1,
            'blocking' => false,
            'sslverify' => false,
        ];

        foreach ($all_urls as $url) {
            wp_remote_get(esc_url_raw($url), $args);
        }

    }
}
