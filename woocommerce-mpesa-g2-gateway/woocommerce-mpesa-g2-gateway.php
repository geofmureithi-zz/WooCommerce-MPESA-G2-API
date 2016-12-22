<?php
/*
Plugin Name: Woocommerce Mpesa-G2 Gateway
Plugin URI: http://www.geoffreymureithi.me.ke/wp-mpesa-plugin
Description: Extends WooCommerce by Adding the MPesa G2-Api Gateway.
Version: 1.0
Author: Geoffrey Mureithi
Author URI: http://www.geoffreymureithi.me.ke
*/

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'spyr_mpesa_g2_api_init', 0 );
function spyr_mpesa_g2_api_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// If we made it this far, then include our Gateway Class
	include_once( 'woocommerce-mpesa-g2.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'spyr_add_mpesa_g2_api_gateway' );
	function spyr_add_mpesa_g2_api_gateway( $methods ) {
		$methods[] = 'SPYR_MPESA_G2';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'spyr_mpesa_g2_api_action_links' );
function spyr_mpesa_g2_api_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'spyr-mpesa-g2-api' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );	
}
 
