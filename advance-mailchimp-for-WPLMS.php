<?php
/**
 * Plugin Name: Advance-mailchimp-for-WPLMS
 * Plugin URI: http://expertwebtechnologies.com/
 * Description: Advance MailChimp provides simple MailChimp integration for WPLMS course and product you can choose the mailing list for each product and course.
 * Author: Shiv
 * Author URI: http://expertwebtechnologies.com/
 * Version: 1.0
 * Text Domain: ad_wc_mailchimp
 * Domain Path: languages
 * 
 * Copyright: © 2014 Shiv
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * MailChimp Docs: http://apidocs.mailchimp.com/
 */

add_action( 'plugins_loaded', 'woocommerce_mailchimp_init', 0 );

function woocommerce_mailchimp_init() {

	if ( ! class_exists( 'WC_Integration' ) )
		return;

	load_plugin_textdomain( 'ad_wc_mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	include_once( 'classes/class-ss-wc-integration-mailchimp.php' );

	/**
 	* Add the Integration to WooCommerce
 	**/
	function add_mailchimp_integration($methods) {
    	$methods[] = 'SS_WC_Integration_MailChimp';
		return $methods;
	}

	add_filter('woocommerce_integrations', 'add_mailchimp_integration' );
	
	function action_links( $links ) {

		global $woocommerce;

		$settings_url = admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=mailchimp' );

		if ( $woocommerce->version >= '2.1' ) {
			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=integration&section=mailchimp' );
		}

		$plugin_links = array(
			'<a href="' . $settings_url . '">' . __( 'Settings', 'ad_wc_mailchimp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}
	// Add the "Settings" links on the Plugins administration screen
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'action_links' );
}

add_action( 'add_meta_boxes', 'add_mailchip_metaboxes' );
// Add the Events Meta Boxes
function add_mailchip_metaboxes() {
	add_meta_box('wpt_mailchip_list', 'Mailchip List', 'wpt_mailchip_list', 'course', 'side', 'default');
	add_meta_box('wpt_mailchip_list', 'Mailchip List', 'wpt_mailchip_list', 'product', 'side', 'default');
}

// The Event Location Metabox
function wpt_mailchip_list() {
	global $post;
	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="mailchipmeta_noncename" id="mailchipmeta_noncename" value="' .
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	// Get the location data if its already been entered
	  $location = get_post_meta($post->ID, '_mailchip_list', true);
	  
	  $grouping = get_post_meta($post->ID, '_mailchip_grouping', true);
	   
	  $group = get_post_meta($post->ID, '_mailchip_group', true);
	// Echo out the field
	$milchip= new SS_WC_Integration_MailChimp();
	$lists= $milchip->get_lists();
	
 
	
	$selectbox .='<select style="" id="mailchip_list" name="_mailchip_list" class="select ">
													<option value="">Select a list...</option>';
	if(is_array($lists)){
	foreach ($lists as $key => $value)
	{
		if($key==$location)$selected='selected="selected" '; else $selected='';
		
		$selectbox .='<option '.$selected.' value="'.$key.'">'.$value.'</option>';
	}}
	$selectbox .='</select>';
 
	$selectbox .='<input type="text"  class="select  id="_mailchip_grouping" name="_mailchip_grouping" value="'.$grouping.'" />';
 
		$selectbox .='<input type="text"  class="select  id="_mailchip_group" name="_mailchip_group" value="'.$group.'" />';
 	
	echo $selectbox;
}

 
 
// Save the Metabox Data
function wpt_save_mailchip_meta($post_id, $post) {
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['mailchipmeta_noncename'], plugin_basename(__FILE__) )) {
	return $post->ID;
	}
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post->ID ))
		return $post->ID;
	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though.
  	$events_meta['_mailchip_list'] = $_POST['_mailchip_list'];
	$events_meta['_mailchip_grouping'] = $_POST['_mailchip_grouping'];
	$events_meta['_mailchip_group'] = $_POST['_mailchip_group'];

 
	// Add values of $events_meta as custom fields
	foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
		if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
			update_post_meta($post->ID, $key, $value);
		} else { // If the custom field doesn't have a value
			add_post_meta($post->ID, $key, $value);
		}
		if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
	}
}
add_action('save_post', 'wpt_save_mailchip_meta', 1, 2); // save the custom fields
