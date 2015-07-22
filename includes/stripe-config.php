<?php
require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/stripe-php-2.2.0/init.php';
require_once plugin_dir_path( dirname(__FILE__) ) . 'public/class-wpmerchant-public.php';
$stripe['status'] = get_option($this->plugin_name.'_stripe_status' );

if($stripe['status'] == 'live'){
	$stripe['secret_key'] = get_option( $this->plugin_name.'_stripe_live_secret_key' );
	$stripe['public_key'] = get_option( $this->plugin_name.'_stripe_live_public_key' );
} else {
	$stripe['secret_key'] = get_option( $this->plugin_name.'_stripe_test_secret_key' );
	$stripe['public_key'] = get_option( $this->plugin_name.'_stripe_live_public_key' );
}

Wpmerchant_Public::stripeFunction('setApiKey',$stripe);