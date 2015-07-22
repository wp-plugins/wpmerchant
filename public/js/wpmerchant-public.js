(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note that this assume you're going to use jQuery, so it prepares
	 * the $ function reference to be used within the scope of this
	 * function.
	 *
	 * From here, you're able to define handlers for when the DOM is
	 * ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * Or when the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and so on.
	 *
	 * Remember that ideally, we should not attach any more than a single DOM-ready or window-load handler
	 * for any particular page. Though other scripts in WordPress core, other plugins, and other themes may
	 * be doing this, we should try to minimize doing that in our own work.
	 */
	var WPMerchant = {
		construct:function(){
			console.log('1');
			$(function() {
				console.log('2')
				if($('.wpMerchantPurchase').length > 0){
					$('body').append('<div class="wpm-overlay"><div id="wpm_loading_indicator" class="wpm-loading-indicator"><img src="'+ajax_object.loading_gif+'" width="50" height="50"></div><div id="wpm_message"><a class="wpm-close-link"><img class="wpm-close" src="'+ajax_object.close_btn_image+'"></a><h1>'+ajax_object.post_checkout_msg+'</h1><p><img src="'+ajax_object.stripe_checkout_image+'" height="128px" width="128px"></p></div></div>');
					
				  $('.wpMerchantPurchase').bind('click', function(e) {
					  console.log('clickspp');
					  //$(".overlayView2").css("display","none");
					  var receiptMsg1 = '';
					  var receiptMsg2 = '';
					  var companyName = ajax_object.company_name;
					  var stripePublicKey = ajax_object.stripe_public_key;
					  if($(this).data('products')){
						  var products = JSON.stringify($(this).data('products'));
					  } else {
						  var products = '';
					  }
					  
					  /*if(spProductId.indexOf(',') > -1){
					     var product_ids = spProductId.split(",");
						 // find the frequency of products listed
						 var products = new Array();
						 for(var i=0;i< product_ids.length;i++)
						 {
						   var key = product_ids[i];
						   products[key] = (products[key])? products[key] + 1 : 1 ;
						 }
					  } else {
						  var products[spProductId] = 1;
					  }
					  if(typeof products !== 'undefined'){
					  	products = JSON.stringify(products);
					  } else {
						  var products = '';
					  }*/
					  
					  var amount = $(this).data('amount');
					  var description =  $(this).data('description');
					  var currency =  ajax_object.currency;
					  
					  var panelLabel = 'Purchase - {{amount}}';
					  
					  var spImage = ajax_object.stripe_checkout_image;
					  console.log(companyName+', '+description+', '+amount+', '+panelLabel+', '+receiptMsg1+', '+receiptMsg2+', '+stripePublicKey+', '+spImage+', '+products+', '+currency);
					  //display the loader gif
					  WPMerchant.overlayOn('loading');
					  WPMerchant.stripeHandler(companyName, description, amount, panelLabel, receiptMsg1, receiptMsg2, stripePublicKey, spImage, products,currency);
				    // Open Checkout with further options
				    /*handler.open({
				      name: 'MettaGroup',
				      description: 'One-on-One Mentoring ($150/month)',
				      amount: 15000
				    });*/
				  }); 
			    }
		 	 });
		},
		overlayOn:function(type){
			console.log('on')
			switch (type) {
			case 'loading':
			  $('#wpm_loading_indicator').css("display","block").css("opacity","1"); 
				break;
			case 'message':
				$('#wpm_message').css("display","block");
				break;
			default:
				
			}
			$('.wpm-overlay').css("display","block");
		},
		overlayOff:function(){
			console.log('off')
			$('#wpm_loading_indicator').css("display","none").css("opacity","0");
			$('#wpm_message').css("display","none");
			$('.wpm-overlay').css("display","none");
		},
		stripeHandler: function(companyName, productDescription, amount, panelLabel, receiptMsg1, receiptMsg2, stripePublicKey, spImage,products,currency){ 
			var handler2 = StripeCheckout.configure({
				key: stripePublicKey,
			    image: spImage,
				panelLabel: panelLabel,
				name: companyName,
				currency:currency,
			    description: productDescription,
			    amount: amount,
				opened:function(){  
					// this runs when the modal is closed
					console.log('opened');
					WPMerchant.overlayOff();
				},
				token: function(token, args) {
				  WPMerchant.overlayOn('loading');
			      // Use the token to create the charge with a server-side script.
			      // You can access the token ID with `token.id`
			      console.log(token);
				  console.log(products);
				  //WPMerchant.loadingModal();
				  var dataString = "token=" + encodeURIComponent(token.id) + "&email=" + encodeURIComponent(token.email) + "&products=" + encodeURIComponent(products)+"&action=wpmerchant_purchase&amount="+encodeURIComponent(amount)+"&security="+ajax_object.purchase_nonce;
				  console.log(ajax_object);
					$.ajax({
						url: ajax_object.ajax_url,  
						type: "POST",
						data: dataString,
						dataType:'json',
						success: function(data){
						    if(data.response == 'success'){
		  					  WPMerchant.overlayOff();
						      console.log('success')
								if(data.redirect){
									console.log('redirect exists')
									window.open(data.redirect,'_self');
								} else {
									console.log('no redirect exists')
									WPMerchant.overlayOn('message');
									$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
								}
								var responseMessage = 'Purchase Complete';
							   var receiptMsg1 = 'We have emailed you a receipt.';
							   var receiptMsg2 = 'Support us by sharing this purchase on your social networks.';
					   		   //WPMerchant.updateModal(productDescription, responseMessage, receiptMsg1, receiptMsg2);
						   } else if (data.response == 'sold_out'){
							   WPMerchant.overlayOff();
   						      console.log('sold_out')
   								/*if(data.redirect){
   									console.log('redirect exists')
   									window.open(data.redirect,'_self');
   								} else {
   									console.log('no redirect exists')
							   		WPMerchant.overlayOn('message');
							   		$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
								}*/
						   		$("#wpm_message").find('h1').empty().text('Sold Out!')
								WPMerchant.overlayOn('message');
						   		$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
							   
					   	   } else {
							   WPMerchant.overlayOff();
   						      console.log('error')
   								/*if(data.redirect){
   									console.log('redirect exists')
   									window.open(data.redirect,'_self');
   								} else {
   									console.log('no redirect exists')
							   		WPMerchant.overlayOn('message');
							   		$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
								}*/
						   		$("#wpm_message").find('h1').empty().text('Purchase Error')
								WPMerchant.overlayOn('message');
						   		$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
							   var responseMessage = 'Purchase Error'
							   var receiptMsg1 = 'We\'re sorry! There was an error purchasing this product.  Please contact <a href="mailto:george@mettagroup.org">george@mettagroup.org</a>.';
							   var receiptMsg2 = '';
					   		   //WPMerchant.updateModal(productDescription, responseMessage, receiptMsg1, receiptMsg2);
						   }
						  console.log( data );
						  },
						error: function(jqXHR, textStatus, errorThrown) { 
					      WPMerchant.overlayOff();
						  console.log('error')
							/*if(data.redirect){
								console.log('redirect exists')
								window.open(data.redirect,'_self');
							} else {
								console.log('no redirect exists')
						   		WPMerchant.overlayOn('message');
						   		$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
							}*/
					   		$("#wpm_message").find('h1').empty().text('Purchase Error')
							WPMerchant.overlayOn('message');
					   		$(".wpm-close-link").bind("click",WPMerchant.overlayOff);
							console.log(jqXHR, textStatus, errorThrown); 
						   var responseMessage = 'Purchase Error'
						   var receiptMsg1 = 'We\'re sorry! There was an error purchasing this product.  Please contact <a href="mailto:george@mettagroup.org">george@mettagroup.org</a>.';
						   var receiptMsg2 = '';
				   		   //WPMerchant.updateModal(productDescription, responseMessage, receiptMsg1, receiptMsg2);
						}
					});
		 	  	 }
		 	 }); 
		 	 handler2.open();
	  	}
	
	}
	WPMerchant.construct();

})( jQuery );
