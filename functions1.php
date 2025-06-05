<?php

/**
 * @author Divi Space
 * @copyright 2022
 * @version 1.6
 */

if ( ! defined('ABSPATH') ) {
	die();
}

add_action('wp_enqueue_scripts', 'ds_ct_enqueue_parent');

function ds_ct_enqueue_parent() {
	wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

add_action('wp_enqueue_scripts', 'ds_ct_loadjs');

function ds_ct_loadjs() {
	wp_enqueue_script('ds-theme-script', get_stylesheet_directory_uri() . '/ds-script.js', array('jquery'));
}

include('login-editor.php');
include('agent-listing.php'); // Include the agent listing functionality
include('member-properties-display.php'); // Include the member properties display functionality

/**
 * Assign custom role based on 'profiles' field during REM user registration.
 */
add_action('user_register', 'rem_assign_custom_role_from_profile_field', 10, 1);
function rem_assign_custom_role_from_profile_field($user_id)
{
	if (isset($_POST['profiles'])) {
		$profile_type = sanitize_text_field($_POST['profiles']);

		// Save the profile type to user meta for later reference
		update_user_meta($user_id, 'user_type', $profile_type);

		// Assign appropriate role based on selection - case insensitive comparison
		$user = new WP_User($user_id);

		$profile_type_upper = strtoupper(trim($profile_type));
		if ($profile_type_upper === 'HOST') {
			$user->set_role('host');
		} elseif ($profile_type_upper === 'MEMBER') {
			$user->set_role('member');
		}
	}
}

/**
 * (Optional) Create 'host' and 'member' roles if they don't exist yet.
 * Only runs once unless removed or updated.
 */
add_action('init', 'rem_add_custom_roles');
function rem_add_custom_roles()
{
	if (!get_role('host')) {
		add_role('host', 'Host', ['read' => true]);
	}

	if (!get_role('member')) {
		add_role('member', 'Member', ['read' => true]);
	}
}

/**
 * Register custom single property template shortcodes
 */
add_action('init', 'ds_register_property_shortcodes');
function ds_register_property_shortcodes()
{
	// Limited property details shortcode
	add_shortcode('ds_property_limited', 'ds_property_limited_shortcode');

	// Full property details shortcode
	add_shortcode('ds_property_full', 'ds_property_full_shortcode');
}

/**
 * Limited property details shortcode callback
 */
function ds_property_limited_shortcode($attrs, $content = '')
{
	$property_id = (isset($attrs['id'])) ? $attrs['id'] : '';
	$sidebar = (isset($attrs['sidebar'])) ? $attrs['sidebar'] : 'disable';

	if (!$property_id) {
		global $wp_query;
		if (isset($wp_query->post->ID)) {
			$property_id = $wp_query->post->ID;
		}
	}

	if ($property_id && get_post_status($property_id)) {
		// Load necessary styles and scripts from REM
		if (function_exists('rem_load_bs_and_fa')) {
			rem_load_bs_and_fa();
		}

		if (function_exists('rem_load_basic_styles')) {
			rem_load_basic_styles();
		}

		ob_start();
		include get_stylesheet_directory() . '/rem/shortcodes/single-property-limited.php';
		return ob_get_clean();
	}

	return apply_filters('the_content', $content);
}

/**
 * Full property details shortcode callback
 */
function ds_property_full_shortcode($attrs, $content = '')
{
	$property_id = (isset($attrs['id'])) ? $attrs['id'] : '';
	$sidebar = (isset($attrs['sidebar'])) ? $attrs['sidebar'] : 'disable';

	if (!$property_id) {
		global $wp_query;
		if (isset($wp_query->post->ID)) {
			$property_id = $wp_query->post->ID;
		}
	}

	if ($property_id && get_post_status($property_id)) {
		// Load necessary styles and scripts from REM
		if (function_exists('rem_load_bs_and_fa')) {
			rem_load_bs_and_fa();
		}

		if (function_exists('rem_load_basic_styles')) {
			rem_load_basic_styles();
		}

		ob_start();
		include get_stylesheet_directory() . '/rem/shortcodes/single-property-full.php';
		return ob_get_clean();
	}

	return apply_filters('the_content', $content);
}

/**
 * Register custom search results shortcodes
 */
add_shortcode('ds_search_results_limited', 'ds_search_results_limited_shortcode');
add_shortcode('ds_search_results_full', 'ds_search_results_full_shortcode');

/**
 * Limited search results shortcode callback
 */
function ds_search_results_limited_shortcode($attrs, $content = '')
{
	if (function_exists('rem_load_bs_and_fa')) {
		rem_load_bs_and_fa();
	}

	if (function_exists('rem_load_basic_styles')) {
		rem_load_basic_styles();
	}

	// Use the same query that's set by the search form
	global $wp_query, $paged;
	$class = isset($attrs['class']) ? $attrs['class'] : 'col-sm-4';

	ob_start();
	include get_stylesheet_directory() . '/rem/shortcodes/search-results-limited.php';
	return ob_get_clean();
}

/**
 * Full search results shortcode callback
 */
function ds_search_results_full_shortcode($attrs, $content = '')
{
	if (function_exists('rem_load_bs_and_fa')) {
		rem_load_bs_and_fa();
	}

	if (function_exists('rem_load_basic_styles')) {
		rem_load_basic_styles();
	}

	// Use the same query that's set by the search form
	global $wp_query, $paged;
	$class = isset($attrs['class']) ? $attrs['class'] : 'col-sm-4';

	ob_start();
	include get_stylesheet_directory() . '/rem/shortcodes/search-results-full.php';
	return ob_get_clean();
}

/**
 * Override REM template paths to use theme templates
 */
add_filter('template_include', 'ds_rem_template_override', 99);
function ds_rem_template_override($template)
{
	global $post;

	if (is_post_type_archive('rem_property') || is_tax('rem_property_category') || is_tax('rem_property_tag') || is_tax('rem_property_status')) {
		// For archive pages, use archive-rem_property.php from child theme if it exists
		if (file_exists(get_stylesheet_directory() . '/archive-rem_property.php')) {
			return get_stylesheet_directory() . '/archive-rem_property.php';
		}
	}

	return $template;
}

/**
 * Add body class to REM pages for targeted styling
 */
add_filter('body_class', 'ds_rem_body_classes');
function ds_rem_body_classes($classes)
{
	if (is_singular('rem_property')) {
		$classes[] = 'divi-rem-property';

		// Add class based on user login status
		if (is_user_logged_in()) {
			$classes[] = 'rem-logged-in';
		} else {
			$classes[] = 'rem-logged-out';
		}
	}

	return $classes;
}

/**
 * Add custom shortcodes for different registration types
 */
function rem_register_host_shortcode($attrs, $content = '')
{
	global $rem_sc_ob; // REM Shortcode Object

	if (!is_user_logged_in()) {
		ob_start();
		$in_theme = get_stylesheet_directory() . '/rem/shortcodes/register-host.php';
		if (file_exists($in_theme)) {
			include $in_theme;
		}
		return ob_get_clean();
	} else {
		return apply_filters('the_content', $content);
	}
}
add_shortcode('rem_register_host', 'rem_register_host_shortcode');

function rem_register_member_shortcode($attrs, $content = '')
{
	global $rem_sc_ob; // REM Shortcode Object

	if (!is_user_logged_in()) {
		ob_start();
		$in_theme = get_stylesheet_directory() . '/rem/shortcodes/register-member.php';
		if (file_exists($in_theme)) {
			include $in_theme;
		}
		return ob_get_clean();
	} else {
		return apply_filters('the_content', $content);
	}
}
add_shortcode('rem_register_member', 'rem_register_member_shortcode');

function rem_register_options_shortcode($attrs, $content = '')
{
	if (!is_user_logged_in()) {
		ob_start();
		$in_theme = get_stylesheet_directory() . '/rem/shortcodes/register-options.php';
		if (file_exists($in_theme)) {
			include $in_theme;
		}
		return ob_get_clean();
	} else {
		return apply_filters('the_content', $content);
	}
}
add_shortcode('rem_register_options', 'rem_register_options_shortcode');

/**
 * Registration Demo shortcode to show all registration forms in one page
 */
function rem_registration_demo_shortcode($attrs, $content = '')
{
	ob_start();
	$in_theme = get_stylesheet_directory() . '/rem/shortcodes/registration-demo.php';
	if (file_exists($in_theme)) {
		include $in_theme;
	}
	return ob_get_clean();
}
add_shortcode('rem_registration_demo', 'rem_registration_demo_shortcode');

/**
 * Hook into the registration process to assign proper roles based on the profiles value
 */
add_action('wp_ajax_nopriv_rem_agent_register', 'custom_rem_register_role_assignment', 5);

function custom_rem_register_role_assignment()
{
	if (isset($_REQUEST['profiles'])) {
		// Store profile type in a transient that will be used when user is created
		set_transient('rem_registration_profile_' . $_REQUEST['username'], $_REQUEST['profiles'], HOUR_IN_SECONDS);
	}
}

/**
 * Assign the correct role based on profiles value
 * Version: 1.1 - Made role assignment consistent between host and member
 */
add_action('rem_new_agent_register', 'assign_role_based_on_profile', 10, 1);
add_action('rem_new_agent_approved', 'assign_role_based_on_profile', 10, 1);

function assign_role_based_on_profile($request_data)
{
	if (!isset($request_data['username'])) {
		return;
	}

	// Get the user by username
	$user = get_user_by('login', $request_data['username']);
	if (!$user) {
		return;
	}

	// Check for stored profile type
	$profile_type = get_transient('rem_registration_profile_' . $request_data['username']);
	delete_transient('rem_registration_profile_' . $request_data['username']);

	// If profile type isn't set in transient, check request directly
	if (!$profile_type && isset($request_data['profiles'])) {
		$profile_type = $request_data['profiles'];
	}

	if ($profile_type == 'HOST') {
		// Set role to host
		$user->set_role('host');
		update_user_meta($user->ID, 'agent_type', 'host');
	} elseif ($profile_type == 'MEMBER') {
		// Set role to member
		$user->set_role('member');
		update_user_meta($user->ID, 'agent_type', 'member');
	}
}

/**
 * Create a shortcode alias for register-host to keep backwards compatibility
 * Version: 1.0
 */
function register_host_shortcode_alias($attrs, $content = '')
{
	// Simply pass all parameters to the existing shortcode handler
	return rem_register_host_shortcode($attrs, $content);
}
add_shortcode('register-host', 'register_host_shortcode_alias');

/**
 * AJAX handler for sending property interest email.
 * v1.0
 */
add_action('wp_ajax_send_property_interest_ajax', 'handle_send_property_interest_ajax');
// add_action('wp_ajax_nopriv_send_property_interest_ajax', 'handle_send_property_interest_ajax'); // Keep commented unless needed for non-logged-in

function handle_send_property_interest_ajax() {
    // Check if user is logged in (since button is only shown to logged-in users)
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Error: You must be logged in.'));
        wp_die();
    }

    // Get property ID and nonce from POST data
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $nonce = isset($_POST['nonce']) ? sanitize_key($_POST['nonce']) : '';

    // Verify nonce
    if (!$property_id || !wp_verify_nonce($nonce, 'send_property_interest_ajax_' . $property_id)) {
        wp_send_json_error(array('message' => 'Error: Security check failed. Please refresh the page and try again.'));
        wp_die();
    }

    // Get user and property details
    $current_user = wp_get_current_user();
    $property_title = get_the_title($property_id);
    $property_url = get_permalink($property_id);

    $user_name = $current_user->user_login;
    $user_email = $current_user->user_email;
    $user_roles = implode(', ', $current_user->roles);
    $profile_link = get_author_posts_url($current_user->ID);

    // Prepare email
    $to = 'info@retailspaceshare.com';
    $subject = 'User Interest in Property: ' . $property_title;
    $body = "A logged-in user has expressed interest in the following property:\n\n";
    $body .= "Property Title: " . $property_title . "\n";
    $body .= "Property ID: " . $property_id . "\n";
    $body .= "Property URL: " . $property_url . "\n\n";
    $body .= "User Details:\n";
    $body .= "Username: " . $user_name . "\n";
    $body .= "Email: " . $user_email . "\n";
    $body .= "Role(s): " . $user_roles . "\n";
    $body .= "Profile Link: " . $profile_link . "\n";

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    $headers[] = 'Reply-To: ' . $current_user->display_name . ' <' . $user_email . '>';

    // Send email
    $sent = wp_mail($to, $subject, $body, $headers);

    // Send JSON response back to JavaScript
    if ($sent) {
        wp_send_json_success(array('message' => 'Thank you! Your interest has been noted.'));
    } else {
        // Log error for debugging if needed
        // error_log('wp_mail failed in handle_send_property_interest_ajax for property ' . $property_id . ' by user ' . $user_name);
        wp_send_json_error(array('message' => 'Sorry, there was an error sending the notification. Please contact the administrator directly.'));
    }

    wp_die(); // Required for AJAX handlers
}

?>
