(function( $ ) {
	'use strict';
	var WPMerchantAdmin = {
		construct:function(){
			$(function() {
				
				 if(location.pathname.search('wp-admin/post.php') != -1 || location.pathname.search('wp-admin/post-new.php') != -1){
					  /*This allows us to use the links as tabs to show the different fields in hte Product Data metabox */
					  $('.product_container_tabs a').click(function(){
						  // hide all tab content
						  $('.product_field_containers').each(function( index ) {
							   $( this ).parent().removeClass("wpm_show").addClass("wpm_hide");
						  });
						  // remove active classes from all li parenst of a links
						  $('.product_container_tabs li').each(function( index ) {
							   $( this ).removeClass("active");
						  });
						  //show the tab content that is clicked and add the active class to the li
						  var href = $(this).data("href");
						  $(this).parent().addClass('active');
						  $(href).removeClass('wpm_hide').addClass('wpm_show');
					  })
		  			  $('#wpmerchant_interval').change(function() {
		  					if($( this ).val() == 'day'){
		  						$('#wpmerchant_interval_count').attr('max','365');
		  					} else if($( this ).val() == 'week'){
		  						$('#wpmerchant_interval_count').attr('max','52');
		  					} else if($( this ).val() == 'month'){
		  						$('#wpmerchant_interval_count').attr('max','12');
		  					} else if($( this ).val() == 'year'){
		  						$('#wpmerchant_interval_count').attr('max','1');
		  					}
		  			  });
				  } else if(location.pathname.search('wp-admin/admin.php') != -1){
					  if(WPMerchantAdmin.getQueryVariable('page') == 'wpmerchant-settings'){
						if($('.mailchimp-login').length <= 0){
							WPMerchantAdmin.getEmailData();
							$("#mailchimp-log-out").bind('click',WPMerchantAdmin.clearMailchimpAPI);
						}
						if($('.stripe-login').length <= 0){
							$("#stripe-log-out").bind('click',WPMerchantAdmin.clearStripeAPI);
						}
						
						  
					  }
				  }
				 
 		 	 });
 		},
		clearMailchimpAPI: function(event){
			event.preventDefault();
			var clear= '';
			$("#wpmerchant_mailchimp_api").val(clear);
			$("#wpmerchant_mailchimp_gen_list_id option:selected").each(function() {
				$( this ).removeAttr('selected');
		    });
			$("#submit").click();
		},
		clearStripeAPI: function(event){
			event.preventDefault();
			var clear= '';
			$("#wpmerchant_stripe_test_public_key").val(clear);
			$("#wpmerchant_stripe_test_secret_key").val(clear);
			$("#wpmerchant_stripe_live_public_key").val(clear);
			$("#wpmerchant_stripe_live_secret_key").val(clear);
			
			$("#submit").click();
		},
		getEmailData: function(){ 
		  var dataString = "action=wpmerchant_get_email_data&security="+encodeURIComponent(ajax_object.get_email_data_nonce);
		  console.log(ajax_object);
		  console.log('getEmailData')
			$.ajax({
				url: ajax_object.ajax_url,  
				type: "GET",
				  data: dataString,
				  dataType:'json',
				  success: function(data){
				    if(data.response == 'success'){
					   console.log('success')
						var options = '';
						var existingValue = $("#wpmerchant_mailchimp_gen_list_id").data("value")
						for (var i = 0; i < data.lists.length; i++) { 
							if(data.lists[i].value == existingValue){
								var selected = 'selected'
							} else {
								var selected = '';
							}
						    options += '<option '+selected+' value="'+data.lists[i].value+'">'+data.lists[i].name+'</option>';
						}
						console.log(options)
						// this is just for hte polling version
						//$("#wpmerchant_mailchimp_gen_list_id").parent().siblings('th').text('General Interest List ID');
						//$("#wpmerchant_mailchimp_gen_list_id").css("display","block");
						$("#wpmerchant_mailchimp_gen_list_id").html(options);							
			   	   } else if(data.response == 'empty'){
					    console.log(data)
					   // polling to see if the key has been received or not
					   // this response is only returned if no api key exists - so keep running it until we get one
					   //WPMerchantAdmin.getEmailData();
				   } else if(data.response == 'error'){
					   // number of polls has gone over the limit so we throw this instead of empty - prevent polling from continuing
				   	   console.log(data)
				   }
				  console.log( data );
				  },
				error: function(jqXHR, textStatus, errorThrown) { 
					console.log(jqXHR, textStatus, errorThrown); 
				    console.log('no lists')
					//$(".planExistsStatus").css("display","block");
					//$(".dashicon-container").empty().append('<span class="dashicons dashicons-no" style="color:#a00;"></span>');
				}
			});
		},
		getQueryVariable:function(variableName) {
		       var query = window.location.search.substring(1);
		       var vars = query.split("&");
		       for (var i=0;i<vars.length;i++) {
		               var pair = vars[i].split("=");
		               if(pair[0] == variableName){return pair[1];}
		       }
		       return(false);
		},
		getCookie: function(cname) {
		    var name = cname + "=";
		    var ca = document.cookie.split(';');
		    for(var i=0; i<ca.length; i++) {
		        var c = ca[i];
		        while (c.charAt(0)==' ') c = c.substring(1);
		        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
		    }
		    return "";
		},
		setCookie: function(cname, cvalue, exdays) {
		    var d = new Date();
		    d.setTime(d.getTime() + (exdays*24*60*60*1000));
		    var expires = "expires="+d.toUTCString();
		    document.cookie = cname + "=" + cvalue + "; " + expires;
		}
	}
	WPMerchantAdmin.construct();
})( jQuery );
