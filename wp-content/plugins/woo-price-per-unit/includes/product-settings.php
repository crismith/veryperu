<?php

/**
* Displays custom settings in WooCommerce backend for product
*
* @package PricePerUnit/Admin
*/

if (!defined('ABSPATH')):
    exit; // Exit if accessed directly
endif;
echo '<div id="mcmp_ppu_options" class="panel woocommerce_options_panel">';
// mcmp_ppu fields will be created here.
    echo '<p>';
        echo '<span class="section-heading">' . esc_html__('Settings for Price per unit plug-in', 'woo-price-per-unit') . '</span></br>';
        echo '<span class="mcmp-pro-advert">';
            echo esc_html__('In PRO version you will find here settings for individual products', 'woo-price-per-unit') . '</br>';
            echo esc_html__('You can buy the plug-in here ', 'woo-price-per-unit');
            echo '<a href="https://mechcomp.cz/price-per-unit-pro/" target="_blank">';
            echo esc_html__('Price per Unit PRO', 'woo-price-per-unit') . '</a>';
        echo '</span>';
        echo '<span class="mcmp-pro-advert">';
        echo esc_html__('Features you will find here:', 'woo-price-per-unit') . '</br>';
        echo esc_html__('- Change of display for product page', 'woo-price-per-unit') . '</br>';
        echo esc_html__('- Change of display for shop page', 'woo-price-per-unit') . '</br>';
        echo esc_html__('- Individual text for recalculated price', 'woo-price-per-unit') . '</br>';
        echo esc_html__('- Option to display price for different weight unit (kg,g,lbs,oz) from shop default. Example: shop in kg and some products in grams', 'woo-price-per-unit') . '</br>';
        echo esc_html__('- Option for entering weight, different from shipping weight. Example: use shipping weight as gross and recalculate per net weight.', 'woo-price-per-unit') . '</br>';
        echo esc_html__('- Option for displaying recalculation for different number of units. Example: You can display price per 5kg', 'woo-price-per-unit') . '</br>';
        echo '</span>';
        echo '<span class="mcmp-pro-advert">';
        echo '<span class="coupon">';
        echo esc_html__('New plug-in available - Sell by Weight PRO', 'woo-price-per-unit') . '</br>';
        echo '</span>';
        echo esc_html__('This plug-in allows you to sell easily products, where you want to have several weight option to sell at the same price for kilogram.', 'woo-price-per-unit') . '</br>';
        echo esc_html__('It works in a similar way as Variable products, but it is easier to manage because you will enter the price only once and the options price is calculated automatically.', 'woo-price-per-unit') . '</br>';
        echo esc_html__('More information can be found here ', 'woo-price-per-unit');
        echo '<a href="https://mechcomp.cz/sell-by-weight-pro/" target="_blank">';
        echo esc_html__('Sell by Weight PRO', 'woo-price-per-unit') . '</a>';
        echo '</span>';
        echo '<span class="mcmp-pro-advert note">';
        echo esc_html__('Note for old version users:', 'woo-price-per-unit') . '</br>';
        echo esc_html__("All your settings from this page are still in database, if you upgrade to PRO version it will be available again.", 'woo-price-per-unit') . '</br>';
        echo esc_html__("If you are not interested in buying PRO version and still want to use the old features, please downgrade to free version 1.9.3.", 'woo-price-per-unit');
        echo '<a href="https://downloads.wordpress.org/plugin/woo-price-per-unit.1.9.3.zip" target="_blank">';
        echo esc_html__('Download here.', 'woo-price-per-unit') . '</a></br>';
        echo '</span>';
    echo '</p>';
echo '</div>';