<?php

/**
 * Fired during plugin activation
 *
 * @link       wpmerchant.com/team
 * @since      1.0.0
 *
 * @package    Wpmerchant
 * @subpackage Wpmerchant/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpmerchant
 * @subpackage Wpmerchant/includes
 * @author     Ben Shadle <ben@wpmerchant.com>
 */
class Wpmerchant_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
		// Create a WPL Payments table
			// Have it include all of the user information (so that we can convert those users into wordpress someone upgrades to a pro plugin)
			// THis is for us to track which email addresses are associated iwth which stripe customer while also allowing us to separate the default wordpress user with a wpl user (so that the pro version has more value)
		global $wpdb;
		$wpmerchant_db_version = '1.0';
		$table_name = $wpdb->prefix . "wpmerchant_customers"; 
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  created timestamp NOT NULL default CURRENT_TIMESTAMP,
		  name tinytext NULL,
		  stripe_id varchar(255) DEFAULT '' NOT NULL,
		  email varchar(255) DEFAULT '' NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		add_option( 'wpmerchant_db_version', $wpmerchant_db_version );
		$payment_processor = 'stripe';
		$email_list_processor = 'mailchimp';
		$currency = 'USD';
		add_option( 'wpmerchant_payment_processor', $payment_processor );
		add_option( 'wpmerchant_email_list_processor', $email_list_processor );
		add_option('wpmerchant_currency',$currency);
	}

}
