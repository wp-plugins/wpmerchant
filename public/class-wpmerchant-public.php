<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       wpmerchant.com/team
 * @since      1.0.0
 *
 * @package    Wpmerchant
 * @subpackage Wpmerchant/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpmerchant
 * @subpackage Wpmerchant/public
 * @author     Ben Shadle <ben@wpmerchant.com>
 */
class Wpmerchant_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		// Ajax functionality to actually purchase the product or subscription
			// each ajax function should also have a nonce value in hte enqueue scripts section and include that in hte ajax call (for security purposes)
		//  Nopriv should also have a normal version so that logged out users work (the nopriv) and logged in users work (the norm)
		add_action( 'wp_ajax_nopriv_wpmerchant_purchase', array($this,'purchase'));
		add_action( 'wp_ajax_wpmerchant_purchase', array($this,'purchase'));
		// this nonce values are set in the admin.php file and included in the admin.js file
		add_action( 'wp_ajax_wpmerchant_get_email_data', array($this,'get_email_data'));
		add_action( 'wp_ajax_wpmerchant_get_payment_data', array($this,'get_payment_data'));
		//NO PRIV - means logged out users can run this functionality - need this to be the case bc the call is jcoming from outside wordpress
		//  Nopriv should also have a normal version so that logged out users work (the nopriv) and logged in users work (the norm)
		add_action( 'wp_ajax_nopriv_wpmerchant_save_email_api', array($this,'save_email_api_key'));
		add_action( 'wp_ajax_nopriv_wpmerchant_save_payment_api', array($this,'save_payment_api_key'));
		add_action( 'wp_ajax_wpmerchant_save_email_api', array($this,'save_email_api_key'));
		add_action( 'wp_ajax_wpmerchant_save_payment_api', array($this,'save_payment_api_key'));

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpmerchant_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpmerchant_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpmerchant-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpmerchant_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpmerchant_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpmerchant-public.js', array( 'jquery' ), $this->version, false );
		// Include the stripe checkout script on all pages
		wp_enqueue_script( $this->plugin_name.'-stripe-checkout', 'https://checkout.stripe.com/checkout.js', array( 'jquery' ), $this->version, false );
  	  
		// include the stripe functionality in this js file
    	  wp_register_script( $this->plugin_name,  plugin_dir_url( __FILE__ ) . 'js/wpmerchant-public.js', array( 'jquery' ), $this->version, false );
    	  wp_enqueue_script( $this->plugin_name);
		  // Set Nonce Values so that the ajax calls are secure
		  $purchaseNonce = wp_create_nonce( "wpmerchant_purchase" );
		  update_option( 'wpmerchant_purchase_nonce', $purchaseNonce ); 
	  	  $stripe_status = get_option( $this->plugin_name.'_stripe_status' );
	  	  if($stripe_status == 'live'){
	  	  	$stripe_public_key = get_option( $this->plugin_name.'_stripe_live_public_key' );
	  	  } else {
	  	  	$stripe_public_key = get_option( $this->plugin_name.'_stripe_test_public_key' );
	  	  }
  		// get option for company name
  		$companyName = get_option( $this->plugin_name.'_company_name' );
  		//http://www.jeansuschrist.com/wp-content/uploads/2015/05/stripe-logo.png
  		$image = get_option( $this->plugin_name.'_stripe_checkout_logo' );
  		if(!$image){
  			$image = '/wp-content/plugins/'.$this->plugin_name.'/public/img/marketplace.png';
  		}
		$loading_gif = '/wp-content/plugins/'.$this->plugin_name.'/public/img/loader.gif';
		$close_btn_img = '/wp-content/plugins/'.$this->plugin_name.'/public/img/close.png';
		
		$thank_you_msg = get_option($this->plugin_name.'_post_checkout_msg');
		if(!$thank_you_msg){
			$thank_you_msg = 'Thank you!';
		}
		$currency2 = get_option( $this->plugin_name.'_currency' );
		$currency1 = Wpmerchant_Admin::get_currency_details($currency2);
		$currency = $currency1['value'];
    	  // pass ajax object to this javascript file
		  // Add nonce values to this object so that we can access them in hte public.js javascript file
		  //wp_localize_script() MUST be called after the script it's being attached to has been registered using
		  wp_localize_script( $this->plugin_name, 'ajax_object', 
		  	array( 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'purchase_nonce'=> $purchaseNonce,
				'stripe_public_key'=>$stripe_public_key,
				'currency'=>$currency,
				'stripe_checkout_image'=>$image,
				'company_name'=>$companyName,
				'loading_gif'=>$loading_gif,
				'close_btn_image'=>$close_btn_img,
				'post_checkout_msg'=>$thank_you_msg
			) 
		  );

	}
	/**
	 * Insert record into the WPMerchant Customer Table
	 *
	 * @since    1.0.0
	 */
	public function addWPMCustomer($wpmCustomer){
		global $wpdb;
		// Get the wpmerchant db version to make sure the functions below are= compatible with the db version
    	$wpmerchant_db_version = get_option( $this->plugin_name.'_db_version' );
		$table_name = $wpdb->prefix . 'wpmerchant_customers';
		// Make sure the insert statement is compatible with the db version		
		$sql = $wpdb->insert( 
			$table_name, 
			array( 
				'name' => $wpmCustomer['name'], 
				'email' => $wpmCustomer['email'],  
				'stripe_id' => $wpmCustomer['stripe_id'], 
			) 
		);
		return $sql;
	}
	/**
	 * Get record from the WPMerchant Customer Table
	 *
	 * @since    1.0.0
	 */
	public function getWPMCustomer($wpmCustomer){
		global $wpdb;
		// Get the wpmerchant db version to make sure the functions below are= compatible with the db version
    	$wpmerchant_db_version = get_option( $this->plugin_name.'_db_version' );
		$table_name = $wpdb->prefix . 'wpmerchant_customers';
		// Make sure the select statement is compatible with the db version		
		$email = $wpmCustomer['email'];
		$wpmCustomer2 = $wpdb->get_row("SELECT * FROM $table_name WHERE email = '$email'");
		return $wpmCustomer2;
	}
	
	public function stripe($action, $data){
		$currency1 = get_option( $this->plugin_name.'_currency' );
		$currency2 = Wpmerchant_Admin::get_currency_details($currency1);
		$currency = $currency2['value'];
		try {
			switch ($action) {
				case 'setApiKey':
					\Stripe\Stripe::setApiKey($data['secret_key']);
					break;
				case 'getCustomer':
					$stripeCustomer = \Stripe\Customer::retrieve($data['stripe_id']);
					break;
				case 'addCustomer':
					$stripeCustomer = \Stripe\Customer::create(array(
					  "email" => $data['email'],
					  "metadata" => array("name" =>$data['name'])));
					  //"source" => $data['token'],
					break;
					case 'addCard':
						$card = $data['customer']->sources->create(array("source" => $data['token']));
						break;
				case 'addCharge':
					$charge = \Stripe\Charge::create(array(
					  "amount" => $data['amount'],
					  "currency" => $currency,
					  "source" => $data['card']->id, // obtained with Stripe.js,
					  "customer"=>$data['customer'],
					  "metadata" => $data['metadata'],
					  "description" => $data['description']
					));
					break;
				default:
					# code...
					break;
			}
		  
		} catch(\Stripe\Error\Card $e) {
		  // Since it's a decline, \Stripe\Error\Card will be caught
		  /*$body = $e->getJsonBody();
		  $err  = $body['error'];

		  print('Status is:' . $e->getHttpStatus() . "\n");
		  print('Type is:' . $err['type'] . "\n");
		  print('Code is:' . $err['code'] . "\n");
		  // param is '' in this case
		  print('Param is:' . $err['param'] . "\n");
		  print('Message is:' . $err['message'] . "\n");*/
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];
			$data['action'] = $action;
			$data['response'] = 'error';
			$data['line'] = __LINE__;
			if($err){
				$data['full'] = serialize($err);
				$data['message'] =  $err['message'];
			} else {
				$data['full'] = '';
				$data['message'] =  '';
			}
			$data['message2'] = '';
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (\Stripe\Error\InvalidRequest $e) {
		  // Invalid parameters were supplied to Stripe's API
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];
			$data['action'] = $action;
			$data['response'] = 'error';
			$data['line'] = __LINE__;
			if($err){
				$data['full'] = serialize($err);
				$data['message'] =  $err['message'];
			} else {
				$data['full'] = '';
				$data['message'] =  '';
			}
			$data['message2'] =  'Invalid parameters were supplied to Stripe\'s API';
			$data['subject'] =  'Stripe Error';
			switch ($action) {
				default:
					$this->logError($data);
					// Set content type
					header('Content-type: application/json');

					// Prevent caching
					header('Expires: 0');
					echo json_encode($data);
					exit();
					break;
			}
		} catch (\Stripe\Error\Authentication $e) {
		  // Authentication with Stripe's API failed
		  // (maybe you changed API keys recently)
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];
			$data['action'] = $action;
			$data['response'] = 'error';
			$data['line'] = __LINE__;
			if($err){
				$data['full'] = serialize($err);
				$data['message'] =  $err['message'];
			} else {
				$data['full'] = '';
				$data['message'] =  '';
			}
			//$data['message2'] =  'Authentication with Stripe\'s API failed';
			$stripeAuth =  false;
			// Set content type
			/*header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();*/
		} catch (\Stripe\Error\ApiConnection $e) {
		  // Network communication with Stripe failed
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];
			$data['action'] = $action;
			$data['response'] = 'error';
			$data['line'] = __LINE__;
			if($err){
				$data['full'] = serialize($err);
				$data['message'] =  $err['message'];
			} else {
				$data['full'] = '';
				$data['message'] =  '';
			}
			$data['message2'] =  'Network communication with Stripe failed';
			$data['subject'] =  'Stripe Error';
			$this->logError($data);
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (\Stripe\Error\Base $e) {
		  // Display a very generic error to the user, and maybe send
		  // yourself an email
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];
			$data['action'] = $action;
			$data['response'] = 'error';
			$data['line'] = __LINE__;
			if($err){
				$data['full'] = serialize($err);
				$data['message'] =  $err['message'];
			} else {
				$data['full'] = '';
				$data['message'] =  '';
			}
			$data['message2'] =  'General Error';
			$data['subject'] =  'Stripe Error';
			$this->logError($data);
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Exception $e) {
		  // Something else happened, completely unrelated to Stripe
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];
			$data['action'] = $action;
			$data['response'] = 'error';
			$data['line'] = __LINE__;
			if($err){
				$data['full'] = serialize($err);
				$data['message'] =  $err['message'];
			} else {
				$data['full'] = '';
				$data['message'] =  '';
			}
			$data['message2'] =  'General Other Error';
			$data['subject'] =  'Stripe Error';
			$this->logError($data);
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		switch ($action) {
			case 'getCustomer':
			case 'addCustomer':
				return $stripeCustomer;
				break;
			case 'addCard':
				return $card;
				break;
			case 'addCharge':
				return $charge;
				break;
			default:
				# code...
				break;
		}
		
	}
	public function sendEmail($to, $subject, $content, $headers, $template){
		if($template == 'normal'){
			$logo = get_option('wpmerchant_logo');
			$company_name = get_option('wpmerchant_company_name');
			$body = "<table cellspacing='0' cellpadding='0' border='0' style='color:#333;font:14px/18px 'Helvetica Neue',Arial,Helvetica;background:#fff;padding:0;margin:0;width:100%'> 
				<tbody>
					<tr width='100%'> 
						<td valign='top' align='left' style='background:#ffffff'> 
							<table style='border:none;padding:0 16px;margin:50px auto;width:500px'> 
								<tbody> 
									<tr width='100%' height='60'> 
										<td valign='top' align='center' style='border-top-left-radius:4px;border-top-right-radius:4px;background:#ffffff url('') bottom left repeat-x;padding:10px 16px;text-align:center'> 
											<img src='".$logo."' title='".$company_name."' style='font-weight:bold;font-size:18px;color:#fff;vertical-align:top'> 
										</td> 
									</tr> 
									<tr width='100%'> 
										<td valign='top' align='left' style='border-bottom-left-radius:4px;border-bottom-right-radius:4px;background:#fff;padding:18px 16px'>
											<h1 style='margin-top:0'>".$content['title']."</h1> <hr style='clear:both;min-height:1px;border:0;border:none;width:100%;background:#dcdcdc;color:#dcdcdc;margin:16px 0;padding:0'> 
											<div> 
												".$content['body']."
												<br style='clear:both'> 
											</div> 
											<hr style='clear:both;min-height:1px;border:0;border:none;width:100%;background:#dcdcdc;color:#dcdcdc;margin:16px 0;padding:0'> 
										</td> 
									</tr> 
									<!--<tr width='100%'> <td valign='top' align='left' style='padding:16px'> <p style='color:#999'>Control how often you receive notification emails on your <a href='https://templatelauncher.com//' target='_blank'>account page</a>.</p> <p style='color:#999'>Follow <a href='https://twitter.com/intent/follow?user_id=' target='_blank'>@</a> on Twitter or like us on <a href='https://www.facebook.com/' target='_blank'>Facebook</a></p> <p style='color:#999'></p> 
											</td> </tr> -->
								</tbody> 
							</table> 
						</td> 
					</tr>
				</tbody> 
			</table>";
		}
		add_filter( 'wp_mail_content_type', array($this,'set_html_content_type'));
		wp_mail( $to, $subject, $body, $headers );
		remove_filter( 'wp_mail_content_type', array($this,'set_html_content_type'));
	}
	public function logError($data){
		$to = 'shadle3@gmail.com';
		$subject = $data['subject'];
		$content['title'] = 'Error Info';
 		$content['body'] = '<strong>Response:</strong>'.$data['response'].'<br>
			<br><strong>Response:</strong>'.$data['response'].'<br>
		<br><strong>Line:</strong>'.$data['line'].'<br>
		<br><strong>Full:</strong>'.$data['full'].'<br>
		<br><strong>Message:</strong>'.$data['message'].'<br>
		<br><strong>Message2:</strong>'.$data['message2'].'<br>
		<br><strong>Action:</strong>'.$data['action'].'<br>
		<br><strong>Date:</strong>'.date('m/d/y H:i:s').'<br>';
		$template = 'normal';
		$this->sendEmail($to, $subject, $content, $headers, $template);
	}
	public function set_html_content_type(){
		return 'text/html';
	}
	public function mailchimp($MailChimp, $action, $data){
		// Morning Meditation Option - because last and first and zip are included (and in the others it isn't)
		// this file is included in the config.php file

		// subscribe the user to the mettagroup contacts adn morning meditation 1 A lists

		/*$result = $MailChimp->call('lists/subscribe', array(
		                'id'                => $mc['MMListId'],
		                'email'             => array('email'=>$email),
		                'merge_vars'        => array('FNAME'=>trim(strip_tags($_POST['firstName'])), 'LNAME'=>trim(strip_tags($_POST['lastName']))),
		                'double_optin'      => false,
		                'update_existing'   => true,
		                'replace_interests' => false,
		                'send_welcome'      => false,
		            ));*/
			switch ($action) {
				case 'listSubscribe':
					if(isset($data['grouping_name'])){
						$groupings['name'] = $data['grouping_name'];
					}
					if(isset($data['group_name'])){
						$groupings['groups'] = array($data['group_name']);
					} 
					if(isset($groupings)){
						$merge_vars = array(
									'FNAME'=>$data['first_name'], 
									'LNAME'=>$data['last_name'], 
									'groupings'=>array(
		                               $groupings
		                             ));
					} else {
						$merge_vars = array(
									'FNAME'=>$data['first_name'], 
									'LNAME'=>$data['last_name']
								);
					}
					$result = $MailChimp->call('lists/subscribe', array(
				                'id'                => $data['list_id'],
				                'email'             => array('email'=>$data['email']),
				                'merge_vars'        => $merge_vars,
								'double_optin'      => false,
				                'update_existing'   => true,
				                'replace_interests' => true,
				                'send_welcome'      => false,
				            ));
					break;
				case 'addInterestGrouping':
					$result = $MailChimp->call('lists/interest-grouping-add', array(
						'id'=> $data['list_id'],
						'name'=>$data['name']
					));
					break;
				case 'getInterestGroups':
					$result = $MailChimp->call('lists/interest-groupings', array('id'=> $data['list_id']));
					break;
				case 'getLists':
					$result = $MailChimp->call('lists/list', array('limit'=>100));
					break;
				default:
					# code...
					break;
			}
			if(isset($result['status']) && $result['status'] == 'error'){
				$data['action'] = $action;
				$data['response'] = 'error';
				$data['line'] = __LINE__;
				if($err){
					$data['full'] = serialize($result);
					$data['message'] =  $result['status'];
				} else {
					$data['full'] = '';
					$data['message'] =  '';
				}
				$data['subject'] =  'MailChimp Error';
				$data['message2'] =  'General Error';
				$this->logError($data);	
				// don't throw an error to the user because			
				return $result;
			} else {
				return $result;
			}
			
	}
	/**
	 * Ajax Function to get payment api data -  apikeys
	 *
	 * @since    1.0.0
	*/
	public function get_payment_data(){
		if(!check_ajax_referer( 'wpmerchant_get_payment_data', 'security', false )){
			$data = 'error'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		$payment_processor = get_option( $this->plugin_name.'_payment_processor' );
		if($payment_processor == 'stripe'){
			
				$test_public_key = get_option( $this->plugin_name.'_'.$payment_processor.'_test_public_key' );
				$test_secret_key = get_option( $this->plugin_name.'_'.$payment_processor.'_test_secret_key' );
				$live_public_key = get_option( $this->plugin_name.'_'.$payment_processor.'_live_public_key' );
				$live_secret_key = get_option( $this->plugin_name.'_'.$payment_processor.'_live_secret_key' );
			
			
			if(!$test_public_key || !$test_secret_key || !$live_public_key || !$live_secret_key){
				// notify the js that hte api key doesn't exist yet
				if(!isset($_COOKIE['wpm_sc_count'])){
					$count = 1;
				} else {
					$count = $_COOKIE['wpm_sc_count'] + 1;
				}
				// This limits the number of polling calls that can be made to 5000
				if($count <= 5000){
					$data['response'] = 'empty';
					setcookie( "wpm_sc_count", $count, strtotime( '+1 days' ) );
				} else {
					$data['response'] = 'error';
					setcookie( "wpm_sc_count", '0', time() - 3600 );
				}
				$data['count'] = $count;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			}
			
	  		$data['response'] = 'success';
			// don't need to pass the apikeys to the front end because we can just reload the page to get that info
			/*$data['secret_key'] = $secret_key;
			$data['public_key'] = $public_key;*/
	  		setcookie( "wpm_sc_count", '0', time() - 3600 );
			// Set content type
	  		header('Content-type: application/json');

	  		// Prevent caching
	  		header('Expires: 0');
	  		echo json_encode($data);
	  		/* close connection */
	  		exit();		
		}
		
	}
	/**
	 * Ajax Function to get email api data - lists and apikey
	 *
	 * @since    1.0.0
	*/
	public function get_email_data(){
		if(!check_ajax_referer( 'wpmerchant_get_email_data', 'security', false )){
			$data = 'error'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		$email_list_processor = get_option( $this->plugin_name.'_email_list_processor' );
		if($email_list_processor == 'mailchimp'){
			$email_list_processor_config['apiKey'] = get_option( $this->plugin_name.'_'.$email_list_processor.'_api' );
			
			if(!$email_list_processor_config['apiKey']){
				// notify the js that hte api key doesn't exist yet
				if(!isset($_COOKIE['wpm_mc_count'])){
					$count = 1;
				} else {
					$count = $_COOKIE['wpm_mc_count'] + 1;
				}
				// This limits the number of polling calls that can be made to 1000
				if($count <= 1000){
					$data['response'] = 'empty';
					setcookie( "wpm_mc_count", $count, strtotime( '+1 days' ) );
				} else {
					$data['response'] = 'error';
					setcookie( "wpm_mc_count", '0', time() - 3600 );
				}
				$data['count'] = $count;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			}
			require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/MailChimp-API-Class/mail_chimp.php';
			//$email_list_processor_config['apiKey'] = '97dabbf56224377e2bbfe467741ba898-us7';
			
			if (!class_exists('MailChimp')) {
				$data = 'error'.__LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
				//$myclass = new MyClass();
			}
			$EmailAPI = new MailChimp($email_list_processor_config['apiKey']);
			$data = array();
			$lists = $this->$email_list_processor($EmailAPI,'getLists',$data);
			
			$mailchimpLists[] = array('value'=> '', 'name' => 'Select MailChimp List');
			if($lists){
				foreach($lists['data'] AS $l){
					$mailchimpLists[] = array('value'=> $l['id'], 'name' => $l['name']);
				}	
			} 
			
	  		$data['response'] = 'success';
	  		$data['lists'] = $mailchimpLists;
			$data['lists2'] = $lists;
			// don't need to pass the apikey to the front end because we can just reload the page to get that info
				//$data['apikey'] = $email_list_processor_config['apiKey'];
				
	  		// Set content type
			setcookie( "wpm_mc_count", '0', time() - 3600 );
	  		header('Content-type: application/json');

	  		// Prevent caching
	  		header('Expires: 0');
	  		echo json_encode($data);
	  		/* close connection */
	  		exit();		
		}
		
	}
	/**
	 * Ajax Function Run to save email api key
	 *
	 * @since    1.0.0
	*/
	public function save_email_api_key(){		
		$nonce = sanitize_text_field($_POST['security']);
		$user_id = intval($_POST['uid']);
		// verify the self created nonce - don't use wp_verify_nonce bc it checks the referring url and it is different 
		$email_processor_nonce = get_option( $this->plugin_name.'_save_email_api_nonce' );
		wp_set_current_user( $user_id );
		
		if(!current_user_can( 'administrator' ) || $email_processor_nonce != $nonce){
		//wp_set_current_user( $user_id );
		//if(!$user_id || !wp_verify_nonce($nonce,'wpmerchant_save_email_api')){
			$data['response'] = 'error'.__LINE__;
			$data['vars'] = $_POST;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		
		$email_list_processor = get_option( $this->plugin_name.'_email_list_processor' );
		// Set the or conditional so that if hte user hasn't saved the first section of hte settings mailchimp connect will still work
		if($email_list_processor == 'mailchimp' || !$email_list_processor){
			$apiKey = sanitize_text_field($_POST['apikey']);
			update_option( $this->plugin_name.'_'.$email_list_processor.'_api', $apiKey );
	  		$data['response'] = 'success';
	  		// Set content type
	  		header('Content-type: application/json');

	  		// Prevent caching
	  		header('Expires: 0');
	  		echo json_encode($data);
	  		/* close connection */
	  		exit();		
		}
		
	}
	/**
	 * Ajax Function Run to save payment api key
	 *
	 * @since    1.0.0
	*/
	public function save_payment_api_key(){		
		$nonce = sanitize_text_field($_POST['security']);
		$user_id = intval($_POST['uid']);
		// verify the self created nonce - don't use wp_verify_nonce bc it checks the referring url and it is different 
		$payment_processor_nonce = get_option( $this->plugin_name.'_save_payment_api_nonce' );
		wp_set_current_user( $user_id );
		
		if(!current_user_can( 'administrator' ) || $payment_processor_nonce != $nonce){
		//wp_set_current_user( $user_id );
		//if(!$user_id || !wp_verify_nonce($nonce,'wpmerchant_save_email_api')){
			$data['response'] = 'error'.__LINE__;
			$data['vars'] = $_POST;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		
		$payment_processor = get_option( $this->plugin_name.'_payment_processor' );
		// Set the or conditional so that if hte user hasn't saved the first section of hte settings stripe connect will still work
		if($payment_processor == 'stripe' || !$payment_processor){
			$live_secret_key = sanitize_text_field($_POST['live_secret_key']);
			$live_public_key = sanitize_text_field($_POST['live_public_key']); 
			$test_secret_key = sanitize_text_field($_POST['test_secret_key']);
			$test_public_key = sanitize_text_field($_POST['test_public_key']);
			
			update_option( $this->plugin_name.'_'.$payment_processor.'_test_public_key', $test_public_key );
			update_option( $this->plugin_name.'_'.$payment_processor.'_test_secret_key', $test_secret_key );
			update_option( $this->plugin_name.'_'.$payment_processor.'_live_public_key', $live_public_key );
			update_option( $this->plugin_name.'_'.$payment_processor.'_live_secret_key', $live_secret_key );
			
	  		$data['response'] = 'success';
	  		// Set content type
	  		header('Content-type: application/json');

	  		// Prevent caching
	  		header('Expires: 0');
	  		echo json_encode($data);
	  		/* close connection */
	  		exit();		
		}
		
	}
	/**
	 * Ajax Function Run following product purchase
	 *
	 * @since    1.0.0
	 */
	public function purchase() {
		$nonce = sanitize_text_field($_POST['security']);
		// verify the self created nonce - don't use wp_verify_nonce bc it checks the referring url and it is different 
		$payment_processor_nonce = get_option( $this->plugin_name.'_purchase_nonce' );
		
		if($payment_processor_nonce != $nonce){
		//wp_set_current_user( $user_id );
		//if(!$user_id || !wp_verify_nonce($nonce,'wpmerchant_save_email_api')){
			$data['response'] = 'error'.__LINE__;
			$data['vars'] = $_POST;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		$payment_processor = get_option( $this->plugin_name.'_payment_processor' );
		//$payment_processor = 'stripe';
		//$email_list_processor = 'mailchimp';
		$email_list_processor = get_option( $this->plugin_name.'_email_list_processor' );
		// dirname gets the parent directory of this file and plugin_dir_path gets the absolute path of the server up to that point allowing us to access the includes directory
		if($payment_processor == 'stripe'){
			require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/stripe-php-2.2.0/init.php';
		}
		if($email_list_processor == 'mailchimp'){
			require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/MailChimp-API-Class/mail_chimp.php';
		}
		
		// Created WPL Payments table - in the activator file
			// Have it include all of the user information (so that we can convert those users into wordpress someone upgrades to a pro plugin)
			// THis is for us to track which email addresses are associated iwth which stripe customer while also allowing us to separate the default wordpress user with a wpl user (so that the pro version has more value)
		$payment_processor_config['status'] = get_option($this->plugin_name.'_'.$payment_processor.'_status' );
		
		if($payment_processor_config['status'] == 'live'){
			$payment_processor_config['secret_key'] = get_option( $this->plugin_name.'_'.$payment_processor.'_live_secret_key' );
			$payment_processor_config['public_key'] = get_option( $this->plugin_name.'_'.$payment_processor.'_live_public_key' );
		} else {
			$payment_processor_config['secret_key'] = get_option( $this->plugin_name.'_'.$payment_processor.'_test_secret_key' );
			$payment_processor_config['public_key'] = get_option( $this->plugin_name.'_'.$payment_processor.'_live_public_key' );
		}
    	$email_list_processor_config['apiKey'] = get_option( $this->plugin_name.'_'.$email_list_processor.'_api' );
		$email_list_processor_config['genListId'] = get_option( $this->plugin_name.'_'.$email_list_processor.'_gen_list_id' );
		
		//These variables allow us to eventually create an option where users can select the payment processor and teh email list processor.  Then, we just need to create functions for each payment processor and email list processor that we provide user's access to.
		if($email_list_processor == 'mailchimp'){
			$EmailAPI = new MailChimp($email_list_processor_config['apiKey']);
		}
		
		$this->$payment_processor('setApiKey',$payment_processor_config);
		
		/**
		FREE VERSION
		**/
		// SEE IF THE CUSTOMER EXISTS IN THE WPMERCHANT_CUSTOMERS TABLE
			// this table was created in the activation hook
		// if customer doesn't exist in wp table then insert new record into wpmerchant customer table
		//$inputAmount = intval($_POST['amount']);
		$inputWPMCustomer['token'] = sanitize_text_field($_POST['token']);
		if(isset($_POST['firstName']) && isset($_POST['lastName'])){
			$inputWPMCustomer['name'] = sanitize_text_field($_POST['firstName']). ' '. sanitize_text_field($_POST['lastName']);
			$inputWPMCustomer['first_name'] = sanitize_text_field($_POST['firstName']);
			$inputWPMCustomer['last_name'] = sanitize_text_field($_POST['lastName']);
		} else {
			$inputWPMCustomer['first_name'] = '';
			$inputWPMCustomer['last_name'] = '';
			$inputWPMCustomer['name'] = '';
		}
		if(isset($_POST['zip'])){
			$inputWPMCustomer['zip'] = sanitize_text_field($_POST['zip']);
		} else {
			$inputWPMCustomer['zip'] = '';
		}
		$inputWPMCustomer['email'] = sanitize_email($_POST['email']);
		// checking if customer exists in wp database
		if($payment_processor_config['status'] == 'live'){
			// only get and write customers to the database if it is the live mode
			// we don't want to hvae emails assoc with test stripe ids - this screws up the switching from test to live 
			// if we reuse customer stripe ids from one mode to another they are wrong so the functinoality won't work
			$wpMCustomer = $this->getWPMCustomer($inputWPMCustomer);
		
			// if customer doesn't already exist in the db
			if(!$wpMCustomer){
				// NEED TO ADD USER AS A STRIPE CUSTOMER FIRST before adding to wp table
				$payment_processor_customer = $this->$payment_processor('addCustomer',$inputWPMCustomer);
				// Then, add user to the wpmerchant_customer table
				if($payment_processor == 'stripe'){
					$inputWPMCustomer['stripe_id'] = $payment_processor_customer->id;
				}
				$affectedRows = $this->addWPMCustomer($inputWPMCustomer);
				if(!$affectedRows){
					//include 'error.html.php';
					$data = 'error'.__LINE__;
					// Set content type
					header('Content-type: application/json');

					// Prevent caching
					header('Expires: 0');
					echo json_encode($data);
					exit();
				}
			} else {
				// Get Stripe Customer - check by making sure the existing or new user exists as a stripe customer
				unset($data);
				if($payment_processor == 'stripe'){
					$data = array('stripe_id'=>$wpMCustomer->stripe_id);
				}
				$payment_processor_customer = $this->$payment_processor('getCustomer',$data);
				if(!$payment_processor_customer){
					// add stripe customer
					$payment_processor_customer = $this->$payment_processor('addCustomer',$inputWPMCustomer);
				}
			}
		} else {
			// create a test stripe user (it doesn't matter if they already exist we don't care)
			// DONT ADD TO WORDPRESS DATABASE BC WE DONT WANT TEST STRIPE IDS THERE
			$payment_processor_customer = $this->$payment_processor('addCustomer',$inputWPMCustomer);
		}
		// Add card to Stripe Customer
		unset($data);
		$data = array('token'=>$inputWPMCustomer['token'], 'customer'=>$payment_processor_customer);
		$card = $this->$payment_processor('addCard',$data);
		
		if(isset($_POST['products']) && $_POST['products']){
			// we're sent a json_encoded array with quantity and product id in each array
			// this function is for a singular product or multiple products
			$products = json_decode(stripslashes($_POST['products']));
			foreach($products AS $key=>$value){
				$p = trim($value->id);
				$p = intval($p);
				$quantity = trim($value->quantity);
				$quantity = intval($quantity);
				if(!$p || !$quantity){
					continue;
				}
				$displayKey = $key+1;
				//$product = get_post($product_id)
				$cost = get_post_meta( $p, $this->plugin_name.'_cost', true );
				$title = get_the_title( $p );
				// add this product's amount onto the sum of all previous products
				if($key == 0){
					$amount = $cost*100*$quantity;
					$description = $title;
				} else {
					$amount += $cost*100*$quantity;
					$description .= '. '.$title;
				}
		
				// Check inventory - is the item in stock.  If in stock continue.
				$stock_status = get_post_meta( $p, $this->plugin_name.'_stock_status', true );	
				if(!$stock_status){
					// if the item is out of stock, check to see if they allow backorders
					//if they do allow bakcorders the user continues below to charge
					$allow_backorders = get_post_meta( $p, $this->plugin_name.'_allow_backorders', true );		
					if(!$allow_backorders){
						// if they don't allow backorders, send sold out message back for display
						$sold_out_message = get_post_meta( $p, $this->plugin_name.'_sold_out_message', true );
						$sold_out_message = do_shortcode($sold_out_message); 
						// dont need this because you want shortcode result to display NOT the shortcode itself
						//$sold_out_message = apply_filters('the_content', $sold_out_message); 
						$data['response'] = 'sold_out';
						$data['product'] = $products[$key];
						$data['message'] = $sold_out_message;
						// Set content type
						header('Content-type: application/json');

						// Prevent caching
						header('Expires: 0');
						echo json_encode($data);
						exit();
					}
				}
				$inventory = get_post_meta( $p, $this->plugin_name.'_inventory', true );
				// use three = signs because it shows that it's 0 rather than null
				if($inventory || $inventory === 0){
					$products[$key]->new_inventory = $inventory - $quantity;
				}
				
				$metadata["product_id_$displayKey"] = $p;
				$metadata["product_name_$displayKey"] = $title;
				$metadata["product_quantity_$displayKey"] = $quantity;
			}
			
			
			// charge user for all of the products at once
			unset($data);
			$data = array('card'=>$card,'customer'=>$payment_processor_customer,'product_description'=>$description,'metadata'=>$metadata,'amount'=>$amount,'token'=>$inputWPMCustomer['token']);
			$charge = $this->$payment_processor('addCharge',$data);
		
			// Mark Inventory - Since hte charge was successful put hte inventory number htat was obtained from above
			foreach($products AS $key=>$value){
				$p = trim($value->id);
				if(isset($value->new_inventory)){
					update_post_meta( $p, $this->plugin_name.'_inventory', $value->new_inventory );
				}
			}
			// IF THE MailChimp genListId exists then Subscribe user to MailChimp General Interest List 
			if($email_list_processor_config['genListId']){
				unset($data);
				$data = array('list_id'=> $email_list_processor_config['genListId'], 'first_name'=> $inputWPMCustomer['first_name'], 'last_name'=>$inputWPMCustomer['last_name'],'email'=>$inputWPMCustomer['email']);
				$this->$email_list_processor($EmailAPI,'listSubscribe',$data);
			}
			
			//Redirect user to this page upon successful purchase
			$successRedirect = get_option($this->plugin_name.'_post_checkout_redirect');
			$data['redirect'] = (isset($successRedirect)) ? $successRedirect : '';
			$data['response'] = 'success';
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			/* close connection */
			exit();		
			
		}
		
		$data['response'] = 'success';
		// Set content type
		header('Content-type: application/json');

		// Prevent caching
		header('Expires: 0');
		echo json_encode($data);
		/* close connection */
		exit();		
	}
}
