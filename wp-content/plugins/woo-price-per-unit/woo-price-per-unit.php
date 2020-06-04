<?php
/*
Plugin Name: WooCommerce Price Per Unit
Plugin URI: https://mechcomp.cz/price-per-unit-pro/
Description: WooCommerce Price Per Unit allows the user to show prices recalculated per units(weight) and do some customization to the look of the prices
Version: 2.0.5
Author: Martin Mechura
Author URI: http://mechcomp.cz
Text Domain: woo-price-per-unit
WC tested up to: 4.1.0
WC requires at least: 3.0

@author         Martin Mechura
@category    Admin

WooCommerce Price Per Unit. A Plugin that works with the WooCommerce plugin for WordPress.
Copyright (C) 2017 Martin Mechura

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */
if (!defined('ABSPATH')):
    exit; // Exit if accessed directly
endif;
class mcmp_PPU
{
    private static $instance = null;
    private $single_pr_id = 0;
    public $woo_version = '';
    public $dependency = null;
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    /**
     * The Constructor!
     * @since 1.0
     */
    public function __construct()
    {
        global $woocommerce;
        add_action( 'admin_init', array( $this, 'admin_activation_check' ) );
        if($this->dependency_checks()!==true){
            return;
        }
        //Loading translation
        add_action('init', array($this, 'plugin_init'));
        //Action on plugin activation
        register_activation_hook(__FILE__, array($this, 'plugin_activated'));
        //After upgrade action
        add_action( 'upgrader_process_complete', array($this,'upgrade_completed'), 10, 2 );
        add_action( 'admin_notices', array($this,'ppu_plugin_update_notice'));
        //Loading stylesheet
        add_action('wp_enqueue_scripts', array($this, 'load_style'));
        add_action('admin_enqueue_scripts', array($this, 'load_admin_style'));
        add_filter('woocommerce_get_sections_products', array($this, 'add_general_options_section'));
        add_filter('woocommerce_get_settings_products', array($this, 'general_options'), 10, 2);
        
        // Render the ppu field output on the frontend
        add_filter('woocommerce_get_price_html', array($this, 'custom_price'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'custom_cart_price'), 10, 3);
        //Adding single product options tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_custom_product_options_tab'), 99, 1);
        //Adding single product options
        add_action('woocommerce_product_data_panels', array($this, 'product_options'));
        //helper for getting single product ID
        add_action('woocommerce_before_single_product', array($this, 'get_single_id'));
        
        //Extending plugin actions
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
    }

