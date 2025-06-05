<?php
/**
 * Registration Examples with Field Customization
 * Version: 1.0
 */

/**
 * Example 1: Member Registration with customized field labels
 *
 * Usage: [rem_register_example_member]
 */
function rem_register_example_member_shortcode($atts, $content = '') {
    // This creates a member registration form with customized field labels
    $shortcode = '[rem_register
        type="member"
        title="Find Your Perfect Space"
        description="Register as a Member to discover retail spaces that match your business needs."
        button_text="Join as Member"
        field_labels="property_name:Business Name,property_type:Business Type,price:Budget Range,beds:Staff Size,baths:Required Amenities"
        field_placeholders="property_name:Enter your business name,property_type:Type of business you operate"
        field_descriptions="property_name:The name of your business that will be shown to hosts,price:Your monthly budget range for retail space"
    ]';

    // Process and return the shortcode
    return do_shortcode($shortcode);
}
add_shortcode('rem_register_example_member', 'rem_register_example_member_shortcode');

/**
 * Example 2: Host Registration with customized field labels
 *
 * Usage: [rem_register_example_host]
 */
function rem_register_example_host_shortcode($atts, $content = '') {
    // This creates a host registration form with customized field labels
    $shortcode = '[rem_register
        type="host"
        title="List Your Retail Space"
        description="Register as a Host to showcase your retail spaces to our community of members."
        button_text="Register as Host"
        field_labels="property_name:Property Name,property_type:Space Type,price:Monthly Rate,beds:Maximum Occupancy,baths:Available Amenities"
        field_placeholders="property_name:Enter your property name,property_type:Type of retail space available"
        field_descriptions="property_name:The name of your property that will be shown to members,price:The monthly rental rate for your space"
    ]';

    // Process and return the shortcode
    return do_shortcode($shortcode);
}
add_shortcode('rem_register_example_host', 'rem_register_example_host_shortcode');
