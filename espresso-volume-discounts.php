<?php
/*
Plugin Name: Event Espresso Volume Discounts
Plugin URI: http://www.eventespresso.com
Description: Volume Discounts addon for Event Espresso - apply discounts based on a factor such as the number of events registered for, or the toal dollar value spent
Version: 0.5.1
Author: Seth Shoultes
Author URI: http://www.eventespresso.com
Copyright (c) 2008-2011 Event Espresso  All Rights Reserved.
License: 

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 
*/
/**
 * ------------------------------------------------------------------------
 *
 * EE_VLM_DSCNT class
 *
 * @package				Event Espresso
 * @subpackage		Volume Discounts
 * @author					Brent Christensen 
 *
 */
class EE_VLM_DSCNT {


 	// instance of the VLM_DSCNT object
	private static $_instance = NULL;
	// an array for storing settings options
	var $_settings_options = array();  
	// the total number of class credites
	var $_class_credits = 0;
	// wp admin page name
	private $_vlm_dscnt_settings_page = FALSE;
	// counter
	private $_vlm_dscnt_cntr = 0;
	// volume discount event categories
	private $_vlm_dscnt_cats = array();




 
	/**
	*		@singleton method used to instantiate class object
	*		@access public
	*		@return class instance
	*/	
	public  function &instance ( ) {
		// check if class object is instantiated
		if ( self::$_instance === NULL  or ! is_object( self::$_instance ) or ! is_a( self::$_instance, __CLASS__ )) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	
	

	
	/**
	*		private constructor to prevent direct creation
	*		@Constructor
	*		@access private
	*		@return void
	*/	
	private function __construct() {
		
		if ( ! defined( 'EVENT_ESPRESSO_VERSION' ))  {
			return;
		}
		// prefix
	    define('VLM_DSCNT', 'vlmdscnt');  
		// the settings page slug  
	    define('VLM_DSCNT_PG_SLUG', 'volume-discounts'); 
		// the name of this plugin
		define( 'VLM_DSCNT_PLUGIN', plugin_basename(__FILE__));
		// the server path to this plugin
		define( 'VLM_DSCNT_PATH', plugin_dir_path( __FILE__ ));
		// the URL path to this plugin
		define( 'VLM_DSCNT_URL', plugin_dir_url( __FILE__ ));
		// url path to the assets folder
		define( 'VLM_DSCNT_ASSETS_PATH', VLM_DSCNT_URL . 'assets/' );
	
		// AJAX
		add_action('wp_ajax_espresso_store_discount_in_session', array( &$this, 'store_discount_in_session' ));
	    add_action('wp_ajax_nopriv_espresso_store_discount_in_session', array( &$this, 'store_discount_in_session' ));
		
		add_action( 'wp_ajax_espresso_set_grand_total_in_session', array( &$this, 'set_grand_total_in_session' ));
		add_action( 'wp_ajax_nopriv_espresso_set_grand_total_in_session', array( &$this, 'set_grand_total_in_session' ));

		
		// load code for admin or frontend ?
		if ( is_admin() ) {
			// you are in control, unless it's an ajax call
			if (!defined('DOING_AJAX'))
                            $this->admin();			
		} else {
			// public functionality
			$this->frontend();
		}

	}
	
	
	
	
	

	// **********************************************************************************************************************
	// **************************************************    ADMIN METHODS     ************************************************	
	// **********************************************************************************************************************



	
	/**
	*		admin functions
	*		@Constructor
	*		@access public
	*		@return void
	*/	
	public function admin() {	
	
		//echo __FUNCTION__ . '<br />';
		
		// installation
		register_activation_hook( __FILE__, array( &$this, 'install_volume_discounts' ));
		// check for MER
		if ( $_GET['page'] == 'volume-discounts' && ! defined( 'ESPRESSO_MULTI_REG_VERSION' )) {
			// admin messages hook!
			add_action('admin_notices', array( &$this, 'mer_required_msg' ));		
		}
		// link to VLM_DSCNT settings page from the plugin page
		add_filter( 'plugin_action_links_' . VLM_DSCNT_PLUGIN, array( &$this, 'plugin_page_to_settings_link' ), 10, 1 );
		// load settings options
		$this->set_settings_options();
		// Hook into admin menu to add VLM_DSCNT settings page 
		add_action('action_hook_espresso_add_new_ee_submenu', array( &$this, 'create_admin_menu_subpage' ));
		// register settings
		add_action('admin_init', array( &$this, 'register_settings' ));
		// admin messages hook!
		add_action('admin_notices', array( &$this, 'admin_msgs' ));		
		//change default event meta
		add_filter( 'filter_hook_espresso_filter_default_event_meta', array( &$this, 'filter_default_event_meta' ), 10, 1 );

	}	





	/**
	*		installation script
	*		@access public
	*		@return void
	*/	
	public function install_volume_discounts() {

		//echo __FUNCTION__ . '<br />';

	
	}





	/**
	*		mer_required_msg
	*		@access public
	*		@return void
	*/	
	public function mer_required_msg() {
		$this->show_msg('<p class="setting-error-message" title="MER is required">' . __( '<b>MER is NOT activated!</b> This addon works in conjunction with the Event Espresso Multi-Event Registration addon and is required in order to function properly. Please activate MER!', 'event_espresso' ) . '</p>', 'error' );
	}
	
	
	
	
	
	/**
	 * 	creates a link within the WP Plugins listings to the Volume Discount settings page
	*		@access public
	*		@param array		$actions	an array of links currently applied to each plugin
	*		@return array
	*/
	public function plugin_page_to_settings_link( $actions ){
		// create the link
		$link_to_settings = '<a href="admin.php?page='.VLM_DSCNT_PG_SLUG.'">' . __( 'Settings', 'event_espresso' ) . '</a>';
		// add it to the $actions array
		array_unshift( $actions, $link_to_settings ); 
		// it's good to give back'
		return $actions;
	}	
	
	
	
	
	
	/** 
	* 		define variables for the settings page 
	*		@access private
	* 		@return array 
	*/  
    private function set_settings_options() {  

		// page settings sections & fields as well as the contextual help text.
		require_once('volume-discounts-settings.config.php');
		// the option name as used in the get_option() call.  
		$this->_settings_options['vlm_dscnt_option_name'] = 'espresso_volume_discounts'; 
		// the setting section  
		$this->_settings_options['vlm_dscnt_settings_sections'] = vlm_dscnt_settings_sections();
		// the setting fields  
		$this->_settings_options['vlm_dscnt_settings_fields'] = vlm_dscnt_settings_fields(); 
		// the contextual help  
		$this->_settings_options['vlm_dscnt_contextual_help'] = vlm_dscnt_contextual_help(); 

		
   }  	
	
	
	
	
	
	/** 
	* 		load css and js resources for the settings page 
	*		@access public
	* 		@return void 
	*/  
    public function _load_settings_resources() {  
		// add a bit o' style
		wp_enqueue_style('espresso_volume_discount', VLM_DSCNT_ASSETS_PATH . 'volume_discounts_admin.css'); 
		// and make it dance
		wp_enqueue_script( 'espresso_volume_discount', VLM_DSCNT_ASSETS_PATH . 'espresso_volume_discounts_admin.js', array('jquery'), '1.0', TRUE );		
	}
	




	/**
	*		create subpage within existing Event Espresso main menu 
	*		@access public
	*		@return void
	*/		
	public function create_admin_menu_subpage() {
	  
		//echo __FUNCTION__ . '<br />';
		global $org_options, $espresso_premium;
		$espresso_manager = '';
		
		//If the permissions manager is installed, then load the $espresso_manager global
		if (function_exists('espresso_permissions_config_mnu') && $espresso_premium == true) {
			global $espresso_manager;
		} else {
			$espresso_manager = array( 
															'espresso_manager_events' => '', 
															'espresso_manager_categories' => '', 
															'espresso_manager_form_groups' => '', 
															'espresso_manager_form_builder' => '', 
															'espresso_manager_groupons' => '', 
															'espresso_manager_discounts' => '', 
															'espresso_manager_event_emails' => '', 
															'espresso_manager_personnel_manager' => '', 
															'espresso_manager_general' => '', 
															'espresso_manager_calendar' => '', 
															'espresso_manager_members' => '', 
															'espresso_manager_payment_gateways' => '', 
															'espresso_manager_social' => '', 
															'espresso_manager_addons' => '', 
															'espresso_manager_support' => '', 
															'espresso_manager_venue_manager' => '', 
															'espresso_manager_event_pricing' => ''
														);
		}
		
		$this->_vlm_dscnt_settings_page = add_submenu_page(
																					'event_espresso',
																					__('Event Espresso - Volume Discount', 'event_espresso'), 
																					__('Volume Discounts', 'event_espresso'), 
																					apply_filters('espresso_management_capability', 'administrator', $espresso_manager['espresso_manager_discounts']), 
																					VLM_DSCNT_PG_SLUG, 
																					array( &$this, 'admin_settings_page' )
																				);		
										
		// load css and js resources
		add_action( 'load-'. $this->_vlm_dscnt_settings_page, array( &$this, '_load_settings_resources' ) );
		
	}	

	
	
	
	
	/**
	*		register settings
	* 
	*		@access public
	*		@return void
	*/		
	public function register_settings(){  
	  
//		echo __FUNCTION__ . '<br />';
		
		//register_setting( $option_group, $option_name, $sanitize_callback );  
	    register_setting( $this->_settings_options['vlm_dscnt_option_name'], $this->_settings_options['vlm_dscnt_option_name'], array( &$this, 'input_validation' ));  

//		echo '<pre>' . print_r( $this->_settings_options['vlm_dscnt_settings_sections'] ) . '</pre><br />';

		// create sections
		if (! empty( $this->_settings_options['vlm_dscnt_settings_sections'] )) {
			// call the "add_settings_section" for each!
			foreach ( $this->_settings_options['vlm_dscnt_settings_sections'] as $id => $title ) {
				add_settings_section( $id, $title, array( &$this, '_create_settings_section' ), VLM_DSCNT_PG_SLUG );
			}
		}
			
		// create fields
		if ( ! empty( $this->_settings_options['vlm_dscnt_settings_fields'] )) {
			// call the "add_settings_field" for each!
			foreach ( $this->_settings_options['vlm_dscnt_settings_fields'] as $settings_field ) {
				$this->_create_settings_field( $settings_field );
			}
		}		
	}





	/**
	 * 	helper function for registering form field settings
	 *
	*		@access public
	 * 	@param 	array	$desc 		array of arguments to be used in creating the field
	 * 	@return 	void
	 */
	public function  _create_settings_section( $desc = 'Settings for this section' ) {
	
//		echo __FUNCTION__ . '<br />';
//		echo '<pre>' . print_r( $desc ) . '</pre><br />';
//		echo "<p>" . __( $desc, 'event_espresso' ) . "</p>";
	}





	
	/**
	 * 	helper function for registering form field settings
	 *
	*		@access private
	 * 	@param 	array	$args 		array of arguments to be used in creating the field
	 * 	@return 	void
	 */
	private function _create_settings_field( $args = array() ) {
	
		// default arguments
		$default_args = array(
												// the HTML form element to use					
												'type'    => 'text', 
												// the section this setting belongs to — must match the array key of a section in vlm_dscnt_settings_sections()							
												'section' => 'general', 			
												// ID of the setting in the options array, and the ID of the HTML form element
												'id'      => 'default_field', 	
												// the HTML form element class					
												'class'   => 'regular-text',
												// used to determine type of validation applied 
												'validation'	=> 'nohtml',
												// the label for the HTML form element				
												'title'   => 'Default Field', 			
												// the description displayed under the HTML form element		
												'desc'    => 'This is a default description.', 	
												// the default value for this setting
												'std'     => '', 			
												// (optional): the values in radio buttons or a drop-down menu		
												'choices' => array()																			
											);
		
		// extract arguments for use below
		extract( wp_parse_args( $args, $default_args ) );
		
		// additional arguments for use in form fields
		$field_args = array(
											'type'      		=> $type,
											'id'        		=> $id,
											'desc'      	=> $desc,
											'std'       		=> $std,
											'choices' 	 	=> $choices,
											'label_for' 	=> $id,
											'class'     		=> $class
										);
	
		add_settings_field( $id, $title, array( &$this, '_create_form_field'), VLM_DSCNT_PG_SLUG, $section, $field_args );
	
	}	
	
	
	
	
	
	/**
	 * 	generate HTML form fields 
	 * 
	 * 	@param 	array	$args 		array of arguments to be used in creating the field
	 * 	@return void
	 */
	function _create_form_field($args = array()) {
		
		extract( $args );
		
		$option_name = $this->_settings_options['vlm_dscnt_option_name'];
		$options = get_option($option_name);
		
		// pass the standard value if the option is not yet set in the database
		if ( !isset( $options[$id] ) && $type != 'checkbox' ) {
			$options[$id] = $std;
		}

		// additional field class. output only if the class is defined in the create_setting arguments
		$field_class = ($class != '') ? ' ' . $class : '';
		
		// switch html display based on the setting type.	
		switch ( $type ) {
		
		
			case 'text':
			
				$options[$id] = stripslashes($options[$id]);
				$options[$id] = esc_attr( $options[$id]);
				echo "<input class='$field_class' type='text' id='$id' name='" . $option_name . "[$id]' value='$options[$id]' />";
				echo ($desc != '') ? "&nbsp;&nbsp;&nbsp;&nbsp;<span class='description'>$desc</span>" : "";
				
			break;
			
			
			case "multi-text":
			
				foreach($choices as $item) {
					$item = explode("|",$item); // cat_name|cat_slug
					$item[0] = esc_html( $item[0] );
					if (!empty($options[$id])) {
						foreach ($options[$id] as $option_key => $option_val){
							if ($item[1] == $option_key) {
								$value = $option_val;
							}
						}
					} else {
						$value = '';
					}
					echo "<span>$item[0]:</span> <input class='$field_class' type='text' id='$id|$item[1]' name='" . $option_name . "[$id|$item[1]]' value='$value' />";
				}
				echo ($desc != '') ? "&nbsp;&nbsp;&nbsp;&nbsp;<span class='description'>$desc</span>" : "";
				
			break;
			
			
			case 'textarea':
			
				echo ($desc != '') ? "<span class='description'>$desc</span><br />" : ""; 						
				$options[$id] = stripslashes($options[$id]);
				$options[$id] = esc_html( $options[$id]);
				echo "<textarea class='textarea$field_class' type='text' id='$id' name='" . $option_name . "[$id]' rows='5' cols='30'>$options[$id]</textarea>";
		
			break;
			
	
			case 'select':
		
				echo "<select id='$id' class='select$field_class' name='" . $option_name . "[$id]'>";
					foreach($choices as $item) {
						$value 	= esc_attr( $item );
						$item 	= esc_html( $item );
						
						$selected = ($options[$id]==$value) ? 'selected="selected"' : '';
						echo "<option value='$value' $selected>$item&nbsp;&nbsp;</option>";
					}
				echo "</select>";
				echo ($desc != '') ? "&nbsp;&nbsp;&nbsp;&nbsp;<span class='description'>$desc</span>" : ""; 

			break;

			
			case 'select2':
		
				echo "<select id='$id' class='select$field_class' name='" . $option_name . "[$id]'>";
				foreach($choices as $item) {
					
					$item = explode("|",$item);
					$item[0] = esc_html( $item[0] );
					
					$selected = ($options[$id]==$item[1]) ? 'selected="selected"' : '';
					echo "<option value='$item[1]' $selected>$item[0]&nbsp;&nbsp;</option>";
				}
				echo "</select>";
				echo ($desc != '') ? "&nbsp;&nbsp;&nbsp;&nbsp;<span class='description'>$desc</span>" : "";

			break;
	
			
			case 'checkbox':
				$field_class = str_replace( 'regular-text', '', $field_class );
				echo "<label title='$id'><input class='$field_class' type='checkbox' id='$id' name='$option_name"."[$id]' value='1' " . checked( $options[$id], 1, false ) . " />&nbsp;$item[0]</label>";
				echo ($desc != '') ? "&nbsp;&nbsp;&nbsp;&nbsp;<span class='description'>$desc</span>" : "";
	
			break;
	
			
			case "multi-checkbox":
	
				echo "\n<fieldset>\n";
				
				echo ($desc != '') ? "<p class='description'>$desc</p>" : "";
				$mc_count = 1;
				foreach($choices as $item) {
					
					$item = explode("|",$item);
					$item[0] = esc_html( $item[0] );
					
					$checked = '';
					
				    if ( isset($options[$id][$item[1]]) ) {
						if ( $options[$id][$item[1]] == 'true') {
				   			$checked = 'checked="checked"';
						}
					}
					
					$field_class = str_replace( 'regular-text', '', $field_class );
					echo "<label title='$id|$item[1]' style='display:inline-block;width:20%;'><input class='$field_class' type='checkbox' id='$id|$item[1]' name='$option_name"."[$id|$item[1]]' value='1' $checked />&nbsp;$item[0]</label>";
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
					echo '<br />';

				}

				echo "\n</fieldset>\n";
			break;
	
			
			case "radio-button":

				echo "\n<fieldset>\n";
				foreach($choices as $item) {
					
					$item = explode("|",$item);
					$item[0] = esc_html( $item[0] );
					
					$checked = '';
					
				    if ( isset($options[$id][$item[1]]) ) {
						if ( $item[1] == $std or $options[$id][$item[1]] == 'true' ) {
				   			$checked = 'checked="checked"';
						}
					}
										
					$field_class = str_replace( 'regular-text', '', $field_class );
					echo "<label title='$item[1]'><input class='$field_class' type='radio' id='$item[1]' name='$id' value='1' $checked />&nbsp;$item[0]</label>";
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				}
				echo ($desc != '') ? "<span class='description'>$desc</span>" : "";
				echo "\n</fieldset>\n";
	
			break;
	
		}
	}





	/**
	*		Validate user input 
	*		@access public
	*		@return void
	*/	
	function input_validation( $input ) {

		// for enhanced security, create a new empty array
		// then collect only the values we expect and fill the new $valid_input array 
		// i.e. whitelist our option IDs
		$valid_input = array();
		
		$options = $this->_settings_options['vlm_dscnt_settings_fields'];
		
		//echo '<h3>$input</h3><pre>' . print_r( $input ) . '</pre><br />';
	
		// cycle thru each option type
		foreach ($options as $option) {
		
			switch ( $option['type'] ) {
				case 'text':
					//switch validation based on the class!
					switch ( $option['validation'] ) {
						//for numeric
						case 'numeric':								
							// trim whitespace
							$input[$option['id']] = trim($input[$option['id']]); 
							//accept the input only when numeric!
							$valid_input[$option['id']] = (is_numeric($input[$option['id']])) ? $input[$option['id']] : $option['std'];

							// register error
							if(is_numeric($input[$option['id']]) == FALSE) {
								add_settings_error(
									$option['id'], // setting title
									VLM_DSCNT . '_txt_numeric_error', // error ID
									__('An error occurred! Invalid data type for <b>'. $option['title'] . '</b>. Expecting a Numeric value!', 'event_espresso'), // error message
									'error' // type of message
								);
							}
						break;

						//for multi-numeric values (separated by a comma)
						case 'multinumeric':
							//accept the input only when the numeric values are comma separated
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace

							if($input[$option['id']] !=''){
								// /^-?\d+(?:,\s?-?\d+)*$/ matches: -1 | 1 | -12,-23 | 12,23 | -123, -234 | 123, 234  | etc.
								$valid_input[$option['id']] = (preg_match('/^-?\d+(?:,\s?-?\d+)*$/', $input[$option['id']]) == 1) ? $input[$option['id']] : $option['std'];
																
							}else{
								$valid_input[$option['id']] = $input[$option['id']];
							}

							// register error
							if($input[$option['id']] !='' && preg_match('/^-?\d+(?:,\s?-?\d+)*$/', $input[$option['id']]) != 1) {
								add_settings_error(
									$option['id'], // setting title
									VLM_DSCNT . '_txt_multinumeric_error', // error ID
									__('An error occurred! Invalid data type for <b>'. $option['title'] . '</b>.Expecting comma separated numeric values!', 'event_espresso'), // error message
									'error' // type of message
								);
							}
						break;

						//for no html
						case 'nohtml':
							//accept the input only after stripping out all html, extra white space etc!
							$input[$option['id']] 		= sanitize_text_field($input[$option['id']]); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;

						//for url
						case 'url':
							//accept the input only when the url has been sanited for database usage with esc_url_raw()
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$valid_input[$option['id']] = esc_url_raw($input[$option['id']]);
						break;

						//for email
						case 'email':
							// trim whitespace
							$input[$option['id']] 		= trim($input[$option['id']]); 
							//accept the input only after the email has been validated
							if($input[$option['id']] != ''){
								$valid_input[$option['id']] = (is_email($input[$option['id']])!== FALSE) ? $input[$option['id']] : $option['std'];
							}elseif($input[$option['id']] == ''){
								$valid_input[$option['id']] = $option['std'];
							}

							// register error
							if(is_email($input[$option['id']])== FALSE || $input[$option['id']] == '') {
								add_settings_error(
									$option['id'], // setting title
									VLM_DSCNT . '_txt_email_error', // error ID
									__('An error occurred! Invalid data type for <b>'. $option['title'] . '</b>.Please enter a valid email address.', 'event_espresso'), // error message
									'error' // type of message
								);
							}
						break;

						// a "cover-all" fall-back when the class argument is not set
						default:
							// accept only a few inline html elements
							$allowed_html = array(
								'a' => array('href' => array (),'title' => array ()),
								'b' => array(),
								'em' => array (),
								'i' => array (),
								'strong' => array()
							);

							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$input[$option['id']] 		= force_balance_tags($input[$option['id']]); // find incorrectly nested or missing closing tags and fix markup
							$input[$option['id']] 		= wp_kses( $input[$option['id']], $allowed_html); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;
					}
				break;

				case "multi-text":
					// this will hold the text values as an array of 'key' => 'value'
					unset($textarray);

					$text_values = array();
					foreach ($option['choices'] as $k => $v ) {
						// explode the connective
						$pieces = explode("|", $v);

						$text_values[] = $pieces[1];
					}

					foreach ($text_values as $v ) {		

						// Check that the option isn't empty
						if (!empty($input[$option['id'] . '|' . $v])) {
							// If it's not null, make sure it's sanitized, add it to an array
							switch ($option['validation']) {
								// different sanitation actions based on the class create you own cases as you need them

								//for numeric input
								case 'numeric':
									//accept the input only if is numeric!
									$input[$option['id'] . '|' . $v]= trim($input[$option['id'] . '|' . $v]); // trim whitespace
									$input[$option['id'] . '|' . $v]= (is_numeric($input[$option['id'] . '|' . $v])) ? $input[$option['id'] . '|' . $v] : '';
								break;

								// a "cover-all" fall-back when the class argument is not set
								default:
									// strip all html tags and white-space.
									$input[$option['id'] . '|' . $v]= sanitize_text_field($input[$option['id'] . '|' . $v]); // need to add slashes still before sending to the database
									$input[$option['id'] . '|' . $v]= addslashes($input[$option['id'] . '|' . $v]);
								break;
							}
							// pass the sanitized user input to our $textarray array
							$textarray[$v] = $input[$option['id'] . '|' . $v];

						} else {
							$textarray[$v] = '';
						}
					}
					// pass the non-empty $textarray to our $valid_input array
					if (!empty($textarray)) {
						$valid_input[$option['id']] = $textarray;
					}
				break;

				case 'textarea':
					//switch validation based on the class!
					switch ( $option['validation'] ) {
						//for only inline html
						case 'inlinehtml':
							// accept only inline html
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$input[$option['id']] 		= force_balance_tags($input[$option['id']]); // find incorrectly nested or missing closing tags and fix markup
							$input[$option['id']] 		= addslashes($input[$option['id']]); //wp_filter_kses expects content to be escaped!
							$valid_input[$option['id']] = wp_filter_kses($input[$option['id']]); //calls stripslashes then addslashes
						break;

						//for no html
						case 'nohtml':
							//accept the input only after stripping out all html, extra white space etc!
							$input[$option['id']] 		= sanitize_text_field($input[$option['id']]); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;

						//for allowlinebreaks
						case 'allowlinebreaks':
							//accept the input only after stripping out all html, extra white space etc!
							$input[$option['id']] 		= wp_strip_all_tags($input[$option['id']]); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;

						// a "cover-all" fall-back when the class argument is not set
						default:
							// accept only limited html
							//my allowed html
							$allowed_html = array(
								'a' 			=> array('href' => array (),'title' => array ()),
								'b' 			=> array(),
								'blockquote' 	=> array('cite' => array ()),
								'br' 			=> array(),
								'dd' 			=> array(),
								'dl' 			=> array(),
								'dt' 			=> array(),
								'em' 			=> array (),
								'i' 			=> array (),
								'li' 			=> array(),
								'ol' 			=> array(),
								'p' 			=> array(),
								'q' 			=> array('cite' => array ()),
								'strong' 		=> array(),
								'ul' 			=> array(),
								'h1' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h2' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h3' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h4' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h5' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h6' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ())
							);

							// trim whitespace
							$input[$option['id']] 		= trim($input[$option['id']]); 
							// find incorrectly nested or missing closing tags and fix markup
							$input[$option['id']] 		= force_balance_tags($input[$option['id']]); 
							// need to add slashes still before sending to the database
							$input[$option['id']] 		= wp_kses( $input[$option['id']], $allowed_html); 
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;
					}
				break;

				case 'select':
					// check to see if the selected value is in our approved array of values!
					$valid_input[$option['id']] = (in_array( $input[$option['id']], $option['choices']) ? $input[$option['id']] : '' );
				break;

				case 'select2':
					// process $select_values
						$select_values = array();
						foreach ($option['choices'] as $k => $v) {
							// explode the connective
							$pieces = explode("|", $v);

							$select_values[] = $pieces[1];
						}
					// check to see if selected value is in our approved array of values!
					$valid_input[$option['id']] = (in_array( $input[$option['id']], $select_values) ? $input[$option['id']] : '' );
				break;

				case 'checkbox':
					// if it's not set, default to null!
					if (!isset($input[$option['id']])) {
						$input[$option['id']] = NULL;
					}
					// Our checkbox value is either 0 or 1
					$valid_input[$option['id']] = ( $input[$option['id']] == 1 ? 1 : 0 );
				break;


				case 'multi-checkbox':
					unset($checkboxarray);
					$check_values = array();
					foreach ($option['choices'] as $k => $v ) {
						// explode the connective
						$pieces = explode("|", $v);

						$check_values[] = $pieces[1];
					}

					foreach ($check_values as $v ) {		

						// Check that the option isn't null
						if (!empty($input[$option['id'] . '|' . $v])) {
							// If it's not null, make sure it's true, add it to an array
							$checkboxarray[$v] = 'true';
						}
						else {
							$checkboxarray[$v] = 'false';
						}
					}
					// Take all the items that were checked, and set them as the main option
					if (!empty($checkboxarray)) {
						$valid_input[$option['id']] = $checkboxarray;
					}
				break;
				
				case 'radio-button':
				
//				echo '<pre>' . print_r( $input ) . '</pre><br />';
//				echo '<pre>' . print_r( $option ) . '</pre><br />';
				
				unset($checkboxarray);
					$check_values = array();
					foreach ($option['choices'] as $k => $v ) {
						// explode the connective
						$pieces = explode("|", $v);

						$check_values[] = $pieces[1];
					}

					foreach ($check_values as $v ) {		

						// Check that the option isn't null
						if (!empty($input[$option['id'] . '|' . $v])) {
							// If it's not null, make sure it's true, add it to an array
							$checkboxarray[$v] = 'true';
						}
						else {
							$checkboxarray[$v] = 'false';
						}
					}
					// Take all the items that were checked, and set them as the main option
					if (!empty($checkboxarray)) {
						$valid_input[$option['id']] = $checkboxarray;
					}
				break;

			}
		}
	
		// return validated input	
		return $valid_input; 
	
	}
	
	
	
	
	
	/**
	*		generate base HTML for admin subpage within Event Espresso admin area
	*		@access public
	*		@return void
	*/		
	public function admin_settings_page() {

		// add contextual help
		if ( $this->_vlm_dscnt_settings_page ) {
			get_current_screen()->add_help_tab( $this->_vlm_dscnt_settings_page, $this->_settings_options['vlm_dscnt_contextual_help'] );
		}
		$template_args = array();
		$path_to_file = VLM_DSCNT_PATH . 'volume_discounts_admin_settings.template.php';
		$this->display_template( $path_to_file, $template_args );

	}




	
	/**
	 * 	Helper function for creating admin messages
	 *
	 * 	@param 	string 	$message 	the message to echo
	 * 	@param 	string 	$msgclass The message class
	 * 	@return 	echoes the message
	 */
	function show_msg($message, $msgclass = 'info') {
		echo "<div id='message' class='$msgclass'>$message</div>";
	}





	/**
	 * 	displays admin messages
	 *
	 * 	@return 	void
	 */
	function admin_msgs() {
		
		if ( isset( $_GET['page'] )) {
			// check for our settings page - need this in conditional further down
			$settings_pg = strpos($_GET['page'], VLM_DSCNT_PG_SLUG);
			// collect setting errors/notices: //http://codex.wordpress.org/Function_Reference/get_settings_errors
			$set_errors = get_settings_errors(); 
			
			//display admin message only for the admin to see, only on our settings page and only when setting errors/notices are returned!	
			if ( current_user_can ('manage_options') && $settings_pg !== FALSE && ! empty($set_errors) ) {
		
				// have our settings succesfully been updated? 
				if ( $set_errors[0]['code'] == 'settings_updated' && isset($_GET['settings-updated']) ) {
					$this->show_msg("<p>" . $set_errors[0]['message'] . "</p>", 'updated');
				
				// have errors been found?
				} else {
					// there maybe more than one so run a foreach loop.
					foreach( $set_errors as $set_error ) {
						// set the title attribute to match the error "setting title" - need this in js file
						$this->show_msg("<p class='setting-error-message' title='" . $set_error['setting'] . "'>" . $set_error['message'] . "</p>", 'error');
					}
				}
			}			
		}
	}





	/**
	 * 	change the default event meta that is stored for each event.
	 *
	 * 	@return 	void
	 */
	function filter_default_event_meta ( $default_event_meta ) {

		$default_event_meta['credits'] = '';
		
		return $default_event_meta;
		
	}
				
		
	
	// **********************************************************************************************************************
	// ***********************************************      FRONTEND  METHODS      *********************************************	
	// **********************************************************************************************************************




	
	/**
	*		admin functions
	*		@Constructor
	*		@access public
	*		@return void
	*/	
	public function frontend() {	
		
		$this->_settings_options = get_option('espresso_volume_discounts');
		
		if ( isset( $this->_settings_options['vlm-dscnt-categories-slct'] ) && ! empty( $this->_settings_options['vlm-dscnt-categories-slct'] )) {
			foreach ( $this->_settings_options['vlm-dscnt-categories-slct'] as $cat_id => $discount_cat ) {			
				if ( $discount_cat == 'true' ) {
					$this->_vlm_dscnt_cats[] = $cat_id;
				}
			}			
		}
		//printr( $this->_vlm_dscnt_cats, '$this->_vlm_dscnt_cats  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
		// start hooking
		add_action( 'init', array( &$this, '_load_frontend_resources' ) );		
//		add_filter( 'filter_hook_espresso_shopping_cart_SQL_select', array( &$this, 'filter_shopping_cart_SQL_select' ), 1, 1 );
		add_filter( 'filter_hook_espresso_shopping_cart_event', array( &$this, 'filter_shopping_cart_event' ), 1, 1 );
		add_action( 'action_hook_espresso_add_to_multi_reg_cart_block', array( &$this, 'hook_add_to_multi_reg_cart_block' )); 
		add_action( 'action_hook_espresso_shopping_cart_before_total', array( &$this, 'hook_shopping_cart_before_total' ));
		add_action( 'action_hook_espresso_shopping_cart_after_total', array( &$this, 'hook_shopping_cart_after_total' ));
		add_action( 'action_hook_espresso_zero_vlm_dscnt_in_session', array( &$this, 'zero_vlm_dscnt_in_session' ));
		add_filter( 'filter_hook_espresso_cart_grand_total', array( &$this, 'filter_cart_grand_total' ), 100, 1 );
		add_filter( 'filter_hook_espresso_attendee_cost', array( &$this, 'filter_attendee_cost' ), 10, 1 );

	}
	
	
	
	
	
	/** 
	* 		load css and js resources for the frontend 
	*		@access public
	* 		@return void 
	*/  
    public function _load_frontend_resources() {  

		// add a bit o' style
		//wp_enqueue_style('espresso_volume_discount', VLM_DSCNT_ASSETS_PATH . 'espresso_volume_discounts.css'); 
		// and make it dance
		wp_enqueue_script( 'espresso_volume_discount', VLM_DSCNT_ASSETS_PATH . 'espresso_volume_discounts.js', array('jquery'), '1.0', TRUE );		
		
		$params = array();
		$params['msg'] = $this->_settings_options['vlm-dscnt-message-txt'];
		
		global $org_options;
		$params['cur_sign'] = $org_options['currency_symbol'];
	
	    // Get current page protocol
	    $protocol = isset( $_SERVER["HTTPS"]) ? 'https://' : 'http://';
		 // Output admin-ajax.php URL with same protocol as current page
		$params['ajaxurl'] = admin_url( 'admin-ajax.php', $protocol );
		
	    wp_localize_script( 'espresso_volume_discount', 'evd', $params );		
			
	}





	/**
	*		edit the SELECT and FROM portion of the SQL statement used to poulate the Shopping Cart
	*		
	*		@access 	public
	*		@param 	string	$sql
	*		@return 		string
	*/	
	function filter_shopping_cart_SQL_select ( $sql ) {
	
		global $wpdb;

		if ( defined( EVENTS_DETAIL_TABLE )) {
			$evnt_tbl = EVENTS_DETAIL_TABLE;
		} else {
			$evnt_tbl = $wpdb->prefix . 'events_detail';
		}	
		
		if ( defined( EVENTS_CATEGORY_TABLE )) {
			$cat_tbl = EVENTS_CATEGORY_TABLE;
		} else {
			$cat_tbl = $wpdb->prefix . 'events_category_detail';
		}
		
		if ( defined( EVENTS_CATEGORY_REL_TABLE )) {
			$evnt_cat_tbl = EVENTS_CATEGORY_REL_TABLE;
		} else {
			$evnt_cat_tbl = $wpdb->prefix . 'events_category_rel';
		}
	
        $sql = 'SELECT e.*, r.cat_id, c.category_name';		
        $sql .= ' FROM ' . $evnt_tbl . ' e';
        $sql .= ' JOIN ' . $evnt_cat_tbl . ' r ON r.event_id = e.id ';
        $sql .= ' JOIN ' . $cat_tbl . ' c ON  c.id = r.cat_id ';		
	
		return $sql;
		
	}





	/**
	*		edit an event in the Shopping Cart
	*		
	*		@access 	public
	*		@param 	object	$event
	*		@return 		string
	*/	
	function filter_shopping_cart_event ( $event ) {
	
		global $wpdb;

		if ( defined( EVENTS_CATEGORY_TABLE )) {
			$cat_tbl = EVENTS_CATEGORY_TABLE;
		} else {
			$cat_tbl = $wpdb->prefix . 'events_category_detail';
		}

		if ( defined( EVENTS_CATEGORY_REL_TABLE )) {
			$evnt_cat_tbl = EVENTS_CATEGORY_REL_TABLE;
		} else {
			$evnt_cat_tbl = $wpdb->prefix . 'events_category_rel';
		}

		$sql = 'SELECT r.cat_id, c.category_name';		
		$sql .= ' FROM ' . $evnt_cat_tbl . ' r ';
		$sql .= ' JOIN ' . $cat_tbl . ' c ON  c.id = r.cat_id ';		
		$sql .= ' WHERE  r.event_id = %d';
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $event->id ));
		