    /**
     * This function runs when WordPress completes its upgrade process
     * It iterates through each plugin updated to see if ours is included
     * @param $upgrader_object Array
     * @param $options Array
     * @since 2.0.2
     */
    public function upgrade_completed( $upgrader_object, $options ) {
        // The path to our plugin's main file
        $ppu_plugin = plugin_basename( __FILE__ );
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
            // Iterate through the plugins being updated and check if ours is there
            foreach( $options['plugins'] as $plugin ) {
                if( $plugin == $ppu_plugin ) {
                    // Set a transient to record that our plugin has just been updated
                    set_transient( 'ppu_plugin_updated', 1 );
                }
            }
        }
    }
    
    /**
    * Show a notice to anyone who has just updated this plugin
    * This notice shouldn't display to anyone who has just installed the plugin for the first time
    * @since 2.0.2
    */
    public function ppu_plugin_update_notice() {
        // Check the transient to see if we've just updated the plugin
        if( get_transient( 'ppu_plugin_updated' ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p>'. esc_html__( 'Price Per Unit', 'woo-price-per-unit');
            echo esc_html__( ' - there was a change in the process of displaying.', 'woo-price-per-unit') . '</p>';
            echo '<p>'. esc_html__( 'It is no loger possible to change behaviour on single product - please check your product settings to get more info.', 'woo-price-per-unit') .'</p></div>';
            delete_transient( 'ppu_plugin_updated' );
        }
    }

    /**
     * Checks woocommerce version and remembers for later purpose
     * @param string $compare_version Version to check against, default is 3.0
     * @return boolean is the WooCommerce version greater than $compare_version
     * @since 1.5
     */
    public function check_woo_version($compare_version = '3.0',$compare_operator = '>=')
    {
        $version = $this->woo_version;
        if (empty($version)) {
            $version=get_option( 'woocommerce_version' );
            $this->woo_version = $version;
        }
        if (version_compare($version, $compare_version, $compare_operator)) {
            return true;
        }
        return false;
    }

    /**
     * Displays admin notice - when in WooCommerce 4 it handles the display through transients and function "display_stored_messages"
     * @param string $message Message to output
     * @param string $type Type of the message
     * @param string $transient Name of transient to store the message
     * @since 1.8
     */
    public function mcmp_add_message($message, $type, $transient = '')
    {
        if(empty($message) or !is_string($message)){
            return;
        }
        //Workaround for WooCommerce > 4, hidden notices
        if ($this->check_woo_version('4.0')){
            $class='mcmp-notice ';
            //$class='mcmp-notice is-dismissible ';
        }else{
            $class='notice is-dismissible ';
        }
        switch ($type) {
            case 'success':
            case 'warning':
            case 'error':
            case 'info':
                $class.='notice-'.$type;
            break;
            default:
                $class.='notice-info';
            break;
        }
        $output = '<div class="' . $class . '"><p>' . wp_kses_post($message) . '</p></div>';
        if (!empty($transient)){
            $notice_num = 0;
            $trans_num = $transient . $notice_num;
            while (get_transient($trans_num)!=false){
                $trans_num = $transient . ++$notice_num;
            } 
            set_transient( $trans_num, $output, 60 );
        }else{
            echo $output;
        }
    }

    /**
     * Displays message stored in transient
     * @param string $message Message to output
     * @param string $type Type of the message
     * @since 2.0.1
     */
    public function display_stored_messages($transient = 'ppu-notice')
    {
        $notice_num = 0;
        $trans_num = $transient . $notice_num;
        $message = get_transient($trans_num);
        while ($message!=false){
            delete_transient($trans_num);
            echo $message;
            $trans_num = $transient . ++$notice_num;
            $message = get_transient($trans_num);
        } 
    }

    /**
     * Truncates number to $precision decimal points
     * @param float $number Number to truncate
     * @param float $precision Number of digits after decimal point
     * @return float trimmed number
     * @since 1.9.5
     */
    public function truncate_number($number, $precision = 0)
    {
        if(function_exists('bcadd')){
            return floatval(bcadd($number, 0, $precision));
        }else{
            return floatval(preg_replace('/\.(\d{'.intval($precision).'}).*/', '.$1', $number));
        }
    }

    /**
     * Load plugin's textdomain
     * @since 1.0
     */
    public function plugin_init()
    {
        $this->legacy_options_fix();
        load_plugin_textdomain('woo-price-per-unit', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Checks if requirements for activation are met
     * Now it tests if free version is active
     * @return mixed String with error message or true when dependecy checks are allright
     * @since 1.9
     */
    public function dependency_checks()
    {
        if (!is_null($this->dependency)){
            return $this->dependency;
        }
        if ( in_array( 'woo-price-per-unit-pro/woo-price-per-unit.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) 
            || (is_multisite() && array_key_exists( 'woo-price-per-unit-pro/woo-price-per-unit.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins' ) ) ) ) ) {
                $this->dependency =  esc_html__('Price per unit plugin - PRO version is active. Please deactivate it first.', 'woo-price-per-unit');
                return $this->dependency;        
        }
        $this->dependency = true;
        return $this->dependency;
    }

    /**
     * Prevents activation of the plugin when dependecy checks fail
     * @since 1.9
     */
    function admin_activation_check()
    {
        $dependecy_info=$this->dependency_checks();
        if($dependecy_info!==true){            
            if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                $type='error';
                //Passing arguments into add_action by using PHP lambda - nice workaround
                add_action('admin_notices', function() use ( $dependecy_info,$type) { 
                    $this->mcmp_add_message($dependecy_info,$type); });
                if ( isset( $_GET['activate'] ) ) {
                    unset( $_GET['activate'] );
                }
            }
        }
    }

    /**
     * Action on plugin activation - currently setting defaults
     * Checks also the conditions for plugin activation - if not it prevents the activation
     * @since 1.5
     */
    public function plugin_activated()
    {
        $dependecy_info=$this->dependency_checks();
        if($dependecy_info!==true){
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die(esc_html__('Plugin NOT activated: ', 'woo-price-per-unit') . $dependecy_info);
        }
        $this->legacy_options_fix();
        add_option('_mcmp_ppu_general', 'add');
        add_option('_mcmp_ppu_single_page', 'add');
        add_option('_mcmp_ppu_cart_page', 'single');
        add_option('_mcmp_ppu_add_row_css', 'yes');
        add_option('_mcmp_ppu_recalc_text', '-automatic-');
        add_option('_mcmp_ppu_recalc_text_options', '/kg|/oz|/g|/lbs');
        add_option('_mcmp_ppu_var_display_option_recalc', 'yes');
        add_option('_mcmp_ppu_recalc_per_units', '');
        add_option('_mcmp_ppu_delete_meta',false);
        add_option('_mcmp_ppu_recalc_text_automatic_preposition','/');
        add_option('_mcmp_ppu_recalc_text_separate','yes');
        add_option('_mcmp_ppu_disable_price_rounding','no');
        add_option('_mcmp_ppu_var_prefix_text','');
        add_option('_mcmp_ppu_recalc_text_prefix','');
    }

    /**
     * Legacy options removal and migrating to new settings
     * @since 1.6
     */
    public function legacy_options_fix()
    {
        $old_opt = get_option('_mcmp_ppu_general_behaviour');
        if ($old_opt !== false) {
            $old_opt = (empty($old_opt)) ? 'not' : $old_opt;
            update_option('_mcmp_ppu_general', $old_opt);
            delete_option('_mcmp_ppu_general_behaviour');
        }
        $old_opt = get_option('_mcmp_ppu_single_page_behaviour');
        if ($old_opt !== false) {
            $old_opt = (empty($old_opt)) ? 'not' : $old_opt;
            update_option('_mcmp_ppu_single_page', $old_opt);
            delete_option('_mcmp_ppu_single_page_behaviour');
        }
    }

    /**
     * Load plugin's CSS style
     * @since 1.3
     */
    public function load_style()
    {
        if (get_option('_mcmp_ppu_add_row_css') == 'yes') {
            wp_enqueue_style('mcmp_price_per_unit_style', plugins_url('/assets/CSS/woo-ppu.css', __FILE__));
        }
    }

    /**
     * Load plugin's admin styles and scripts
     * @since 1.6
     */
    public function load_admin_style()
    {
        wp_register_script('mcmp-ppu-admin', plugins_url('/assets/JS/woo-ppu-admin.js', __FILE__), array('jquery'));
        wp_enqueue_script('mcmp-ppu-admin');
        wp_enqueue_style('mcmp_price_per_unit_admin_style', plugins_url('/assets/CSS/woo-ppu-admin.css', __FILE__));
    }

    /**
     * Add settings link
     * @return string HTML output into plugin info
     * @since 1.0
     */
    public function plugin_action_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=products&section=mcmp_price_pu') . '">' . esc_html__('Settings', 'woo-price-per-unit') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Adds tab to product options
     * @param array $product_opt_tabs Newly created tab
     * @since 1.0
     */
    public function add_custom_product_options_tab($product_opt_tabs)
    {
        $product_opt_tabs['price-per-unit'] = array(
            'label' => esc_html__('Price Per Unit', 'woo-price-per-unit'), // translatable
            'target' => 'mcmp_ppu_options', // translatable
        );
        return $product_opt_tabs;
    }

    /**
     * @param array $atts Outputs options to show on plugin general settings tab
     * Add the custom fields to the product general tab
     * @since 1.0
     */
    public function product_options($atts)
    {
        global $woocommerce, $post;
        include 'includes/product-settings.php';
    }
    
    /**
     * Add Price per Unit settings section under the Products tab.
     * @param array $sections List of sections
     * @since 1.0
     */
    public function add_general_options_section($sections)
    {
        $sections['mcmp_price_pu'] = esc_html__('Price Per Unit - free version', 'woo-price-per-unit');
        return $sections;
    }

    /**
     * Populates Price per Unit settings section under the Products tab.
     * @param array $settings Settings of WooCommerce
     * @param string $current_section Which section is populated right now
     * @since 1.0
     */
    public function general_options($settings, $current_section)
    {
        if ($current_section == 'mcmp_price_pu') {
            $cust_settings = include 'includes/general-settings.php';
            return $cust_settings;
            // If not, return the standard settings
        } else {
            return $settings;
        }
    }

    /**
     * Saves product id from single product view
     * needed for determining if not running from widget on single page
     * @since 1.0
     */
    public function get_single_id()
    {
        global $product;
        if(is_object($product)){
            $this->single_pr_id = $product->get_id();
        }
    }

     /**
     * Checks if the product is valid and then returns it's ID
     * @since 2.0
     * @param mixed $product_to_inspect which product to check
     * @param boolean $get_parent whether to get parent product ID
     * @return mixed ID of the verified product of false when wrong Product
     */
    public function check_product_id($product_to_inspect, $get_parent = false)
    {
        $product_id = false;
        if (!empty($product_to_inspect)) {
            if (is_numeric($product_to_inspect)) {
                $product_to_inspect = wc_get_product($product_to_inspect);
            }
            if (is_object($product_to_inspect)) {
                $product_id = $product_to_inspect->get_id();
                if($get_parent == true && $product_to_inspect->get_type() === 'variation'){
                    $product_id =  $product_to_inspect->get_parent_id();
                }
            }
        }
        return $product_id;
    }

    /**
     * Gets option with product option overrides
     * Works with product meta to check for option, if value is empty proceed to general options
     * @since 1.6
     * @param string $option which option to check
     * @param integer $product_id_to_inspect which product to check - supposed to be checked first
     * @param mixed $default value which should be passed if nothing is found
     * @return string
     */
    public function get_option_override($option, $product_id_to_inspect, $default = '')
    {
        $option_val = '';
        //General options
        if (empty($option_val)) {
            $option_val = get_option($option);
        }
        //Everything failed and we have default? Place it there. The ifs order is this because default is rarely used 
        if (!empty($default)) {
            if (empty($option_val)) {
                $option_val = $default;
            }
        }
        return $option_val;
    }

    /**
     * Gets behaviour of recalculation
     * The product is checked - on problems returns false
     * @since 1.6
     * @param mixed $product_to_inspect product id of product object
     * @param string $special_case Alter the behavior on special ocasion
     * @return mixed false if should not recalculate or string type of behaviour for recalculation
     *
     */
    public function get_recalc_behaviour($product_to_inspect, $special_case = false)
    {
        $behav = '';
        $product_id = $this->check_product_id($product_to_inspect, true);
        if (empty($product_id)) {
            return false;
        }
        switch ($special_case) {
            case 'cart':
                $option = get_option('_mcmp_ppu_cart_page');
                switch ($option) {
                    case 'single':
                        $option = '_mcmp_ppu_single_page';
                        break;
                    case 'shop':
                        $option = '_mcmp_ppu_general';
                        break;
                    default:
                        $behav = $option;
                        break;
                }
                break;
            
            default:
                //Determine whether to recalculate or not - depending also on override
                if (is_product() && $product_id === $this->single_pr_id) {
                    //Single product page + is it That product or some widget product?
                    $option = '_mcmp_ppu_single_page';
                } else {
                    //Other pages
                    $option = '_mcmp_ppu_general';
                }
                break;
        }
        $behav = empty($behav) ? $this->get_option_override($option, $product_id) : $behav;
        $behav = ($behav === 'not') ? false : $behav;
        return $behav;
    }

    /**
     * Render additional recalculated text and wrap it around the original price
     * @param integer $product_id_to_inspect Product which will receive the additional text - supposed to be checked first
     * @param mixed $price_text Recalculated price
     * @param boolean $row_wrapper Should be a recalc row wrapper?
     * @return string Automatic additional text 
     * @since 1.9.3
     */
    public function render_recalc_text($product_id_to_inspect , $price_text = '', $row_wrapper = false)
    {
        
        $pre_text = $this->get_option_override('_mcmp_ppu_recalc_text_prefix', $product_id_to_inspect);
        $pre_text = empty($pre_text) ? '' : $pre_text;
        $suf_text = $this->get_option_override('_mcmp_ppu_recalc_text', $product_id_to_inspect);
        $suf_text = (empty($suf_text) || $suf_text==' ') ? '' : $suf_text;
        if($suf_text=='-automatic-'){
            $suf_text = get_option('_mcmp_ppu_recalc_text_automatic_preposition','/');
            $suf_text = str_replace('%',' ',$suf_text);
            $recalc_per_units = $this->get_option_override('_mcmp_ppu_recalc_per_units', $product_id_to_inspect, 1);
            if($recalc_per_units!=1){
                $suf_text.=$recalc_per_units.' ';
            }
            $ratio_unit = $this->get_option_override('_mcmp_ppu_ratio_unit', $product_id_to_inspect);
            if(empty($ratio_unit)){
                $ratio_unit = get_option('woocommerce_weight_unit');
            }
            switch ( $ratio_unit) {
                case 'kg':
                    $ratio_unit = esc_html(_nx( 'kg', 'kg', $recalc_per_units,'weight unit', 'woo-price-per-unit'));
                    break;
                case 'g':
                    $ratio_unit = esc_html(_nx( 'g', 'g', $recalc_per_units,'weight unit', 'woo-price-per-unit'));
                    break;
                case 'oz':
                    $ratio_unit = esc_html(_nx( 'oz', 'oz', $recalc_per_units,'weight unit', 'woo-price-per-unit'));
                    break;
                case 'lbs':
                    $ratio_unit = esc_html(_nx( 'lb', 'lbs', $recalc_per_units,'weight unit', 'woo-price-per-unit'));
                    break;
                default:
                    $ratio_unit=esc_html__($ratio_unit, 'woo-price-per-unit');    
            }
            $suf_text.=$ratio_unit;
        }
        if(!empty($pre_text) || !empty($suf_text)){
            $separator=get_option('_mcmp_ppu_recalc_text_separate');
            $separator=($separator=='no')?'':'&nbsp;';
        }
        if (!empty($pre_text)) {
            $pre_text = '<span class="woocommerce-Price-currencySymbol amount mcmp-recalc-price-prefix">' . esc_html__($pre_text, 'woo-price-per-unit') . '</span>' . $separator;
        }
        if (!empty($suf_text)) {
            $suf_text = $separator . '<span class="woocommerce-Price-currencySymbol amount mcmp-recalc-price-suffix">' . esc_html__($suf_text, 'woo-price-per-unit') . '</span>';
        }
        if($row_wrapper == true){
            $pre_text = '</br><span class="mcmp_recalc_price_row">' . $pre_text;
            $suf_text .= '</span>';
        }

        return $pre_text . $price_text . $suf_text;
    }

    /**
     * Render cart and minicart price - caled from filter woocommerce_cart_item_price
     * @param string $price_text Original text
     * @param array $product_data Information about the product in the cart
     * @param integer $cart_key Id of the cart
     * @since 1.6
     * @return string recalculated $price + custom string
     */
    public function custom_cart_price($price_text, $product_data, $cart_key)
    {
        if (is_null($product_data)){
            return $price_text;
        }
        $product_id = (empty($product_data['variation_id'])) ? $product_data['product_id'] : $product_data['variation_id'];
        //get_recalc_behaviour also checks the product ID for validity - no need to check it again
        if (empty($this->get_recalc_behaviour($product_id,'cart'))) {
            return $price_text;
        }
        $product = wc_get_product($product_id);
        $weight = $this->get_option_override('_mcmp_ppu_cust_num_of_units', $product_id);
        if (empty($weight)) {
            $weight = $product->get_weight(); //$product_data[data]->get_weight()
        }
        $weight = floatval($weight);
        $normal_price = floatval($product->get_price());
        if (empty($weight) || empty($normal_price)) {
            return $price_text;
        }
        $round_prices = get_option('_mcmp_ppu_disable_price_rounding') == 'yes' ? false : true;
        $normal_price = wc_get_price_to_display($product, array('price' => $normal_price));
        $normal_price = $normal_price / $weight;
        $normal_price = $this->price_ratio_calc($normal_price, $product_id);
        if ($round_prices == false){
            $wc_decimals=wc_get_price_decimals();
            $normal_price = $this->truncate_number($normal_price, $wc_decimals);
        }
        $recalc = wc_price($normal_price);
        if ($product->is_on_sale()) {
            if (get_option('_mcmp_ppu_hide_sale_price') != 'yes') {
                $regular_price = floatval($product->get_regular_price());
                $regular_price = wc_get_price_to_display($product, array('price' => $regular_price));
                $regular_price = $regular_price / $weight;
                $regular_price = $this->price_ratio_calc($regular_price, $product_id);
                if ($round_prices == false){
                    $regular_price = $this->truncate_number($regular_price, $wc_decimals);
                }
                $recalc = '<del>' . wc_price($regular_price) . '</del>&nbsp;<ins>' . $recalc . '</ins>';
            }
        }
        $recalc = $this->render_recalc_text($product_id, $recalc, true);
        return $price_text . $recalc;
    }

    /**
     * Price ratio calculation - product id is not verified, supposedly it should be already verified
     * takes custom ratio and calculate it in - the price should be already divided by weight(cannot be here because of varible products calculation)
     * @since 1.7
     * @param float $price - price which should be recalculated
     * @param integer $product_id_to_inspect - id of the product - supposed to be checked first
     * @return float recalculated price
     */
    public function price_ratio_calc($price, $product_id_to_inspect)
    {
        $cust_ratio = 1;
        $recalc_per_units = $this->get_option_override('_mcmp_ppu_recalc_per_units', $product_id_to_inspect, 1);
        $recalc_per_units = floatval($recalc_per_units);
        $ratio_unit = $this->get_option_override('_mcmp_ppu_ratio_unit', $product_id_to_inspect);
        if (!empty($ratio_unit)) {
            $current_unit = get_option('woocommerce_weight_unit');
            $cust_ratio = wc_get_weight(1, $current_unit, $ratio_unit);
        }
        $price *= $cust_ratio * $recalc_per_units;
        return $price;
    }

    /**
     * Modifies the general price text
     * @param integer $product_id_to_inspect Product for which the text will be altered - supposed to be checked first
     * @param mixed $price_text Recalculated price
     * @return string Altered general price text
     * @since 2.0.0
     */
    public function general_price_manipulation($product_id_to_inspect , $price_text = '')
    {
        $product = wc_get_product($product_id_to_inspect);
        $product_type = $product->get_type();
        $var_prefix_text = '';
        switch ($product_type) {
            case 'simple':
                $hide_sale = get_option('_mcmp_ppu_hide_sale_price') == 'yes' ? true : false;
                if ($hide_sale == true && $product->is_on_sale()) {
                    $price_text = floatval($product->get_price());
                    if (!empty($price_text)) {
                        $price_text = wc_get_price_to_display($product, array('price' => $price_text));
                    }
                    $price_text = wc_price($price_text);
                }
            break;
            case 'variable':
                //hide variable max price?
                $hide_max = get_option('_mcmp_ppu_var_hide_max_price') == 'yes' ? true : false;
                if ($hide_max == true) {
                    //needles to remake the price?
                    $variable_price_min = floatval($product->get_variation_price('min',true));
                    $price_text = wc_price($variable_price_min);
                }
                if ($product->is_on_sale()){
                    $show_sale_price = get_option('_mcmp_ppu_var_show_sale_price') == 'yes' ? true : false;
                    if ($show_sale_price == true){
                        $price_text = '<ins>' . $price_text . '</ins>';
                        $variable_regular_price_min = floatval($product->get_variation_regular_price('min',true));
                        $price_regular = '<del>' . wc_price($variable_regular_price_min);
                        if ($hide_max == false){
                            $variable_regular_price_max = floatval($product->get_variation_regular_price('max',true));
                            if ($variable_regular_price_min !== $variable_regular_price_max){
                                $price_regular .= '–' . wc_price($variable_regular_price_max);
                            }
                        }
                        $price_regular .= '</del>';
                        $price_text = $price_regular . $price_text;
                    }
                }
                //fill prefix text for variables
                $var_prefix_text = get_option('_mcmp_ppu_var_prefix_text');
                $var_prefix_text = (empty($var_prefix_text)) ? '' : '<span class="woocommerce-Price-currencySymbol amount mcmp-variable-price-prefix">' . esc_html__($var_prefix_text, 'woo-price-per-unit') . ' ' . '</span>';
                $price_text = $var_prefix_text . $price_text;
            break;
        }
        $add_text = $this->get_option_override('_mcmp_ppu_additional_text', $product_id_to_inspect);
        $separator = get_option('_mcmp_ppu_recalc_text_separate') == 'no' ? '' : ' ';
        $add_text = (empty($add_text)) ? '' : '<span class="woocommerce-Price-currencySymbol amount mcmp-general-price-suffix">' . $separator . esc_html__($add_text, 'woo-price-per-unit') . '</span>';
        return $price_text . $add_text;
    }

    /**
     * Render the output - called from filter woocommerce_get_price_html
     * @param string $price Original text
     * @param object $instance Product for which to recalculate
     * @since 1.0
     * @return string recalculated $price + custom string
     */
    public function custom_price($price, $instance)
    {
		global $woocommerce, $page;
		if (is_null($instance)){
			global $product;
		}else{
			$product=$instance;
        }
        //Product validity check
        $prod_id = $this->check_product_id($product);
        if($prod_id == false){
            return false;
        }
        $product_type = $product->get_type();
        //Do not recalculate single variation - it's not displayed anywhere
        if ($product_type == 'variation') {
            return $price;
        }
        $behav = $this->get_recalc_behaviour($prod_id);
        // Recalculate price
        if (!empty($behav)) {
            //Price recalculation
            $round_prices = get_option('_mcmp_ppu_disable_price_rounding') == 'yes' ? false : true;
            $wc_decimals=wc_get_price_decimals();
            $recalc_price = '';
            switch ($product_type) {
                case 'simple':
                    $units = floatval($this->get_option_override('_mcmp_ppu_cust_num_of_units', $prod_id));
                    if (empty($units) && $product->has_weight()) {
                        $units = $product->get_weight();
                    }
                    $normal_price = floatval($product->get_price());
                    if ($units > 0 && !empty($normal_price)) {
                        $hide_sale = get_option('_mcmp_ppu_hide_sale_price') == 'yes' ? true : false;
                        $normal_price = wc_get_price_to_display($product, array('price' => $normal_price));
                        $normal_price = $normal_price / $units;
                        $normal_price = $this->price_ratio_calc($normal_price, $prod_id);
                        if ($round_prices == false){
                            $normal_price = $this->truncate_number($normal_price, $wc_decimals);
                        }
                        if ($product->is_on_sale() && $hide_sale == false) {
                            $regular_price = floatval($product->get_regular_price());
                            $regular_price = wc_get_price_to_display($product, array('price' => $regular_price));
                            $regular_price = $regular_price / $units;
                            $regular_price = $this->price_ratio_calc($regular_price, $prod_id);
                            if ($round_prices == false){
                                $regular_price = $this->truncate_number($regular_price, $wc_decimals);
                            }
                            $recalc_price = '<del>' . wc_price($regular_price) . '</del><ins>' . wc_price($normal_price) . '</ins>';
                        } else {
                            $recalc_price = wc_price($normal_price);
                        }
                    }
                    break;
                case 'variable':
                    //When getting variants the filter will fire this again - this is to speed up the process
                    remove_filter('woocommerce_get_price_html', array($this, 'custom_price'), 10);
                    $variations = $product->get_available_variations();
                    add_filter('woocommerce_get_price_html', array($this, 'custom_price'), 10, 2);
                    $num_of_variants = count($variations);
                    if ($num_of_variants > 0) {
                        $parent_prod_weight = $product->get_weight();
                        foreach($variations as $value){
                            $var_id = $value['variation_id'];
                            $units = $this->get_option_override('_mcmp_ppu_cust_num_of_units', $var_id);
                            if (empty($units)) {
                                $units=!empty($value['weight']) ? $value['weight'] : $parent_prod_weight;
                            }
                            if(!empty($units) && !empty($value['display_price'])){
                                $var_recalc_prices[]= $value['display_price'] / floatval($units);
                            }
                        }
                        if (isset($var_recalc_prices) && !empty($var_recalc_prices)) {
                            $hide_max = get_option('_mcmp_ppu_var_hide_max_price') == 'yes' ? true : false;
                            asort($var_recalc_prices);
                            $variable_price_min = reset($var_recalc_prices);
                            $variable_price_min = $this->price_ratio_calc($variable_price_min, $prod_id);
                            if ($round_prices == true){
                                $variable_price_min = round($variable_price_min,$wc_decimals);
                            } else {
                                $variable_price_min = $this->truncate_number($variable_price_min, $wc_decimals);
                            }
                            $recalc_price = wc_price($variable_price_min);
                            if ($hide_max == false) {
                                $variable_price_max = end($var_recalc_prices);
                                $variable_price_max = $this->price_ratio_calc($variable_price_max, $prod_id);
                                if ($round_prices == true){
                                    $variable_price_max = round($variable_price_max,$wc_decimals);
                                } else {
                                    $variable_price_max = $this->truncate_number($variable_price_max, $wc_decimals);
                                }
                                if ($variable_price_min !== $variable_price_max) {
                                    $recalc_price .= '–' . wc_price($variable_price_max);
                                }
                            }
                        }
                    }
                    break;
            }
        }
        switch ($behav) {
            case 'replace':
                //Recalc happened - let's replace
                //otherwise render normal text
                if(!empty($recalc_price)){
                    $price = $this->render_recalc_text($prod_id, $recalc_price);
                    if ($product_type == 'variable'){
                        //The _mcmp_ppu_var_prefix_text needs to be displayed even for the replaced price text
                        $var_prefix_text = get_option('_mcmp_ppu_var_prefix_text');
                        $var_prefix_text = (empty($var_prefix_text)) ? '' : '<span class="woocommerce-Price-currencySymbol amount mcmp-variable-price-prefix">' . esc_html__($var_prefix_text, 'woo-price-per-unit-pro') . ' ' . '</span>';
                        $price = $var_prefix_text . $price;
                    }
                }else{
                    $price = $this->general_price_manipulation($prod_id, $price);
                }
            break;
            case 'add':
                $price = $this->general_price_manipulation($prod_id, $price);
                if(!empty($recalc_price)){
                    $price .=  $this->render_recalc_text($prod_id, $recalc_price, true);
                }
            break;
            default:
                $price = $this->general_price_manipulation($prod_id, $price);
            break;
        }
        return $price;
    }
} 
// END class mcmp_ppu
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || (is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins' ) ) ) ) ) {
    // Instantiate the class
    $mcmp_ppu_obj = mcmp_PPU::get_instance();
}