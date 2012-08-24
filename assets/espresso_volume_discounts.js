/*
 * jQuery doTimeout: Like setTimeout, but better! - v1.0 - 3/3/2010
 * http://benalman.com/projects/jquery-dotimeout-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function($){var a={},c="doTimeout",d=Array.prototype.slice;$[c]=function(){return b.apply(window,[0].concat(d.call(arguments)))};$.fn[c]=function(){var f=d.call(arguments),e=b.apply(this,[c+f[0]].concat(f));return typeof f[0]==="number"||typeof f[1]==="number"?this:e};function b(l){var m=this,h,k={},g=l?$.fn:$,n=arguments,i=4,f=n[1],j=n[2],p=n[3];if(typeof f!=="string"){i--;f=l=0;j=n[1];p=n[2]}if(l){h=m.eq(0);h.data(l,k=h.data(l)||{})}else{if(f){k=a[f]||(a[f]={})}}k.id&&clearTimeout(k.id);delete k.id;function e(){if(l){h.removeData(l)}else{if(f){delete a[f]}}}function o(){k.id=setTimeout(function(){k.fn()},j)}if(p){k.fn=function(q){if(typeof p==="string"){p=g[p]}p.apply(m,d.call(n,i))===true&&!q?o():e()};o()}else{if(k.fn){j===undefined?e():k.fn(j===false);return true}else{e()}}}})(jQuery);



(function($) {

	if ( $('#spinner').height() == null ) {
		var spinner = '<img id="spinner" style="display:none;" src="'+EEGlobals.plugin_url+'images/ajax-loader.gif">';
		$('#event_total_price').after( spinner );
	}


	function vlm_dscnt_loading() {
		$('#shopping_cart_after_total').html('<span class="event_total_price"><img src="'+EEGlobals.plugin_url+'images/ajax-loader.gif"></span>');		
	}




	
	function kickstart() {

		// set the spinny thingy
		$('#event_total_price').fadeOut('fast', function() {
			$('#spinner').fadeIn('fast');
		});		
		
		// target function is from MER and we are sending the entire form serialized - overkill?
		var params = "action=event_espresso_calculate_total&" + jQuery("#event_espresso_shopping_cart").serialize();

		$.ajax({
				        type: "POST",
				        url:  evd.ajaxurl,
						data: params,
						dataType: "json",
						beforeSend: function(){
							//alert( params.toSource() );
						},
						success: function(response){
						
							var new_grand_total = response.grand_total;
							//alert( 'new_grand_total = ' + new_grand_total );
	
							$.ajax({
									        type: "POST",
									        url:  evd.ajaxurl,
											data: {
															"action" : "espresso_set_grand_total_in_session",
															"grand_total" : new_grand_total
														},
											dataType: "json",
											success: function(response){
												//alert(response.success);
											},
											error: function(response) {
												//alert(response.error);
											}			
									});	
											
							$('#event_total_price').html( new_grand_total);
							$('#spinner').fadeOut('fast', function() {
								$('#event_total_price').fadeIn('fast');
							});		
	
						},
						error: function(response) {
							//alert("Error.");
						}			
	
				});			

		event_total_price_blur();
		
	}
	
	
	
	
	
	function espresso_store_discount_in_session( vlm_dscnt ) {
	
		data = {
			'action' : 'espresso_store_discount_in_session',
			'vlm_dscnt' : vlm_dscnt
		};

		$.getJSON( evd.ajaxurl, data, function(response) {
/*			if ( response ) {
				// trigger it ASAP
				alert( 'vlm_dscnt : ' + response.vlm_dscnt );
			}*/
		});

	}




	function espresso_process_vlm_dscnt() {
		
		// counter
		var vlm_dscnt_cntr = 0;
		// counter total
		var vlm_dscnt_cntr_total = 0;
		// what discounts are based on
		var vlm_dscnt_factor = $('#vlm_dscnt_factor').val();
		// threshhold at which discounts occur
		var vlm_dscnt_threshold = $('#vlm_dscnt_threshold').val();
		// do we have a discount?
		var process_vlm_dscnt = $('#process_vlm_dscnt').val();
		// csv list of categories that discounts will apply to
		var vlm_dscnt_cats = $('#vlm_dscnt_categories').val();
		// change to an array
		var vlm_dscnt_categories = vlm_dscnt_cats.split(',');
		// price_id selector
		var priceID = false;
		
		//alert( 'vlm_dscnt_factor = ' + vlm_dscnt_factor + '\n' + 'vlm_dscnt_threshold = ' + vlm_dscnt_threshold + '\n' + 'process_vlm_dscnt = ' + process_vlm_dscnt + '\n' + 'vlm_dscnt_categories = ' + vlm_dscnt_categories );

		// cycle through all of the events in the cart
		$('.multi_reg_cart_block').each(function () {
			// total tickets for this event
			var totalTickets = 0;
			// all price selectors for this event
			var priceSelects = $( this ).find( '.price_id' );
			// loop thru price selectors
			priceSelects.each(function () {
				// verify it's a dropdown'
				if ( $( this ).is('select')) {
					// add number of selected tickets to total
					totalTickets += parseInt( $( this ).val() );
				} 
			});
			// set counters for events using dropdowns for number of tickets 
			$( this ).find( '.vlm_dscnt_cntr' ).val( parseInt( totalTickets ));

			// category id for this event
			var event_cat = $( this ).find( '.vlm_dscnt_cat' ).val();	
			if ( event_cat == undefined || event_cat == '' ) {
				event_cat = -1;
			}
			//alert( 'event_cat = '+event_cat );
		
			// is it in our list of categories that get discounts?
			if( jQuery.inArray( event_cat, vlm_dscnt_categories) > -1 || vlm_dscnt_categories == 'A' ) {
				//alert( event_cat+' is in '+vlm_dscnt_categories );
				//alert( '132) vlm_dscnt_cntr: '+vlm_dscnt_cntr+'\n\vlm_dscnt_cntr_total: '+vlm_dscnt_cntr_total );
						
				// is discount based on cart total ?
				if ( vlm_dscnt_factor == 'fctr_dollar_value' ) {

					// grab the prices
					var prices = $( this ).find( 'td.price' );
					vlm_dscnt_cntr = 0;
					prices.each(function () {
						price = $( this ).html();
						quantity = $( this ).siblings( 'td.selection' ).children( '.price_id' ).val();
						// remove the currency sign
						price = price.replace( evd.cur_sign, '');
						// parse values as floats
						vlm_dscnt_cntr += parseFloat( price ) * quantity;
					});
										
	
					// make sure the counter total is set to 'T' 
					$('#process_vlm_dscnt').val( 'T' );
		
				// or is discount based on adding up registrations or meta data ?	
				} else {
	
					// grab whatever value we are counting
					vlm_dscnt_cntr = $( this ).find( '.vlm_dscnt_cntr' ).val();
					// parse values as integers
					vlm_dscnt_cntr = parseInt( vlm_dscnt_cntr );


				}
				//alert( 'vlm_dscnt_cntr = ' + vlm_dscnt_cntr );
				// if counter value is a number
				if ( vlm_dscnt_cntr != NaN ) {
					// add it up
					vlm_dscnt_cntr_total = parseInt( vlm_dscnt_cntr_total ) + parseInt( vlm_dscnt_cntr );
				}		
						
				//alert( '164) vlm_dscnt_cntr: '+vlm_dscnt_cntr+'\n\vlm_dscnt_cntr_total: '+vlm_dscnt_cntr_total );

			}
	
		});
		
		// set the counter total
		$('#vlm_dscnt_cntr_total').val( vlm_dscnt_cntr_total );

		//alert( 'vlm_dscnt_cntr_total: '+vlm_dscnt_cntr_total+'\nvlm_dscnt_threshold : '+vlm_dscnt_threshold );

		// has the volume discount counter surpassed the threshold ???
		if ( vlm_dscnt_cntr_total >= vlm_dscnt_threshold ) {
			// threshold has been met and we have a discount
			// is discount based on Total cart value ?
			if ( process_vlm_dscnt == 'T' ) {
				// process discount based on Total
				$('#process_vlm_dscnt').val( 'T' );
			} else {
				// YES process discount
				$('#process_vlm_dscnt').val( 'Y' );
			}
			return 'Y';
			
		} else {
		
			// no discount
			if ( process_vlm_dscnt == 'T' ) {
				// process still based on Total
				$('#process_vlm_dscnt').val( 'T' );
			} else {
				// NO don't process'
				$('#process_vlm_dscnt').val( 'N' );
			}	
			return 'N';
			
		}
	}





	$('#event_total_price').on( 'focus blur change', function() {
		event_total_price_blur();
	});



	function event_total_price_blur() {

	    // Check to see if the counter has been initialized
	    if ( typeof event_total_price_blur.counter == 'undefined' ) {
	        event_total_price_blur.counter = 0;
	    }
		
		var event_total_price = $('#event_total_price').html();
		
		if ( $.isNumeric( event_total_price )) {

			// is there a discount?
			var process_vlm_dscnt = espresso_process_vlm_dscnt();
			
			 // YEAH ???
//			if ( process_vlm_dscnt == 'Y' ) {
				
				// show spinny thing
				vlm_dscnt_loading();
					
				// wait a second for other ajax stuff to fire - is this hacky or what?
				$.doTimeout( 1250, function() {
				
					// get the cart total
					var event_total_price = $('#event_total_price').html()
					event_total_price = $.trim( event_total_price );
					
					// if cart total isn't done calculating, drive around in circles
					while ( event_total_price == NaN ) {
						// then every 1/4 second
						$.doTimeout( 250, function() {
							// check cart total again
							event_total_price = $('#event_total_price').html()
						});
					}
					
					// if price is not zero
					if ( event_total_price != '0.00' && event_total_price != NaN ) {
					//alert( '251)  event_total_price: '+event_total_price );
					
						// no savings yet, so for now this is the orig price
						var orig_total = event_total_price;
						// what discounts are based on
						var vlm_dscnt_factor = $('#vlm_dscnt_factor').val();
						// what discounts are based on
						var vlm_dscnt_amount = $('#vlm_dscnt_amount').val();
						// what discounts are based on
						var vlm_dscnt_type = $('#vlm_dscnt_type').val();
						//alert( '279) vlm_dscnt_factor: '+vlm_dscnt_factor+'\n\vlm_dscnt_amount: '+vlm_dscnt_amount+'\n\vlm_dscnt_type: '+vlm_dscnt_type );

						// is discount a fixed dollar value discount or percentage ?
						if ( vlm_dscnt_type == 'vlm-dscnt-type-dollar' ) {
							// subtract discount from total
							event_total_price = event_total_price - vlm_dscnt_amount;
						} else {
							// percentage discount
							event_total_price = event_total_price * (( 100 - vlm_dscnt_amount ) / 100 );
						}
						
						// make it look like money
						var discounted_total = parseFloat(event_total_price).toFixed(2);
						var savings = orig_total - discounted_total;
						var discount = savings.toFixed(2);
						
						//alert( 'discounted_total: '+discounted_total+'\n\discount: '+discount );

						if ( process_vlm_dscnt == 'Y' && ! isNaN(discount) && ! isNaN(discounted_total) ) {

							// put together discount html
//							var msg = '<span class="event_total_price" style="clear:both;">'+discount+'</span>';
//							msg = msg+'<span class="event_total_price" style="width:auto;">'+evd.msg+' ' + evd.cur_sign + '</span>';
//							msg = msg+'<span class="event_total_price" style="clear:both;" >'+discounted_total+'</span>';
//							msg = msg+'<span class="event_total_price" style="width:auto;">Total ' + evd.cur_sign + '</span>';
							
							var msg = '<span class="event_total_price" style="clear:both; width:auto;">'+evd.msg+' ' + evd.cur_sign + '<span>'+discount+'</span></span>';
							msg = msg+'<span class="event_total_price" style="clear:both; width:auto;">Total ' + evd.cur_sign + '<span>'+discounted_total+'</span></span>';
							
							// style="color: #333333; float: right; font-size: 25px; padding: 5px;"
							
							// hide it, switch it, then fade it in... oh yeah that's nice
							$('#shopping_cart_after_total').hide().html(msg).fadeIn();

						} else {
							// remove discount
							$('#shopping_cart_after_total').html('')
							discount = 0;
						}

						// final check to make sure discounted total has been calculated correctly
						if ( event_total_price_blur.counter > 3 ) {
							$('#spinner').fadeOut('fast');
							return false;
						} else if ( discounted_total == NaN || parseFloat(discounted_total) > parseFloat(orig_total) || discounted_total == '0.00' ) {			
							event_total_price_blur.counter++;				
							// if not then run the whole show again
							event_total_price_blur();
						} else {
							// it's good? then store it in the session'
							espresso_store_discount_in_session( discount );
							$('#spinner').fadeOut('fast');
						}
						
					} else {
						// hmmmm... MER ajax still not done?? do it again.
						event_total_price_blur();
					}
					
				});	
					 		
 		}

   }






	function trigger_total_event_cost_update(){
		// wait a bit
//		$.doTimeout( 1500, function() {

			// show spinny thing
			//vlm_dscnt_loading();
				
			// need a quick way to toggle whether to recheck discount 
			if ( $('#process_vlm_dscnt').val() == 'T' ) {
				// discount based on cart total - which always rechecks
				$('#process_vlm_dscnt').val( 'T');				
			} else {
				// rechecks discount
				$('#process_vlm_dscnt').val( 'N');				
			}

			// then trigger it
			event_total_price_blur();
//		});		
	}





	$('#event_espresso_refresh_total').on( 'click', function(){
		// click??? trigger it 
		trigger_total_event_cost_update();
//		event_total_price_blur();
//		return false;
    });






	$('.price_id, #event_espresso_coupon_code').on( 'change', function(){
		// click??? trigger it 
		trigger_total_event_cost_update();
//		event_total_price_blur();
//		return false;
    });
	
	
	
	
	
	$('.ee_delete_item_from_cart').on( 'click', function(){
		trigger_total_event_cost_update();
		kickstart();		
    });




	// if the event_total_price field is defined, then we must be on the cart
	if ( $('#event_total_price').html() != undefined ) {
		// initialize the cart	
		kickstart();
	}
	
	//$('#event_total_price').css({ 'padding-right' : '5px' });

	
})(jQuery);