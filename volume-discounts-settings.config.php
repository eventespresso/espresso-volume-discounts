<?php
/**
 * Define our settings sections
 *
 * array key=$id, array value=$title in: add_settings_section( $id, $title, $callback, $page );
 * @return array
 */
function vlm_dscnt_settings_sections() {
	
	$sections = array();
	$sections['factors'] = '<br />' . __('Determining Factors', 'espresso');
	$sections['discount'] = '<br />' . __('The Actual Discount', 'espresso');
	$sections['categories'] = '<br />' . __('Event Categories', 'espresso');
	
	return $sections;	
}

/**
 * Define our form fields (settings) 
 *
 * @return array
 */
function vlm_dscnt_settings_fields() {

	// Text Form Fields section
	$options = array();
	
	// Select Form Fields section
	$options[] = array(
		'type'    		=> 'select2',
		'section' 		=> 'factors',
		'id'      			=> 'vlm-dscnt-factor-slct',
		'class'   		=> 'nohtml',
		'validation'	=> 'nohtml',
		'title'   			=> __( 'Discount based on:', 'espresso' ),
		'desc'    		=> __( 'Select the option that will be used to determine whether a volume discount applies or not.', 'espresso' ),
		'std'    			=> 'Number of Registrations',
		'choices' 		=> array( __('Number of Registrations', 'espresso' ) . '|fctr_registrations', __('Total Dollar Value', 'espresso' ) . '|fctr_dollar_value', __('An Event Meta Field', 'espresso' ) . '|fctr_meta_field' )
	);
	
	$options[] = array(
		'type'    	=> 'text',
		'section' 	=> 'factors',
		'id'      		=>'vlm-dscnt-meta-field-txt',
		'class'  	 => 'nohtml meta-field-option',
		'validation'	=> 'nohtml',
		'title'   		=> __( 'Event meta field', 'espresso' ),
		'desc'   	 => __( 'Enter the name of the Event Meta Field to be used. You will have to create this Meta Field for every Event that needs a value different than the default value.', 'espresso' ),
		'std'   		  => __('event-meta-field','espresso')
	);
	
	$options[] = array(
		'type'    => 'text',
		'section' => 'factors',
		'id'      => 'vlm-dscnt-default-meta-field-value-txt',
		'class'   => 'small-text meta-field-option',
		'validation'	=> 'numeric',
		'title'   => __( 'Default meta field value', 'espresso' ),
		'desc'    => __( 'This is the default value for the above Event Meta Field, that will be applied to every event, unless explicitely overwritten by adding a new value to an Event\'s meta data.', 'espresso' ),
		'std'     => 0
	);	
	
	$options[] = array(
		'type'    => 'text',
		'section' => 'factors',
		'id'      => 'vlm-dscnt-threshold-txt',
		'class'   => 'small-text',
		'validation'	=> 'numeric',
		'title'   => __( 'Discount threshold', 'espresso' ),
		'desc'    => __( 'This is the point at which discounts begin. If the factor chosen above reaches this Discount Threshold, then the Discount will be applied.', 'espresso' ),
		'std'     => 0
	);
	
	$options[] = array(
		'type'    => 'text',
		'section' => 'discount',
		'id'      => 'vlm-dscnt-amount-txt',
		'class'   => 'small-text',
		'validation'	=> 'numeric',
		'title'   => __( 'Discount amount', 'espresso' ),
		'desc'    => __( 'The amount of Discount to be applied once the Discount Threshold has been reached.', 'espresso' ),
		'std'     => 0
	);


	$options[] = array(
		'type'    => 'select2',
		'section' => 'discount',
		'id'      => 'vlm-dscnt-type-slct',
		'class'   => '',
		'validation'	=> 'nohtml',
		'title'   => __( 'Discount type', 'espresso' ),
		'desc'    => __( 'This decides whether the discount will be a fixed dollar amount, like $25 off, or a percentage discount off the total value spent, like 10% off $250 (also $25).', 'espresso' ),
		'std'     => 'vlm-dscnt-type-dollar',
		'choices' => array( __('Fixed Dollar Amount','espresso') . '|vlm-dscnt-type-dollar', __('Percentage Discount','espresso') . '|vlm-dscnt-type-percent' )	

	);
	
	$options[] = array(
		'type'    => 'text',
		'section' => 'discount',
		'id'      => 'vlm-dscnt-message-txt',
		'css' 	=> 'regular-text',
		'validation'	=> 'nohtml',
		'title'   => __( 'Discount applied message', 'espresso' ),
		'desc'    => __( 'This is the message that users will see when a discount has been successfully applied to their purchase.', 'espresso' ),
		'std'     => 'Multi Event Discount: You Saved '
	);
	
	$options[] = array(
		"type"    => "multi-checkbox",
		"section" => "categories",
		"id"      =>  "vlm-dscnt-categories-slct",
		'class'   => '',
		'validation'	=> 'nohtml',
		"title"   => __( 'Discounts apply to', 'espresso' ),
		"desc"    => __( 'Only the following checked off categories will have Volume Discounts applied to them', 'espresso' ),
		"std"     => '',
		"choices" => vlm_dscnt_get_category_options()
	);
	
	
	return $options;	
		
}



/**
 * Contextual Help
 */
function vlm_dscnt_contextual_help() {
	
	$text 	= '<h3>' . __('Volume Discounts - Contextual Help','espresso') . '</h3>';
	$text 	.= '<p>' . __('','espresso') . '</p>';
	
	// must return text! NOT echo
	return $text;
} 



function vlm_dscnt_get_categories() {

	global $wpdb;
	
//	$user = wp_get_current_user();
//	$current_wp_user_id = isset( $user->id ) ? $user->id : FALSE;
		
	if ( defined( EVENTS_CATEGORY_TABLE )) {
		$cat_tbl = EVENTS_CATEGORY_TABLE;
	} else {
		$cat_tbl = $wpdb->prefix . 'events_category_detail';
	}
	
	$sql = 'SELECT id, category_name';
	
//	if ( $current_wp_user_id ) {
//		$sql .= ', wp_user';
//	}

	$sql .= ' FROM '. $cat_tbl;
	
//	if ( $current_wp_user_id ) {
//		$sql .= ' WHERE wp_user = ' . $current_wp_user_id;
//	}

	//$safe_sql = $wpdb->prepare( $sql );
	$event_cats = $wpdb->get_results( $sql );


	if ( count( $event_cats ) > 0 ) {
		return $event_cats;
	} else {
		return FALSE;
	}
	
}



function vlm_dscnt_get_category_options() {
 
	$cat_options = array();
	$cat_options[] = __( 'All Categories','espresso' ) . '|A';	

	if ( $categories = vlm_dscnt_get_categories() ) {
		foreach ( $categories as $category ) {
			$cat_options[] = __( $category->category_name,'espresso' ) . '|' . $category->id;
		}
		
	} 
	
	return $cat_options;


}










