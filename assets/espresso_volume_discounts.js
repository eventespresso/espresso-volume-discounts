var nIntervId;
var waitingForAjax;
var vDebug = false;		//  true		false

(function($) {

	if ( vDebug ) {
		$('#event_espresso_shopping_cart').append( '<div id="volume-discounts-debug"></div>' );
	}

	if ( $('#spinner').height() == null ) {
		var spinner = '<img id="spinner" style="display:none;" src="'+EEGlobals.plugin_url+'images/ajax-loader.gif">';
		$('#event_total_price').after( spinner );
	}
	
	
	function display_vDebug( vDebugMsg ) {
		if ( vDebug ) {
			$('#volume-discounts-debug').append( vDebugMsg +"<br /><br />" );
			console.log( vDebugMsg );
		}		
	}


	function vlm_dscnt_loading() {
		if ( $('#shopping_cart_after_total').html() == '' ) {
			$('#shopping_cart_after_total').html('<span class="event_total_price"><img src="'+EEGlobals.plugin_url+'images/ajax-loader.gif"></span>');		
		}		
	}

	function apply_discount_to_total_price() {
	
		var event_total_price = $('#event_total_price').html();
		event_total_price = parseFloat(event_total_price);

		if ( $.isNumeric( event_total_price ) && event_total_price != NaN  ) {
		
			display_vDebug( (new Error).lineNumber + ')  event_total_price: '+event_total_price );
		
			// no savings yet, so for now this is the orig price
			var orig_total = parseFloat( event_total_price );
			// what discounts are based on
			var vlm_dscnt_factor = $('#vlm_dscnt_factor').val();
			// what discounts are based on
			var vlm_dscnt_amount = $('#vlm_dscnt_amount').val();
			// what discounts are based on
			var vlm_dscnt_type = $('#vlm_dscnt_type').val();
			display_vDebug( (new Error).lineNumber + ') vlm_dscnt_factor: '+vlm_dscnt_factor+'<br />' + 'vlm_dscnt_amount: '+vlm_dscnt_amount+'<br />' + 'vlm_dscnt_type: '+vlm_dscnt_type );

			// is discount a fixed dollar value discount or percentage ?
			if ( vlm_dscnt_type == 'vlm-dscnt-type-dollar' ) {
				// subtract discount from total
				event_total_price = event_total_price - vlm_dscnt_amount;
			} else {
				// percentage discount
				event_total_price = event_total_price * (( 100 - vlm_dscnt_amount ) / 100 );
			}
			// make it look like money
			var discounted_total = parseFloat( event_total_price );
			if ( discounted_total < 0 ) {
				discounted_total = 0.00;
			}
			var discount = parseFloat( orig_total - discounted_total );

			// is there a discount?
			var process_vlm_dscnt = espresso_process_vlm_dscnt();
			
			if ( process_vlm_dscnt == 'Y' && ! isNaN(discount) && ! isNaN(discounted_total) && discount > 0 && discounted_total >= 0 ) {

				display_vDebug( (new Error).lineNumber + ') process_vlm_dscnt: '+dump( process_vlm_dscnt )+'<br />' + 'discount: '+dump( discount )+'<br />' + 'discounted_total: '+dump( discounted_total ));
				var msg = '<span class="event_total_price" style="clear:both; width:auto;">'+evd.msg+' ' + evd.cur_sign + '<span>'+discount.toFixed(2)+'</span></span>';
				msg = msg+'<span class="event_total_price" style="clear:both; width:auto;">Total ' + evd.cur_sign + '<span>'+discounted_total.toFixed(2)+'</span></span>';
			
				// hide it, switch it, then fade it in... oh yeah that's nice
				$('#shopping_cart_after_total').hide().html(msg).fadeIn();

			} else {
				display_vDebug( (new Error).lineNumber + ') no discount' );
				// remove discount
				$('#shopping_cart_after_total').html('')
				discount = 0;
			}
			
			if ( parseFloat(discounted_total) != NaN && parseFloat(discounted_total) <= parseFloat(orig_total) ) {			
				// it's good? then store it in the session'
				espresso_store_discount_in_session( discount.toFixed(2) );
			}
			
			$('#spinner').fadeOut('fast');
			clearTimeout( waitingForAjax );
			
		} else {
			event_total_price_blur();
		}

	}





	function event_total_price_blur() {				
		// show spinny thing
		vlm_dscnt_loading();
		$('#volume-discounts-debug').html('');
		waitingForAjax = setTimeout( apply_discount_to_total_price, 250 );
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
							//display_vDebug( (new Error).lineNumber + ') ' + params.toSource() );
						},
						success: function(response){
						
							var new_grand_total = response.grand_total;
							display_vDebug( (new Error).lineNumber + ') new_grand_total = ' + new_grand_total );
	
							$.ajax({
									        type: "POST",
									        url:  evd.ajaxurl,
											data: {
															"action" : "espresso_set_grand_total_in_session",
															"grand_total" : new_grand_total
														},
											dataType: "json",
											success: function(response){
												//display_vDebug( (new Error).lineNumber + ') ' + response.success);
											},
											error: function(response) {
												//display_vDebug( (new Error).lineNumber + ') ' + response.error);
											}			
									});	
											
							$('#event_total_price').html( new_grand_total);
							$('#spinner').fadeOut('fast', function() {
								$('#event_total_price').fadeIn('fast');
							});		
	
						},
						error: function(response) {
							display_vDebug( (new Error).lineNumber + ') Error.');
						}			
	
				});			

		event_total_price_blur();
		
	}
	
	
	
	
	
	function espresso_store_discount_in_session( vlm_dscnt ) {
	
		data = {
			'action' : 'espresso_store_discount_in_session',
			'vlm_dscnt' : vlm_dscnt
		};

		$.getJSON( evd.ajaxurl, data );

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
		if ( vlm_dscnt_categories instanceof Array ) {
			$.each( vlm_dscnt_categories, function( i, vlm_dscnt_cat ){
				vlm_dscnt_categories[ i ] = parseInt( vlm_dscnt_cat );
				display_vDebug( (new Error).lineNumber + ') ' + 'vlm_dscnt_cat = '+ dump( vlm_dscnt_cat ) );
			});		
		} else {
			vlm_dscnt_categories = new Array();
			vlm_dscnt_categories[0] = parseInt( vlm_dscnt_cats );			
			display_vDebug( (new Error).lineNumber + ') ' + 'vlm_dscnt_categories = '+ vlm_dscnt_cats );
		} 
		// price_id selector
		var priceID = false;
		
		display_vDebug( (new Error).lineNumber + ') vlm_dscnt_factor = ' + vlm_dscnt_factor + '<br />' + 'vlm_dscnt_threshold = ' + vlm_dscnt_threshold + '<br />' + 'process_vlm_dscnt = ' + process_vlm_dscnt + '<br />' + 'vlm_dscnt_categories = ' + vlm_dscnt_categories );

		// cycle through all of the events in the cart
		$('.multi_reg_cart_block').each(function () {
			display_vDebug( (new Error).lineNumber + ') ' + $( this ).find( '.event_title' ).html() );
			// total tickets for this event
			var totalTickets = 0;
			var event_cat_good = false;
			// category id for this event
			var event_cats = $( this ).find( '.vlm_dscnt_cat' ).each( function () {
				event_cat = parseInt( $( this ).val() );
				display_vDebug( (new Error).lineNumber + ') event_cat = '+ dump( event_cat ) + '<br />' + 'vlm_dscnt_categories = '+ dump( vlm_dscnt_categories ) + '<br />' + 'inArray = '+jQuery.inArray( event_cat, vlm_dscnt_categories )  );
				
				
				if (  jQuery.inArray( event_cat, vlm_dscnt_categories )> -1 ) {
					event_cat_good = true;
				}
			});	

			display_vDebug( (new Error).lineNumber + ') event_cat_good = '+event_cat_good );
		
			// is it in our list of categories that get discounts?
			if( vlm_dscnt_categories == 'A,' || event_cat_good ) {
			
				display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr: '+vlm_dscnt_cntr+'<br />' + 'vlm_dscnt_cntr_total: '+vlm_dscnt_cntr_total );
						
				// is discount based on cart total ?
				if ( vlm_dscnt_factor == 'fctr_dollar_value' ) {

					vlm_dscnt_cntr = 0;
					
					// grab the prices
					var prices = $( this ).find( 'td.price' );
					prices.each(function () {
						
						var price = $( this ).html();
						var price_selector = $( this ).next( 'td.selection' ).children( '.price_id' );
							
						if ( price_selector.prop('type') == 'select-one' ){
							var quantity = parseInt( price_selector.val() );
						} else if ( price_selector.prop('type') == 'radio' && price_selector.is(':checked') ){
							var quantity = 1;
						} else {
							var quantity = 0;
						}

						// remove the currency sign
						price = price.replace( evd.cur_sign, '');
						// parse values as floats
						vlm_dscnt_cntr += parseFloat( price ) * quantity;
						display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr = ' + vlm_dscnt_cntr +'<br />' + 'price: '+parseFloat( price ) +'<br />' + 'quantity: '+quantity );
					});
										
	
					// make sure the counter total is set to 'T' 
					$('#process_vlm_dscnt').val( 'T' );
		
				// or is discount based on adding up registrations or meta data ?	
				} else {
	
					// all price selectors for this event
					var priceSelects = $( this ).find( '.price_id' );
					// loop thru price selectors
					priceSelects.each(function () {
						// verify it's a dropdown'
						if ( $( this ).is('select')) {
							// add number of selected tickets to total
							totalTickets += parseInt( $( this ).val() );
						} else {
							totalTickets++;
						}
					});
					display_vDebug( (new Error).lineNumber + ') totalTickets = '+totalTickets );	
					// grab whatever value we are counting
					var vlm_dscnt_cntr_init = $( this ).find( '.vlm_dscnt_cntr' ).val();
					display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr_init = ' + vlm_dscnt_cntr_init );
					// set counters for events using dropdowns for number of tickets 
					vlm_dscnt_cntr_init = parseFloat( vlm_dscnt_cntr_init ) * parseInt( totalTickets );
					display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr_init = ' + vlm_dscnt_cntr_init );
					// parse values as integers
					vlm_dscnt_cntr = parseFloat( vlm_dscnt_cntr_init );
					display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr = ' + vlm_dscnt_cntr );
				
				}
			
				display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr = ' + vlm_dscnt_cntr );
				// if counter value is a number
				if ( vlm_dscnt_cntr != NaN ) {
					// add it up
					vlm_dscnt_cntr_total += parseFloat( vlm_dscnt_cntr );
				}		
						
				display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr: '+vlm_dscnt_cntr+'<br />' + 'vlm_dscnt_cntr_total: '+vlm_dscnt_cntr_total );

			}
	
		});
		
		if ( vlm_dscnt_cntr_total == undefined || vlm_dscnt_cntr_total == null ) {
			vlm_dscnt_cntr_total = 0;
		}
		
		// set the counter total
		$('#vlm_dscnt_cntr_total').val( vlm_dscnt_cntr_total );

		display_vDebug( (new Error).lineNumber + ') vlm_dscnt_cntr_total: '+vlm_dscnt_cntr_total+'<br />' + 'vlm_dscnt_threshold : '+vlm_dscnt_threshold );

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

	function trigger_total_event_cost_update(){
				
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
	}





	$('#event_espresso_refresh_total').on( 'click', function(){
		// click??? trigger it 
		trigger_total_event_cost_update();
    });






	$('.price_id, #event_espresso_coupon_code, #event_espresso_groupon_code').on( 'change', function(){
		// change??? trigger it 
		trigger_total_event_cost_update();
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

	
	/**
	 * Function : dump()
	 * Arguments: The data - array,hash(associative array),object
	 *    The level - OPTIONAL
	 * Returns  : The textual representation of the array.
	 * This function was inspired by the print_r function of PHP.
	 * This will accept some data as the argument and return a
	 * text that will be a more readable version of the
	 * array/hash/object that is given.
	 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
	 */
	function dump(arr,level) {
		var dumped_text = "[ "+typeof(arr)+" ] \n";
		if(!level) level = 0;
		
		//The padding given at the beginning of the line.
		var level_padding = "";
		for(var j=0;j<level+1;j++) level_padding += "    ";
		
		if(typeof(arr) == 'object') { //Array/Hashes/Objects 
			for(var item in arr) {
				var value = arr[item];
				
				//If it is an array,
				if(typeof(value) == 'object') { 
					dumped_text += level_padding + "'" + item + "' ...\n";
					dumped_text += dump(value,level+1);
				} else {
					dumped_text += level_padding + "'" + item + "' => '" + value + "'\n";
				}
			}
		} else { //Stings/Chars/Numbers etc.
			dumped_text += " "+arr;
		}
		return dumped_text;
	}


	
})(jQuery);