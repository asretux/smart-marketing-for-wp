<?php
require_once(ABSPATH . '/wp-admin/includes/plugin.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://www.e-goi.com
 * @package    Egoi_For_Wp
 * @subpackage Egoi_For_Wp/admin
 * @author     E-goi <info@e-goi.com>
 */
class Egoi_For_Wp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	const API_OPTION = 'egoi_api_key';
	const OPTION_NAME = 'egoi_sync';
	const BAR_OPTION_NAME = 'egoi_bar_sync';
	const FORM_OPTION_1 = 'egoi_form_sync_1';
	const FORM_OPTION_2 = 'egoi_form_sync_2';
	const FORM_OPTION_3 = 'egoi_form_sync_3';
	const FORM_OPTION_4 = 'egoi_form_sync_4';
	const FORM_OPTION_5 = 'egoi_form_sync_5';
	const FORM_OPTION_6 = 'egoi_form_sync_6';
	const FORM_OPTION_7 = 'egoi_form_sync_7';
	const FORM_OPTION_8 = 'egoi_form_sync_8';
	const FORM_OPTION_9 = 'egoi_form_sync_9';
	const FORM_OPTION_10 = 'egoi_form_sync_10';

	/**
	 * Limit Subscribers
	 *
	 *
	 * @var integer
	 */
	private $limit_subs = 10000;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Server Protocol
	 *
	 * @var string
	 */
	protected $protocol;

	/**
	 * Server Port if is in use
	 *
	 * @var string
	 */
	protected $port;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version, $debug = false) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->server_url = $_SERVER['REQUEST_URI'];
		$this->protocol = $_SERVER['HTTPS'] ?: 'http://';
		$this->port = ':'.$_SERVER['SERVER_PORT'];

		//settings pages
		$this->load_api = $this->load_api();
		$this->options_list = $this->load_options();
		$this->bar_post = $this->load_options_bar();
		if(isset($_GET['form'])){
			$id = $_GET['form'];
			$this->form_post = $this->load_options_forms($id);
		}

		// register options
		register_setting( Egoi_For_Wp_Admin::API_OPTION, Egoi_For_Wp_Admin::API_OPTION);
		register_setting( Egoi_For_Wp_Admin::OPTION_NAME, Egoi_For_Wp_Admin::OPTION_NAME);
		register_setting( Egoi_For_Wp_Admin::BAR_OPTION_NAME, Egoi_For_Wp_Admin::BAR_OPTION_NAME);

		// register forms
		register_setting( Egoi_For_Wp_Admin::FORM_OPTION_1, Egoi_For_Wp_Admin::FORM_OPTION_1);
		register_setting( Egoi_For_Wp_Admin::FORM_OPTION_2, Egoi_For_Wp_Admin::FORM_OPTION_2);
		register_setting( Egoi_For_Wp_Admin::FORM_OPTION_3, Egoi_For_Wp_Admin::FORM_OPTION_3);
		register_setting( Egoi_For_Wp_Admin::FORM_OPTION_4, Egoi_For_Wp_Admin::FORM_OPTION_4);
		register_setting( Egoi_For_Wp_Admin::FORM_OPTION_5, Egoi_For_Wp_Admin::FORM_OPTION_5);

		// hooks Core
        //add_action('init', array($this, 'smsnf_clean_te_user_id'), 10, 1);

		if(!isset($_GET['key']) || substr($_GET['key'], 0, 8) != 'wc_order') {
			add_action('wp_footer', array($this, 'hookEcommerce'), 10, 1);
		} else {
			add_action('wp_loaded', array($this, 'hookEcommerce'), 10, 1);
		}

		if(strpos($this->server_url, 'egoi') !== false){
			// HOOK TO CHANGE DEFAULT FOOTER TEXT
			add_filter('admin_footer_text', array($this, 'remove_footer_admin'), 1, 2);
		}

		// hooks Woocommerce
		//add_action('woocommerce_order_details_after_order_table', array($this, 'hookEcommerce'), 10, 3);
		add_action('woocommerce_add_to_cart', array($this, 'hookEcommerce'), 10, 3);
		add_action('woocommerce_after_cart_item_quantity_update', array($this, 'hookCartQuantityUpdate'), 10, 3);
		add_action('woocommerce_before_cart_item_quantity_zero', array($this, 'hookCartQuantityUpdate'), 10, 3);
		add_action('woocommerce_cart_updated', array($this, 'hookRemoveItem'), 10, 3);
		add_action('woocommerce_checkout_order_processed', array($this, 'hookProcessOrder'), 10, 1);
        add_action('woocommerce_widget_shopping_cart_buttons', array($this, 'hookRemoveItem'), 11);

		// paypal
		add_action('valid-paypal-standard-ipn-request', array($this, 'hookIpnResponse'), 10, 1);

		// after billing form
		add_action('woocommerce_after_checkout_billing_form', array($this, 'hookWoocommercePostBilling'), 10);

		// hook contact form 7
		add_action('wpcf7_submit', array($this, 'getContactForm'), 10, 1);

		// hook comment form
		add_action('comment_post', array($this, 'insertCommentHook'), 10, 3);
		add_action('comment_form_after_fields', array($this, 'checkNewsletterPostComment'), 10, 1);

		//Sets up a JSON endpoint at /wp-json/egoi/v1/products_data/
		add_action( 'rest_api_init', array($this, 'egoi_products_data_api_init'), 10, 3) ;


		// Map shortcode to Visual Composer
		if ( function_exists( 'vc_lean_map' ) ) {
			vc_lean_map( 'egoi_vc_shortcode', array( $this, 'egoi_vc_shortcode_map' ) );
		}

		// Add widget to main WP dashboard
        add_action( 'wp_dashboard_setup', array($this, 'smsnf_main_dashboard_widget') );

		// hook map fields to E-goi
		$this->mapFieldsEgoi();

		$rmdata = $_POST['rmdata'];
		if(isset($rmdata) && ($rmdata)){
			$this->saveRMData($rmdata);
		}
	}

	public function smsnf_main_dashboard_widget() {
        wp_add_dashboard_widget(
            'egoi_main_dashboard_widget',         // Widget slug.
            'E-goi',         // Title.
            array($this, 'smsnf_main_dashboard_widget_content') // Display function.
        );
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		//only load CSS on smart marketing pages or in pages with smart marketing elements
		if(strpos(get_current_screen()->id, 'smart-marketing') !== false ||
            strpos(get_current_screen()->id, 'widgets') !== false ||
            strpos(get_current_screen()->id, 'shop-order') !== false
        ) {

			wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/egoi-for-wp-admin.min.css', array(), $this->version, 'all' );
			wp_enqueue_style('wp-color-picker');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		//only load CSS on smart marketing pages or in pages with smart marketing elements
		if(strpos(get_current_screen()->id, 'smart-marketing') !== false ||
            strpos(get_current_screen()->id, 'widgets') !== false ||
            strpos(get_current_screen()->id, 'shop-order') !== false
        ) {

			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/egoi-for-wp-admin.js', array('jquery'), $this->version, false);

			wp_register_script('custom-script1', plugin_dir_url(__FILE__) . 'js/capture.min.js', array('jquery'));
			wp_enqueue_script('custom-script1');

			wp_register_script('custom-script2', plugin_dir_url(__FILE__) . 'js/forms.min.js', array('jquery'));
			wp_enqueue_script('custom-script2');

			wp_register_script('custom-script3', plugin_dir_url(__FILE__) . 'js/egoi-for-wp-map.js', array('jquery'));
			wp_enqueue_script('custom-script3');

			wp_register_script('custom-script4', plugin_dir_url(__FILE__) . 'js/egoi-for-wp-widget.js', array('jquery'));
			wp_enqueue_script('custom-script4');

			wp_register_script('custom-script5', plugin_dir_url(__FILE__) . 'js/clipboard.min.js', array('jquery'));
			wp_enqueue_script('custom-script5');


			wp_enqueue_script('wp-color-picker');

			wp_localize_script($this->plugin_name, 'url_egoi_script', array('ajaxurl' => admin_url('admin-ajax.php')));

	        wp_enqueue_script( 'smsnf-notifications-ajax-script', plugin_dir_url( __FILE__ ) . 'js/egoi-for-wp-notifications.js', array('jquery') );
	        wp_localize_script( 'smsnf-notifications-ajax-script', 'smsnf_notifications_ajax_object', array('ajax_url' => admin_url( 'admin-ajax.php' )) );

	        if (get_current_screen()->id == 'smart-marketing_page_egoi-4-wp-dashboard') {
	            wp_register_script('chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js');
	            wp_enqueue_script('chartjs');

	            wp_enqueue_script( 'smsnf-dashboard-ajax-script', plugin_dir_url( __FILE__ ) . 'js/egoi-for-wp-dashboard.js', array('jquery') );
	            wp_localize_script( 'smsnf-dashboard-ajax-script', 'smsnf_dashboard_ajax_object', array('ajax_url' => admin_url( 'admin-ajax.php' )) );
	        }
    	}
	}

	/**
	 * Remove footer for the admin area.
	 *
	 * @since    1.1.0
	 */
	public function remove_footer_admin(){

		$url = 'https://wordpress.org/support/plugin/smart-marketing-for-wp/reviews/?filter=5';
        $text = sprintf( esc_html__( 'Please rate %sSmart Marketing SMS and Newsletters Forms%s %s on %sWordPress.org%s to help us spread the word. Thank you from the E-goi team!', 'egoi-for-wp' ), '<strong>', '</strong>', '<a class="" href="' .  $url . '" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a>', '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">', '</a>' );
        return $text;
	}

	/**
	 * Add Admin menu
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		add_menu_page( 'Smart Marketing - Main Page', 'Smart Marketing', 'Egoi_Plugin', $this->plugin_name, array($this, 'display_plugin_setup_page'), plugin_dir_url( __FILE__ ).'img/logo_small.png');

        $capability = 'manage_options';
		$apikey = get_option('egoi_api_key');
		$haslists = get_option('egoi_has_list');
		if(isset($apikey['api_key']) && ($apikey['api_key']) && ($haslists)) {

            add_submenu_page($this->plugin_name, __('Dashboard', 'egoi-for-wp'), __('Dashboard', 'egoi-for-wp'), $capability, 'egoi-4-wp-dashboard', array($this, 'display_plugin_dashboard'));

			add_submenu_page($this->plugin_name, __('Capture Contacts', 'egoi-for-wp'), __('Capture Contacts', 'egoi-for-wp'), $capability, 'egoi-4-wp-form', array($this, 'display_plugin_subscriber_form'));

			add_submenu_page($this->plugin_name, __('Sync Contacts', 'egoi-for-wp'), __('Sync Contacts', 'egoi-for-wp'), $capability, 'egoi-4-wp-subscribers', array($this, 'display_plugin_subscriber_page'));

			add_submenu_page($this->plugin_name, __('Ecommerce', 'egoi-for-wp'), __('Ecommerce', 'egoi-for-wp'), $capability, 'egoi-4-wp-ecommerce', array($this, 'display_plugin_subscriber_ecommerce'));

			add_submenu_page($this->plugin_name, __('Integrations', 'egoi-for-wp'), __('Integrations', 'egoi-for-wp'), $capability, 'egoi-4-wp-integrations', array($this, 'display_plugin_integrations'));

            add_submenu_page($this->plugin_name, __('Web Push', 'egoi-for-wp'), __('Web Push', 'egoi-for-wp'), $capability, 'egoi-4-wp-webpush', array($this, 'display_plugin_webpush'));

            add_submenu_page($this->plugin_name, __('RSS Feed', 'egoi-for-wp'), __('RSS Feed', 'egoi-for-wp'), $capability, 'egoi-4-wp-rssfeed', array($this, 'display_plugin_rssfeed'));

		}

        add_submenu_page($this->plugin_name, __('Account', 'egoi-for-wp'), __('Account', 'egoi-for-wp'), $capability, 'egoi-4-wp-account', array($this, 'display_plugin_setup_page'));
	}

	public function add_action_links($links) {

		$link_account = 'egoi-4-wp-account';
	   	$settings_link = array(
	    '<a href="'.admin_url('admin.php?page='.$link_account).'">'.__('Settings', $this->plugin_name).'</a>');
	   	return array_merge(  $settings_link, $links );
	}

	public function del_action_link($actions) {

		if (array_key_exists('edit', $actions )){
			unset($actions ['edit']);
		}
		return $actions;
	}

	public function display_plugin_setup_page() {
	    if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
	    	include_once( 'partials/egoi-for-wp-admin-display.php' );
	    }
	}

	public function display_plugin_lists_page() {
		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-lists.php' );
		}
	}

	public function display_plugin_subscriber_page() {

		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-subscribers.php' );
		}

	}

	public function display_plugin_subscriber_bar_page() {

		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-bar.php' );
		}

	}

	public function display_plugin_subscriber_form() {

		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-forms.php' );
		}

	}

	public function display_plugin_subscriber_widget() {

		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-widget.php' );
		}

	}

	public function display_plugin_subscriber_ecommerce() {

		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-ecommerce.php' );
		}

	}

	public function display_plugin_integrations() {

		if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    } else {
			include_once( 'partials/egoi-for-wp-admin-integrations.php' );
		}

	}

    public function display_plugin_webpush() {

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        } else {
            include_once( 'partials/egoi-for-wp-admin-webpush.php' );
        }

    }

    public function display_plugin_rssfeed() {

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        } else {
            include_once( 'partials/egoi-for-wp-admin-rssfeed.php' );
        }

    }

    public function display_plugin_dashboard() {

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        } else {
            include_once( 'partials/egoi-for-wp-admin-dashboard.php' );
        }

    }

	private function load_api() {

		static $api_defaults = array(
			'api_key' => ''
		);

    	if(!get_option( self::API_OPTION, array() )) {
    		add_option( self::API_OPTION, array($api_defaults) );
    	}else{
			$options = (array) get_option( self::API_OPTION, array() );

			$options = array_merge($api_defaults, $options);
			return (array) apply_filters( 'egoi_api_key', $options );
		}
	}

	private function load_options() {

		static $defaults = array(
			'list' => '',
			'enabled' => 0,
			'track' => 1,
			'role' => 'All'
		);

    	if(!get_option( self::OPTION_NAME, array() )) {
    		add_option( self::OPTION_NAME, array($defaults) );
    	}else{
			$options = (array) get_option( self::OPTION_NAME, array() );

			$options = array_merge($defaults, $options);
			return (array) apply_filters( 'egoi_sync_options', $options );
		}
	}

	private function load_options_bar() {

		static $bar_defaults = array(
			'list' => '',
			'double_optin' => 0,
			'send_welcome' => 0,
			'enabled' => 0,
			'open' => 0,
			'text_bar' => '',
			'text_email_placeholder' => '',
			'text_button' => '',
			'position' => 'top',
			'size' => '',
			'color_bar' => '',
			'border_color' => '#ccc',
			'border_px' => '1px',
			'color_text' => '',
			'bar_text_color' => '',
			'sticky' => 0,
			'color_button' => '',
			'color_button_text' => '',
			'success_bgcolor' => '#5cb85c',
			'error_bgcolor' => '#d9534f',
			'text_subscribed' => '',
			'text_invalid_email' => '',
			'text_already_subscribed' => '',
			'text_error' => '',
			'redirect' => ''
		);

    	if(!get_option( self::BAR_OPTION_NAME, array() )) {
    		add_option( self::BAR_OPTION_NAME, array($bar_defaults) );
    	}else{
			$bar_post = (array) get_option( self::BAR_OPTION_NAME, array() );

			$bar_post = array_merge($bar_defaults, $bar_post);
			return (array) apply_filters( 'egoi_bar_sync_options', $bar_post );
		}
	}

	private function load_options_forms($id) {

		static $form_defaults = array(
			'list' => '',
			'enabled' => 1,
			'show_title' => 0,
			'egoi' => '',
			'form_id' => '',
			'form_name' => '',
			'form_content' => '',
			'width' => '',
			'height' => '',
			'border_color' => '',
			'border' => ''
		);

		switch($id) {
			case '1':
				$foption = self::FORM_OPTION_1;
				break;
			case '2':
				$foption = self::FORM_OPTION_2;
				break;
			case '3':
				$foption = self::FORM_OPTION_3;
				break;
			case '4':
				$foption = self::FORM_OPTION_4;
				break;
			case '5':
				$foption = self::FORM_OPTION_5;
				break;
		}

    	if(!get_option( $foption, array() )) {
    		add_option( $foption, array($form_defaults) );
    	}else{

			$form_post = (array) get_option( $foption, array() );
			$form_post = array_merge($form_defaults, $form_post);
			return (array) apply_filters( 'egoi_form_sync', $form_post );
		}
	}

	public function execEc($client_id, $list_id, $user_email, $products = array(), $order_items = array(), $sum_price = false, $cart_zero = 0){

		return include dirname( __DIR__ ) . '/includes/ecommerce/t&e.php';
	}

	/*
	* -- HOOKS ---
	*/
	public function users_queue(){

		if(isset($_POST['submit']) && ($_POST['submit'])){

		    try {

			    $api = new Egoi_For_Wp();
			    $listID = $_POST['listID'];
			    $count_users = count_users();

			    if($count_users['total_users'] > $this->limit_subs){
			    	global $wpdb;
					$sql = "SELECT * FROM ".$wpdb->prefix."users LIMIT 100000";
					$users = $wpdb->get_results($sql);
			    }else{
					$users = get_users($args);
			    }

			    $current_user = wp_get_current_user();
			    $current_email = $current_user->data->user_email;

		    	if (class_exists('WooCommerce')) {
					$wc = new WC_Admin_Profile();
					foreach ($wc->get_customer_meta_fields() as $key => $value_field) {
						foreach($value_field['fields'] as $key_value => $label){
							$row_new_value = $api->getFieldMap(0, $key_value);
			                if($row_new_value){
								$woocommerce[$row_new_value] = $key_value;
							}
						}
					}
				}


		    	foreach ($users as $user) {
			        if($current_email != $user->user_email){
                        $user_meta = get_user_meta($user->ID);

                        if (isset($user->first_name) && $user->first_name != "" && isset($user->last_name) && $user->last_name != "") {
                            $fname = $user->first_name;
                            $lname = $user->last_name;
                        } else if (
                            (isset($user_meta['first_name'][0]) && $user_meta['first_name'][0] != "")
                            || (isset($user_meta['last_name'][0]) && $user_meta['last_name'][0] != "")
                        ) {
                            $fname = $user_meta['first_name'][0];
                            $lname = $user_meta['last_name'][0];
                        } else {
                            $name = $user->display_name ? $user->display_name : $user->user_login;
                            $full_name = explode(' ', $name);
                            $fname = $full_name[0];
                            $lname = $full_name[1];
                        }

			            $email = $user->user_email;
			            $url = $user->user_url;

			            $subscribers['status'] = 1;
		                $subscribers['email'] = $email;
		                $subscribers['cellphone'] = '';
		                $subscribers['fax'] = '';
		                $subscribers['telephone'] = '';
		                $subscribers['first_name'] = $fname;
		                $subscribers['last_name'] = $lname;
		                $subscribers['birth_date'] = '';
		                $subscribers['lang'] = '';

		                foreach($woocommerce as $key => $value){
		                    if (isset($user->$value)) {
                                $subscribers[str_replace('key', 'extra', $key)] = $user->$value;
                            } else if (isset($user_meta[$value][0])) {
                                $subscribers[str_replace('key', 'extra', $key)] = $user_meta[$value][0];
                            }
		                }

		                $subscribers['telephone'] = $api->smsnf_get_valid_phone($subscribers['telephone']);
                        $subscribers['cellphone'] = $api->smsnf_get_valid_phone($subscribers['cellphone']);

	                	$subs[] = $subscribers;
			        }
			    }

			    if(isset($subs) && count($subs) >= $this->limit_subs){
				    $subs = array_chunk($subs, $this->limit_subs, true);
				    for($x=0; $x<=9; $x++){
				    	$api->addSubscriberBulk($listID, $tags, $subs[$x]);
				    }
				}else{
					$api->addSubscriberBulk($listID, $tags, $subs);
				}

		    } catch(Exception $e) {
		    	$this->sendError('Bulk Subscription ERROR', $e->getMessage());
		    }

		}

		wp_die();
	}

	public function hookWoocommercePostBilling(){

		try {

			if (!is_user_logged_in()){
				echo '<p class="form-row form-row-wide">
				<input class="input-checkbox" type="checkbox" name="egoi_check_sync" id="egoi_check" value="1" checked>
				<label for="egoi_check" class="checkbox">'.__('Subscribe Newsletter', 'egoi-for-wp').'</label></p>';
			}

		} catch(Exception $e) {
	    	echo $e->getMessage();
	    	return false;
	    }
	}

	/**
	 * Global hook for deploy E-commerce events.
	 *
	 * @param 	 $cart_id
	 * @since    1.1.2
	 */
	public function hookEcommerce($cart_id = false){
		// for security reasons

		if(strpos($this->server_url, 'wp-json') !== false){
			return;
		}

		if(!is_admin()){
			if($cart_id){
				$this->hookCartUpdate();
			}else{

				if(!isset($_GET['wc-ajax']) || !$_GET['wc-ajax']){

					if(
					    (!isset($_GET['remove_item']) || !$_GET['remove_item'])
                        && ( !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (!strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])))
                    ) {

						$list_id = $this->options_list['list'];
						$track = $this->options_list['track'];

						if($track && $list_id){

							// check for saved addtocart event
							$validated_cart = $this->checkForCart();

							// check for saved Order event
							$validated_order = $this->checkForOrder();

							if($validated_cart && $validated_order){

                                if (isset($_GET['key'])) {
                                    $te_user_id = $this->smsnf_check_te_user_id();
                                    if ($te_user_id) {
                                        $te_order_id = get_option('egoi_te_order_id_'.$te_user_id);
                                        $test = get_option('egoi_track_order_'.$te_order_id);
                                        echo html_entity_decode($test[0], ENT_QUOTES);
                                    }
                                }else{

                                    // check && execute now for page view
                                    $client_info = get_option('egoi_client');
                                    if(isset($client_info->CLIENTE_ID) && ($client_info->CLIENTE_ID)) {

                                        $client_id = $client_info->CLIENTE_ID;

                                        $user = wp_get_current_user();
                                        $user_email = $user->data->user_email;

                                        echo $this->execEc($client_id, $list_id, $user_email);
                                    }
                                }

							}
						}
					}
				}
			}
		}
	}

	/**
	 * Remove product from E-commerce event AddToCart.
	 *
	 * @param 	 $cart_id
	 * @since    1.1.0
	 */
	public function hookRemoveItem(){
		if ( ( isset($_GET['removed_item']) && ($_GET['removed_item']) ) ||
            ( isset($_GET['wc-ajax']) && $_GET['wc-ajax'] == 'remove_from_cart' )
        ) {
			$this->hookCartUpdate();
		}
	}

	public function hookCartQuantityUpdate(){
		$this->hookCartUpdate();
	}

	/**
	 * Process E-commerce event AddToCart.
	 *
	 * @since    1.1.0
	 */
	public function hookCartUpdate(){

		$list_id = $this->options_list['list'];
		$track = $this->options_list['track'];

		if($track && $list_id){

			$client_info = get_option('egoi_client');
			if(isset($client_info->CLIENTE_ID) && ($client_info->CLIENTE_ID)) {

				$user = wp_get_current_user();
			 	$user_id = $user->data->ID;
			 	$user_email = $user->data->user_email;

				$client_id = $client_info->CLIENTE_ID;

                $te_user_id = $this->smsnf_check_te_user_id();

			 	$data = array(
			 		'client_id' => $client_id,
			 		'list_id' => $list_id,
			 		'user_email' => $user_email,
			 		'hash_cart' => $user_id ?: $te_user_id
			 	);

                if ($te_user_id) {
                    update_option('egoi_te_session_cart_' . $te_user_id, $data['hash_cart']);
                }

			 	$args = array(
			 		'post_type' => 'product'
			 	);
			 	$products = get_posts($args);

				$this->hookProcessCart($data, $products);
			}
		}
	}

	/**
	 * Process && Execute E-commerce events.
	 *
	 * @param 	 $data
	 * @param 	 $products
	 * @since    1.1.0
	 */
	public function hookProcessCart($data, $products = array()) {

		if(!empty($products) && ($data)){

			global $woocommerce;

			$client_id = $data['client_id'];
			$list_id = $data['list_id'];
			$user_email = $data['user_email'];
			$hash_cart = $data['hash_cart'];

			$sum_price = 0;
			$products = array();

			foreach($woocommerce->cart->get_cart() as $k => $product){

				$product_info = wc_get_product($product['data']->get_id());
				if (get_post_meta($product['product_id'], '_sale_price', true)) {
                    $price = get_post_meta($product['product_id'], '_sale_price', true);
                } else if (get_post_meta($product['product_id'], '_regular_price', true)) {
                    $price = get_post_meta($product['product_id'], '_regular_price', true);
                } else {
                    $price = get_post_meta($product['product_id'], '_price', true);
                }

				$products[$k]['id'] = $product['product_id'];
				$products[$k]['name'] = $product_info->get_title();
				$products[$k]['cat'] = ' - ';
				$products[$k]['price'] = $price;
				$products[$k]['quantity'] = $product['quantity'];
				$sum_price += floatval(round($price * $product['quantity'],2));
			}

			$cart_zero = empty($products) ? 1 : 0;
			$te = $this->execEc($client_id, $list_id, $user_email, $products, array(), $sum_price, $cart_zero);
			$content = stripslashes(htmlspecialchars($te, ENT_QUOTES, 'UTF-8'));
			update_option('egoi_track_addtocart_'.$hash_cart, array($content));
		}
	}



	/**
	 * Process E-commerce event Order for Paypal.
	 *
	 * @param 	 $data
	 * @since    1.0.14
	 */
	public function hookIpnResponse($data){

		$order_data = unserialize(str_replace('\"', '"', $data['custom']));
        $order_id = $order_data[0];

		return $this->hookProcessOrder($order_id);
	}

	/**
	 * Process E-commerce event Order.
	 *
	 * @param 	 $data
	 * @since    1.0.5
	 */
	public function hookProcessOrder($order_id){

		try {

			$api = new Egoi_For_Wp();

			if (!is_user_logged_in()){
				if(!$_POST['createaccount']){
					if($_POST['egoi_check_sync']){
						$first_name = $_POST['billing_first_name'];
						$last_name = $_POST['billing_last_name'];
						$guest_email = $_POST['billing_email'];
                        $phone = $_POST['billing_phone'];
						$cellphone = $_POST['billing_cellphone'];
						$name = $first_name.' '.$last_name;

                        $phone = $api->smsnf_get_valid_phone($phone);
						$cellphone = $api->smsnf_get_valid_phone($cellphone);

						$api->addSubscriber($this->options_list['list'], $name, $guest_email, '', 1, $cellphone, 'Guest', $phone);
					}
				}
			}

			$track = $this->options_list['track'];
			$list_id = $this->options_list['list'];
			if($track && $list_id){

			 	$client_info = get_option('egoi_client');
				if(isset($client_info->CLIENTE_ID) && ($client_info->CLIENTE_ID)) {

					$user = wp_get_current_user();

                    if($user->data->user_email){
                        $user_email = $user->data->user_email;
                    }
                    else{
                        $user_email = $_POST['billing_email']; //if user is guest
                    }

					$client_id = $client_info->CLIENTE_ID;

					$order = new WC_Order($order_id);
					$items = $order->get_items();

					$products = array();
					foreach($items as $k => $item){

                        if (get_post_meta($product['product_id'], '_sale_price', true)) {
                            $price = get_post_meta($product['product_id'], '_sale_price', true);
                        } else if (get_post_meta($product['product_id'], '_regular_price', true)) {
                            $price = get_post_meta($product['product_id'], '_regular_price', true);
                        } else {
                            $price = get_post_meta($product['product_id'], '_price', true);
                        }

						$products[$k]['id'] = $item['product_id'];
						$products[$k]['name'] = $item['name'];
						$products[$k]['cat'] = ' - ';
						$products[$k]['price'] = $price;
						$products[$k]['quantity'] = $item['qty'];
					}

					$order_items = array(
						'order_id' => $order_id,
						'order_total' => $order->get_total(),
						'order_subtotal' => $order->get_subtotal(),
						'order_tax' => $order->get_total_tax(),
						'order_shipping' => $order->get_total_shipping(),
						'order_discount' => $order->get_total_discount()
					);
					$te = $this->execEc($client_id, $list_id, $user_email, $products, $order_items);
					$content = stripslashes(htmlspecialchars($te, ENT_QUOTES, 'UTF-8'));
					update_option('egoi_track_order_'.$order_id, array($content));
                    $te_user_id = $this->smsnf_check_te_user_id();
                    if ($te_user_id) {
                        update_option('egoi_te_order_id_' . $te_user_id, $order_id);
                    }

				}
			}

			return false;

		} catch(Exception $e) {
	    	$this->sendError('WooCommerce - Order ERROR', $e->getMessage());
	    }

	}

	/**
	 * Checks && Executes E-commerce event if exists.
	 *
	 * @param 	 $data
	 * @since    1.1.2
	 */
	public function checkForCart(){
        $te_user_id = $this->smsnf_check_te_user_id();
        $te = get_option('egoi_te_session_cart_'.$te_user_id);

		if((isset($te) && $te) || get_option('egoi_te_session_cart_on')){

		    $option = isset($te) && $te ? 'egoi_track_addtocart_'.$te : 'egoi_track_addtocart_on';

			$content = get_option($option);

			echo html_entity_decode($content[0], ENT_QUOTES);

			delete_option('egoi_track_addtocart_'.$te);
            delete_option('egoi_track_addtocart_on');
			delete_option('egoi_te_session_cart_'.$te_user_id);
            delete_option('egoi_te_session_cart_on');

			return false;
		}

		return true;
	}

	/**
	 * Checks && Executes E-commerce event if exists.
	 *
	 * @param 	 $data
	 * @since    1.1.2
	 */
	public function checkForOrder(){

		if (!isset($_GET['key']) || substr($_GET['key'], 0, 8) != 'wc_order'){
            $te_user_id = $this->smsnf_check_te_user_id();
		    $te_order_id = get_option('egoi_te_order_id_'.$te_user_id);
			if((isset($te_order_id) && $te_order_id) || get_option('egoi_te_order_id_on')){

			    $order_id = isset($te_order_id) && $te_order_id ? $te_order_id : 'on';

				$content = get_option('egoi_track_order_'.$order_id);

				echo html_entity_decode($content[0], ENT_QUOTES);

				delete_option('egoi_track_order_'.$order_id);
				delete_option('egoi_te_order_id_'.$te_user_id);
                delete_option('egoi_track_order_on');
                delete_option('egoi_te_order_id_on');

				return false;
			}
		}

		return true;
	}

	/**
	 * Process data from ContactForm7 POST events.
	 *
	 * @param 	 $result
	 * @since    1.0.1
	 */
	public function getContactForm($result){

		try {

			if(class_exists('WPCF7_ContactForm')){

				$opt = get_option('egoi_int');
				$egoi_int = $opt['egoi_int'];

				if($egoi_int['enable_cf']) {

					$api = new Egoi_For_Wp();

					$form_id = $_POST['_wpcf7'];
					if(in_array($form_id, $opt['contact_form'])) {

						$key_name = 'your-name';
						$key_email = 'your-email';
						if(strpos($result->form, $key_name) !== false){
							$name = $_POST[$key_name];
						}else{
							if($_POST['first_name']){
								$name = $_POST['first_name'];
							}
						}

						if($_POST['last_name']){
							$lname = $_POST['last_name'];
						}

						if(strpos($result->form, $key_email) !== false){
							$email = $_POST[$key_email];
						}else{
							$match = array_filter(
								$_POST,
									function($value) {
										return preg_match("/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i", $value);
									}
								);

							$key = array_keys($match);
							$email = $_POST[$key[0]];
						}

						// telephone
						$get_tel = explode('[tel ', $result->form);
						$str_tel = explode(' ', strstr($get_tel[1], ']', true));
						$tel = $_POST[$str_tel[0]];

						// cellphone
						foreach ($_POST as $key_cell => $value_cell) {
							$cell = strpos($key_cell, 'cell');
							if ($cell !== false) {
								$mobile[] = $value_cell;
							}
						}
						$cell = $mobile[0];

						// birthdate
						$get_bd = explode('[date ', $result->form);
						$str_bd = explode(' ', strstr($get_bd[1], ']', true));
						$bd = $_POST[$str_bd[0]];

						// fax
						if($_POST['egoi-fax']){
							$fax = $_POST['egoi-fax'];
						}

						// lang
						if($_POST['egoi-lang']){
							$lang = $_POST['egoi-lang'];
						}

						// extra fields
						foreach ($_POST as $key => $value) {
							if(is_array($value)){
								$indval = 0;
								foreach ($value as $option_val) {
									$extra_fields[$key] .= $option_val.'; ';
								}
							}else{
								$exra = strpos($key, 'extra_');
								if ($exra !== false) {
									$extra_fields[$key] = $value;
								}
							}
						}

						if(!empty($extra_fields)){
							$option = 1;
						}

						$ref_fields = array('tel' => $tel, 'cell' => $cell, 'bd' => $bd, 'fax' => $fax, 'lang' => $lang);

						$subject = $_POST['your-subject'];
						$status = $_POST['status-egoi'];
						$error_msg = $result->prop('messages');
						$error_sent = $error_msg['mail_sent_ng'];

						// get contact form 7 name tag
						$cf7 = $api->getContactFormInfo($form_id);

						// check if subscriber exists
						$get = $api->getSubscriber($egoi_int['list_cf'], $email);
						if($get->subscriber->STATUS != '2'){

							if($get->subscriber->EMAIL == $email){
								$update = $egoi_int['edit'];
								if($update){

									if($subject){ // check if tag exists in E-goi
										$get_tags = $api->getTag($subject);
						                $tag = isset($get_tags['ID']) ? $get_tags['ID'] : $get_tags['NEW_ID'];
							        }

						       		// check if tag cf7 exists in E-goi
									$get_tg = $api->getTag($cf7[0]->post_title);
				                	$cf7tag = isset($get_tg['ID']) ? $get_tg['ID'] : $get_tg['NEW_ID'];

									$api->editSubscriber(
										$egoi_int['list_cf'],
										$email,
										array($cf7tag, $tag ? $tag : 0),
										$name,
										$lname,
										$extra_fields,
										$option,
										$ref_fields
									);
								}

							}else{

								if($subject){ // check if tag exists in E-goi
									$get_tags = $api->getTag($subject);
					                $tag = isset($get_tags['ID']) ? $get_tags['ID'] : $get_tags['NEW_ID'];
						        }

						        // check if tag cf7 exists in E-goi
								$get_tg = $api->getTag($cf7[0]->post_title);
				                $cf7tag = isset($get_tg['ID']) ? $get_tg['ID'] : $get_tg['NEW_ID'];

								$api->addSubscriberTags(
									$egoi_int['list_cf'],
									$email,
									array($cf7tag, $tag ? $tag : 0),
									$name,
									$lname,
									1,
									$extra_fields,
									$option,
									$ref_fields,
									$status
								);
							}
						}else{
							echo $error_sent;
						}
					}

				}
			}

		} catch(Exception $e) {
		    $this->sendError('ContactForm7 ERROR', $e->getMessage());
		}

	}

	/**
	 * Process data from CorePostComments.
	 *
	 * @param 	 $id
	 * @param 	 $approved
	 * @param 	 $data
	 * @since    1.0.0
	 */
	public function insertCommentHook($id, $approved = false, $data) {

		$opt = get_option('egoi_int');
		$egoi_int = $opt['egoi_int'];

		if($egoi_int['enable_pc']) {

			$name = $data['comment_author'];
			$email = $data['comment_author_email'];
		 	$check = $_POST['check_newsletter'];

		 	if($check == 'on') {

				$api = new Egoi_For_Wp();
				$add = $api->addSubscriberTags($egoi_int['list_cp'], $email, array($tag), $name, 1);

				if($add->UID){
					if($egoi_int['redirect']){
						wp_redirect($egoi_int['redirect']);
						exit;
					}
				}

			}else{
				return false;
			}

		}
	}

	/**
	 * Check if form is available for newsletter.
	 *
	 * @since    1.0.0
	 */
	public function checkNewsletterPostComment() {

		$opt = get_option('egoi_int');
		$egoi_int = $opt['egoi_int'];

		if($egoi_int['enable_pc']) {
			$check =  "<p class='comment-form-check-newsletter'><label for='check_newsletter'>".__('I want to receive newsletter', 'egoi-for-wp')."</label>
						<input type='checkbox' name='check_newsletter' id='check_newsletter'><p>";
			echo $check;
			return '';
		}
	}

	/**
	 * Map custom fields with Core / Woocommerce to E-goi.
	 *
	 * @since    1.0.6
	 */
	public function mapFieldsEgoi() {

		$id = (int)$_POST["id_egoi"];
		$token = (int)$_POST["token_egoi_api"];
		$wp = $_POST["wp"];
		$egoi = $_POST["egoi"];

		if(isset($token) && ($wp) && ($egoi)){

			global $wpdb;

			$table = $wpdb->prefix."egoi_map_fields";
			$wp_name = $_POST["wp_name"];
			$egoi_name = $_POST["egoi_name"];

			$wp = sanitize_text_field($wp);
			$egoi = sanitize_text_field($egoi);

			$values = array(
				'wp' => $wp,
				'wp_name' => $wp_name,
				'egoi' => $egoi,
				'egoi_name' => $egoi_name,
				'status' => '1'
			);

			$sql_exists = "SELECT COUNT(*) AS COUNT FROM $table WHERE wp='$wp' OR egoi='$egoi'";
			$exists = $wpdb->get_results($sql_exists);

			if(!$exists[0]->COUNT){
				$wpdb->insert($table, $values);

				if($wpdb->insert_id){
					if(!get_option('egoi_mapping')){
						add_option('egoi_mapping', 'true');
					}

				 	$sql="SELECT * FROM $table order by id DESC LIMIT 1";
                 	$rows = $wpdb->get_results($sql);
                 	foreach ($rows as $post){
						echo "<tr id='egoi_fields_$post->id'>";
						$wc = explode('_', $post->wp);
						if(($wc[0] == 'billing') || ($wc[0] == 'shipping')){
							echo "<td style='border-bottom: 1px solid #ccc;font-size: 16px;'>".$post->wp_name." (WooCommerce)</td>";
						}else{
							echo "<td style='border-bottom: 1px solid #ccc;font-size: 16px;'>".$post->wp_name."</td>";
						}
						echo "<td style='border-bottom: 1px solid #ccc;font-size: 16px;'>".$post->egoi_name."</td>
						<td><button type='button' id='field_$post->id' class='egoi_fields button button-secondary' data-target='$post->id'>
							<span class='dashicons dashicons-trash'></span>
							</button>
						</td></tr>";
                 	}
				}

			}else{
				echo "ERROR";
			}
			exit;
			//return '';

		}else if(isset($id) && ($id != '')){

			global $wpdb;

			$values = array(
				'id' => $id
			);

			$table = $wpdb->prefix."egoi_map_fields";
			$wpdb->delete($table, $values);

			$sql = "SELECT COUNT(*) FROM $table";
			$count = $wpdb->get_results($sql);
			if($count[0]->COUNT == 0){
				delete_option('egoi_mapping');
			}

			exit;
		}
	}

	private function saveRMData($post = false) {

		if(!get_option('egoi_data'))
			add_option('egoi_data', $post);

		update_option('egoi_data', $post);
		exit;
	}

	/*
	* Debug
	*/
	private function sendError($subject, $message) {

		$path = dirname(__FILE__).'/logs/';

		$fp = fopen($path.'logs.txt', 'a+');
		fwrite($fp, $subject.': '.$message."\xA");
		fclose($fp);

		return '';
	}

	public function get_form_processed() {

        if(!empty($_POST)){
            $api = new Egoi_For_Wp();
            echo json_encode($api->getForms($_POST['listID']));
        }
        wp_die();
    }

    public function get_lists() {

        if(!empty($_POST)){
            $api = new Egoi_For_Wp();
            echo json_encode($api->getLists($_POST['listID']));
        }
        wp_die();
    }

    public function get_tags() {

        if(!empty($_POST)){
            $api = new Egoi_For_Wp();
            echo json_encode($api->getTags());
        }
        wp_die();
    }

    public function add_tag($name) {

        if(!empty($_POST)){
            $api = new Egoi_For_Wp();
            echo json_encode($api->addTag($name));
        }
        wp_die();
    }

    // ADD A SIMPLE FORM SUBSCRIBER
	public function subscribe_egoi_simple_form_add() {
        $api = new Egoi_For_Wp();

		// double opt-in
        $status = filter_var(stripslashes($_POST['egoi_double_optin']), FILTER_SANITIZE_STRING) == '1' ? 0 : 1;

		$result = $api->addSubscriberWpForm(
            filter_var($_POST['egoi_list'], FILTER_SANITIZE_NUMBER_INT),
            array(
                'email' => filter_var($_POST['egoi_email'], FILTER_SANITIZE_EMAIL),
                'cellphone' => filter_var($_POST['egoi_country_code']."-".$_POST['egoi_mobile'], FILTER_SANITIZE_STRING),
                'first_name' => filter_var(stripslashes($_POST['egoi_name']), FILTER_SANITIZE_STRING),
                'lang' => filter_var($_POST['egoi_lang'], FILTER_SANITIZE_EMAIL),
                'tags' => array(filter_var($_POST['egoi_tag'], FILTER_SANITIZE_NUMBER_INT)),
                'status' => $status,
            )
        );

		if (!isset($result->ERROR) && !isset($result->MODIFICATION_DATE) ) {

		    $form_id = filter_var($_POST['egoi_simple_form'], FILTER_SANITIZE_NUMBER_INT);
            $api->smsnf_save_form_subscriber($form_id, 'simple-form', $result);

			echo $this->check_subscriber($result).' ';
			_e('was successfully registered!', 'egoi-for-wp');
		} else if (isset($result->MODIFICATION_DATE)) {
			_e('Subscriber data from', 'egoi-for-wp');
			echo ' '.$this->check_subscriber($result).' ';
			_e('has been updated!', 'egoi-for-wp');
		} else if (isset($result->ERROR)) {
			if ($result->ERROR == 'NO_DATA_TO_INSERT') {
				_e('ERROR: no data to insert', 'egoi-for-wp');
			} else if ($result->ERROR == 'EMAIL_ADDRESS_INVALID_MX_ERROR') {
				_e('ERROR: e-mail address is invalid', 'egoi-for-wp');
			} else {
				_e('ERROR: invalid data submitted', 'egoi-for-wp');
			}

		}

		wp_die(); // this is required to terminate immediately and return a proper response

	}

	public function check_subscriber($subscriber_data) {
		$data = array('FIRST_NAME','EMAIL','CELLPHONE');
		foreach ($data as $value) {
            if ($subscriber_data->$value) {
                $subscriber = $subscriber_data->$value;
                break;
            }
        }
		return $subscriber;
	}

	//Sets up a JSON endpoint at /wp-json/egoi/v1/products_data/
	public function egoi_products_data_api_init() {
		$namespace = 'egoi/v1';
		register_rest_route( $namespace, '/products_data/', array(
			'methods' => 'GET',
			'callback' => array( $this, 'egoi_products_data_return' ),
			'args' => array(
				'ids' => array(
					'sanitize_callback'  => 'sanitize_text_field'
				),
				'decimal_sep' => array(
					'sanitize_callback'  => 'sanitize_text_field'
				),
				'decimal_space' => array(
					'sanitize_callback'  => 'sanitize_text_field'
				),
			),
		) );
	}

	//Outputs Easy Post data on the JSON endpoint
	public function egoi_products_data_return( WP_REST_Request $request ) {

		global $_wp_additional_image_sizes;

		// Get query strings params from request
		$params = $request->get_query_params('ids');


		$params["ids"] = filter_var($params["ids"], FILTER_SANITIZE_STRING);
		$ids = str_replace(" ","",$params["ids"]);
		$ids = explode(",",$ids);
		foreach ($ids as $value) {
			if (!is_numeric($value))
				die();
		}

		$args = array( 'post_type' => array('product', 'product_variation') , 'post__in' => $ids, 'numberposts' => -1);
		$products = get_posts( $args );

		$products_data = array();
		foreach ($products as $product) {
			if ( in_array($product->ID, $ids) ) {

				$sizes = array('thumbnail', 'medium', 'medium_large', 'large');
				foreach ($_wp_additional_image_sizes as $key => $value) {
					$sizes[] = $key;
				}
				foreach ($sizes as $size) {
					$image_sizes[$size] = "<img src='".get_the_post_thumbnail_url($product->ID, $size)."' />";
					$image_url[$size] = get_the_post_thumbnail_url($product->ID, $size);
				}
				$sku = get_post_meta( $product->ID, '_sku', true);
				$price = get_post_meta( $product->ID, '_regular_price', true);
				$sale = get_post_meta( $product->ID, '_sale_price', true);
				$sale_dates_from = get_post_meta( $product->ID, '_sale_price_dates_from', true);
				$sale_dates_to = get_post_meta( $product->ID, '_sale_price_dates_to', true);
				$image_gallery = $image[0];
				$upsell_ids = get_post_meta( $product->ID, '_upsell_ids', true);
				$crosssell_ids = get_post_meta( $product->ID, '_crosssell_ids', true);
				$manage_stock = get_post_meta( $product->ID, '_manage_stock', true);
				$stock_quantity = get_post_meta( $product->ID, '_stock', true);
				$stock_status = get_post_meta( $product->ID, '_stock_status', true);
				$weight = get_post_meta( $product->ID, '_weight', true);
				$length = get_post_meta( $product->ID, '_length', true);
				$width = get_post_meta( $product->ID, '_width', true);
				$height = get_post_meta( $product->ID, '_height', true);
				$shipping_class = get_the_terms($product->ID, 'product_shipping_class');
				$categories = get_the_terms( $product->ID, 'product_cat' );
				$tags = get_the_terms( $product->ID, 'product_tag' );
				$virtual = get_post_meta( $product->ID, '_virtual', true);
				$downloadable = get_post_meta( $product->ID, '_downloadable', true);
				$download_limit = get_post_meta( $product->ID, '_download_limit', true);
				$download_expiry = get_post_meta( $product->ID, '_download_expiry', true);
				$url = get_permalink($product->ID);

				if (isset($params['decimal_space']) && is_numeric($params['decimal_space'])) {
					$price = number_format($price, $params['decimal_space']);
					$sale = number_format($sale, $params['decimal_space']);
				}

				if (isset($params['decimal_sep'])) {
					$price = str_replace('.', str_replace('"', '', $params['decimal_sep']), $price);
					$sale = str_replace('.', str_replace('"', '', $params['decimal_sep']), $sale);
				}

				$products_data['items']['item'][] = array(
					'id' => $product->ID,
					'name' => $product->post_title,
					'sku' => $sku,
					'regular_price' => $price,
					'sale_price' => $sale,
					'sale_dates_from' => $sale_dates_from,
					'sale_dates_to' => $sale_dates_to,
					'image_thumbnail' => $image_sizes['thumbnail'],
					'image_thumbnail_URL' => $image_url['thumbnail'],
					'image_medium' => $image_sizes['medium'],
					'image_medium_URL' => $image_url['medium'],
					'image_medium_large' => $image_sizes['medium_large'],
					'image_medium_large_URL' => $image_url['medium_large'],
					'image_large' => $image_sizes['large'],
					'image_large_URL' => $image_url['large'],
					'image_home-blog-post' => $image_sizes['home-blog-post'],
					'image_home-blog-post_URL' => $image_url['home-blog-post'],
					'image_home-event-post' => $image_sizes['home-event-post'],
					'image_home-event-post_URL' => $image_url['home-event-post'],
					'image_event-detail-post' => $image_sizes['event-detail-post'],
					'image_event-detail-post_URL' => $image_url['event-detail-post'],
					'image_shop_thumbnail' => $image_sizes['shop_thumbnail'],
					'image_shop_thumbnail_URL' => $image_url['shop_thumbnail'],
					'image_shop_catalog' => $image_sizes['shop_catalog'],
					'image_shop_catalog_URL' => $image_url['shop_catalog'],
					'image_shop_thumbnail' => $image_sizes['shop_single'],
					'image_shop_thumbnail_URL' => $image_url['shop_single'],
					'upsell_ids' => $upsell_ids,
					'crosssell_ids' => $crosssell_ids,
					'manage_stock' => $manage_stock,
					'stock_quantity' => $stock_quantity,
					'stock_status' => $stock_status,
					'weight' => $weight,
					'length' => $length,
					'width' => $width,
					'height' => $height,
					'shipping_class' => $shipping_class[0],
					'excerpt' => $product->post_excerpt,
					'categories' => $categories,
					'tags' => $tags,
					'virtual' => $virtual,
					'downloadable' => $downloadable,
					'download_limit' => $download_limit,
					'download_expiry' => $download_expiry,
					'url' => $url
				);

			}
		}

		return $products_data;
	}


	//Map shortcode to Visual Composer
	public function egoi_vc_shortcode_map() {

        global $wpdb;

        $rows = $wpdb->get_results( " SELECT ID, post_title FROM ".$wpdb->prefix."posts WHERE post_type = 'egoi-simple-form'" );
        $shortcode_ids = array();
        foreach ($rows as $row) {
        	$shortcode_ids[$row->ID." - ".$row->post_title] = $row->ID;
        }
		return array(
			'name'        => 'E-goi',
      		'icon' 		  => plugin_dir_url( __FILE__ ) . "img/logo.png",
			'description' => 'Shortcode E-goi.',
			'base'        => 'egoi_vc_shortcode',
			'params'      => array(
				array(

					'type'       => 'dropdown',
					'heading'    => 'Shortcode ID',
					'param_name' => 'shortcode_id',
					'value'      => $shortcode_ids,
				),
			)
		);

	}


	//get all languages of a specific list
	public function get_languages() {
		//exit;
        if(!empty($_GET)){
            $api = new Egoi_For_Wp();
            echo json_encode($api->getLists($_['listID']));
        }
        wp_die();
    }


    /*
     * RSS Feed - handler for links
     */
    public function prepareUrl($complement = '') {
        if (strpos($_SERVER['REQUEST_URI'], '&del=')) {
            $url = substr($_SERVER['REQUEST_URI'], 0, -34);
        } else if (strpos($_SERVER['REQUEST_URI'], '&add=')) {
            $url = substr($_SERVER['REQUEST_URI'], 0, -6);
        } else if (strpos($_SERVER['REQUEST_URI'], '&edit=') || strpos($_SERVER['REQUEST_URI'], '&view=')) {
            $url = substr($_SERVER['REQUEST_URI'], 0, -35);
        } else {
            $url = $_SERVER['REQUEST_URI'];
        }
        return $url.$complement;
    }

    /*
     * RSS Feed - Create new feed
     */
    public function createFeed($post, $edit) {
        $code = filter_var($post['code'], FILTER_SANITIZE_STRING);
        $type = filter_var($post['type'], FILTER_SANITIZE_STRING);
        $categories = $post[substr($type,0,-1)."_categories_include"];
        $categories_exclude = $post[substr($type,0,-1)."_categories_exclude"];
        $tags = $post[substr($type,0,-1)."_tags_include"];
        $tags_exclude = $post[substr($type,0,-1)."_tags_exclude"];

        $rssfeed = array(
            'code' => $code,
            'name' => filter_var($post['name'], FILTER_SANITIZE_STRING),
            'max_characters' => filter_var($post['max_characters'], FILTER_SANITIZE_NUMBER_INT),
            'image_size' => filter_var($post['image_size'], FILTER_SANITIZE_STRING),
            'type' => filter_var($post['type'], FILTER_SANITIZE_STRING),
            'categories' => $categories,
            'categories_exclude' => $categories_exclude,
            'tags' => $tags,
            'tags_exclude' => $tags_exclude
        );
        if ($edit) {
            update_option('egoi_rssfeed_' . $code, $rssfeed);
        } else {
            add_option('egoi_rssfeed_' . $code, $rssfeed);
        }

        return true;
    }

    public function egoi_rss_feeds_content() {

        $feed = filter_var($_GET['feed'], FILTER_SANITIZE_STRING);
        $feed_configs = get_option($feed);

        // RSS Feed Head
        $this->egoi_rss_feed_head();

        /*
         * RSS Feed Content
         */
        $args = $this->get_egoi_rss_feed_args($feed_configs);

        $query = new WP_Query( $args );

        while ( $query->have_posts() ) : $query->the_post();

            $description = $this->egoi_rss_feed_description(get_the_excerpt(), $feed_configs['max_characters']);

            $all_content = implode(' ', get_extended(  get_post_field( 'post_content', get_the_ID() ) )) ;

            if ($feed_configs['type'] == 'products') {
                $product_cats = get_the_terms( get_the_ID(), 'product_cat' );
            } else {
                $price = false;
            }
            ?>
            <item>
                <title><?php the_title_rss() ?></title>
                <link><?php the_permalink_rss() ?></link>
                <?php if ( get_comments_number() || comments_open() ) : ?>
                    <comments><?php comments_link_feed(); ?></comments>
                <?php endif; ?>
                <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                <dc:creator><![CDATA[<?php the_author() ?>]]></dc:creator>
                <?php
                    if ($feed_configs['type'] == 'posts') {
                        the_category_rss('rss2');
                    } else {
                        foreach ( $product_cats as $cat ) {
                            ?>
                            <category><![CDATA[<?=$cat->name?>]]></category>
                            <?php
                        }
                    }
                ?>
                <guid isPermaLink="false"><?php the_guid(); ?></guid>

                <?php if ( has_post_thumbnail() ) {
                    $img_url = get_the_post_thumbnail_url(get_the_ID(), $feed_configs['image_size']);
                    $pos = strpos($img_url, "?");
                    if ($pos !== false) {
                        $img_url = substr($img_url, 0, $pos);
                    }

                    ?>
                    <enclosure url="<?php echo $img_url; ?>" type="image/jpg" />
                <?php } else if ($gallery = get_post_gallery_images( get_the_ID() )) {
                    foreach( $gallery as $img_url ) {
                        $pos = strpos($img_url, "?");
                        if ($pos !== false) {
                            $img_url = substr($img_url, 0, $pos);
                        }
                        ?><enclosure url="<?php echo $img_url; ?>" type="image/jpg" /><?php
                        break;
                    }
                } else  {
                    preg_match('~<img.*?src=["\']+(.*?)["\']+~', $all_content, $img);
                    if (isset($img[1])) {
                        $pos = strpos($img[1], "?");
                        if ($pos !== false) {
                            $img_url = substr($img[1], 0, $pos);
                        }
                        ?><enclosure url="<?php echo $img_url; ?>" type="image/jpg" /><?php
                    }
                }?>

                <?php if (get_option('rss_use_excerpt')) : ?>
                    <description><![CDATA[<?php echo $description; ?>]]></description>
                <?php else : ?>
                    <description><![CDATA[<?php echo $description; ?>]]></description>
                    <content:encoded><![CDATA[<?php echo the_content(); ?>]]></content:encoded>
                <?php endif; ?>
                <?php if ( get_comments_number() || comments_open() ) : ?>
                    <wfw:commentRss><?php echo esc_url( get_post_comments_feed_link(null, 'rss2') ); ?></wfw:commentRss>
                    <slash:comments><?php echo get_comments_number(); ?></slash:comments>
                <?php endif; ?>
                <?php rss_enclosure(); ?>
                <?php
                /**
                 * Fires at the end of each RSS2 feed item.
                 *
                 * @since 2.0.0
                 */

                do_action( 'rss2_item' );
                ?>
            </item>
        <?php endwhile; ?>
        </channel>
        </rss>
        <?php
    }

    public function egoi_rss_feed_head() {

        $this->feed_cleaner();

        /**
         * RSS2 Feed Template for displaying RSS2 Posts feed.
         *
         * @package WordPress
         */

        header('Content-Type: ' . feed_content_type('rss2') . '; charset=' . get_option('blog_charset'), true);

        echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';

        /**
         * Fires between the xml and rss tags in a feed.
         *
         * @since 4.0.0
         *
         * @param string $context Type of feed. Possible values include 'rss2', 'rss2-comments',
         *                        'rdf', 'atom', and 'atom-comments'.
         */
        do_action( 'rss_tag_pre', 'rss2' );
        ?>
    <rss version="2.0"
         xmlns:content="http://purl.org/rss/1.0/modules/content/"
         xmlns:wfw="http://wellformedweb.org/CommentAPI/"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:atom="http://www.w3.org/2005/Atom"
         xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
         xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
        <?php
        /**
         * Fires at the end of the RSS root to add namespaces.
         *
         * @since 2.0.0
         */
        do_action( 'rss2_ns' );
        ?>
    >

        <channel>
        <title><?php wp_title_rss(); ?></title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss("description") ?></description>
        <lastBuildDate><?php
            $date = get_lastpostmodified( 'GMT' );
            echo $date ? mysql2date( 'r', $date, false ) : date( 'r' );
            ?></lastBuildDate>
        <language><?php bloginfo_rss( 'language' ); ?></language>
        <sy:updatePeriod><?php
            $duration = 'hourly';

            /**
             * Filters how often to update the RSS feed.
             *
             * @since 2.1.0
             *
             * @param string $duration The update period. Accepts 'hourly', 'daily', 'weekly', 'monthly',
             *                         'yearly'. Default 'hourly'.
             */
            echo apply_filters( 'rss_update_period', $duration );
            ?></sy:updatePeriod>
        <sy:updateFrequency><?php
            $frequency = '1';

            /**
             * Filters the RSS update frequency.
             *
             * @since 2.1.0
             *
             * @param string $frequency An integer passed as a string representing the frequency
             *                          of RSS updates within the update period. Default '1'.
             */
            echo apply_filters( 'rss_update_frequency', $frequency );
            ?></sy:updateFrequency>
        <?php
        /**
         * Fires at the end of the RSS2 Feed Header.
         *
         * @since 2.0.0
         */
        do_action( 'rss2_head');
    }

    public function get_egoi_rss_feed_args($feed_configs) {

        if ($feed_configs['type'] == 'posts') {
            $cat_taxonomy = 'category';
            $tag_taxonomy = 'post_tag';
        } else if ($feed_configs['type'] == 'products') {
            $cat_taxonomy = 'product_cat';
            $tag_taxonomy = 'product_tag';
        }

        $args = array(
            'post_type' => substr($feed_configs['type'], 0, -1),
            'posts_per_page' => -1
        );
        if (isset($feed_configs['categories'])) {
            $args['tax_query'][] = array(
                'taxonomy' => $cat_taxonomy,
                'terms' =>  $feed_configs['categories']
            );
        }
        if (isset($feed_configs['categories_exclude'])) {
            $args['tax_query'][] = array(
                'taxonomy' => $cat_taxonomy,
                'terms' =>  $feed_configs['categories_exclude'],
                'operator' => 'NOT IN'
            );
        }
        if (isset($feed_configs['tags'])) {
            $args['tax_query'][] = array(
                'taxonomy' => $tag_taxonomy,
                'terms' =>  $feed_configs['tags']
            );
        }
        if (isset($feed_configs['tags_exclude'])) {
            $args['tax_query'][] = array(
                'taxonomy' => $tag_taxonomy,
                'terms' =>  $feed_configs['tags_exclude'],
                'operator' => 'NOT IN'
            );
        }
        return $args;
    }

    public function egoi_rss_feed_description($text, $num_max_chars) {
        $words = explode(' ', strip_tags($text));
        $words_num = $char_num = 0;
        foreach ($words as $word) {
            $char_num += strlen($word);
            $words_num++;
            if ($char_num >= $num_max_chars) {
                break;
            }
        }
        $excerpt = explode(' ', $text, $words_num);

        if (count($words)>$words_num) {
            array_pop($excerpt);
            $excerpt = implode(" ",$excerpt).' [...]';
        } else {
            $excerpt = implode(" ",$excerpt);
        }
        $description = preg_replace('`[[^]]*]`','',$excerpt);
        return $description;
    }

    public function feed_cleaner()
    {
        remove_all_filters('the_content_feed');
        remove_all_filters('the_excerpt_rss');
        remove_all_filters('the_content_rss');
        remove_all_filters('the_title_rss');
        remove_all_filters('comment_text_rss');
        remove_all_filters('post_comments_feed_link');
        remove_all_filters('author_feed_link');
        remove_all_filters('the_content_feed');
        remove_all_filters('comment_author_rss');
        remove_all_filters('pre_link_rss');
        remove_all_filters('bloginfo_rss');
        remove_all_filters('category_feed_link');
        remove_all_filters('the_category_rss');
        remove_all_filters('feed_link');

        remove_all_actions('commentrss2_item');
        remove_all_actions('do_feed_rss');
        remove_all_actions('do_feed_rss2');
        remove_all_actions('rss_head');
        remove_all_actions('rss_item');
        remove_all_actions('rss2_head');
        remove_all_actions('rss2_item');
        remove_all_actions('rss2_ns');
        remove_all_actions('atom_entry');
        remove_all_actions('atom_head');
        remove_all_actions('atom_ns');
        remove_all_actions('rdf_header');
        remove_all_actions('rdf_item');
        remove_all_actions('rdf_ns');
    }



    /**
     * @param $account
     * @return mixed
     */
    public function create_egoi_account($account) {

        $url = 'http://dev-web-agency.e-team.biz/smaddonsms/account/create';

        $email = filter_var($account['new_account_email'], FILTER_SANITIZE_EMAIL);
        $company = filter_var($account['new_account_company'], FILTER_SANITIZE_STRING);
        $prefix = filter_var($account['new_account_prefix'], FILTER_SANITIZE_NUMBER_INT);
        $phone = filter_var($account['new_account_phone'], FILTER_SANITIZE_NUMBER_INT);
        $password = filter_var($account['new_account_password'], FILTER_SANITIZE_STRING);

        $response = wp_remote_post($url, array(
            'timeout' => 60,
            'body' => array (
                'email' => $email,
                'company' => $company,
                'remote_addr' => $_SERVER['REMOTE_ADDR'],
                'phone' => $phone,
                'password' => $password,
                'prefix' => $prefix
            )
        ));

        return $response['body'];

    }

    public function smsnf_check_te_user_id() {

        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, 'wp_woocommerce_session_') !== false) {
                $wc_session = explode("||", $_COOKIE[$key]);
                return $wc_session[0];
            }
        }

        $current_user = wp_get_current_user();
        if (isset($current_user->ID) && $current_user->ID) {
            return $current_user->ID;
        }

        setcookie('egoi_te_cart_session', 'on');
        return 'on';

    }

    public function smsnf_generate_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    /**
     *
     * Dashboard
     *
     */

    public function smsnf_get_form_susbcribers_total($period = 'ever') {
        global $wpdb;

        $today = date('Y-m-d');

        $sql = "SELECT COUNT(*) total FROM {$wpdb->prefix}egoi_form_subscribers ";

        if ($period == 'today') {
            return $wpdb->get_row($sql." WHERE created_at BETWEEN '$today' AND '".date('Y-m-d', strtotime($today. ' +1 day'))."' ");
        } else if ($period == 'ever') {
            return $wpdb->get_row($sql);
        }
        return false;
    }

    public function smsnf_get_form_subscribers_best_day() {
        global $wpdb;

        $sql = "SELECT DATE(created_at) date, COUNT(*) total FROM {$wpdb->prefix}egoi_form_subscribers GROUP BY date ORDER BY total DESC ";

        return $wpdb->get_row($sql);
    }

    public function smsnf_get_form_subscribers_last($num = 5) {
        global $wpdb;

        $sql = " SELECT * FROM {$wpdb->prefix}egoi_form_subscribers ORDER BY created_at DESC LIMIT $num";

        return $wpdb->get_results($sql);
    }

    public function smsnf_get_form_subscriber_total_by($type, $id = null) {
        global $wpdb;

        $sql = " SELECT {$type}_id, {$type}_title title , COUNT(*) total FROM {$wpdb->prefix}egoi_form_subscribers ";
        $sql .= $id !== null ? " WHERE {$type}_id = $id " : null;
        $sql .= " GROUP BY {$type}_id ";
        $sql .= $type == 'form' ? ", form_type" : null;

        return $wpdb->get_results($sql);
    }

    public function smsnf_get_form_subscribers_list($list = null, $period = 6) {
        global $wpdb;

        $period--;
        $start_day = date('Y-m', strtotime(date(). ' -'.$period.' month')).'-01';

        $sql = " SELECT list_id list, MONTH(created_at) month, YEAR(created_at) year, COUNT(*) total FROM {$wpdb->prefix}egoi_form_subscribers WHERE  created_at >= '$start_day' ";
        $sql .= $list ? " AND list_id = '$list' " : null;
        $sql .= " GROUP BY list_id, month, year ORDER BY list, year, month";

        $total_subscribers_flag = $total_subscribers = $lists = array();

        foreach ($wpdb->get_results($sql) as $row) {
            $total_subscribers_flag[$row->list][$row->month] = $row->total;
            if (!in_array($row->list, $lists)) {
                $lists[] = $row->list;
            }
        }

        for ($i=0; $i<=$period; $i++) {
            $month = date('n', strtotime($start_day.' +'.$i.' month'));

            foreach ($lists as $list) {
                if (!in_array($month, $total_subscribers['months'])) {
                    $total_subscribers['months'][$month] = date('M', strtotime($start_day.' +'.$i.' month'));
                }

                if (isset($total_subscribers_flag[$list][$month])) {
                    $total_subscribers[$list]['totals'][] = $total_subscribers_flag[$list][$month];
                } else {
                    $total_subscribers[$list]['totals'][] = 0;
                }
            }
        }

        return $total_subscribers;
    }

    public function smsnf_get_blog_posts($num_items = 2) {
        $url = __('https://blog.e-goi.com/feed/egoi', 'egoi-for-wp');
        $blog = fetch_feed($url);

        if (!is_wp_error($blog)) {
            $posts = array();
            $num_items = $blog->get_item_quantity($num_items);
            if ($num_items > 0) {
                $items = $blog->get_items(0, $num_items);
                foreach ($items as $item) {
                    $excerpt = wp_trim_words($item->get_description(), 30);
                    $posts[] = array(
                        'title' => $item->get_title(),
                        'date' => $item->get_date('d/m/Y'),
                        'link' => $item->get_permalink(),
                        'category' => $item->get_category()->term,
                        'excerpt' => $excerpt
                    );
                }
            }
            return $posts;
        }
        return false;
    }

    public function smsnf_show_blog_posts() {
        $output = '';
        $posts = $this->smsnf_get_blog_posts();
        foreach ($posts as $key => $post) {
            $output .= '
                <div class="smsnf-dashboard-blog-last-post__content">
                    <div>
                        <div>'.$post['date'].'</div>
                    </div>
                    <a href="'.$post['link'].'" target="_blank">
                        <h4 class="smsnf-dashboard-blog-last-post__content__title">'.$post['title'].'</h4>
                    </a>
                    <a href="'.$post['link'].'" target="_blank">
                        <p class="smsnf-dashboard-blog-last-post__content__description">'.$post['excerpt'].'</p>
                    </a>
            ';
            $output .= count($posts)-1 > $key ? '<hr></div>' : '</div>';
        }
        echo $output;
        wp_die();
    }

    public function smsnf_get_last_campaigns() {
        $last_campaigns_flag = array('email' => 0, 'sms_premium' => 0);
        $last_campaigns = array();
        $channels = array('email', 'sms_premium');

        $api = new Egoi_For_Wp();
        $campaigns = $api->getCampaigns();

        foreach ($campaigns as $campaign) {

            if ($campaign->STATUS != 'finished' && $campaign->STATUS != 'archived') {
                continue;
            }

            if (!in_array(0, $last_campaigns_flag)) {
                break;
            }

            foreach ($channels as $channel) {

                if ($channel == $campaign->CHANNEL) {

                    if (!isset($last_campaigns[$channel]) ||
                        (
                            isset($campaigns_flag[$channel]) &&
                            $campaigns_flag[$channel]['name'] == $campaign->SUBJECT &&
                            $campaigns_flag[$channel]['start_time'] - strtotime($campaign->START) < 300
                        )) {

                        $last_campaigns[$channel][] = array(
                            'hash' => $campaign->HASH,
                            'id' => $campaign->REF,
                            'list' => $campaign->LISTNUM,
                            'name' => $this->isJson($campaign->SUBJECT) ? json_decode($campaign->SUBJECT) : $campaign->SUBJECT,
                            'internal_name' => $this->isJson($campaign->INTERNAL_NAME) ? json_decode($campaign->INTERNAL_NAME) : $campaign->INTERNAL_NAME,
                            'status' => $campaign->STATUS
                        );
                        $campaigns_flag[$channel] = array(
                            'name' => $campaign->SUBJECT,
                            'start_time' => strtotime($campaign->START)
                        );
                    } else {
                        $last_campaigns_flag[$channel] = 1;
                    }
                }
            }
        }
        return $last_campaigns;
    }

    public function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function smsnf_last_campaigns_reports() {

        $api = new Egoi_For_Wp();

        $last_campaigns = $this->smsnf_get_last_campaigns();
        $reports = array();

        foreach ($last_campaigns as $channel => $campaign) {

            $reports[$channel] = array(
                'name' => str_replace('"', '', $campaign[0]['name']),
                'internal_name' => str_replace('"', '', $campaign[0]['internal_name'])
            );

            foreach ($campaign as $key => $value) {
                $report = $api->getReport($value['hash']);
                $reports[$channel]['id'] .= $value['id'].' | ';
                $reports[$channel]['list'] .= $value['list'].' | ';
                if (!isset($report->ERROR)) {
                    $reports[$channel]['sent'] += $report->SENT;
                    if ($channel == 'email') {
                        $reports[$channel]['chart']['opens'] += $report->UNIQUE_VIEWS;
                        $reports[$channel]['chart']['clicks'] += $report->UNIQUE_CLICKS;
                        $reports[$channel]['chart']['bounces'] += $report->RETURNED;
                        $reports[$channel]['chart']['removes'] += $report->TOTAL_REMOVES;
                        $reports[$channel]['chart']['complains'] += $report->COMPLAIN;
                    } else if ($channel == 'sms_premium') {
                        $reports[$channel]['chart']['delivered'] += $report->DELIVERED;
                        $reports[$channel]['chart']['not_delivered'] += $report->NOT_DELIVERED;
                    }
                } else {
                    $reports[$channel]['sent'] = $report->ERROR;
                }

            }

            $reports[$channel]['id'] = substr($reports[$channel]['id'], 0, -2);
            $reports[$channel]['list'] = substr($reports[$channel]['list'], 0, -2);

        }
        return $reports;
    }

    public function smsnf_show_last_campaigns_reports() {
        $output = array('email' => '', 'sms_premium' => '');

        $campaigns = $this->smsnf_last_campaigns_reports();

        foreach ($output as $type => $value) {
            $campaign_chart = implode(",", $campaigns[$type]['chart']);
            $type_clean = str_replace('_premium', '', $type);
            $output[$type] .= '
                <table class="table smsnf-dashboard-campaigns--table">
                    <tbody>
                        <tr>
                            <td>'.__('Subject', 'egoi-for-wp').'</td>
                            <td>'.$campaigns[$type]['name'].'</td>
                        </tr>
                        <tr>
                            <td>'.__('Internal Name', 'egoi-for-wp').'</td>
                            <td>'.$campaigns[$type]['internal_name'].'</td>
                        </tr>
                        <tr>
                            <td>ID</td>
                            <td>'.$campaigns[$type]['id'].'</td>
                        </tr>
                        <tr>
                            <td>'.__('Total sent', 'egoi-for-wp').'</td>
                            <td class="smsnf-dashboard-last-'.$type_clean.'-campaign__totalsend">';

            $output[$type] .= $campaigns[$type]['sent'] === 'NO_DATA' ? '<span class="totalsend--wait">'.__('A aguardar resultados...', 'egoi-for-wp').'</span>' : $campaigns[$type]['sent'];

            $output[$type] .= '
                            </td>
                        </tr>
                    </tbody>
                </table>
                ';

            if ($campaigns[$type]['sent'] > 0) {

                if ($type == 'email') {
                    $labels = '"Aberturas", "Cliques", "Bounces", "Remoções", "Queixas"';
                    $background_color = '
                        "rgba(0, 174, 218, 0.4)",
                        "rgba(147, 189, 77, 0.3)",
                        "rgba(246, 116, 73, 0.3)",
                        "rgba(250, 70, 19, 0.4)",
                        "rgba(237, 60, 47, 0.6)"
                    ';
                    $border_color = '
                        "rgba(0, 174, 218, 0.5)",
                        "rgba(147, 189, 77, 0.4)",
                        "rgba(246, 116, 73, 0.4)",
                        "rgba(242, 91, 41, 0.5)",
                        "rgba(237, 60, 47, 0.7)"
                    ';
                } else if ($type == 'sms_premium') {
                    $labels = '"Entregues", "Não Entregues"';
                    $background_color = '
                        "rgba(147, 189, 77, 0.3)",
                        "rgba(250, 70, 19, 0.4",
                    ';
                    $border_color = '
                        "rgba(147, 189, 77, 0.4)",
                        "rgba(250, 70, 19, 0.5)"
                    ';
                }

                $output[$type] .= '
                    <div class="smsnf-dashboard-last-'.$type_clean.'-campaign__chart">
                        <canvas id="smsnf-dlec__doughnutChart" height="120"></canvas>
                    </div>
                    <script>
                    Chart.defaults.global.legend.labels.usePointStyle = true;
                    var ctx = document.getElementById("smsnf-dlec__doughnutChart").getContext("2d");
                    var myChart = new Chart(ctx, {
                        type: "doughnut",
                        data: {
                            labels: ['.$labels.'],
                            datasets: [{
                                label: "# of Votes",
                                data: ['.$campaign_chart.'],
                                backgroundColor: ['.$background_color.'],
                                borderColor: ['.$border_color.'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            legend: {
                                display: true,
                                position: "right",
                                labels: {
                                    fontColor: "#333",
                                }
                            },
                            layout: {
                                padding: {
                                    left: 0,
                                    right: 0,
                                    top: 0,
                                    bottom: 0
                                }
                            }
                        }
                    });
                </script>
                ';
            }
        }

        echo json_encode($output);
        wp_die();
    }


    public function smsnf_hide_notification() {
        $notifications = get_option('egoi_notifications');
        $notifications[$_POST['notification']] = current_time('mysql');
        update_option('egoi_notifications', $notifications);
        wp_die();
    }

    public function smsnf_check_notification_option($notification) {
        $notifications = get_option('egoi_notifications');
        $time = 15*24*60*60;
        if (
            !isset($notifications[$notification]) ||
            (
                date('Y-m') != date('Y-m', strtotime($notifications[$notification])) &&
                strtotime(date('Y-m-d')) - strtotime($notifications[$notification]) > $time
            )
        ) {
            return true;
        }
        return false;
    }

    public function smsnf_show_notifications($customer) {

        $notifications = array(
            'limit' => false,
            'upgrade' => false
        );

        if (
            ($customer->PLAN_EMAIL_LIMIT != 0 && $customer->PLAN_EMAIL_SENT/$customer->PLAN_EMAIL_LIMIT >= 0.8 ||
            $customer->PLAN_SMS_LIMIT != 0 && $customer->PLAN_SMS_SENT/$customer->PLAN_SMS_LIMIT >= 0.8) &&
            $this->smsnf_check_notification_option('account-limit')
        ) {
            $notifications['limit'] = true;
        }

        if (
            (strpos($customer->CONTRACT, '5001') !== false ||
            strpos($customer->CONTRACT, 'Pay With Love') !== false ||
            strpos($customer->CONTRACT, 'paywithlove') !== false) &&
            $this->smsnf_check_notification_option('upgrade-account')
        ) {
            $notifications['upgrade'] = true;
        }

        return $notifications;
    }

    public function smsnf_get_account_info() {
        $api = new Egoi_For_Wp();
        $customer = $api->getClient();

        return $customer;
    }

    public function smsnf_show_account_info($destination) {
        $customer = $this->smsnf_get_account_info();

        $output['notifications'] = $this->smsnf_show_notifications($customer);
        $email_limit = $customer->PLAN_EMAIL_LIMIT != 0 ? $customer->PLAN_EMAIL_LIMIT : __('Unlimited', 'egoi-for-wp');
        $sms_limit = $customer->PLAN_SMS_LIMIT != 0 ? $customer->PLAN_SMS_LIMIT : __('Unlimited', 'egoi-for-wp');

        if ($destination == 'wp-dashboard') {
			$table_class = 'table smsnf-wpdash--table';
            $output['account'] = '
			<div class="smsnf-wpdash-table--head">
				<img src="'.plugins_url().'/smart-marketing-for-wp/admin/img/symbol.png"/>
				<p>E-goi - Smart Marketing</p>
			</div>
            ';
        } else {
			$table_class = 'table';
            $output['account'] = '';
        }

        $output['account'] .= '
            <table class="'.$table_class.'">
                <tbody>
					<tr>
						<td><span class="smsnf-dashboard-account__content__table--total">'.__('Plan', 'egoi-for-wp').'</span></td>
						<td><span class="">'.$customer->CONTRACT.'</span></td>
                    </tr>
                    <tr>
						<td><span class="smsnf-dashboard-account__content__table--total">'.__('Current Balance', 'egoi-for-wp').'</span></td>
						<td><span class="smsnf-dashboard-account__content__table--cash">'.$customer->CREDITS.'</span></td>
                    </tr>';

        if ($customer->CONTRACT_EXPIRE_DATE) {
            $output['account'] .= '
                        <tr>
                            <td><span class="smsnf-dashboard-account__content__table--total">'.__('Expires in', 'egoi-for-wp').'</span></td>
                            <td><span class="">' . $customer->CONTRACT_EXPIRE_DATE . '</span></td>
                        </tr>
                        ';
        }

        $output['account'] .= '
                </tbody>
            </table>
            <p class="smsnf-dashboard-account__content__table--subtitle">'.__('Your current plan includes', 'egoi-for-wp').'</p>
            <table class="'.$table_class.'">
                <tbody>
                    <tr>
                        <td>Email/Push</td>
                        <td><span class="">'.$email_limit.'</span></td>
                    </tr>
                    <tr>
                        <td>SMS</td>
                        <td><span class="">'.$sms_limit.'</span></td>
                    </tr>
                </tbody>
            </table>
            <p class="smsnf-dashboard-account__content__table--subtitle">'.__('Total sent', 'egoi-for-wp').'</p>
            <table class="'.$table_class.'">
                <tbody>
                    <tr>
                        <td>Email/Push</td>
                        <td><span class="">'.$customer->PLAN_EMAIL_SENT.'</span></td>
                    </tr>
                    <tr>
                        <td>SMS</td>
                        <td><span class="">'.$customer->PLAN_SMS_SENT.'</span></td>
                    </tr>
        ';

        if (!is_plugin_active( 'sms-orders-alertnotifications-for-woocommerce/smart-marketing-addon-sms-order.php' )) {

            $output['account'] .= '
                    </tbody>
				</table>
				<div class="smsnf-dashboard-plugin-sms">
			';

            $locale = get_locale();
            if ($locale == 'pt_PT') {
				$output['account'] .= '<img class="smsnf-dashboard-plugin-sms__img" src="'.plugins_url().'/smart-marketing-for-wp/admin/img/sm-sms-pt.png">';
            } else {
				$output['account'] .= '<img class="smsnf-dashboard-plugin-sms__img" src="'.plugins_url().'/smart-marketing-for-wp/admin/img/sm-sms-lang.png">';
            }

            $output['account'] .= '
					<div class="smsnf-dashboard-plugin-sms__text">'.__('Send SMS notifications to your customers and administrators for each change to the order status on your WooCommerce', 'egoi-for-wp').'</div>
					<a href="https://wordpress.org/plugins/sms-orders-alertnotifications-for-woocommerce/" type="button" class="button-smsnf-primary" target="_blank">'.__('Instalar Plugin', 'egoi-for-wp').'</a>
				</div>
            ';

        } else {
            $output['account'] .= '
                        <tr>
                            <td>'.__('Transactional SMS', 'egoi-for-wp').'</td>
                            <td><span class="">'.get_option('egoi_sms_counter').'</span></td>
                        </tr>
                    </tbody>
                </table>
            ';
        }

        return json_encode($output);
    }

    public function smsnf_show_account_info_ajax() {
        $output = $this->smsnf_show_account_info('smart-marketing-dashboard');
        echo $output;
        wp_die();
    }

    public function smsnf_main_dashboard_widget_content() {
        $content = json_decode($this->smsnf_show_account_info('wp-dashboard'));

        echo '
            <div class="smsnf-dashboard-account__content__table">
                '.$content->account.'
            </div>
        ';

    }

}
