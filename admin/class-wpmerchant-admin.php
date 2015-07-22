<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       wpmerchant.com/team
 * @since      1.0.0
 *
 * @package    Wpmerchant
 * @subpackage Wpmerchant/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpmerchant
 * @subpackage Wpmerchant/admin
 * @author     Ben Shadle <ben@wpmerchant.com>
 */
class Wpmerchant_Admin {

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
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		// Init - register custom post types
		add_action('init', array( $this, 'register_custom_post_types' ));
		add_action('add_meta_boxes_wpmerchant_products', array( $this, 'setup_product_metaboxes' ));
		add_action( 'save_post_wpmerchant_products',  array( $this, $this->plugin_name.'_products_save_meta_box_data') );
		
		// Add Settings Page to Sidebar
			// setting the priority to 9 or less allows us to move the post types to the bottom of the submenu
		add_action('admin_menu', array( $this, 'add_plugin_admin_menu' ), 9);
	    // Register Settings
	    add_action('admin_init', array( $this, 'register_and_build_fields' ));
		
		// Create Shortcode
		add_shortcode( 'wpmerchant_button', array( $this, 'stripeCheckoutShortcode' ));
		
		// UPdate the columns shown on hte products edit.php file - so we also have cost, inventory and product id
		add_filter('manage_wpmerchant_products_posts_columns' , array($this,'wpmerchant_products_columns'));
		// this fills in the columns that were created with each individual post's value
		add_action( 'manage_wpmerchant_products_posts_custom_column' , array($this,'fill_wpmerchant_products_columns'), 10, 2 );
		// make the first field on the edit plans and products pages show Enter name here instead of Enter title here
		add_filter( 'enter_title_here', array($this,'custom_post_title') );
		// update the messages shown to the user when a successful save has occurred on the custom post type pages - product or plan pages
		add_filter( 'post_updated_messages', array($this,'wpmerchant_updated_messages') );
	}
	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpmerchant-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.2
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

		// include the stripe functionality in this js file
    	  wp_register_script( $this->plugin_name,  plugin_dir_url( __FILE__ ) . 'js/wpmerchant-admin.js', array( 'jquery' ), $this->version, false );
    	  wp_enqueue_script( $this->plugin_name);
		  // Set Nonce Values so that the ajax calls are secure
		  $getEmailDataNonce = wp_create_nonce( "wpmerchant_get_email_data" );
		  $getPaymentDataNonce = wp_create_nonce( "wpmerchant_get_payment_data" );
		  $user_ID = get_current_user_id();
    	  // pass ajax object to this javascript file
		  // Add nonce values to this object so that we can access them in hte public.js javascript file
		  wp_localize_script( $this->plugin_name, 'ajax_object', 
		  	array( 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'get_email_data_nonce'=> $getEmailDataNonce,
				'get_payment_data_nonce'=> $getPaymentDataNonce,
				'user_id' => $user_ID
			) 
		  );

	}
	public function wpmerchant_updated_messages($messages){
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );
		$post_types[] = array('id'=>'wpmerchant_products','singular'=>'Product');
		
		foreach($post_types AS $p){
			$messages[$p['id']] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( $p['singular'].' updated.'),
				2  => __( 'Custom field updated.'),
				3  => __( 'Custom field deleted.'),
				4  => __( $p['singular'].' updated.'),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( __( $p['singular'].' restored to revision from %s', 'your-plugin-textdomain' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => __( $p['singular'].' published.'),
				7  => __( $p['singular'].' saved.'),
				8  => __( $p['singular'].' submitted.'),
				9  => sprintf(
					__( $p['singular'].' scheduled for: <strong>%1$s</strong>.', 'wpmerchant' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'your-plugin-textdomain' ), strtotime( $post->post_date ) )
				),
				10 => __( $p['singular'].' draft updated.', 'your-plugin-textdomain' )
			);
			// plans isn't here because it's not publicly searchable
			if ( $p['id'] == 'wpmerchant_products' ) {
				$permalink = get_permalink( $post->ID );

				$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View '.$p['singular'], 'your-plugin-textdomain' ) );
				$messages[ $p['id'] ][1] .= $view_link;
				$messages[ $p['id'] ][6] .= $view_link;
				$messages[ $p['id'] ][9] .= $view_link;

				$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
				$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview '.$p['singular'], 'your-plugin-textdomain' ) );
				$messages[ $p['id'] ][8]  .= $preview_link;
				$messages[ $p['id'] ][10] .= $preview_link;
			}
		}
		return $messages;
	}
	public function setup_product_metaboxes(){
		
		add_meta_box( $this->plugin_name.'_product_data_meta_box', 'Product Data', array($this,$this->plugin_name.'_product_data_meta_box'), 'wpmerchant_products', 'normal','high' );
		add_meta_box( $this->plugin_name.'_product_description_meta_box', 'Product Description', array($this,$this->plugin_name.'_product_description_meta_box'), 'wpmerchant_products', 'normal','core' );
		add_meta_box( $this->plugin_name.'_product_shortcode_meta_box', 'Buy Button Shortcode', array($this,$this->plugin_name.'_product_shortcode_meta_box'), 'wpmerchant_products', 'normal','core' );
		
		/*add_meta_box( $this->plugin_name.'_inventory', 'Product Inventory', array($this,$this->plugin_name.'_product_inventory_meta_box'), 'wpmerchant_products', 'normal','high' );*/
	}
	
	function custom_post_title( $post ) {
		global $post_type;
		if ( 'wpmerchant_products' == $post_type){
			    return __( 'Enter name here');
		} else {
			return __('Enter title here');
		}
	}
	/**
	* Add Custom Columns to edit.php page for wpmerchant_products
	 * 
	 * @since    1.0.0
	*/
	function wpmerchant_products_columns($columns) {
		// Remove Author and Comments from Columns and Add Cost, Inventory and Product Id
	    return array(
	           'cb' => '<input type="checkbox" />',
	           'title' => __('Name'),
	           'cost' => __('Cost'),
	           'inventory' => __('Inventory'),
	           'product_id' =>__( 'Product ID'),
			   'date' =>__( 'Date')
	       );
	    //return $columns;
	}
	/**
	*
	* Fill in Custom Columns
	* @since 1.0.0
	*/
	public function fill_wpmerchant_products_columns( $column, $post_id ) {
	    // Fill in the columns with meta box info associated with each post
		switch ( $column ) {
		case 'cost' :
			$currency1 = get_option( $this->plugin_name.'_currency' );
			$currency = $this->get_currency_details($currency1);
			$currencyPrepend = ($currency['symbol']) ? $currency['symbol'] : $currency['value'];
			$cost = get_post_meta( $post_id , $this->plugin_name.'_cost' , true );
			if($cost){
				echo $currencyPrepend.$cost;
			} else {
				echo '';
			}
			break;
		case 'inventory' :
		    echo get_post_meta( $post_id , $this->plugin_name.'_inventory' , true ); 
		    break;
		case 'product_id' :
		    echo $post_id; 
		    break;
	    }
	}
    public function add_notice_query_var( $location ) {
     remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
     return add_query_arg( array( 'wpmerchant_status' => 'saved' ), $location );
    }
	/**
	 * Product Shortcode Meta Box Callback
	 * 
	 * @since    1.0.0
	 */
	function wpmerchant_product_shortcode_meta_box( $post ) {
		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		echo '<p><code>[wpmerchant_button products="'.$post->ID.'"]Buy[/wpmerchant_button]</code></p>';
		
	}
	/**
	 * Product Data Meta Box Callback
	 * 
	 * @since    1.0.0
	 */
	function wpmerchant_product_data_meta_box( $post ) {

		// Add a nonce field so we can check for it later.
		wp_nonce_field( $this->plugin_name.'_meta_box', $this->plugin_name.'_meta_box_nonce' );
	
		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		//$description = get_post_meta( $post->ID, $this->plugin_name.'_description', true );
		unset($args);
		$args = array('dashicon'=>'dashicons-admin-home','class'=>'general_tab active', 'tab_text'=>'General','link_href'=>'#general_product_fields','link_title'=>'General Product Data');
		$general_tab_id = 'general_product_fields';
		$general_tab = $this->getPostMetaTab($args);
		unset($args);
		$args = array('dashicon'=>'dashicons-chart-line','class'=>'inventory_tab', 'tab_text'=>'Inventory','link_href'=>'#inventory_product_fields','link_title'=>'Inventory Product Data');
		$inventory_tab = $this->getPostMetaTab($args);
		$inventory_tab_id = 'inventory_product_fields';
		echo '<div class="product_container">
				<ul class="product_container_tabs wpm_tabs">'.$general_tab.$inventory_tab.'</ul>
				<!--Display block if general product fields is clicked otherwise hide-->
				<div id="'.$general_tab_id.'" class="wpm_show tab_content"><div class="product_field_containers">';
		echo '<ul class="wpmerchant_product_data_metabox">';
		
		echo '<li><label for="'.$this->plugin_name.'_cost">';
		_e( 'Cost', $this->plugin_name.'_cost' );
		echo '</label>';
		$currency1 = get_option( $this->plugin_name.'_currency' );
		$currency = $this->get_currency_details($currency1);
		if($currency['symbol']){
			$currencyPrepend = $currency['symbol'];
		} else {
			$currencyPrepend = $currency['value'];
		}
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'number',
				  'id'	  => $this->plugin_name.'_cost',
				  'name'	  => $this->plugin_name.'_cost',
				  'required' => 'required="required"',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'post_meta',
				  'post_id'=> $post->ID,
				  'min'=> '0',
				  'step'=> 'any',
				  'prepend_value'=>$currencyPrepend
	          );
		// this gets the post_meta value and echos back the input
		$this->wpmerchant_render_settings_field($args);
		echo '</li>';
		echo '</ul></div></div>';
		echo '<div id="'.$inventory_tab_id.'" class="wpm_hide tab_content"><div class="product_field_containers"><ul class="wpmerchant_plan_data_metabox"><li><label for="'.$this->plugin_name.'_stock_status">';
		_e( 'Stock Status', $this->plugin_name.'_stock_status' );
		echo '</label>';
		unset($args);
	  	$args = array (
	              'type'      => 'select',
				  'subtype'	  => '',
				  'id'	  => $this->plugin_name.'_stock_status',
				  'name'	  => $this->plugin_name.'_stock_status',
				  'required' => '',
				  'get_options_list' => 'get_stock_status_list',
				  'value_type'=>'normal',
				  'wp_data' => 'post_meta',
				  'post_id'=> $post->ID
	          );
		// this gets the post_meta value and echos back the input
		$this->wpmerchant_render_settings_field($args);
		
		
		echo '</li><li><label for="'.$this->plugin_name.'_inventory">';
		_e( 'Inventory', $this->plugin_name.'_inventory' );
		echo '</label>';
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_inventory',
				  'name'	  => $this->plugin_name.'_inventory',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'post_meta',
				  'post_id'=> $post->ID
	          );
		// this gets the post_meta value and echos back the input
		$this->wpmerchant_render_settings_field($args);
		
		
		echo '</li><li><label for="'.$this->plugin_name.'_allow_backorders">';
		_e( 'Allow Backorders', $this->plugin_name.'_allow_backorders' );
		echo '</label>';
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'checkbox',
				  'id'	  => $this->plugin_name.'_allow_backorders',
				  'name'	  => $this->plugin_name.'_allow_backorders',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'post_meta',
				  'post_id'=> $post->ID
	          );
		// this gets the post_meta value and echos back the input
		$this->wpmerchant_render_settings_field($args);
		
		echo '</li>';
		// provide textarea name for $_POST variable
		$sold_out_message = get_post_meta( $post->ID, $this->plugin_name.'_sold_out_message', true );
		//THis isn't necessary because we want the shortcodes to remain and only the description data to be shown
		//$sold_out_message = apply_filters('the_content', $sold_out_message); 
		$args = array(
		'textarea_name' => $this->plugin_name.'_sold_out_message',
		); 
		echo '<li><label for="'.$this->plugin_name.'_sold_out_message">';
				_e( 'Sold Out Message', $this->plugin_name.'_sold_out_message' );
				echo '</label>';
		wp_editor( $sold_out_message, 'wpmerchant_sold_out_message_editor',$args); 
		echo '</li></ul></div></div><div class="clear"></div></div>';
	}
	/**
	 * Product Cost Meta Box Callback
	 * 
	 * @since    1.0.0
	 */
	function wpmerchant_product_description_meta_box( $post ) {
		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$description = get_post_meta( $post->ID, $this->plugin_name.'_description', true );
		//THis isn't necessary because we want the shortcodes to remain and only the description data to be shown
		//$description = apply_filters('the_content', $description); 
		// provide textarea name for $_POST variable
		$args = array(
		'textarea_name' => $this->plugin_name.'_description',
		); 
		wp_editor( $description, 'wpmerchant_product_description_editor',$args); 
	}
	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @since    1.0.0
	 */
	function wpmerchant_products_save_meta_box_data( $post_id ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		
		// Check if our nonce is set.
		if ( ! isset( $_POST[$this->plugin_name.'_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST[$this->plugin_name.'_meta_box_nonce'], $this->plugin_name.'_meta_box' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/* OK, it's safe for us to save the data now. */
	
		// Make sure that it is set.
		if ( ! isset( $_POST[$this->plugin_name.'_cost'] ) && ! isset( $_POST[$this->plugin_name.'_description'] ) && ! isset( $_POST[$this->plugin_name.'_inventory'] ) && ! isset( $_POST[$this->plugin_name.'_allow_backorders'] ) && ! isset( $_POST[$this->plugin_name.'_sold_out_message'] ) && ! isset( $_POST[$this->plugin_name.'_stock_status'] )) {
			return;
		}

		// Sanitize user input.
		$product_allow_backorders = sanitize_text_field( $_POST[$this->plugin_name.'_allow_backorders'] );
		$product_cost = sanitize_text_field( $_POST[$this->plugin_name.'_cost'] );
		$product_inventory = sanitize_text_field( $_POST[$this->plugin_name.'_inventory'] );
		$product_description = wp_kses_post( $_POST[$this->plugin_name.'_description'] );
		$sold_out_message = wp_kses_post( $_POST[$this->plugin_name.'_sold_out_message'] );
		$stock_status = sanitize_text_field( $_POST[$this->plugin_name.'_stock_status'] );
		
		// Update the meta field in the database.
		update_post_meta( $post_id, $this->plugin_name.'_description', $product_description );
		update_post_meta( $post_id, $this->plugin_name.'_cost', $product_cost );
		update_post_meta( $post_id, $this->plugin_name.'_inventory', $product_inventory );
		update_post_meta( $post_id, $this->plugin_name.'_allow_backorders', $product_allow_backorders );
		update_post_meta( $post_id, $this->plugin_name.'_sold_out_message', $sold_out_message );
		update_post_meta( $post_id, $this->plugin_name.'_stock_status', $stock_status );
	}
	/**
	 * Register Custom Post Types
	 *
	 * @since    1.0.0
	 */
	public function register_custom_post_types(){
		
		$productArgs = array(
			'label'=>'WPMerchant Products',
			'labels'=>
				array(
					'name'=>'Products',
					'singular_name'=>'Product',
					'add_new'=>'Add Product',
					'add_new_item'=>'Add New Product',
					'edit_item'=>'Edit Product',
					'new_item'=>'New Product',
					'view_item'=>'View Product',
					'search_items'=>'Search Product',
					'not_found'=>'No Products Found',
					'not_found_in_trash'=>'No Products Found in Trash'
				),
			'public'=>true,
			'description'=>'WPMerchant Products', 
			'exclude_from_search'=>false,
			'show_ui'=>true,
			'show_in_menu'=>$this->plugin_name,
			'supports'=>array('title','thumbnail', 'custom_fields'),
			'taxonomies'=>array('category','post_tag'));
		register_post_type( 'wpmerchant_products', $productArgs );
	}
	/**
	 * StripeCheckout Shortcode Functionality
	 *
	 * @since    1.0.0
	 */
	public function stripeCheckoutShortcode( $atts, $content = "" ) {
		//[wpmerchant_button plans="" products="" element="" classes="" style="" other=""][/wpmerchant_button]
		// [wpmerchant_button plans="" products="1196" element="span" classes="nectar-button handdrawn-arrow large accent-color regular-button" style="visibility: visible; color: rgb(255, 255, 255) !important;" other="data-color-override:false;data-hover-color-override:false;data-hover-text-color-override:#fff;"]Buy Now[/wpmerchant_button]
		$a = shortcode_atts( array(
			          'plans' => '',
					  'products'=>'',
					  'classes'=>'',
					  'element'=>'',
					  'style'=>'',
					  'other'=>''
			      ), $atts );
		if($a['products']) {
			// ALLOW MULTIPLE PRODUCTS TO BE PURCHASED
			if(strstr($a['products'],',')){
				$product_ids2 = $a['products'];
				$product_ids1 = explode(",",$product_ids2);
				// get Quantity of products purchased by getting frequency of different product ids in hte array
				$product_id_frequency = array_count_values($product_ids1);
				foreach($product_id_frequency AS $key=>$value){
					$product_ids[] = array('quantity'=>$value, 'id'=>$key);
				}
				
				foreach($product_ids AS $key=>$value){
					$p = trim($value['id']);
					if(!intval($p)){
						continue;
					}
					//$product = get_post($product_id)
					$cost = get_post_meta( $p, $this->plugin_name.'_cost', true );
					$title = get_the_title( $p );
					// add this product's amount onto the sum of all previous products
					if($key == 0){
						$amount = $cost*100*$value['quantity'];
						$description = $title;
					} else {
						$amount += $cost*100*$value['quantity'];
						$description .= ', '.$title;
						//$title = 'Multiple Products';
					}
				}
			} else {
				$amount = get_post_meta( $a['products'], $this->plugin_name.'_cost', true )*100;
				//$description = get_post_meta( $a['product_id'], $this->plugin_name.'_description', true );
				$description = get_the_title( $a['products'] );
				$product_ids[] = array('quantity'=>1, 'id'=>$a['products']);
			}
			$products = json_encode($product_ids);
		} else {
			$products = '';
		}
		if($a['plans']) {
			// ALLOW MULTIPLE PRODUCTS TO BE PURCHASED
			if(strstr($a['plans'],',')){
				$plan_ids2 = $a['plans'];
				$plan_ids1 = explode(",",$plan_ids2);
				// get Quantity of products purchased by getting frequency of different product ids in hte array
				$plan_id_frequency = array_count_values($plan_ids1);
				foreach($plan_id_frequency AS $key=>$value){
					$plan_ids[] = array('quantity'=>$value, 'id'=>$key);
				}
				
				foreach($plan_ids AS $key=>$value){
					$p = trim($value['id']);
					if(!intval($p)){
						continue;
					}
					//$product = get_post($product_id)
					$cost = get_post_meta( $p, $this->plugin_name.'_cost', true );
					$title = get_the_title( $p );
					// add this product's amount onto the sum of all previous products
					if($key == 0){
						$amount = $cost*100*$value['quantity'];
						$description = $title;
					} else {
						$amount += $cost*100*$value['quantity'];
						$description .= ', '.$title;
						//$title = 'Multiple Products';
					}
				}
			} else {
				$amount = get_post_meta( $a['plans'], $this->plugin_name.'_cost', true )*100;
				//$description = get_post_meta( $a['plan_id'], $this->plugin_name.'_description', true );
				$description = get_the_title( $a['plans'] );
				$plan_ids[] = array('quantity'=>1, 'id'=>$a['plans']);
			}
			$plans = json_encode($plan_ids);
			
		} else {
			$plans = '';
		}
		
		/**
		A bunch of vars are being localized to the public.js script (so they aren't included in the data vars) because they're in the class-wpmerchant-public.php enqueue_scripts function.  This is a lot more efficient than running all of the other get_option functions every time we're populating shortcode html.
		**/
		$element = $a['element'] ? $a['element'] : 'button';
		$classes1 = 'wpMerchantPurchase';
		$classes = $a['classes'] ? $classes1.' '.$a['classes'] : $classes1;
		$style = $a['style'] ? $a['style'] : '';
		
		if($a['other']){
			// split the strings based on colon and semicolon
			$pairs = explode(";",$a['other']);
			$other = '';
			foreach($pairs AS $p){
				if(!$p){
					continue;
				}
				$key_value = explode(":",$p);
				$other .= ' '.$key_value[0] .'="'. esc_attr($key_value[1]).'"';
			}
		} else {
			$other = '';
		}
		return '<'.$element.' class="'.esc_attr($classes).'" style="'.esc_attr($style).'" data-description="'.esc_attr($description).'" data-amount="'.esc_attr($amount).'" data-plans="'.esc_attr($plans).'" data-products="'.esc_attr($products).'" '.$other.'>'.$content.'</'.$element.'>';
		// if plan id panelLabel 'Subscribe - {{amount}}/month',
		// how ami passing public key to js file
	}
	/**
	OPTION PAGE FUNCTIONALITY
	**/
	/**
     * Register the Stripe account section Fields, Stripe API Secret and Public Key fields etc
     * 
     *
     * @since     1.0.0
     */
    public function register_and_build_fields() {
		/**
	   * First, we add_settings_section. This is necessary since all future settings must belong to one.
	   * Second, add_settings_field
	   * Third, register_setting
	   */ 
		
		
		add_settings_section(
		  // ID used to identify this section and with which to register options
		  'wpmerchant_general_section', 
		  // Title to be displayed on the administration page
		  '',  
		  // Callback used to render the description of the section
		   array( $this, 'wpmerchant_display_general_account' ),    
		  // Page on which to add this section of options
		  $this->plugin_name.'_general_settings'                   
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_company_name',
				  'name'	  => $this->plugin_name.'_company_name',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_company_name',
		  'Company Name',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_general_settings',
		  'wpmerchant_general_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_logo',
				  'name'	  => $this->plugin_name.'_logo',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_logo',
		  'Logo',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_general_settings',
		  'wpmerchant_general_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'select',
				  'subtype'	  => '',
				  'id'	  => $this->plugin_name.'_currency',
				  'name'	  => $this->plugin_name.'_currency',
				  'required' => 'required="required"',
				  'get_options_list' => 'get_currency_list',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_currency',
		  'Currency',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_general_settings',
		  'wpmerchant_general_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'select',
				  'subtype'	  => '',
				  'id'	  => $this->plugin_name.'_payment_processor',
				  'name'	  => $this->plugin_name.'_payment_processor',
				  'required' => 'required="required"',
				  'get_options_list' => 'get_payment_processor_list',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_payment_processor',
		  'Payment Processor',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_general_settings',
		  'wpmerchant_general_section',
		  $args
		);
			
		unset($args);
	  	$args = array (
	              'type'      => 'select',
				  'subtype'	  => '',
				  'id'	  => $this->plugin_name.'_email_list_processor',
				  'name'	  => $this->plugin_name.'_email_list_processor',
				  'required' => 'required="required"',
				  'get_options_list' => 'get_email_list_processor_list',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_email_list_processor',
		  'Email List Processor',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_general_settings',
		  'wpmerchant_general_section',
		  $args
		);
		
		register_setting(
				  			    'wpmerchant_general_settings',
				  			    'wpmerchant_company_name'
				  			    );
				register_setting(
				  			    'wpmerchant_general_settings',
				  			    'wpmerchant_logo'
				  			    );
				register_setting(
				   'wpmerchant_general_settings',
				   'wpmerchant_currency'
				   );
		   	 	register_setting(
		   		   'wpmerchant_general_settings',
		   		   'wpmerchant_payment_processor'
		   		   );
		   	 	register_setting(
		   		   'wpmerchant_general_settings',
		   		   'wpmerchant_email_list_processor'
		   		   );		   		
			add_settings_section(
				// ID used to identify this section and with which to register options
				'wpmerchant_post_checkout_section', 
				// Title to be displayed on the administration page
				'After Checkout',  
				// Callback used to render the description of the section
				array( $this, 'wpmerchant_display_post_checkout' ),    
				// Page on which to add this section of options
				$this->plugin_name.'_post_checkout_settings'                   
			);
			unset($args);
		  	$args = array (
		              'type'      => 'input',
					  'subtype'	  => 'text',
					  'id'	  => $this->plugin_name.'_post_checkout_redirect',
					  'name'	  => $this->plugin_name.'_post_checkout_redirect',
					  'required' => '',
					  'get_options_list' => '',
					  'value_type'=>'normal',
					  'wp_data' => 'option'
		          );
			add_settings_field(
			  'wpmerchant_post_checkout_redirect',
			  'Thank You Page Redirect',
			  array( $this, 'wpmerchant_render_settings_field' ),
			  $this->plugin_name.'_post_checkout_settings',
			  'wpmerchant_post_checkout_section',
			  $args
			);
			unset($args);
		  	$args = array (
		              'type'      => 'input',
					  'subtype'	  => 'text',
					  'id'	  => $this->plugin_name.'_post_checkout_msg',
					  'name'	  => $this->plugin_name.'_post_checkout_msg',
					  'required' => '',
					  'get_options_list' => '',
					  'value_type'=>'normal',
					  'wp_data' => 'option'
		          );
			add_settings_field(
			  'wpmerchant_post_checkout_msg',
			  'Thank You Message',
			  array( $this, 'wpmerchant_render_settings_field' ),
			  $this->plugin_name.'_post_checkout_settings',
			  'wpmerchant_post_checkout_section',
			  $args
			);
	   		register_setting(
		   	 	'wpmerchant_post_checkout_settings',
		    	'wpmerchant_post_checkout_redirect'
		    );
	   		register_setting(
  			    'wpmerchant_post_checkout_settings',
  			    'wpmerchant_post_checkout_msg'
  			    );
		
		
		
		add_settings_section(
			// ID used to identify this section and with which to register options
			'wpmerchant_stripe_account_section', 
			// Title to be displayed on the administration page
			'Stripe Account',  
			// Callback used to render the description of the section
			array( $this, 'wpmerchant_display_stripe_account' ),    
			// Page on which to add this section of options
			$this->plugin_name.'_stripe_settings'                   
		);
		unset($args);
	  	$args = array (
	              'type'      => 'select',
				  'subtype'	  => '',
				  'id'	  => $this->plugin_name.'_stripe_status',
				  'name'	  => $this->plugin_name.'_stripe_status',
				  'required' => 'required="required"',
				  'get_options_list' => 'get_stripe_status_list',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
			'wpmerchant_stripe_status',
			'Stripe Status',
			array( $this, 'wpmerchant_render_settings_field' ),
			$this->plugin_name.'_stripe_settings',
			'wpmerchant_stripe_account_section',
			$args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_stripe_checkout_logo',
				  'name'	  => $this->plugin_name.'_stripe_checkout_logo',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_checkout_logo',
		  'Checkout Logo (128x128px minimum)',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_stripe_settings',
		  'wpmerchant_stripe_account_section',
		  $args
		);
		
		
		$siteURL = urlencode(get_site_url().'/wp-admin/admin.php?page=wpmerchant-settings');
		$stripeLivePublicKey = get_option('wpmerchant_stripe_live_public_key');
		$stripeLiveSecretKey = get_option('wpmerchant_stripe_live_secret_key');
		$stripeTestPublicKey = get_option('wpmerchant_stripe_test_public_key');
		$stripeTestSecretKey = get_option('wpmerchant_stripe_test_secret_key');
		if($stripeLivePublicKey && $stripeLiveSecretKey && $stripeTestPublicKey && $stripeTestSecretKey){
			add_settings_field(
			  'wpmerchant_stripe_api_2',
			  'Connected to Stripe',
			  array( $this, 'wpmerchant_render_stripe_connected' ),
			  $this->plugin_name.'_stripe_settings',
			  'wpmerchant_stripe_account_section',
			  $args
			);
		} else {
			add_settings_field(
			  'wpmerchant_stripe_api_2',
			  'Connect to Stripe',
			  array( $this, 'wpmerchant_render_stripe_connect' ),
			  $this->plugin_name.'_stripe_settings',
			  'wpmerchant_stripe_account_section',
			  $args
			);
		}
		/*if($stripeLivePublicKey && $stripeLiveSecretKey){
			add_settings_field(
			  'wpmerchant_stripe_live_api_2',
			  'Connected to Live Stripe',
			  array( $this, 'wpmerchant_render_stripe_live_connected' ),
			  $this->plugin_name,
			  'wpmerchant_stripe_account_section',
			  $args
			);
		} else {
			unset($args);
		  	$args = array (
		              'type'      => 'link',
					  'subtype'	  => '',
					  'id'	  => $this->plugin_name.'_stripe_live_api_2',
					  'name'	  => $this->plugin_name.'_stripe_live_api_2',
					  'required' => '',
					  'get_options_list' => '',
					  'value_type'=>'normal',
					  'wp_data' => 'option',
					  'href'=> 'http://wpmerchant.wpengine.com/stripe-connect/auth.php',
					  'content'=> 'Connect',
					  'target'=>'_blank',
					  'class'=>'btn stripe-live-login'
		          );
			add_settings_field(
			  'wpmerchant_stripe_live_api_2',
			  'Connect to Live Stripe',
			  array( $this, 'wpmerchant_render_settings_field' ),
			  $this->plugin_name,
			  'wpmerchant_stripe_account_section',
			  $args
			);
		}*/
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'hidden',
				  'id'	  => $this->plugin_name.'_stripe_live_secret_key',
				  'name'	  => $this->plugin_name.'_stripe_live_secret_key',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_live_secret_key',
		  '',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_stripe_settings',
		  'wpmerchant_stripe_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'hidden',
				  'id'	  => $this->plugin_name.'_stripe_live_public_key',
				  'name'	  => $this->plugin_name.'_stripe_live_public_key',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_live_public_key',
		  '',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_stripe_settings',
		  'wpmerchant_stripe_account_section',
		  $args
		);
		/* NEED TO BC USING REFRESH TOKEN TO GET TEST SECRET and PUBLIC KEYS DIDN"T WORK*/
		/*if($stripeTestPublicKey && $stripeTestSecretKey){
			add_settings_field(
			  'wpmerchant_stripe_test_api_2',
			  'Connected to Test Stripe',
			  array( $this, 'wpmerchant_render_stripe_test_connected' ),
			  $this->plugin_name,
			  'wpmerchant_stripe_account_section',
			  $args
			);
		} else {
			unset($args);
		  	$args = array (
		              'type'      => 'link',
					  'subtype'	  => '',
					  'id'	  => $this->plugin_name.'_stripe_test_api_2',
					  'name'	  => $this->plugin_name.'_stripe_test_api_2',
					  'required' => '',
					  'get_options_list' => '',
					  'value_type'=>'normal',
					  'wp_data' => 'option',
					  'href'=> 'http://wpmerchant.wpengine.com/stripe-connect/auth.php',
					  'content'=> 'Connect',
					  'target'=>'_blank',
					  'class'=>'btn stripe-test-login'
		          );
			add_settings_field(
			  'wpmerchant_stripe_test_api_2',
			  'Connect to Test Stripe',
			  array( $this, 'wpmerchant_render_settings_field' ),
			  $this->plugin_name,
			  'wpmerchant_stripe_account_section',
			  $args
			);
		}*/
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'hidden',
				  'id'	  => $this->plugin_name.'_stripe_test_secret_key',
				  'name'	  => $this->plugin_name.'_stripe_test_secret_key',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_test_secret_key',
		  '',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_stripe_settings',
		  'wpmerchant_stripe_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'hidden',
				  'id'	  => $this->plugin_name.'_stripe_test_public_key',
				  'name'	  => $this->plugin_name.'_stripe_test_public_key',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_test_public_key',
		  '',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_stripe_settings',
		  'wpmerchant_stripe_account_section',
		  $args
		);
		
		/*unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_stripe_live_secret_key',
				  'name'	  => $this->plugin_name.'_stripe_live_secret_key',
				  'required' => 'required="required"',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_live_secret_key',
		  'Live Secret Key*',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_stripe_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_stripe_live_public_key',
				  'name'	  => $this->plugin_name.'_stripe_live_public_key',
				  'required' => 'required="required"',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_live_public_key',
		  'Live Public Key*',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_stripe_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_stripe_test_secret_key',
				  'name'	  => $this->plugin_name.'_stripe_test_secret_key',
				  'required' => 'required="required"',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_test_secret_key',
		  'Test Secret Key*',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_stripe_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_stripe_test_public_key',
				  'name'	  => $this->plugin_name.'_stripe_test_public_key',
				  'required' => 'required="required"',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_stripe_test_public_key',
		  'Test Public Key*',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_stripe_account_section',
		  $args
		);*/
		
		// Finally, we register the fields with WordPress
		register_setting(
				  			    'wpmerchant_stripe_settings',
				  			    'wpmerchant_stripe_live_secret_key',
								array( $this, 'wpmerchant_validate_secret_api_key' )
				  			    );
				register_setting(
				  			    'wpmerchant_stripe_settings',
				  			    'wpmerchant_stripe_live_public_key'
								/*array( $this, 'wpmerchant_validate_public_api_key' )*/
				  			    );
				register_setting(
				  			    'wpmerchant_stripe_settings',
				  			    'wpmerchant_stripe_test_secret_key'
								/*array( $this, 'wpmerchant_validate_test_secret_api_key' )*/
				  			    );
				register_setting(
				  			    'wpmerchant_stripe_settings',
				  			    'wpmerchant_stripe_test_public_key'
								/*array( $this, 'wpmerchant_validate_test_public_api_key' )*/
				  			    );
				register_setting(
				  			    'wpmerchant_stripe_settings',
				  			    'wpmerchant_stripe_checkout_logo'
								/*array( $this, 'wpmerchant_validate_test_public_api_key' )*/
				  			    );
				register_setting(
				  			    'wpmerchant_stripe_settings',
				  			    'wpmerchant_stripe_status',
								array( $this, 'wpmerchant_validate_status' )
				  			    );
								
			/*MAILCHIMP*/
		add_settings_section(
		  // ID used to identify this section and with which to register options
		  'wpmerchant_mailchimp_account_section', 
		  // Title to be displayed on the administration page
		  'MailChimp Account',  
		  // Callback used to render the description of the section
		   array( $this, 'wpmerchant_display_mailchimp_account' ),    
		  // Page on which to add this section of options
		  $this->plugin_name.'_mailchimp_settings'                   
		);
		// GET MAILCHIMP API FROM OAUTH2
		$siteURL = urlencode(get_site_url().'/wp-admin/admin.php?page=wpmerchant-settings');
		$mailchimpAPIKey = get_option('wpmerchant_mailchimp_api');
		if($mailchimpAPIKey){
			add_settings_field(
			  'wpmerchant_mailchimp_api_2',
			  'Connected to MailChimp',
			  array( $this, 'wpmerchant_render_mailchimp_connected' ),
			  $this->plugin_name.'_mailchimp_settings',
			  'wpmerchant_mailchimp_account_section',
			  $args
			);
		} else {
			add_settings_field(
			  'wpmerchant_mailchimp_api_2',
			  'Connect to MailChimp',
			  array( $this, 'wpmerchant_render_mailchimp_connect' ),
			  $this->plugin_name.'_mailchimp_settings',
			  'wpmerchant_mailchimp_account_section',
			  $args
			);
		}
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'hidden',
				  'id'	  => $this->plugin_name.'_mailchimp_api',
				  'name'	  => $this->plugin_name.'_mailchimp_api',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_mailchimp_api',
		  '',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name.'_mailchimp_settings',
		  'wpmerchant_mailchimp_account_section',
		  $args
		);
		if($mailchimpAPIKey){
			unset($args);
		  	$args = array (
		              'type'      => 'select',
					  'subtype'	  => '',
					  'id'	  => $this->plugin_name.'_mailchimp_gen_list_id',
					  'name'	  => $this->plugin_name.'_mailchimp_gen_list_id',
					  'required' => '',
					  'get_options_list' => 'get_mailchimp_list',
					  'value_type'=>'normal',
					  'wp_data' => 'option',
					  'attr_value' =>true
		          );
			add_settings_field(
			  'wpmerchant_mailchimp_gen_list_id',
			  'General Interest List',
			  array( $this, 'wpmerchant_render_settings_field' ),
			  $this->plugin_name.'_mailchimp_settings',
			  'wpmerchant_mailchimp_account_section',
			  $args
			);
		} else {
			unset($args);
		  	$args = array (
		              'type'      => 'select',
					  'subtype'	  => '',
					  'id'	  => $this->plugin_name.'_mailchimp_gen_list_id',
					  'name'	  => $this->plugin_name.'_mailchimp_gen_list_id',
					  'required' => '',
					  'get_options_list' => 'get_mailchimp_list',
					  'value_type'=>'normal',
					  'wp_data' => 'option',
					  'display'=>'none',
					  'attr_value' =>true
		          );
			add_settings_field(
			  'wpmerchant_mailchimp_gen_list_id',
			  '',
			  array( $this, 'wpmerchant_render_settings_field' ),
			  $this->plugin_name.'_mailchimp_settings',
			  'wpmerchant_mailchimp_account_section',
			  $args
			);
		}
		/*unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_mc_sub_list_id',
				  'name'	  => $this->plugin_name.'_mc_sub_list_id',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_mc_sub_list_id',
		  'Subscriber List ID',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_mailchimp_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_mc_sub_grouping_name',
				  'name'	  => $this->plugin_name.'_mc_sub_grouping_name',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_mc_sub_grouping_name',
		  'General List Grouping Name (*for Subscriber)',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_mailchimp_account_section',
		  $args
		);
		unset($args);
	  	$args = array (
	              'type'      => 'input',
				  'subtype'	  => 'text',
				  'id'	  => $this->plugin_name.'_mc_sub_group_name',
				  'name'	  => $this->plugin_name.'_mc_sub_group_name',
				  'required' => '',
				  'get_options_list' => '',
				  'value_type'=>'normal',
				  'wp_data' => 'option'
	          );
		add_settings_field(
		  'wpmerchant_mc_sub_group_name',
		  'General List Group Name (*for Subscriber)',
		  array( $this, 'wpmerchant_render_settings_field' ),
		  $this->plugin_name,
		  'wpmerchant_mailchimp_account_section',
		  $args
		);*/
	 	register_setting(
		   'wpmerchant_mailchimp_settings',
		   'wpmerchant_mailchimp_api'
		   );
   	 	register_setting(
   		   'wpmerchant_mailchimp_settings',
   		   'wpmerchant_mailchimp_api_2'
   		   );
	 	register_setting(
		   'wpmerchant_mailchimp_settings',
		   'wpmerchant_mailchimp_gen_list_id'
		   );
		   /**
			Tried this to make the user's life easier - but couldn't get the lists to prefill
			$data = array('list_id'=> $mailchimp['genListId'], 'first_name'=> $inputWPMCustomer['first_name'], 'last_name'=>$inputWPMCustomer['last_name'],'email'=>$inputWPMCustomer['email']);
			$this->$email_list_processor($MailChimpAPI,'getLists',$data);
		   **/
    }
	public function wpmerchant_render_mailchimp_connected(){
		/*echo '<span class="dashicons dashicons-yes" style="color:#7ad03a;"></span>&nbsp;<a id="mailchimp-log-out" style="cursor:pointer;">Log Out?</a>';*/
		echo '<a class="btn mailchimp-connected"><span>| Connected</span></a>&nbsp;&nbsp;<a id="mailchimp-log-out" style="cursor:pointer;">Disconnect?</a>';
	}
	public function wpmerchant_return_mailchimp_connected(){
		/*echo '<span class="dashicons dashicons-yes" style="color:#7ad03a;"></span>&nbsp;<a id="mailchimp-log-out" style="cursor:pointer;">Log Out?</a>';*/
		return '<a class="btn mailchimp-connected"><span>| Connected</span></a>';
	}
	public function wpmerchant_render_stripe_connected(){
		/*echo '<span class="dashicons dashicons-yes" style="color:#7ad03a;"></span>&nbsp;<a id="stripe-log-out" style="cursor:pointer;">Log Out?</a>';*/
		echo '<a class="btn stripe-connected"><span>| Connected</span></a>&nbsp;&nbsp;<a id="stripe-log-out" style="cursor:pointer;">Disconnect?</a>';
	}
	public function wpmerchant_return_stripe_connected(){
		/*echo '<span class="dashicons dashicons-yes" style="color:#7ad03a;"></span>&nbsp;<a id="stripe-log-out" style="cursor:pointer;">Log Out?</a>';*/
		return '<a class="btn stripe-connected"><span>| Connected</span></a>';
	}
	/**
	Settings Sections Displays
	**/
	/**
	 * This function provides a simple description for the Stripe Payments Options page.
	 * This function is being passed as a parameter in the add_settings_section function.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_display_stripe_account() {
	  echo '<p>Please select whether you would like the Stripe functionality to be set to Live or Test and enter your live/test secret and public api keys. This information connects the WPMerchant plugin with your Stripe account.</p> <p><strong>*IMPORTANT NOTE*</strong> The shortcode won\'t work without correct api keys.</p>';
	} 
	/**
	 * This function provides a simple description for the Stripe Payments Options page.
	 * This function is being passed as a parameter in the add_settings_section function.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_display_post_checkout() {
	  echo '<p>After a user successfully purchases a product, you can redirect them to a page that you enter in below OR you can add a short message to display to the user.</p>';
	}
	/**
	 * This function provides a simple description for the Stripe Payments Options page.
	 * This function is being passed as a parameter in the add_settings_section function.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_display_mailchimp_account() {
	  echo '<p>If you have a MailChimp account, click the Connect button below.  Otherwise, <a class="mailchimp-sign-up" target="_blank" href="http://login.mailchimp.com/signup">Sign Up</a>.</p><p>This information will allow us to subscribe anyone who purchases a one off product from your site to your General Interest MailChimp list.</p>';
	}
	/**
	 * This function provides a simple description for the Stripe Payments Options page.
	 * This function is being passed as a parameter in the add_settings_section function.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_display_general_account() {
	  echo '<p>These settings apply to all WPMerchant functionality.</p>';
	} 
	/**
	ADD ADMIN MENU
	**/
    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
      /*
       * Add a settings page for this plugin to the Settings menu.
       */
      /*add_options_page(
        __( 'WPLauncher Stripe Payments Settings', $this->plugin_name ),
        __( 'WPLauncher Stripe Payments', $this->plugin_name ),
        'manage_options',
        $this->plugin_name,
        array( $this, 'display_plugin_admin_page' )
      );*/
	  /**
add_menu_page('My Custom Page', 'My Custom Page', 'manage_options', 'my-top-level-slug');

add_submenu_page( 'my-top-level-slug', 'My Custom Page', 'My Custom Page', 'manage_options', 'my-top-level-slug');

add_submenu_page( 'my-top-level-slug', 'My Custom Submenu Page', 'My Custom Submenu Page', 'manage_options', 'my-secondary-slug');
	  **/
	  //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	  add_menu_page( 'WPMerchant', 'WPMerchant', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard' ), plugin_dir_url( __FILE__ ) . 'img/logo2.png', 26 );
      // this call removes the duplicate link at the top of the submenu 
	  	// bc you're giving the parent slug and menu slug the same values
	  add_submenu_page( $this->plugin_name, 'WPMerchant Dashboard', 'Dashboard', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard' ));
	  add_submenu_page( $this->plugin_name, 'WPMerchant Settings', 'Settings', 'administrator', $this->plugin_name.'-settings', array( $this, 'display_plugin_admin_settings' ));
	  //add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
  	  
	  /*add_submenu_page( $this->plugin_name, 'Products', 'Products', 'administrator', $this->plugin_name.'-products', array( $this, 'display_plugin_products_page' ));
	  add_submenu_page( $this->plugin_name, 'Plans', 'Plans', 'administrator', $this->plugin_name.'-plans', array( $this, 'display_plugin_plans_page' ));*/
	  
    }
  	/**
  	 * View for AdminSettings Page
  	 *
  	 * @since    1.0.0
  	 */
    public function display_plugin_admin_settings() {
		// set this var to be used in the settings-display view
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
	    if(isset($_GET['error_message'])){
			// to display an error - add the admin_notices action
			// run do_action to pass an argument to the admin notices callback function
			// in the callback array run add_settings_error
			add_action('admin_notices', array($this,'wpmerchant_settings_messages'));
			do_action( 'admin_notices', $_GET['error_message'] );
	    }
		require_once 'partials/wpmerchant-admin-settings-display.php';
    }
  	/**
  	 * View for AdminSettings Page
  	 *
  	 * @since    1.0.0
  	 */
    public function display_plugin_admin_dashboard() {
		// set this var to be used in the settings-display view
		$active_slide = isset( $_GET[ 'slide' ] ) ? $_GET[ 'slide' ] : 'payments';
	    if(isset($_GET['error_message'])){
			// to display an error - add the admin_notices action
			// run do_action to pass an argument to the admin notices callback function
			// in the callback array run add_settings_error
			add_action('admin_notices', array($this,'wpmerchant_settings_messages'));
			do_action( 'admin_notices', $_GET['error_message'] );
	    }
		require_once 'partials/wpmerchant-admin-dashboard-display.php';
    }
	public function dashboard_slide_contents($image_class, $header, $description, $btn){
		if(!$btn && $image_class == 'payments'){
			$stripeLivePublicKey = get_option('wpmerchant_stripe_live_public_key');
			$stripeLiveSecretKey = get_option('wpmerchant_stripe_live_secret_key');
			$stripeTestPublicKey = get_option('wpmerchant_stripe_test_public_key');
			$stripeTestSecretKey = get_option('wpmerchant_stripe_test_secret_key');
			if($stripeLivePublicKey && $stripeLiveSecretKey && $stripeTestPublicKey && $stripeTestSecretKey){
				$description = 'Nice! You\'ve linked to a payment processor.';
				$btn = $this->wpmerchant_return_stripe_connected();
			} else {
				$btn = $this->wpmerchant_return_stripe_connect();
			}
			
		} elseif(!$btn && $image_class == 'newsletters'){ 
			$mailchimpAPIKey = get_option('wpmerchant_mailchimp_api');
			if($mailchimpAPIKey){
				$description = 'Nice! You\'ve linked to a newsletter provider.';
				$btn = $this->wpmerchant_return_mailchimp_connected();
			} else {
				$btn = $this->wpmerchant_return_mailchimp_connect();
			}
		}
	    echo '<div class="no-data-img '.$image_class.'"></div><h2>'.$header.'</h2><p>'.$description.'</p><div class="controls"><p>'.$btn.'</p></div>';
	}
	/**
	* Admin Error Messages
	* @since 1.0.0
	*/
	public function wpmerchant_settings_messages($error_message){
		switch ($error_message) {
			case '1':
				$message = __( 'There was an error connecting to MailChimp. Please try again.  If this persists, shoot us an <a href="mailto:ben@wpmerchant.com">email</a>.', 'my-text-domain' );
				$err_code = esc_attr( 'mailchimp_settings' );
				$setting_field = 'wpmerchant_stripe_status';
				break;
			case '2':
				$message = __( 'There was an error connecting to Stripe. Please try again.  If this persists, shoot us an <a href="mailto:ben@wpmerchant.com">email</a>.', 'my-text-domain' );
				$err_code = esc_attr( 'stripe_settings' );
				$setting_field = 'wpmerchant_mailchimp_api';
				break;
		}
		$type = 'error';
		add_settings_error(
	           $setting_field,
	           $err_code,
	           $message,
	           $type
	       );
	}
	/**
	 * Render Mailchimp Connect Button
	 *
	 * @since    1.0.0
	 */
	public function wpmerchant_render_mailchimp_connect(){
  	  $args = $this->wpmerchant_mailchimp_connect_details();
	  $args['ref_url'] = admin_url( 'admin.php?page=wpmerchant-settings&tab=emails' );
	  $href = 'https://www.wpmerchant.com/wp-admin/admin-ajax.php?action=mailchimp_connect_auth&ajax_url='.urlencode($args['ajax_url']).'&ref_url='.urlencode($args['ref_url']).'&action2=wpmerchant_save_email_api&security='.urlencode($args['saveEmailAPINonce']).'&user_id='.urlencode($args['user_ID']);
   	  	echo '<a class="btn mailchimp-login" href="'.$href.'"><span>| Connect</span></a>';
	}
	public function wpmerchant_return_mailchimp_connect(){
    	  $args = $this->wpmerchant_mailchimp_connect_details();
  	  $args['ref_url'] = admin_url( 'admin.php?page=wpmerchant&slide=newsletters' );
  	  $href = 'https://www.wpmerchant.com/wp-admin/admin-ajax.php?action=mailchimp_connect_auth&ajax_url='.urlencode($args['ajax_url']).'&ref_url='.urlencode($args['ref_url']).'&action2=wpmerchant_save_email_api&security='.urlencode($args['saveEmailAPINonce']).'&user_id='.urlencode($args['user_ID']);
     	  	return '<a class="btn mailchimp-login" href="'.$href.'"><span>| Connect</span></a>';
	}
	public function wpmerchant_mailchimp_connect_details(){
  	  $args['saveEmailAPINonce'] = wp_create_nonce( "wpmerchant_save_email_api" );
  	  update_option( 'wpmerchant_save_email_api_nonce', $args['saveEmailAPINonce'] );
  	  $args['user_ID'] = get_current_user_id();
  	  $args['ajax_url'] = admin_url( 'admin-ajax.php' );
	  return $args;
	}
	/**
	 * Render Stripe Connect Button
	 *
	 * @since    1.0.0
	 */
	public function wpmerchant_render_stripe_connect(){
	  $args = $this->wpmerchant_stripe_connect_details();
	  $args['ref_url'] = admin_url( 'admin.php?page=wpmerchant-settings&tab=payment' );
	  $href = 'https://www.wpmerchant.com/wp-admin/admin-ajax.php?action=stripe_connect_auth&ajax_url='.urlencode($args['ajax_url']).'&ref_url='.urlencode($args['ref_url']).'&action2=wpmerchant_save_payment_api&security='.urlencode($args['savePaymentAPINonce']).'&user_id='.urlencode($args['user_ID']);
	  echo '<a class="btn stripe-login" href="'.$href.'"><span>| Connect</span></a>';	 
	}
	public function wpmerchant_return_stripe_connect(){
	  $args = $this->wpmerchant_stripe_connect_details();
	  $args['ref_url'] = admin_url( 'admin.php?page=wpmerchant&slide=payments' );
	  $href = 'https://www.wpmerchant.com/wp-admin/admin-ajax.php?action=stripe_connect_auth&ajax_url='.urlencode($args['ajax_url']).'&ref_url='.urlencode($args['ref_url']).'&action2=wpmerchant_save_payment_api&security='.urlencode($args['savePaymentAPINonce']).'&user_id='.urlencode($args['user_ID']);
	  return '<a class="btn stripe-login" href="'.$href.'"><span>| Connect</span></a>';	 
	}
	public function wpmerchant_stripe_connect_details(){
  	  $args['savePaymentAPINonce'] = wp_create_nonce( "wpmerchant_save_payment_api" );
  	  update_option( 'wpmerchant_save_payment_api_nonce', $args['savePaymentAPINonce'] );
  	  $args['user_ID'] = get_current_user_id();
  	  $args['ajax_url'] = admin_url( 'admin-ajax.php' );
	  return $args;
	}
	/**
	 * Render Settings Fields Inputs/Select Boxes - This streamlines the creation of a setting input or select box field. Pass arguments to this function to create the setting field you would like to create
	 *
	 * @since    1.0.0
	 */
	public function wpmerchant_render_settings_field($args) {
		/* EXAMPLE INPUT
	              'type'      => 'select',
				  'subtype'	  => '',
				  'id'	  => $this->plugin_name.'_currency',
				  'name'	  => $this->plugin_name.'_currency',
				  'required' => 'required="required"',
				  'get_option_list' => {function_name},
					'value_type' = serialized OR normal,
		'wp_data'=>(option or post_meta),
		'post_id' =>
		*/
		if($args['wp_data'] == 'option'){
			$wp_data_value = get_option($args['name']);
		} elseif($args['wp_data'] == 'post_meta'){
			$wp_data_value = get_post_meta($args['post_id'], $args['name'], true );
		}
		
		switch ($args['type']) {
			case 'select':
				// get the options list array from the get_options_list array value
				$wp_data_list = $this->$args['get_options_list']($args);
				foreach($wp_data_list AS $o){
					$value = ($args['value_type'] == 'serialized') ? serialize($o) : $o['value'];
					$select_options .= ($value == $wp_data_value) ? '<option selected="selected" value=\''.esc_attr($value).'\'>'.$o['name'].'</option>' : '<option value=\''.esc_attr($value).'\'>'.$o['name'].'</option>';
				}
				if(isset($args['disabled'])){
					// hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
					echo '<select id="'.$args['id'].'_disabled" disabled name="'.$args['name'].'_disabled">'.$select_options.'</select><input type="hidden" id="'.$args['id'].'" name="'.$args['name'].'" value="' . esc_attr($wp_data_value) . '" />';
				} else {
					$display = (isset($args['display'])) ? 'style="display:'.$args['display'].';"' : '';
					$attr_value = (isset($args['attr_value'])) ? 'data-value="'.esc_attr($wp_data_value).'"' : '';
					echo '<select '.$attr_value.' '.$display.' id="'.$args['id'].'" "'.$args['required'].'" name="'.$args['name'].'">'.$select_options.'</select>';
					
				}
				
				break;
			case 'input':
				$value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
				if($args['subtype'] != 'checkbox'){
					$prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
					$prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
					$step = (isset($args['step'])) ? 'step="'.$args['step'].'"' : '';
					$min = (isset($args['min'])) ? 'min="'.$args['min'].'"' : '';
					$max = (isset($args['max'])) ? 'max="'.$args['max'].'"' : '';
					if(isset($args['disabled'])){
						// hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
						echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'_disabled" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="'.$args['id'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
					} else {
						echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
					}
					/*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/
					
				} else {
					$checked = ($value) ? 'checked' : '';
					echo '<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" name="'.$args['name'].'" size="40" value="1" '.$checked.' />';
				}
				break;
			default:
				# code...
				break;
		}
	}
	/**
	 * Render Post Meta Tab
	 *
	 * @since    1.0.0
	 */
	public function getPostMetaTab($args){
		return '<li class="'.$args['class'].'">
			<a title="'.$args['link_title'].'" data-href="'.$args['link_href'].'">
				<div>
					<span class="dashicons '.$args['dashicon'].'"></span><span class="wpmTabText">'.$args['tab_text'].'</span>
				</div>
			</a>
		</li>';
	}
	
	
	/**
	Settings Field Validation Functions
	**/
	/**
	 * Sanitization callback for the email option.
	 * Use is_email for Sanitization
	 *
	 * @param  $input  The email user inputed
	 *
	 * @return         The sanitized email.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_validate_input_email ( $input ) {
	  // Get old value from DB
	  $sp_stripe_email = get_option( $this->plugin_name.'_stripe_email' );

	  // Don't trust users
	  $input = sanitize_email( $input );

	  if ( is_email( $input ) || !empty( $input ) ) {
	      $output = $input;
	  }
	  else
	    add_settings_error( $this->plugin_name.'_stripe_account_section', 'invalid-email', __( 'You have entered an invalid email.', $this->plugin_name ) );

	  return $output;

	  } 
  	/**
  	 * Sanitization callback for the status option.
  	 *
  	 * @param  $input  The status user inputed
  	 *
  	 * @return         The sanitized status.
  	 *
  	 * @since 1.0.0
  	 */
  	public function wpmerchant_validate_status ( $input ) {
  	  // Get old value from DB
  	  $sp_stripe_status = get_option( $this->plugin_name.'_stripe_status' );
	  
  	  if (!empty( $input ) && ($input == 'test' || $input == 'live')) {
  	      $output = $input;
  	  }
  	  else
  	    add_settings_error( $this->plugin_name.'_stripe_account_section', 'invalid-status', __( 'You have entered an invalid status.') );

  	  return $output;

  	  } 

	/**
	 * Sanitization callback for the api key option.
	 *
	 * @param  $input  The api key user inputed
	 *
	 * @return         The sanitized api key.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_validate_public_api_key( $input ) {
	  // Get old value
	  	//$output = get_option( 'wpl_stripe_payments_stripe_api_key');

	  // Don't trust users
	  // Strip all HTML and PHP tags and properly handle quoted strings
	  // Leave a-z, A-Z, 0-9 only
	  /*$input = preg_replace('/[^a-zA-Z0-9]/', '' , strip_tags( stripslashes( $input ) ) );*/
	  if( !empty( $input ) ) {
	    $output = $input;
	  } else {
	    add_settings_error( $this->plugin_name.'_stripe_live_public_key', 'invalid-api-key', __( 'Make sure your Stripe Live Public Key is not empty.', $this->plugin_name ) );
	  }
	  return $output;
	} 
	/**
	 * Sanitization callback for the api key option.
	 *
	 * @param  $input  The api key user inputed
	 *
	 * @return         The sanitized api key.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_validate_secret_api_key( $input ) {
	  // Get old value
	  	//$output = get_option( 'wpl_stripe_payments_stripe_api_key');

	  // Don't trust users
	  // Strip all HTML and PHP tags and properly handle quoted strings
	  // Leave a-z, A-Z, 0-9 only
	  /*$input = preg_replace('/[^a-zA-Z0-9]/', '' , strip_tags( stripslashes( $input ) ) );*/
	  if( !empty( $input )) {
	    $output = $input;
	  } else {
	    add_settings_error( $this->plugin_name.'_stripe_live_secret_key', 'invalid-api-key', __( 'Make sure you Connect to Stripe.', $this->plugin_name ) );
	  }
	  return $output;
	} 
	/**
	 * Sanitization callback for the api key option.
	 *
	 * @param  $input  The api key user inputed
	 *
	 * @return         The sanitized api key.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_validate_test_public_api_key( $input ) {
	  // Get old value
	  	//$output = get_option( 'wpl_stripe_payments_stripe_api_key');

	  // Don't trust users
	  // Strip all HTML and PHP tags and properly handle quoted strings
	  // Leave a-z, A-Z, 0-9 only
	  /*$input = preg_replace('/[^a-zA-Z0-9]/', '' , strip_tags( stripslashes( $input ) ) );*/
	  if( !empty( $input ) ) {
	    $output = $input;
	  } else {
	    add_settings_error( $this->plugin_name.'_stripe_test_public_key', 'invalid-api-key', __( 'Make sure your Stripe Test Public Key is not empty.', $this->plugin_name ) );
	  }
	  return $output;
	} 
	/**
	 * Sanitization callback for the api key option.
	 *
	 * @param  $input  The api key user inputed
	 *
	 * @return         The sanitized api key.
	 *
	 * @since 1.0.0
	 */
	public function wpmerchant_validate_test_secret_api_key( $input ) {
	  // Get old value
	  	//$output = get_option( 'wpl_stripe_payments_stripe_api_key');

	  // Don't trust users
	  // Strip all HTML and PHP tags and properly handle quoted strings
	  // Leave a-z, A-Z, 0-9 only
	  /*$input = preg_replace('/[^a-zA-Z0-9]/', '' , strip_tags( stripslashes( $input ) ) );*/
  	  //$stripeAuthentication = Wpmerchant_Public::stripeFunction('setApiKey',$input);
	  if( !empty( $input )) {
	    $output = $input;
	  } else {
	    add_settings_error( $this->plugin_name.'_stripe_test_secret_key', 'invalid-api-key', __( 'Make sure your Stripe Test Secret Key is not empty.', $this->plugin_name ) );
	  }
	  return $output;
	} 
	/**
	GET SETTINGS FIELD SELECT OPTION LISTS
	**/
	/**
	* Get Payment Processor List for the Payment Processor Settings function
	* @since 1.0.2
	*/
	public function get_payment_processor_list(){
  	  $paymentProcessorList = array(		
  						  0 => array('value'=> 'stripe', 'name' => 'Stripe')
  					);
	  return $paymentProcessorList;
	}
	/**
	* Get Email List Processor List for the Email Processor Settings function
	* @since 1.0.2
	*/
	public function get_email_list_processor_list(){
		$emailListProcessorList = array(		
							  0 => array('value'=> 'mailchimp', 'name' => 'MailChimp')
						);
	  return $emailListProcessorList;
	}
	/**
	* Get Stripe Status List for the STripeStatus Settings function
	* @since 1.0.2
	*/
	public function get_stripe_status_list(){
		$stripeStatusList = array(		
							  0 => array('value'=> 'test', 'name' => 'Test'),
							  1 => array('value'=> 'live', 'name' => 'Live'),	  
						);
	  return $stripeStatusList;
	}
	/**
	* Get STock Status List for the Post Meta Product function
	* @since 1.0.2
	*/
	public function get_stock_status_list(){
		$stockStatusList = array(		
							  0 => array('value'=> '1', 'name' => 'In Stock'),	  
							  1 => array('value'=> '0', 'name' => 'Out of Stock'),
						);
	  return $stockStatusList;
	}
	/**
	* Get Mailchimp lists function
	* @since 1.0.2
	*/
	public function get_mailchimp_list($args){
		//$args['api_key']
		$mailchimpLists = array(		
							  0 => array('value'=> '', 'name' => '&nbsp;')
						);
		return $mailchimpLists;
		
	}
	/**
	* Get STock Status List for the Post Meta Product function
	* @since 1.0.2
	*/
	public function get_interval_list(){
		$intervalList = array(		
							  0 => array('value'=> 'day', 'name' => 'Day(s)'),
							  1 => array('value'=> 'week', 'name' => 'Week(s)'),	  
							  2 => array('value'=> 'month', 'name' => 'Month(s)'),	  
							  3 => array('value'=> 'year', 'name' => 'Year'),	  
						);
	  return $intervalList;
	}
	/**
	* Currency List
	* @since 1.0.2
	*/
	public function get_currency_details($currency){
		$currency_list = Wpmerchant_Admin::get_currency_list();
		foreach($currency_list AS $c){
			if($c['value'] == $currency){
				return $c;
				break;
			}
		}
	}
	/**
	* Currency List
	* @since 1.0.2
	*/
	public function get_currency_list(){
	  	  $currencyList = array(		
			  0 => array('value'=> 'AED', 'symbol' =>'', 'name' => 'AED - United Arab Emirates Dirham' 				),
			  1 => array('value'=> 'AFN', 'symbol' =>'؋', 'name' => 'AFN - Afghan Afghani*' 							),
			  2 => array('value'=> 'ALL', 'symbol' =>'Lek', 'name' => 'ALL - Albanian Lek' 							),
			  3 => array('value'=> 'AMD', 'symbol' =>'', 'name' => 'AMD - Armenian Dram' 							),
			  4 => array('value'=> 'ANG', 'symbol' =>'ƒ', 'name' => 'ANG - Netherlands Antillean Gulden' 			),
			  5 => array('value'=> 'AOA', 'symbol' =>'', 'name' => 'AOA - Angolan Kwanza*' 							),
			  6 => array('value'=> 'ARS', 'symbol' =>'$', 'name' => 'ARS - Argentine Peso*' 							),
			  7 => array('value'=> 'AUD', 'symbol' =>'$', 'name' => 'AUD - Australian Dollar' 						),
			  8 => array('value'=> 'AWG', 'symbol' =>'ƒ', 'name' => 'AWG - Aruban Florin' 							),
			  9 => array('value'=> 'AZN', 'symbol' =>'ман', 'name' => 'AZN - Azerbaijani Manat' 						),
			 10 => array('value'=> 'BAM', 'symbol' =>'KM', 'name' => 'BAM - Bosnia & Herzegovina Convertible Mark'	),
			 11 => array('value'=> 'BBD', 'symbol' =>'$', 'name' => 'BBD - Barbadian Dollar' 						),
			 12 => array('value'=> 'BDT', 'symbol' =>'', 'name' => 'BDT - Bangladeshi Taka' 						),
			 13 => array('value'=> 'BGN', 'symbol' =>'лв', 'name' => 'BGN - Bulgarian Lev' 							),
			 14 => array('value'=> 'BIF', 'symbol' =>'', 'name' => 'BIF - Burundian Franc' 							),
			 15 => array('value'=> 'BMD', 'symbol' =>'$', 'name' => 'BMD - Bermudian Dollar' 						),
			 16 => array('value'=> 'BND', 'symbol' =>'$', 'name' => 'BND - Brunei Dollar' 							),
			 17 => array('value'=> 'BOB', 'symbol' =>'$b', 'name' => 'BOB - Bolivian Boliviano*' 						),
			 18 => array('value'=> 'BRL', 'symbol' =>'R$', 'name' => 'BRL - Brazilian Real*' 							),
			 19 => array('value'=> 'BSD', 'symbol' =>'$', 'name' => 'BSD - Bahamian Dollar' 							),
			 20 => array('value'=> 'BWP', 'symbol' =>'P', 'name' => 'BWP - Botswana Pula' 							),
			 21 => array('value'=> 'BZD', 'symbol' =>'BZ$', 'name' => 'BZD - Belize Dollar' 							),
			 22 => array('value'=> 'CAD', 'symbol' =>'$', 'name' => 'CAD - Canadian Dollar' 							),
			 23 => array('value'=> 'CDF', 'symbol' =>'', 'name' => 'CDF - Congolese Franc' 							),
			 24 => array('value'=> 'CHF', 'symbol' =>'CHF', 'name' => 'CHF - Swiss Franc' 								),
			 25 => array('value'=> 'CLP', 'symbol' =>'$', 'name' => 'CLP - Chilean Peso*' 							),
			 26 => array('value'=> 'CNY', 'symbol' =>'¥', 'name' => 'CNY - Chinese Renminbi Yuan' 					),
			 27 => array('value'=> 'COP', 'symbol' =>'$', 'name' => 'COP - Colombian Peso*' 							),
			 28 => array('value'=> 'CRC', 'symbol' =>'₡', 'name' => 'CRC - Costa Rican Colón*' 						),
			 29 => array('value'=> 'CVE', 'symbol' =>'', 'name' => 'CVE - Cape Verdean Escudo*' 					),
			 30 => array('value'=> 'CZK', 'symbol' =>'Kč', 'name' => 'CZK - Czech Koruna*' 							),
			 31 => array('value'=> 'DJF', 'symbol' =>'', 'name' => 'DJF - Djiboutian Franc*' 						),
			 32 => array('value'=> 'DKK', 'symbol' =>'kr', 'name' => 'DKK - Danish Krone' 							),
			 33 => array('value'=> 'DOP', 'symbol' =>'RD$', 'name' => 'DOP - Dominican Peso' 							),
			 34 => array('value'=> 'DZD', 'symbol' =>'', 'name' => 'DZD - Algerian Dinar' 							),
			 35 => array('value'=> 'EEK', 'symbol' =>'kr', 'name' => 'EEK - Estonian Kroon*' 							),
			 36 => array('value'=> 'EGP', 'symbol' =>'£', 'name' => 'EGP - Egyptian Pound' 							),
			 37 => array('value'=> 'ETB', 'symbol' =>'', 'name' => 'ETB - Ethiopian Birr' 							),
			 38 => array('value'=> 'EUR', 'symbol' =>'€', 'name' => 'EUR - Euro' 									),
			 39 => array('value'=> 'FJD', 'symbol' =>'$', 'name' => 'FJD - Fijian Dollar' 							),
			 40 => array('value'=> 'FKP', 'symbol' =>'£', 'name' => 'FKP - Falkland Islands Pound*' 					),
			 41 => array('value'=> 'GBP', 'symbol' =>'£', 'name' => 'GBP - British Pound' 							),
			 42 => array('value'=> 'GEL', 'symbol' =>'', 'name' => 'GEL - Georgian Lari' 							),
			 43 => array('value'=> 'GIP', 'symbol' =>'£', 'name' => 'GIP - Gibraltar Pound' 							),
			 44 => array('value'=> 'GMD', 'symbol' =>'', 'name' => 'GMD - Gambian Dalasi' 							),
			 45 => array('value'=> 'GNF', 'symbol' =>'', 'name' => 'GNF - Guinean Franc*' 							),
			 46 => array('value'=> 'GTQ', 'symbol' =>'Q', 'name' => 'GTQ - Guatemalan Quetzal*' 						),
			 47 => array('value'=> 'GYD', 'symbol' =>'$', 'name' => 'GYD - Guyanese Dollar' 							),
			 48 => array('value'=> 'HKD', 'symbol' =>'$', 'name' => 'HKD - Hong Kong Dollar' 						),
			 49 => array('value'=> 'HNL', 'symbol' =>'L', 'name' => 'HNL - Honduran Lempira*' 						),
			 50 => array('value'=> 'HRK', 'symbol' =>'kn', 'name' => 'HRK - Croatian Kuna' 							),
			 51 => array('value'=> 'HTG', 'symbol' =>'', 'name' => 'HTG - Haitian Gourde' 							),
			 52 => array('value'=> 'HUF', 'symbol' =>'Ft', 'name' => 'HUF - Hungarian Forint*' 						),
			 53 => array('value'=> 'IDR', 'symbol' =>'Rp', 'name' => 'IDR - Indonesian Rupiah' 						),
			 54 => array('value'=> 'ILS', 'symbol' =>'₪', 'name' => 'ILS - Israeli New Sheqel' 						),
			 55 => array('value'=> 'INR', 'symbol' =>'', 'name' => 'INR - Indian Rupee*' 							),
			 56 => array('value'=> 'ISK', 'symbol' =>'kr', 'name' => 'ISK - Icelandic Króna' 							),
			 57 => array('value'=> 'JMD', 'symbol' =>'J$', 'name' => 'JMD - Jamaican Dollar' 							),
			 58 => array('value'=> 'JPY', 'symbol' =>'¥', 'name' => 'JPY - Japanese Yen' 							),
			 59 => array('value'=> 'KES', 'symbol' =>'', 'name' => 'KES - Kenyan Shilling' 							),
			 60 => array('value'=> 'KGS', 'symbol' =>'лв', 'name' => 'KGS - Kyrgyzstani Som' 							),
			 61 => array('value'=> 'KHR', 'symbol' =>'៛', 'name' => 'KHR - Cambodian Riel' 							),
			 62 => array('value'=> 'KMF', 'symbol' =>'', 'name' => 'KMF - Comorian Franc' 							),
			 63 => array('value'=> 'KRW', 'symbol' =>'₩', 'name' => 'KRW - South Korean Won' 						),
			 64 => array('value'=> 'KYD', 'symbol' =>'$', 'name' => 'KYD - Cayman Islands Dollar' 					),
			 65 => array('value'=> 'KZT', 'symbol' =>'лв', 'name' => 'KZT - Kazakhstani Tenge' 						),
			 66 => array('value'=> 'LAK', 'symbol' =>'₭', 'name' => 'LAK - Lao Kip*' 								),
			 67 => array('value'=> 'LBP', 'symbol' =>'£', 'name' => 'LBP - Lebanese Pound' 							),
			 68 => array('value'=> 'LKR', 'symbol' =>'₨', 'name' => 'LKR - Sri Lankan Rupee' 						),
			 69 => array('value'=> 'LRD', 'symbol' =>'$', 'name' => 'LRD - Liberian Dollar' 							),
			 70 => array('value'=> 'LSL', 'symbol' =>'', 'name' => 'LSL - Lesotho Loti' 							),
			 71 => array('value'=> 'LTL', 'symbol' =>'Lt', 'name' => 'LTL - Lithuanian Litas' 						),
			 72 => array('value'=> 'LVL', 'symbol' =>'Ls', 'name' => 'LVL - Latvian Lats' 							),
			 73 => array('value'=> 'MAD', 'symbol' =>'', 'name' => 'MAD - Moroccan Dirham' 							),
			 74 => array('value'=> 'MDL', 'symbol' =>'', 'name' => 'MDL - Moldovan Leu' 							),
			 75 => array('value'=> 'MGA', 'symbol' =>'', 'name' => 'MGA - Malagasy Ariary' 							),
			 76 => array('value'=> 'MKD', 'symbol' =>'ден', 'name' => 'MKD - Macedonian Denar' 						),
			 77 => array('value'=> 'MNT', 'symbol' =>'₮', 'name' => 'MNT - Mongolian Tögrög' 						),
			 78 => array('value'=> 'MOP', 'symbol' =>'', 'name' => 'MOP - Macanese Pataca' 							),
			 79 => array('value'=> 'MRO', 'symbol' =>'', 'name' => 'MRO - Mauritanian Ouguiya' 						),
			 80 => array('value'=> 'MUR', 'symbol' =>'₨', 'name' => 'MUR - Mauritian Rupee*' 						),
			 81 => array('value'=> 'MVR', 'symbol' =>'', 'name' => 'MVR - Maldivian Rufiyaa' 						),
			 82 => array('value'=> 'MWK', 'symbol' =>'', 'name' => 'MWK - Malawian Kwacha' 							),
			 83 => array('value'=> 'MXN', 'symbol' =>'$', 'name' => 'MXN - Mexican Peso*' 							),
			 84 => array('value'=> 'MYR', 'symbol' =>'RM', 'name' => 'MYR - Malaysian Ringgit' 						),
			 85 => array('value'=> 'MZN', 'symbol' =>'MT', 'name' => 'MZN - Mozambican Metical' 						),
			 86 => array('value'=> 'NAD', 'symbol' =>'$', 'name' => 'NAD - Namibian Dollar' 							),
			 87 => array('value'=> 'NGN', 'symbol' =>'₦', 'name' => 'NGN - Nigerian Naira' 							),
			 88 => array('value'=> 'NIO', 'symbol' =>'C$', 'name' => 'NIO - Nicaraguan Córdoba*' 						),
			 89 => array('value'=> 'NOK', 'symbol' =>'kr', 'name' => 'NOK - Norwegian Krone' 							),
			 90 => array('value'=> 'NPR', 'symbol' =>'₨', 'name' => 'NPR - Nepalese Rupee' 							),
			 91 => array('value'=> 'NZD', 'symbol' =>'$', 'name' => 'NZD - New Zealand Dollar' 						),
			 92 => array('value'=> 'PAB', 'symbol' =>'B/.', 'name' => 'PAB - Panamanian Balboa*' 						),
			 93 => array('value'=> 'PEN', 'symbol' =>'S/.', 'name' => 'PEN - Peruvian Nuevo Sol*' 						),
			 94 => array('value'=> 'PGK', 'symbol' =>'', 'name' => 'PGK - Papua New Guinean Kina' 					),
			 95 => array('value'=> 'PHP', 'symbol' =>'₱', 'name' => 'PHP - Philippine Peso' 							),
			 96 => array('value'=> 'PKR', 'symbol' =>'₨', 'name' => 'PKR - Pakistani Rupee' 							),
			 97 => array('value'=> 'PLN', 'symbol' =>'zł', 'name' => 'PLN - Polish Złoty' 							),
			 98 => array('value'=> 'PYG', 'symbol' =>'Gs', 'name' => 'PYG - Paraguayan Guaraní*' 						),
			 99 => array('value'=> 'QAR', 'symbol' =>'﷼', 'name' => 'QAR - Qatari Riyal' 							),
			100 => array('value'=> 'RON', 'symbol' =>'lei', 'name' => 'RON - Romanian Leu' 							),
			101 => array('value'=> 'RSD', 'symbol' =>'Дин.', 'name' => 'RSD - Serbian Dinar' 							),
			102 => array('value'=> 'RUB', 'symbol' =>'руб', 'name' => 'RUB - Russian Ruble' 							),
			103 => array('value'=> 'RWF', 'symbol' =>'', 'name' => 'RWF - Rwandan Franc' 							),
			104 => array('value'=> 'SAR', 'symbol' =>'﷼', 'name' => 'SAR - Saudi Riyal' 								),
			105 => array('value'=> 'SBD', 'symbol' =>'$', 'name' => 'SBD - Solomon Islands Dollar' 					),
			106 => array('value'=> 'SCR', 'symbol' =>'₨', 'name' => 'SCR - Seychellois Rupee' 						),
			107 => array('value'=> 'SEK', 'symbol' =>'kr', 'name' => 'SEK - Swedish Krona' 							),
			108 => array('value'=> 'SGD', 'symbol' =>'$', 'name' => 'SGD - Singapore Dollar' 						),
			109 => array('value'=> 'SHP', 'symbol' =>'£', 'name' => 'SHP - Saint Helenian Pound*' 					),
			110 => array('value'=> 'SLL', 'symbol' =>'', 'name' => 'SLL - Sierra Leonean Leone' 					),
			111 => array('value'=> 'SOS', 'symbol' =>'S', 'name' => 'SOS - Somali Shilling' 							),
			112 => array('value'=> 'SRD', 'symbol' =>'$', 'name' => 'SRD - Surinamese Dollar*' 						),
			113 => array('value'=> 'STD', 'symbol' =>'', 'name' => 'STD - São Tomé and Príncipe Dobra' 				),
			114 => array('value'=> 'SVC', 'symbol' =>'$', 'name' => 'SVC - Salvadoran Colón*' 						),
			115 => array('value'=> 'SZL', 'symbol' =>'', 'name' => 'SZL - Swazi Lilangeni' 							),
			116 => array('value'=> 'THB', 'symbol' =>'฿', 'name' => 'THB - Thai Baht' 								),
			117 => array('value'=> 'TJS', 'symbol' =>'', 'name' => 'TJS - Tajikistani Somoni' 						),
			118 => array('value'=> 'TOP', 'symbol' =>'', 'name' => 'TOP - Tongan Paʻanga' 							),
			119 => array('value'=> 'TRY', 'symbol' =>'', 'name' => 'TRY - Turkish Lira' 							),
			120 => array('value'=> 'TTD', 'symbol' =>'TT$', 'name' => 'TTD - Trinidad and Tobago Dollar' 				),
			121 => array('value'=> 'TWD', 'symbol' =>'NT$', 'name' => 'TWD - New Taiwan Dollar' 						),
			122 => array('value'=> 'TZS', 'symbol' =>'', 'name' => 'TZS - Tanzanian Shilling' 						),
			123 => array('value'=> 'UAH', 'symbol' =>'₴', 'name' => 'UAH - Ukrainian Hryvnia' 						),
			124 => array('value'=> 'UGX', 'symbol' =>'', 'name' => 'UGX - Ugandan Shilling' 						),
			125 => array('value'=> 'USD', 'symbol' =>'$', 'name' => 'USD - United States Dollar' 					),
			126 => array('value'=> 'UYU', 'symbol' =>'$U', 'name' => 'UYU - Uruguayan Peso*' 							),
			127 => array('value'=> 'UZS', 'symbol' =>'лв', 'name' => 'UZS - Uzbekistani Som' 							),
			128 => array('value'=> 'VND', 'symbol' =>'₫', 'name' => 'VND - Vietnamese Đồng' 							),
			129 => array('value'=> 'VUV', 'symbol' =>'', 'name' => 'VUV - Vanuatu Vatu' 							),
			130 => array('value'=> 'WST', 'symbol' =>'', 'name' => 'WST - Samoan Tala' 								),
			131 => array('value'=> 'XAF', 'symbol' =>'', 'name' => 'XAF - Central African Cfa Franc' 				),
			132 => array('value'=> 'XCD', 'symbol' =>'$', 'name' => 'XCD - East Caribbean Dollar' 					),
			133 => array('value'=> 'XOF', 'symbol' =>'', 'name' => 'XOF - West African Cfa Franc*' 					),
			134 => array('value'=> 'XPF', 'symbol' =>'', 'name' => 'XPF - Cfp Franc*' 								),
			135 => array('value'=> 'YER', 'symbol' =>'﷼', 'name' => 'YER - Yemeni Rial' 								),
			136 => array('value'=> 'ZAR', 'symbol' =>'R', 'name' => 'ZAR - South African Rand' 						),
			137 => array('value'=> 'ZMW', 'symbol' =>'', 'name' => 'ZMW - Zambian Kwacha' 							),
		);
		return $currencyList;
	}
}
