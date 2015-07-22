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
		add_action( 'wp_ajax_wpmerchant_purchase', array($this,'purchase'));
		add_action( 'wp_ajax_wpmerchant_get_plan', array($this,'getPlan'));
		add_action( 'wp_ajax_wpmerchant_add_plan', array($this,'addPlan'));
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
	  	  $stripe_status = get_option( $this->plugin_name.'_stripe_status' );
	  	  if($stripe_status == 'live'){
	  	  	$stripe_public_key = get_option( $this->plugin_name.'_stripe_live_public_key' );
	  	  } else {
	  	  	$stripe_public_key = get_option( $this->plugin_name.'_stripe_test_public_key' );
	  	  }
  		// get option for company name
  		$companyName = get_option( $this->plugin_name.'_company_name' );
  		//http://www.jeansuschrist.com/wp-content/uploads/2015/05/stripe-logo.png
  		$image = get_option( $this->plugin_name.'_logo' );
  		if(!$image){
  			$image = '/wp-content/plugins/'.$this->plugin_name.'/public/img/marketplace.png';
  		}
		$currency1 = unserialize(get_option( $this->plugin_name.'_currency' ));
		$currency = $currency1['value'];
    	  // pass ajax object to this javascript file
		  // Add nonce values to this object so that we can access them in hte public.js javascript file
		  //wp_localize_script() MUST be called after the script it's being attached to has been registered using
		  wp_localize_script( $this->plugin_name, 'ajax_object', 
		  	array( 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'purchase_nonce'=> $purchaseNonce,
				'public_key'=>$stripe_public_key,
				'currency'=>$currency,
				'image'=>$image,
				'company_name'=>$companyName
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
	 * Insert record into the WPMerchant Customer Table
	 *
	 * @since    1.0.0
	 */
	public function getWPMCustomer($wpmCustomer){
		global $wpdb;
		// Get the wpmerchant db version to make sure the functions below are= compatible with the db version
    	$wpmerchant_db_version = get_option( $this->plugin_name.'_db_version' );
		$table_name = $wpdb->prefix . 'wpmerchant_customers';
		// Make sure the insert statement is compatible with the db version		
		$email = $wpmCustomer['email'];
		$wpmCustomer2 = $wpdb->get_row("SELECT * FROM $table_name WHERE email = '$email'");
		return $wpmCustomer2;
	}
	
	public function stripe($action, $data){
		$currency1 = unserialize(get_option( $this->plugin_name.'_currency' ));
		$currency = $currency1['value'];
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
				case 'addPlan':
					// max subscription is yearly
					// interval_count max is 365
					// interval - day, week, month, year
					$plan = \Stripe\Plan::create(
						array( 
							"amount" => $data['amount'],
							"interval" => $data['interval'],
							"interval_count" => $data['interval_count'],
							"trial_period_days" => $data['trial_period_days'],
							"name" => $data['name'], 
							"currency" => $currency,
							"id" => $data['id'],
							"metadata" => array("post_id" =>$data['post_id'])
						) 
					);
					break;
				case 'getPlan':
					$plan = \Stripe\Plan::retrieve($data['planId']);
					break;
				case 'updatePlan':
					// you can only update the plan's name - nothing else
					$data['stripePlan']->name = $data['name'];
					$data['stripePlan']->save();
					$plan = $data['stripePlan'];
					break;
				case 'getSubscriptions':
					$subscriptions = $data['customer']->subscriptions->all(array('limit'=>100));
					break;
				case 'addSubscription':
					$subscriptionArray = array("plan" => $data['plan_id'],"quantity"=>$data['quantity'],'metadata'=>$data['metadata']);
					if(isset($data['coupon']) && $data['coupon']){
						$subscriptionArray['coupon'] = $data['coupon'];
					}
					if(isset($data['trial_end']) && $data['trial_end']){
						$subscriptionArray['trial_end'] = $data['trial_end'];
					}
					$subscription = $data['customer']->subscriptions->create($subscriptionArray);
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
				case 'getPlan':
					$plan = false;
					break;
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
			case 'addPlan':
			case 'getPlan':
			case 'updatePlan':
				return $plan;
				break;
			case 'getSubscriptions':
				return $subscriptions;
				break;
			case 'addSubscription':
				return $subscription;
				break;
			default:
				# code...
				break;
		}
		
	}
	public function logError($data){
		$to = 'shadle3@gmail.com';
		$subject = $data['subject'];
		$body = 'Error Info<br><strong>Response:</strong>'.$data['response'].'<br>
			<br><strong>Response:</strong>'.$data['response'].'<br>
		<br><strong>Line:</strong>'.$data['line'].'<br>
		<br><strong>Full:</strong>'.$data['full'].'<br>
		<br><strong>Message:</strong>'.$data['message'].'<br>
		<br><strong>Message2:</strong>'.$data['message2'].'<br>
		<br><strong>Action:</strong>'.$data['action'].'<br>
		<br><strong>Date:</strong>'.date('m/d/y H:i:s').'<br>';
		$headers = array('Content-Type: text/html; charset=UTF-8');

		wp_mail( $to, $subject, $body, $headers );
		
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
	* Ajax functio nfor adding a plan
	* @since    1.0.0
	*
	**/
	public function getPlan(){
		// nonce value is set in hte enqueue scripts function above
		if(!check_ajax_referer( 'wpmerchant_get_plan', 'security', false )){
			$data = 'error'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
	}
	/**
	* Ajax functio nfor adding a plan
	* @since    1.0.0
	*
	**/
	public function addPlan(){
		// nonce value is set in hte enqueue scripts function above
		if(!check_ajax_referer( 'wpmerchant_add_plan', 'security', false )){
			$data = 'error'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
		$post_id = intval($_POST['post_id']);
	    $post = get_post($post_id);

		$payment_processor = get_option( $this->plugin_name.'_payment_processor' );
		//$payment_processor = 'stripe';

		// Make sure the post_type is wpmerchant plans
		if($post->post_type != 'wpmerchant_plans'){
			return;
		}
		require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/stripe-php-2.2.0/init.php';
		$stripe['status'] = get_option($this->plugin_name.'_stripe_status' );

		if($stripe['status'] == 'live'){
			$stripe['secret_key'] = get_option( $this->plugin_name.'_stripe_live_secret_key' );
			$stripe['public_key'] = get_option( $this->plugin_name.'_stripe_live_public_key' );
		} else {
			$stripe['secret_key'] = get_option( $this->plugin_name.'_stripe_test_secret_key' );
			$stripe['public_key'] = get_option( $this->plugin_name.'_stripe_live_public_key' );
		}

		$this->$payment_processor('setApiKey',$stripe);
		
		$title = sanitize_text_field( $_POST['name'] );
		// Sanitize user input.
		$plan_cost = intval( $_POST['cost'] ); 
		//$plan_description = wp_kses_post( $_POST[$this->plugin_name.'_description'] );
		$interval =  sanitize_text_field($_POST['interval'] );
		$interval_count = intval( $_POST['interval_count'] );
		$trial_period_days = intval( $_POST['trial_period_days'] );
		$id = sanitize_text_field( $_POST['stripe_plan_id'] );
		
		if($plan_cost && $interval && $interval_count && $id){
			// included stripe api library above
			// See if hte plan exists in stripe		
			unset($data);
			$data = array('planId'=>$id);
			$stripePlan = $this->$payment_processor('getPlan',$data);
			unset($data);
			if(!$stripePlan){
				// add plan
				$data = array('amount'=>$plan_cost,'trial_period_days'=>$trial_period_days,'interval'=>$interval,'interval_count'=>$interval_count,'id'=>$id,'name'=>$title,'post_id'=>$post_id);
				$stripePlan = $this->$payment_processor('addPlan',$data);
			} else {
				// update plan
				$data = array('amount'=>$plan_cost,'interval'=>$interval,'interval_count'=>$interval_count,'stripePlan'=>$stripePlan,'name'=>$title);
				$stripePlan = $this->$payment_processor('updatePlan',$data);
			}		
			$data['response'] = 'success';
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			/* close connection */
			exit();	
		} else {
			$data['response'] = 'requires-all';
			$data['vars'] = var_dump($plan_cost, $interval, $interval_count, $id);
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
		if(!check_ajax_referer( 'wpmerchant_purchase', 'security', false )){
			$data = 'error'.__LINE__;
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
			$EmailAPIName = $email_list_processor.'_api';
			$$EmailAPIName = new MailChimp($email_list_processor_config['apiKey']);
		}
		
		
		$this->$payment_processor('setApiKey',$payment_processor_config);
		
		/**
		FREE VERSION
		**/
		// SEE IF THE CUSTOMER EXISTS IN THE WPMERCHANT_CUSTOMERS TABLE
			// this table was created in the activation hook
		// if customer doesn't exist in wp table then insert new record into wpmerchant customer table
		$inputAmount = intval($_POST['amount']);
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
						$sold_out_message = apply_filters('the_content', $sold_out_message); 
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
				$this->$email_list_processor($$EmailAPIName,'listSubscribe',$data);
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
		/**
		PRO VERSION
		**/ 
		// validate coupone
			// add coupon to the customer
			//$stripeCoupon = Stripe_Coupon::retrieve(trim(strip_tags($_POST['promoCode'])));
		/**
		GOLD VERSION: ADD SUBSCRIPTION TO CUSTOMER
		**/
		// make sure plan has been passed		
		if(isset($_POST['plans']) && $_POST['plans']){
			// we're sent a json_encoded array with quantity and plan id in each array
			// this function is for a singular plans or multiple plans
			$plans = json_decode(stripslashes($_POST['plans']));
			
			foreach($plans AS $key=>$value){
				$p = trim($value->id);
				$p = intval($p);
				if(!$p){
					continue;
				}
				// create this so that we can check the existing plans against this new plan array
				$new_plan_ids[] = $p;
			}
			
			// Get all subscriptions assoc with user
			// see if hte subscription is assoc with the stripe customer (if hte customer is a new customer)	
			// add the subscription to the stripe customer
			$data = array('customer'=>$payment_processor_customer);
			$existingSubscriptions = $this->$payment_processor('getSubscriptions',$data);
			if($existingSubscriptions && $existingSubscriptions['data']){
				$subCount = 0;
				foreach($existingSubscriptions['data'] AS $s){
					if(in_array($s->plan->id,$new_plan_ids)){
						//Cancel the existing subscription so you can replace it with the newly entered card
							//$stripeCustomer->subscriptions->retrieve($s->id)->cancel();
						$subCount = $subCount + 1;
					}
				}
				if($subCount > 0){
					$data['response'] = 'subscriptionExists';
					// Set content type
					header('Content-type: application/json');

					// Prevent caching
					header('Expires: 0');
					echo json_encode($data);
					exit();
				}
			}
			
			foreach($plans AS $key=>$value){
				$p = trim($value->id);
				$p = intval($p);
				$quantity = trim($value->quantity);
				$quantity = intval($quantity);
				if(!$p || !$quantity){
					continue;
				}
				//$product = get_post($product_id)
				$email_list_processor_config['subListId'] = get_option($p, $this->plugin_name.'_sub_list_id', true );
				$email_list_processor_config['subGroupingName'] = get_option( $p, $this->plugin_name.'_sub_grouping_name', true );
				$email_list_processor_config['subGroupName'] = get_option( $p, $this->plugin_name.'_sub_group_name', true );
				$payment_processor_plan_id = get_post_meta( $p, $this->plugin_name.'_'.$payment_processor.'_plan_id', true );
				$title = get_the_title( $p );

				// Check inventory - is the item in stock.  If in stock continue.
				$stock_status = get_post_meta( $p, $this->plugin_name.'_stock_status', true );	
				if(!$stock_status){
					// if the item is out of stock, check to see if they allow backorders
					//if they do allow bakcorders the user continues below to charge
					$allow_backorders = get_post_meta( $p, $this->plugin_name.'_allow_backorders', true );		
					if(!$allow_backorders){
						// if they don't allow backorders, send sold out message back for display
						$sold_out_message = get_post_meta( $p, $this->plugin_name.'_sold_out_message', true );
						$sold_out_message = apply_filters('the_content', $sold_out_message); 
						$data['response'] = 'sold_out';
						$data['product'] = $plans[$key];
						$data['message'] = $sold_out_message;
						// Set content type
						header('Content-type: application/json');

						// Prevent caching
						header('Expires: 0');
						echo json_encode($data);
						exit();
					}
				}
				$metadata["plan_id"] = $p;
				$metadata["plan_name"] = $title;
				$metadata["plan_quantity"] = $quantity;
				
				$trial_end = (!isset($value->trial_end)) ? '' : sanitize_text_field($trial_end);
				$coupon = (!isset($value->coupon)) ? '' : sanitize_text_field($coupon);
				unset($data);
				$data = array('customer'=>$payment_processor_customer,'plan_id'=>$payment_processor_plan_id,'quantity'=>$quantity,'coupon'=>$coupon,'trial_end'=>$trial_end,'metadata'=>$metadata);
				$subscription = $this->$payment_processor('addSubscription',$data);
			}
				// Subscribe user to MailChimp General Interest List and the Group provided OR just the Subscriber list id provided 
			unset($data);
			
			// grouping is the name of a bunch of groups
				// for example sports could be a grouping and basketball, football, golf could be groups
			
			if(!$email_list_processor_config['subGroupingName'] && !$email_list_processor_config['subGroupName']){
				$data = array('list_id'=> $email_list_processor_config['subListId'], 'first_name'=> $inputWPMCustomer['first_name'], 'last_name'=>$inputWPMCustomer['last_name'],'email'=>$inputWPMCustomer['email']);
			} else {
				$data = array('list_id'=> $email_list_processor_config['genListId'], 'first_name'=> $inputWPMCustomer['first_name'], 'last_name'=>$inputWPMCustomer['last_name'],'email'=>$inputWPMCustomer['email']);
				if($email_list_processor_config['subGroupingName']){
					$data['grouping_name'] = $email_list_processor_config['subGroupingName'];
				} 
				if($email_list_processor_config['subGroupName']){
					$data['group_name'] = $email_list_processor_config['subGroupName'];
				} 
			}
			$this->$email_list_processor($$EmailAPIName,'listSubscribe',$data);
			
			/*$lists = $this->$email_list_processor($MailChimpAPI,'getLists');
			$data = array('list_id'=> $mailchimp['genListId']);
			$intGroups = $this->$email_list_processor($MailChimpAPI,'getInterestGroups',$data);*/
			
			
			$data['response'] = 'success';
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			/* close connection */
			exit();		
		} else {
			$data['response'] = 'err'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			/* close connection */
			exit();		
			
		}
		
		/**
		TEST THE SUBScRI{TION FUNCTIONALTY ABOVE BUT START HERE
		**/
		/**
		GOLD VERSION
		**/
		/**
		IF USER WANTS TO USE THE MEMBERSHIP FUNCTIONALTIY Make sure hte membershgip settings have been enabled
		**/
		//$membership = get_option( $this->plugin_name.'_membership' );
		if($membership){
			// ADD WORDPRESS USER 
				// After checking if username exists and if email exists and creating a password
			$existing_user_id = username_exists( $inputWPMCustomer['email'] );
			if ( !$existing_user_id and email_exists($inputWPMCustomer['email']) == false ) {
				$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$user_id = wp_create_user( $inputWPMCustomer['email'], $random_password, $inputWPMCustomer['email'] );
			} else {
				$user_id = $existing_user_id;
			}

			// Make sure the Group Table exists
			global $wpdb;
			$tables = wpdb::tables ('all', false );
			foreach($tables AS $t){
				if($t == 'groups_user_group'){
					$tableExists = true;
				}
			}
			if(isset($tableExists)){
				// REMOVE USER FROM ANY CURRENT GROUP ASSOCIATIONS THAT MAY EXIST
				$this->deleteUserGroup($user_id);
			
				// ADD USER TO WORDPRESS GROUP specified in plugin options
				$newUserGroupRecord = $this->addUserGroup($user_id);
				if(!$newUserGroupRecord){
					$data['response'] = 'error_'.__LINE__;
					$data['message'] = 'group_associated_fail';
					// Set content type
					header('Content-type: application/json');
					// Prevent caching
					header('Expires: 0');
					echo json_encode($data);
					exit();
				}
			
			} else {
				$data['response'] = 'error_'.__LINE__;
				$data['message'] = 'install_groups_plugin';
				// Set content type
				header('Content-type: application/json');
				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			}
	
			if(isset($_POST['planId']) == 1){
			/**
			SEND AN EMAIL GIVING MORNING MEDITATION USERS A RECEIPT ALSO CONTAINING USERNAME AND PASSWORD
			wpMandrill::mail($to, $subject, $html, $headers = '', $attachments = array(), $tags = array(), $from_name = '', $from_email = '', $template_name = '');

			**/
			}
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
	/**
	 * Delete record from the Groups Plugin Table
	 *
	 * @since    1.0.0
	 */
	public function deleteUserGroup($user_id){
		global $wpdb;
		// Get the wpmerchant db version to make sure the functions below are= compatible with the db version
    	$wpmerchant_db_version = get_option( $this->plugin_name.'_db_version' );
		$table_name = $wpdb->prefix . 'groups_user_group';
		// Make sure the insert statement is compatible with the db version		
		
		$wpdb->delete( $table_name, array( 'user_id' => $user_id ) );
	}
	/**
	 * Insert record into the Groups Plugin Table
	 *
	 * @since    1.0.0
	 */
	public function addUserGroup($user_id){
		global $wpdb;
		// Get the wpmerchant db version to make sure the functions below are= compatible with the db version
    	$wpmerchant_db_version = get_option( $this->plugin_name.'_db_version' );
		$table_name = $wpdb->prefix . 'groups_user_group';
		// user gives us the groups group id on the settings page - they get it from the groups-admin page
			//https://mettagroup.org/wp-admin/admin.php?page=groups-admin
		$group_id = get_option( $this->plugin_name.'_groups_group_id' );
		if($group_id){
			// Make sure the insert statement is compatible with the db version		
			$sql = $wpdb->insert( 
				$table_name, 
				array( 
					'user_id' => $user_id, 
					'group_id' => $group_id
				) 
			);
		} else {
			$sql = false;
		}
		
		return $sql;
	}
	/**
	 * Ajax Function To VAlidate Coupon
	 *
	 * @since    1.0.0
	 */
	public function validateCoupon(){
		if(isset($_POST['promoCode']) && $_POST['promoCode']){
			try{
				$stripeCoupon = Stripe_Coupon::retrieve(trim(strip_tags($_POST['promoCode'])));
			} catch(Stripe_CardError $e) {
			  // Since it's a decline, Stripe_CardError will be caught
			  	$body = $e->getJsonBody();
			  	$err  = $body['error'];

			  	//print('Status is:' . $es->getHttpStatus() . "\n");
			  	//print('Type is:' . $err['type'] . "\n");
			  	//print('Code is:' . $err['code'] . "\n");
			  	// param is '' in this case
			  	//print('Param is:' . $err['param'] . "\n");
			  	//print('Message is:' . $err['message'] . "\n");
				$output = $err['message'];
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $output;
				//include 'error.html.php';
				$data['response'] = 'invalidCoupon';
				$data['details'] = __LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			} catch (Stripe_InvalidRequestError $e) {
				  // Invalid parameters were supplied to Stripe's API
				$body = $e->getJsonBody();
					$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
				//include 'error.html.php';
				$data['response'] = 'invalidCoupon';
				$data['message'] = $body->message;
				$data['details'] = __LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			} catch (Stripe_AuthenticationError $e) {
			  // Authentication with Stripe's API failed
			  // (maybe you changed API keys recently)
				$body = $e->getJsonBody();
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
				$data['response'] = 'invalidCoupon';
				$data['details'] = __LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			} catch (Stripe_ApiConnectionError $e) {
			  // Network communication with Stripe failed
				$body = $e->getJsonBody();
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
				$data['response'] = 'invalidCoupon';
				$data['details'] = __LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			} catch (Stripe_Error $e) {
			  // Display a very generic error to the user, and maybe send
			  // yourself an email
				$body = $e->getJsonBody();
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
				$data['response'] = 'invalidCoupon';
				$data['details'] = __LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			} catch (Exception $e) {
			  // Something else happened, completely unrelated to Stripe
			  	$body = $e->getJsonBody();
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
				$data['response'] = 'invalidCoupon';
				$data['details'] = __LINE__;
				// Set content type
				header('Content-type: application/json');

				// Prevent caching
				header('Expires: 0');
				echo json_encode($data);
				exit();
			}
		} 
	}
	
	public function addNewCustomerAndCoupon(){
		try{
			if(isset($_POST['promoCode']) && isset($stripeCoupon) && $stripeCoupon->id && $stripeCoupon->percent_off == 100){
				$stripeCustomer = Stripe_Customer::create(array(
				  "card" => trim(strip_tags($_POST['token'])),
				  "coupon" => $stripeCoupon->id,
				  "email" => $email,
				  "description" => $description
				));
			} else {
				$stripeCustomer = Stripe_Customer::create(array(
				  "card" => trim(strip_tags($_POST['token'])),
				  "email" => $email,
				  "description" => $description
				));
			}
		
		} catch(Stripe_CardError $e) {
		  // Since it's a decline, Stripe_CardError will be caught
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];

		  	//print('Status is:' . $e->getHttpStatus() . "\n");
		  	//print('Type is:' . $err['type'] . "\n");
		  	//print('Code is:' . $err['code'] . "\n");
		  	// param is '' in this case
		  	//print('Param is:' . $err['param'] . "\n");
		  	//print('Message is:' . $err['message'] . "\n");
			$output = $err['message'];
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $output;
			//include 'error.html.php';
			$data['response'] = 'errorCreatingCustomer'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_InvalidRequestError $e) {
			  // Invalid parameters were supplied to Stripe's API
			$body = $e->getJsonBody();
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			//include 'error.html.php';
			$data['response'] = 'errorCreatingCustomer'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_AuthenticationError $e) {
		  // Authentication with Stripe's API failed
		  // (maybe you changed API keys recently)
			$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCustomer'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_ApiConnectionError $e) {
		  // Network communication with Stripe failed
			$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCustomer'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_Error $e) {
		  // Display a very generic error to the user, and maybe send
		  // yourself an email
			$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCustomer'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Exception $e) {
		  // Something else happened, completely unrelated to Stripe
		  	$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCustomer'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
	}
	/**
	 * Add Customer Coupon
	 *
	 * @since    1.0.0
	 */
	public function addExistingCustomerCoupon(){
		/**
		UPDATE THE CUSTOMER WITH A NEW COUPON ASSOC with them
		**/
		try{
			$stripeCustomer = Stripe_Customer::retrieve($customers['stripe_id']);
			// add credit card to tehir list
			$card = $stripeCustomer->cards->create(array("card" => trim(strip_tags($_POST['token']))));
			if(isset($_POST['promoCode']) && isset($stripeCoupon) && $stripeCoupon->id && $stripeCoupon->percent_off == 100){
				$stripeCustomer->coupon = $stripeCoupon->id;
				$stripeCustomer->save();	
			}
		} catch(Stripe_CardError $e) {
		  // Since it's a decline, Stripe_CardError will be caught
		  	$body = $e->getJsonBody();
		  	$err  = $body['error'];

		  	//print('Status is:' . $e->getHttpStatus() . "\n");
		  	//print('Type is:' . $err['type'] . "\n");
		  	//print('Code is:' . $err['code'] . "\n");
		  	// param is '' in this case
		  	//print('Param is:' . $err['param'] . "\n");
		  	//print('Message is:' . $err['message'] . "\n");
			$output = $err['message'];
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $output;
			//include 'error.html.php';
			$data['response'] = 'errorCreatingCard'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_InvalidRequestError $e) {
			  // Invalid parameters were supplied to Stripe's API
			$body = $e->getJsonBody();
				$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			//include 'error.html.php';
			$data['response'] = 'errorCreatingCard'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_AuthenticationError $e) {
		  // Authentication with Stripe's API failed
		  // (maybe you changed API keys recently)
			$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCard'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_ApiConnectionError $e) {
		  // Network communication with Stripe failed
			$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCard'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Stripe_Error $e) {
		  // Display a very generic error to the user, and maybe send
		  // yourself an email
			$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCard'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		} catch (Exception $e) {
		  // Something else happened, completely unrelated to Stripe
		  	$body = $e->getJsonBody();
			$error = $_SERVER['SCRIPT_FILENAME'] . ':' . $body;
			$data['response'] = 'errorCreatingCard'.__LINE__;
			// Set content type
			header('Content-type: application/json');

			// Prevent caching
			header('Expires: 0');
			echo json_encode($data);
			exit();
		}
	
	}
}