		$categories = array();
		foreach( $results as $result ) {
			$categories[ $result->cat_id ] = $result->category_name;
		}		
		$event->categories = $categories;
	
		return $event;
		
	}




	
	/**
	*		add content to the bottom of each multi_reg_cart_block in the shopping cart template
	*		
	*		@param 	array 	$event
	*		@access public
	*		@return 	echo string
	*/	
	public function hook_add_to_multi_reg_cart_block( $event ) {	
	
//		printr( $event, '$event  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//		printr( $this->_settings_options, '$this->_settings_options  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//		printr( $this->_vlm_dscnt_cats, '$this->_vlm_dscnt_cats  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		if ( isset( $event->categories )) {
			foreach ( $event->categories as $cat_id => $cat_name  ) {
				if (  isset( $this->_settings_options['vlm-dscnt-categories-slct']['A'] ) && $this->_settings_options['vlm-dscnt-categories-slct']['A'] == 'true' ) {
					$this->_vlm_dscnt_cats[] = $cat_id;
				}		
				echo '
			<input class="vlm_dscnt_cat" type="hidden" name="vlm_dscnt_cat['.$event->id.'][]" value="' . $cat_id . '" />';
			}
		}

		
//		echo '<h4>$cat_id : ' . $cat_id . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		printr( $this->_vlm_dscnt_cats, '$this->_vlm_dscnt_cats  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
//		echo '
//			<input class="vlm_dscnt_cat" type="hidden" name="vlm_dscnt_cat['.$event->id.']" value="' . implode( ',', array_keys( $event->categories )) . '" />';
		
		
		switch ( $this->_settings_options['vlm-dscnt-factor-slct'] ) {
			
			case 'fctr_registrations' :
				$this->_vlm_dscnt_cntr++;
				echo '
			<input class="vlm_dscnt_cntr" type="hidden" name="vlm_dscnt_cntr['.$event->id.']" value="1" />';
			break;
			
			case 'fctr_dollar_value' :
				$this->_vlm_dscnt_cntr = 'T';
				echo '
			<input class="vlm_dscnt_cntr" type="hidden" name="vlm_dscnt_cntr['.$event->id.']" value="T" />';
			break;
			
			case 'fctr_meta_field' :

				$event_meta = unserialize( $event->event_meta );	
				
				if ( isset( $event_meta[ $this->_settings_options['vlm-dscnt-meta-field-txt'] ] )) {
					$vlm_dscnt_cntr = $event_meta[ $this->_settings_options['vlm-dscnt-meta-field-txt'] ];
				} else {
					$vlm_dscnt_cntr = $this->_settings_options['vlm-dscnt-default-meta-field-value-txt'];
				}
				
				$this->_vlm_dscnt_cntr += $vlm_dscnt_cntr;
				
				echo '
			<input class="vlm_dscnt_cntr" type="hidden" name="vlm_dscnt_cntr['.$event->id.']" value="'.$vlm_dscnt_cntr.'" />	';				
			
			break;
			
		}
	
	}




	
	/**
	*		add content to the bottom of the shopping cart template after the total
	*		
	*		@access public
	*		@return void
	*/	
	public function hook_shopping_cart_before_total() {	
	
		// grab categories and remove duplicates 
		$this->_vlm_dscnt_cats = array_unique( $this->_vlm_dscnt_cats );
		// convert to csv
		$vlm_dscnt_cats = implode( ',', $this->_vlm_dscnt_cats );
		
		
		$discount = '
			<input id="vlm_dscnt_factor" type="hidden" name="vlm_dscnt_factor" value="'.$this->_settings_options['vlm-dscnt-factor-slct'].'" />
			<input id="vlm_dscnt_threshold" type="hidden" name="vlm_dscnt_threshold" value="'.$this->_settings_options['vlm-dscnt-threshold-txt'].'" />
			<input id="vlm_dscnt_amount" type="hidden" name="vlm_dscnt_amount" value="'.$this->_settings_options['vlm-dscnt-amount-txt'].'" /> 
			<input id="vlm_dscnt_type" type="hidden" name="vlm_dscnt_type" value="'.$this->_settings_options['vlm-dscnt-type-slct'].'" />
			<input id="vlm_dscnt_categories" type="hidden" name="vlm_dscnt_categories" value="'.$vlm_dscnt_cats.'" />
			';
			
		if ( $this->_vlm_dscnt_cntr != 'T' ) {
			
			if ( $this->_vlm_dscnt_cntr >= $this->_settings_options['vlm-dscnt-threshold-txt'] ) {
				$discount .= '
			<input id="process_vlm_dscnt" type="hidden" name="process_vlm_dscnt" value="Y" />
			<input id="vlm_dscnt_cntr_total" type="hidden" name="vlm_dscnt_cntr_total" value="'.$this->_vlm_dscnt_cntr.'" />';
			} else {
				$discount .= '
			<input id="process_vlm_dscnt" type="hidden" name="process_vlm_dscnt" value="N" />
			<input id="vlm_dscnt_cntr_total" type="hidden" name="vlm_dscnt_cntr_total" value="'.$this->_vlm_dscnt_cntr.'" />';
			}
			
		} else {
				$discount .= '
			<input id="process_vlm_dscnt" type="hidden" name="process_vlm_dscnt" value="T" />
			<input id="vlm_dscnt_cntr_total" type="hidden" name="vlm_dscnt_cntr_total" value="'.$this->_vlm_dscnt_cntr.'" />';
		}
		
		echo '
		<div id="shopping_cart_before_total" style="clear:both;">'.$discount.'</div>';

	}




	
	/**
	*		add content to the bottom of the shopping cart template after the total
	*		
	*		@access public
	*		@return void
	*/	
	public function hook_shopping_cart_after_total() {	
	
		echo '
		<div id="shopping_cart_after_total" style="clear:both;"></div>
		<div style="clear:both;"></div>
		';
	
	}




	
	/**
	*		hook to change cart grand total
	*		
	*		@param 	float 	$event_total_cost
	*		@access 	public
	*		@return 	float
	*/
	public function filter_cart_grand_total( $event_total_cost ) {	
	
		$event_total_cost = $_SESSION['espresso_session']['pre_discount_total'] - $_SESSION['espresso_session']['volume_discount'];
		$_SESSION['espresso_session']['grand_total'] = $event_total_cost;
		
		return $event_total_cost;
	
	}




	
	/**
	*		hook to change attendee  cost
	*		
	*		@param 	float 	$attendee_cost
	*		@access 	public
	*		@return 	float
	*/
	public function filter_attendee_cost( $attendee_cost ) {	
	
		if (  isset( $_SESSION['espresso_session']['grand_total'] ) &&  $_SESSION['espresso_session']['grand_total'] > 0 && 	isset( $_SESSION['espresso_session']['pre_discount_total'] ) && $_SESSION['espresso_session']['pre_discount_total'] > 0 && isset($_SESSION['espresso_session']['volume_discount']) && $_SESSION['espresso_session']['volume_discount'] > 0 ) {
		
			if ( $discount_ratio = $_SESSION['espresso_session']['grand_total'] / $_SESSION['espresso_session']['pre_discount_total'] ) {
				$attendee_cost = $attendee_cost * $discount_ratio;
				$attendee_cost = number_format( $attendee_cost, 2, '.', '' );
			}			
			
		}

		return $attendee_cost;
	
	}




	
	/**
	*		store calculated volume discount in session
	*		
	*		@access 	public
	*		@return 	void
	*/	
	public function store_discount_in_session( ) {	
		
		if( isset ( $_REQUEST['vlm_dscnt'] )) {
			$_SESSION['espresso_session']['volume_discount'] = $_REQUEST['vlm_dscnt'];
			//die( $_SESSION['espresso_session']['volume_discount'] ); 
			//echo event_espresso_json_response(array('success' => 'The Volume Discount value in the session was updated successfully'));
			echo json_encode( array( 'success' => 'The Volume Discount value in the session was updated successfully', 'errors' => FALSE, 'vlm_dscnt' => $_REQUEST['vlm_dscnt'] ));
			die();			
		} else {
			die( '-1' );
		}
	
	}




	
	/**
	*		zero the vlm_dscnt variable in the session
	*		
	*		@access 	public
	*		@return 	void
	*/	
	public function zero_vlm_dscnt_in_session() {	
	
		// do I have to explain this?
		$_SESSION['espresso_session']['volume_discount'] = 0;
		$_SESSION['espresso_session']['grand_total'] = isset( $_SESSION['espresso_session']['pre_discount_total'] ) ? $_SESSION['espresso_session']['pre_discount_total'] : $_SESSION['espresso_session']['grand_total'];
	
	}



	
	/**
	*		set the grand total in the session
	*		
	*		@access 	public
	*		@return 	void
	*/	
	public function set_grand_total_in_session() {	
	
		if( isset ( $_REQUEST['grand_total'] )) {
			$_SESSION['espresso_session']['grand_total'] = number_format( $_REQUEST['grand_total'], 2, '.', '' );
			echo event_espresso_json_response(array('success' => 'The Grand Total value in the session was updated successfully' )); 
			die();			
		} else {
			die( '-1' );
		}
	
	}
		
		
	
	
	// --------------- SHARED FUNCTIONS ---------------------	




	
	/**
	 *		@load and display a template
	 *		@access 		private
	 *		@param 		string		$path_to_file		server path to the file to be loaded, including the file name and extension
	 *		@param 		array		$template_args	an array of arguments to be extracted for use in the template
	 *		@return 		void
	 */	
	private function display_template( $path_to_file = FALSE, $template_args ) {
	
		if ( ! $path_to_file ) {
			return FALSE;
		}
		
		extract($template_args);
		$view_template = file_get_contents( $path_to_file );
		
		// check if short tags is on cuz eval chokes if not presented the correct tag type
		$php_short_tags_on = (bool) ini_get('short_open_tag');
		
		if ( $php_short_tags_on ) {
			eval( "?> $view_template <? " );
		} else {
			// don't forget the space after php
			eval( "?> $view_template <?php " );
		}
		
	}	
	
	
	
	
	

}


function espresso_run_volume_discounts() {
	// instantiate !!!
	$EE_VLM_DSCNT = EE_VLM_DSCNT::instance();	
}
add_action( 'plugins_loaded', 'espresso_run_volume_discounts', 100 );

/* End of file espresso-volume-discounts.php */
/* Location: espresso-volume-discounts.php */