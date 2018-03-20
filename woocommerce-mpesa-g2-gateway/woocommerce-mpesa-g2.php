<?php
/* Mpesa G2 Payment Gateway Class */
class SPYR_MPESA_G2 extends WC_Payment_Gateway {

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "SPYR_MPESA_G2";

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "Mpesa G2", 'spyr-mpesa-G2' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "Mpesa G2 Payment Gateway Plug-in for WooCommerce", 'spyr-mpesa-G2' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( "Mpesa G2", 'spyr-mpesa-G2' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = null;

		// Bool. Can be set to true if you want payment fields to show on the checkout 
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = true;

		// Supports the default credit card form
		//$this->supports = array( 'default_credit_card_form' );

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// Lets check for SSL
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // End __construct()

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'spyr-mpesa-G2' ),
				'label'		=> __( 'Enable this payment gateway', 'spyr-mpesa-G2' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'spyr-mpesa-G2' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'spyr-mpesa-G2' ),
				'default'	=> __( 'MPESA Paybill', 'spyr-mpesa-G2' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'spyr-mpesa-G2' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'spyr-mpesa-G2' ),
				'default'	=> __( 'Pay securely using the Lipa na Mpesa Service.', 'spyr-mpesa-G2' ),
				'css'		=> 'max-width:350px;'
			),
			'shortCode' => array(
				'title'		=> __( 'Short Code ', 'spyr-mpesa-G2' ),
				'type'		=> 'text',
				'default'	=> 898998,
				'desc_tip'	=> __( 'This is your Shortcode as provided by Daraja.', 'spyr-mpesa-G2' ),
			),
			'paymentType' => array(
                'title'     => __('Payment Type'),
                'desc_tip'  => __('Choose if payment is via Paybill or Till Number'),
                'type'      => 'select',
                'options'   => array(
                    'CustomerPayBillOnline' => 'Paybill',
                    'CustomerBuyGoodsOnline' => 'Buy Goods'
                )
            ),
			'callback_url' => array(
				'title'		=> __( 'Callback URL', 'spyr-mpesa-G2' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'The Callback url for Safaricom API, Do not change this unless you know what you are doing.', 'spyr-mpesa-G2' ),
				'default'	=> __( 'https://safaricom.co.ke/mpesa_online/lnmo_checkout_server.php?wsdl', 'spyr-mpesa-G2' ),
			),
			'consumer_key' => array(
				'title'		=> __( 'Consumer Key', 'spyr-mpesa-G2' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Consumer Key for your App. Required for all calls', 'spyr-mpesa-G2' ),
			),
            'consumer_secret' => array(
                'title'		=> __( 'Consumer Secret', 'spyr-mpesa-G2' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'Consumer Key for your App. Required for all calls', 'spyr-mpesa-G2' ),
            ),
			'environment' => array(
				'title'		=> __( 'Mpesa Test Mode', 'spyr-mpesa-G2' ),
				'label'		=> __( 'Enable Demo Mode', 'spyr-mpesa-G2' ),
				'type'		=> 'checkbox',
				'description' => __( 'Place the payment gateway in test mode.', 'spyr-mpesa-G2' ),
				'default'	=> 'no',
			)
		);		
	}
	
	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;
		
		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );
		
		// Are we testing right now or is it a real transaction
		$environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		$environment_url = ( "FALSE" == $environment ) 
						   ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/simulate'
						   : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';
        $access_token = $this->o_auth($this->get_option( 'consumer_key'), $this->get_option( 'consumer_secret'));
		// This is where the fun stuff begins
		$payload = array(
			"Amount"             	=> $customer_order->order_total,
			"BillRefNumber"        	=> str_replace( "#", "", $customer_order->get_order_number() ),
			"Msisdn"              	=> $customer_order->billing_phone,
			
		);
	
		// Send this payload to Mpesa for processing
		$response = wp_remote_post( $environment_url, array(
			'method'    => 'POST',
			'headers'   => array('Content-Type:application/json','Authorization:Bearer '.$access_token),
			'body'      => http_build_query( $payload ),
			'timeout'   => 90,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) 
			throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'spyr-mpesa-G2' ) );

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'Mpesa\'s Response was empty.', 'spyr-mpesa-G2' ) );
			
		// Retrieve the body's resopnse if no errors found
		$response_body = wp_remote_retrieve_body( $response );

		// Parse the response into something we can read
		foreach ( preg_split( "/\r?\n/", $response_body ) as $line ) {
			$resp = explode( "|", $line );
		}

		// Get the values we need
		$r['response_code']             = $resp[0];
		$r['response_sub_code']         = $resp[1];
		$r['response_reason_code']      = $resp[2];
		$r['response_reason_text']      = $resp[3];

		// Test the code to know if the transaction went through or not.
		// 1 or 4 means the transaction was a success
		if ( ( $r['response_code'] == 1 ) || ( $r['response_code'] == 4 ) ) {
			// Payment has been successful
			$customer_order->add_order_note( __( 'Mpesa payment completed.', 'spyr-mpesa-G2' ) );
												 
			// Mark order as Paid
			$customer_order->payment_complete();

			// Empty the cart (Very important step)
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			// Transaction was not succesful
			// Add notice to the cart
			wc_add_notice( $r['response_reason_text'], 'error' );
			// Add note to the order for your reference
			$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
		}

	}

	//Authenticate
    protected function o_auth($consumer_key, $consumer_secret) {
        // Are we testing right now or is it a real transaction
        // Decide which URL to post to
        $environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
        $environment_url = ( "FALSE" == $environment )
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($consumer_key.':'.$consumer_secret);
        $response = wp_remote_get( $environment_url, array(
            'method'    => 'GET',
            'timeout'   => 90,
            'headers'   => array(
                'Content-type' => 'application/type',
                'Authorization' => 'Authorization: Basic '. $credentials
            ),
            'sslverify' => false,
        ));
        $response_body = wp_remote_retrieve_body( $response );
        return $response_body->access_token;
    }
	
	// Validate fields
	public function validate_fields() {
		return true;
	}
	
	// Check if we are forcing SSL on checkout pages
	// Custom function not required by the Gateway
	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}

} // End of spyr-mpesa_G2
