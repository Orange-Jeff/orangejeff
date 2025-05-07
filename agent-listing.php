<?php
/**
 * Role-Based Agent Listings
 * Version: 1.9.1
 * Adds shortcodes to display users/agents with role-based filtering and field restrictions
 */

if (!defined('ABSPATH')) {
    die();
}

/**
 * Main shortcode to display users based on role with field restrictions
 * Usage: [ds_agent_list role="host" style="1"]
 */
function ds_agent_list_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'role' => 'member',       // Role to filter by (host, member, rem_property_agent, etc)
            'style' => '2',        // Different display styles (1, 2, etc)
            'count' => 200,         // Number of agents to show
            'orderby' => 'display_name', // Field to order by (display_name, registered, etc)
            'order' => 'ASC',      // Order direction (ASC or DESC)
            'exclude_roles' => '',  // Comma-separated roles to exclude
        ),
        $atts,
        'ds_agent_list'
    );

    // Start output buffering to capture the HTML
    ob_start();

    // Build user query arguments
    $args = array(
        'number' => intval($atts['count']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    );

    // Handle role filtering
    if ($atts['role'] !== 'all') {
        $args['role'] = $atts['role'];
    }

    // Handle role exclusions
    if (!empty($atts['exclude_roles'])) {
        $exclude_roles = explode(',', $atts['exclude_roles']);
        $args['role__not_in'] = $exclude_roles;
    }

    // Get users
    $users = get_users($args);

    // If no users found
    if (empty($users)) {
        echo '<p>No users found matching the selected criteria.</p>';
        return ob_get_clean();
    }

    // Include the appropriate template based on style
    switch ($atts['style']) {
        case '2':
            ds_agent_list_style_2($users, $atts);
            break;
        default:
            ds_agent_list_style_1($users, $atts);
            break;
    }

    // Return the buffered output
    return ob_get_clean();
}
add_shortcode('ds_agent_list', 'ds_agent_list_shortcode');

/**
 * Get agent data from REM plugin
 * @param int $user_id WordPress user ID
 * @return array Agent data
 */
function ds_get_rem_agent_data($user_id) {
    $agent_data = array();

    // Try to get data from REM plugin's agent table if available
    if (function_exists('rem_get_agent_data')) {
        $agent_data = rem_get_agent_data($user_id);
    } else {
        // Fallback to checking if REM stores data in postmeta
        global $wpdb;
        $table_name = $wpdb->prefix . 'rem_agent_data';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $agent_data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id),
                ARRAY_A
            );
        }
    }

    // If nothing found, try WordPress user meta as fallback
    if (empty($agent_data)) {
        $fields = array(
            'first_name', 'last_name', 'description', 'phone', 'mobile',
            'facebook_url', 'twitter_url', 'linkedin_url', 'rem_agent_title'
        );

        foreach ($fields as $field) {
            $value = get_user_meta($user_id, $field, true);
            if (!empty($value)) {
                $agent_data[$field] = $value;
            }
        }
    }

    return $agent_data;
}

/**
 * Style 1: Basic list with avatar, name, and limited info
 * Limited information display - good for public-facing pages
 */
function ds_agent_list_style_1($users, $atts) {
    echo '<div class="ds-agent-list ds-agent-list-style-1">';

    foreach ($users as $user) {
        // Get user data
        $user_id = $user->ID;
        $name = $user->display_name;
        $role = reset($user->roles); // Get the first role
        $avatar = get_avatar($user_id, 96);
        $user_type = get_user_meta($user_id, 'user_type', true);

        // Get REM agent data
        $agent_data = ds_get_rem_agent_data($user_id);

        // Get agent title from REM data if available
        $agent_title = !empty($agent_data['rem_agent_title']) ? $agent_data['rem_agent_title'] : '';
        if (empty($agent_title)) {
            $agent_title = !empty($agent_data['title']) ? $agent_data['title'] : '';
        }

        // Get business description/bio
        $bio = !empty($agent_data['description']) ? $agent_data['description'] : get_user_meta($user_id, 'description', true);

        // Get phone numbers
        $phone = !empty($agent_data['phone']) ? $agent_data['phone'] : get_user_meta($user_id, 'phone', true);
        $mobile = !empty($agent_data['mobile']) ? $agent_data['mobile'] : get_user_meta($user_id, 'mobile', true);

        // Get company name if available
        $company = !empty($agent_data['company']) ? $agent_data['company'] : get_user_meta($user_id, 'company', true);

        // Output user card
        ?>
        <div class="ds-agent-item">
            <div class="ds-agent-avatar"><?php echo $avatar; ?></div>
            <div class="ds-agent-info">
                <h3 class="ds-agent-name"><?php echo esc_html($name); ?></h3>
                <?php if (!empty($company)) : ?>
                    <p class="ds-agent-company"><?php echo esc_html($company); ?></p>
                <?php endif; ?>

                <?php if (!empty($agent_title)) : ?>
                    <p class="ds-agent-title"><?php echo esc_html($agent_title); ?></p>
                <?php elseif (!empty($user_type)) : ?>
                    <p class="ds-agent-type"><?php echo esc_html($user_type); ?></p>
                <?php endif; ?>

                <?php if (!empty($bio)) : ?>
                    <div class="ds-agent-bio-excerpt">
                        <?php
                        // Truncate bio to approximately 100 characters
                        echo esc_html(substr($bio, 0, 100)) . (strlen($bio) > 100 ? '...' : '');
                        ?>
                    </div>
                <?php endif; ?>

                <div class="ds-agent-contact-info">
                    <?php if (!empty($phone)) : ?>
                        <p class="ds-agent-phone">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($mobile) && $mobile != $phone) : ?>
                        <p class="ds-agent-mobile">
                            <i class="fas fa-mobile-alt"></i>
                            <a href="tel:<?php echo esc_attr($mobile); ?>"><?php echo esc_html($mobile); ?></a>
                        </p>
                    <?php endif; ?>

                    <p class="ds-agent-email">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                            <?php echo esc_html($user->user_email); ?>
                        </a>
                    </p>
                </div>

                <?php // Properties count if using REM ?>
                <?php if (function_exists('rem_get_user_properties_count')) : ?>
                    <p class="ds-agent-properties-count">
                        <?php echo rem_get_user_properties_count($user_id); ?> Properties
                    </p>
                <?php endif; ?>

                <?php // Link to agent profile page if available ?>
                <?php if (function_exists('get_author_posts_url')) : ?>
                    <a href="<?php echo esc_url(get_author_posts_url($user_id)); ?>" class="ds-agent-profile-link">View Profile</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    echo '</div>';

    // Add enhanced CSS for Style 1
    ?>
    <style>
        .ds-agent-list-style-1 {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .ds-agent-list-style-1 .ds-agent-item {
            display: flex;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .ds-agent-list-style-1 .ds-agent-avatar {
            margin-right: 15px;
            flex-shrink: 0;
        }

        .ds-agent-list-style-1 .ds-agent-info {
            flex-grow: 1;
        }

        .ds-agent-list-style-1 .ds-agent-name {
            margin: 0 0 5px 0;
            color: #333;
        }

        .ds-agent-list-style-1 .ds-agent-title,
        .ds-agent-list-style-1 .ds-agent-type {
            margin: 0 0 8px 0;
            font-style: italic;
            color: #666;
        }

        .ds-agent-list-style-1 .ds-agent-company {
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #444;
        }

        .ds-agent-list-style-1 .ds-agent-bio-excerpt {
            margin: 10px 0;
            color: #555;
            line-height: 1.4;
            font-size: 0.9em;
        }

        .ds-agent-list-style-1 .ds-agent-contact-info {
            margin: 10px 0;
            font-size: 0.9em;
        }

        .ds-agent-list-style-1 .ds-agent-contact-info p {
            margin: 0 0 5px 0;
        }

        .ds-agent-list-style-1 .ds-agent-contact-info i {
            width: 16px;
            margin-right: 5px;
            color: #666;
        }

        .ds-agent-list-style-1 .ds-agent-properties-count {
            font-size: 0.85em;
            color: #666;
            margin: 8px 0;
        }

        .ds-agent-list-style-1 .ds-agent-profile-link {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background-color: #f5f5f5;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            font-size: 0.85em;
            border: 1px solid #ddd;
        }

        .ds-agent-list-style-1 .ds-agent-profile-link:hover {
            background-color: #ebebeb;
        }

        @media (min-width: 768px) {
            .ds-agent-list-style-1 .ds-agent-item {
                width: calc(50% - 15px);
            }
        }
    </style>
    <?php
}

/**
 * Style 2: Comprehensive agent cards with full details
 * More detailed information display - good for registered users
 */
function ds_agent_list_style_2($users, $atts) {
    echo '<div class="ds-agent-list ds-agent-list-style-2">';

    foreach ($users as $user) {
        // Get user data
        $user_id = $user->ID;
        $name = $user->display_name;
        $role = reset($user->roles); // Get the first role
        $avatar = get_avatar($user_id, 128);
        $user_type = get_user_meta($user_id, 'user_type', true);

        // Get REM agent data
        $agent_data = ds_get_rem_agent_data($user_id);

        // Get fields with priority to REM data and fallback to user meta
        $phone = !empty($agent_data['phone']) ? $agent_data['phone'] : get_user_meta($user_id, 'phone', true);
        $mobile = !empty($agent_data['mobile']) ? $agent_data['mobile'] : get_user_meta($user_id, 'mobile', true);
        $facebook = !empty($agent_data['facebook_url']) ? $agent_data['facebook_url'] : get_user_meta($user_id, 'facebook_url', true);
        $twitter = !empty($agent_data['twitter_url']) ? $agent_data['twitter_url'] : get_user_meta($user_id, 'twitter_url', true);
        $linkedin = !empty($agent_data['linkedin_url']) ? $agent_data['linkedin_url'] : get_user_meta($user_id, 'linkedin_url', true);
        $bio = !empty($agent_data['description']) ? $agent_data['description'] : get_user_meta($user_id, 'description', true);
        $title = !empty($agent_data['rem_agent_title']) ? $agent_data['rem_agent_title'] : '';
        if (empty($title)) {
            $title = !empty($agent_data['title']) ? $agent_data['title'] : '';
        }

        // Get company information
        $company = !empty($agent_data['company']) ? $agent_data['company'] : get_user_meta($user_id, 'company', true);
        $address = !empty($agent_data['address']) ? $agent_data['address'] : get_user_meta($user_id, 'address', true);
        $license = !empty($agent_data['license']) ? $agent_data['license'] : get_user_meta($user_id, 'license', true);

        // Get additional fields that may be useful for business listings
        $business_hours = !empty($agent_data['hours_days_operation']) ? $agent_data['hours_days_operation'] : get_user_meta($user_id, 'hours_days_operation', true);
        $property_type = !empty($agent_data['property_type']) ? $agent_data['property_type'] : get_user_meta($user_id, 'property_type', true);
        $price_range = !empty($agent_data['price']) ? $agent_data['price'] : get_user_meta($user_id, 'price', true);

        // Output user card
        ?>
        <div class="ds-agent-card">
            <div class="ds-agent-header">
                <div class="ds-agent-avatar"><?php echo $avatar; ?></div>
                <div class="ds-agent-header-info">
                    <h3 class="ds-agent-name"><?php echo esc_html($name); ?></h3>

                    <?php if (!empty($company)) : ?>
                        <p class="ds-agent-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($title)) : ?>
                        <p class="ds-agent-role"><?php echo esc_html($title); ?></p>
                    <?php elseif (!empty($user_type)) : ?>
                        <p class="ds-agent-role"><?php echo esc_html($user_type); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ds-agent-body">
                <?php if (!empty($bio)) : ?>
                    <div class="ds-agent-bio">
                        <h4 class="ds-agent-bio-title">Business Description</h4>
                        <?php echo wpautop(esc_html($bio)); ?>
                    </div>
                <?php endif; ?>

                <div class="ds-agent-details">
                    <?php if (!empty($property_type)) : ?>
                        <div class="ds-agent-detail-item">
                            <span class="ds-agent-detail-label">Property Type:</span>
                            <span class="ds-agent-detail-value"><?php echo esc_html($property_type); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($price_range)) : ?>
                        <div class="ds-agent-detail-item">
                            <span class="ds-agent-detail-label">Price Range:</span>
                            <span class="ds-agent-detail-value"><?php echo esc_html($price_range); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($business_hours)) : ?>
                        <div class="ds-agent-detail-item">
                            <span class="ds-agent-detail-label">Business Hours:</span>
                            <span class="ds-agent-detail-value"><?php echo esc_html($business_hours); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ds-agent-contact">
                    <h4 class="ds-agent-contact-title">Contact Information</h4>

                    <p class="ds-agent-email">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                            <?php echo esc_html($user->user_email); ?>
                        </a>
                    </p>

                    <?php if (!empty($phone)) : ?>
                        <p class="ds-agent-phone">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr($phone); ?>">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($mobile) && $mobile != $phone) : ?>
                        <p class="ds-agent-mobile">
                            <i class="fas fa-mobile-alt"></i>
                            <a href="tel:<?php echo esc_attr($mobile); ?>">
                                <?php echo esc_html($mobile); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($address)) : ?>
                        <p class="ds-agent-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html($address); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($license)) : ?>
                        <p class="ds-agent-license">
                            <i class="fas fa-id-card"></i>
                            <strong>License #:</strong> <?php echo esc_html($license); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($facebook) || !empty($twitter) || !empty($linkedin)) : ?>
                    <div class="ds-agent-social">
                        <?php if (!empty($facebook)) : ?>
                            <a href="<?php echo esc_url($facebook); ?>" target="_blank" class="ds-social-icon facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($twitter)) : ?>
                            <a href="<?php echo esc_url($twitter); ?>" target="_blank" class="ds-social-icon twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($linkedin)) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="ds-social-icon linkedin">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php // Properties link if using REM ?>
                <?php if (function_exists('rem_get_user_properties_count')) :
                    $properties_count = rem_get_user_properties_count($user_id);
                    if ($properties_count > 0) : ?>
                    <div class="ds-agent-properties">
                        <a href="<?php echo esc_url(get_author_posts_url($user_id)); ?>" class="ds-agent-properties-link">
                            View <?php echo esc_html($properties_count); ?> Properties
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    echo '</div>';

    // Add enhanced CSS for the agent cards
    ?>
    <style>
        .ds-agent-list-style-2 {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
            margin: 20px 0;
        }

        .ds-agent-list-style-2 .ds-agent-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            background-color: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .ds-agent-list-style-2 .ds-agent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .ds-agent-list-style-2 .ds-agent-header {
            display: flex;
            padding: 20px;
            background-color: #f8f8f8;
            border-bottom: 1px solid #eee;
        }

        .ds-agent-list-style-2 .ds-agent-avatar {
            margin-right: 15px;
            flex-shrink: 0;
        }

        .ds-agent-list-style-2 .ds-agent-header-info {
            flex-grow: 1;
        }

        .ds-agent-list-style-2 .ds-agent-body {
            padding: 20px;
        }

        .ds-agent-list-style-2 .ds-agent-name {
            margin: 0 0 5px 0;
            font-size: 1.4em;
            color: #333;
        }

        .ds-agent-list-style-2 .ds-agent-company {
            margin: 0 0 5px 0;
            font-weight: bold;
            color: #333;
        }

        .ds-agent-list-style-2 .ds-agent-role {
            margin: 0;
            color: #666;
            font-style: italic;
        }

        .ds-agent-list-style-2 .ds-agent-bio {
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .ds-agent-list-style-2 .ds-agent-bio-title {
            margin: 0 0 10px 0;
            font-size: 1.1em;
            color: #333;
        }

        .ds-agent-list-style-2 .ds-agent-details {
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .ds-agent-list-style-2 .ds-agent-detail-item {
            margin-bottom: 8px;
        }

        .ds-agent-list-style-2 .ds-agent-detail-label {
            font-weight: bold;
            margin-right: 5px;
            color: #555;
        }

        .ds-agent-list-style-2 .ds-agent-contact {
            margin: 0 0 20px 0;
        }

        .ds-agent-list-style-2 .ds-agent-contact-title {
            margin: 0 0 10px 0;
            font-size: 1.1em;
            color: #333;
        }

        .ds-agent-list-style-2 .ds-agent-contact p {
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
        }

        .ds-agent-list-style-2 .ds-agent-contact i {
            width: 20px;
            margin-right: 8px;
            color: #666;
        }

        .ds-agent-list-style-2 .ds-agent-social {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        .ds-agent-list-style-2 .ds-social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: #333;
            color: #fff;
            border-radius: 50%;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .ds-agent-list-style-2 .ds-social-icon.facebook {
            background-color: #3b5998;
        }

        .ds-agent-list-style-2 .ds-social-icon.twitter {
            background-color: #1da1f2;
        }

        .ds-agent-list-style-2 .ds-social-icon.linkedin {
            background-color: #0077b5;
        }

        .ds-agent-list-style-2 .ds-social-icon:hover {
            opacity: 0.9;
        }

        .ds-agent-list-style-2 .ds-agent-properties {
            margin-top: 15px;
            text-align: center;
        }

        .ds-agent-list-style-2 .ds-agent-properties-link {
            display: inline-block;
            padding: 8px 15px;
            background-color: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }

        .ds-agent-list-style-2 .ds-agent-properties-link:hover {
            background-color: #005177;
        }

        @media (max-width: 767px) {
            .ds-agent-list-style-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php
}

/**
 * Custom Divi-friendly agent listing shortcode
 * Specifically designed for use in Divi Text modules
 * Usage: [rem_custom_agents style="2"]
 * Version: 1.3
 */
add_shortcode('rem_custom_agents', 'rem_custom_agents_shortcode');
function rem_custom_agents_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'role' => 'all',       // Role to filter by (host, member, rem_property_agent, etc)
            'style' => '2',        // Different display styles (1, 2, etc)
            'count' => 20,         // Number of agents to show
            'orderby' => 'display_name', // Field to order by (display_name, registered, etc)
            'order' => 'ASC',      // Order direction (ASC or DESC)
            'columns' => 'col-sm-12', // Column class from Divi/Bootstrap
            'debug' => 'no',       // Set to 'yes' to show debugging info
            'show_version' => 'yes', // Show version number at the top
        ),
        $atts,
        'rem_custom_agents'
    );

    // Start output buffering to capture the HTML
    ob_start();

    // Show version info at top if enabled
    if ($atts['show_version'] === 'yes') {
        echo '<div class="rem-version-info">Agent Listings v1.3</div>';
    }

    // Show debugging info if requested
    if ($atts['debug'] === 'yes') {
        echo '<div style="background:#f8f8f8; border:1px solid #ddd; padding:10px; margin-bottom:15px; font-family:monospace;">';
        echo '<strong>Debug Info:</strong><br>';
        echo 'Role: ' . $atts['role'] . '<br>';
        echo 'Style: ' . $atts['style'] . '<br>';
        echo 'Count: ' . $atts['count'] . '<br>';
        echo 'Order By: ' . $atts['orderby'] . '<br>';
        echo 'Order: ' . $atts['order'] . '<br>';
        echo '</div>';
    }

    // Build user query arguments
    $args = array(
        'number' => intval($atts['count']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    );

    // Handle role filtering
    if ($atts['role'] !== 'all') {
        $args['role'] = $atts['role'];
    }

    // Get users
    $users = get_users($args);

    // If no users found
    if (empty($users)) {
        echo '<p>No agents found matching the selected criteria.</p>';
        return ob_get_clean();
    }

    echo '<div class="rem-custom-agents-container ' . esc_attr($atts['columns']) . '">';

    // Process each user
    foreach ($users as $user) {
        // Get user data
        $user_id = $user->ID;
        $name = $user->display_name;
        $email = $user->user_email;

        // Debug: Show role of the current user
        if ($atts['debug'] === 'yes') {
            echo '<div style="background:#f0f0f0; padding:5px; margin:5px 0; font-size:small;">';
            echo 'User: ' . $name . ' - Roles: ' . implode(', ', $user->roles);
            echo '</div>';
        }

        // Get agent data
        $agent_data = ds_get_rem_agent_data($user_id);

        // Get fields with priority to REM data and fallback to user meta
        $phone = !empty($agent_data['phone']) ? $agent_data['phone'] : get_user_meta($user_id, 'phone', true);
        $mobile = !empty($agent_data['mobile']) ? $agent_data['mobile'] : get_user_meta($user_id, 'mobile', true);
        $bio = !empty($agent_data['description']) ? $agent_data['description'] : get_user_meta($user_id, 'description', true);
        $title = !empty($agent_data['rem_agent_title']) ? $agent_data['rem_agent_title'] : '';
        if (empty($title)) {
            $title = !empty($agent_data['title']) ? $agent_data['title'] : '';
        }

        // Get company information
        $company = !empty($agent_data['company']) ? $agent_data['company'] : get_user_meta($user_id, 'company', true);

        // Get additional fields for business listings
        $business_hours = !empty($agent_data['hours_days_operation']) ? $agent_data['hours_days_operation'] : get_user_meta($user_id, 'hours_days_operation', true);
        $property_type = !empty($agent_data['property_type']) ? $agent_data['property_type'] : get_user_meta($user_id, 'property_type', true);
        $price_range = !empty($agent_data['price']) ? $agent_data['price'] : get_user_meta($user_id, 'price', true);

        // Get avatar
        $avatar = get_avatar($user_id, 180);

        // Get the tagline field if available
        $tagline = !empty($agent_data['tagline']) ? $agent_data['tagline'] : get_user_meta($user_id, 'tagline', true);
        if (empty($tagline) && !empty($bio)) {
            // Use the first sentence of bio as tagline if no specific tagline exists
            $first_period = strpos($bio, '.');
            $tagline = ($first_period !== false) ? substr($bio, 0, $first_period + 1) : substr($bio, 0, 100) . '...';
        }

        // Build custom agent card with two-column layout
        ?>
        <div class="custom-agent-card">
            <div class="custom-agent-content">
                <div class="custom-agent-avatar">
                    <?php echo $avatar; ?>
                </div>

                <div class="custom-agent-info">
                    <h3 class="custom-agent-name"><?php echo esc_html($name); ?></h3>

                    <?php if (!empty($company)) : ?>
                        <p class="custom-agent-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($title)) : ?>
                        <p class="custom-agent-title"><?php echo esc_html($title); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($tagline)) : ?>
                        <p class="custom-agent-tagline"><?php echo esc_html($tagline); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($bio)) : ?>
                        <div class="custom-agent-bio">
                            <?php echo wpautop(esc_html($bio)); ?>
                        </div>
                    <?php endif; ?>

                    <div class="custom-agent-details">
                        <?php if (!empty($property_type)) : ?>
                            <p><strong>Property Type:</strong> <?php echo esc_html($property_type); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($price_range)) : ?>
                            <p><strong>Price Range:</strong> <?php echo esc_html($price_range); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($business_hours)) : ?>
                            <p><strong>Business Hours:</strong> <?php echo esc_html($business_hours); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="custom-agent-contact">
                        <p><i class="fa fa-envelope"></i> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>

                        <?php if (!empty($phone)) : ?>
                            <p><i class="fa fa-phone"></i> <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></p>
                        <?php endif; ?>

                        <?php if (!empty($mobile) && $mobile != $phone) : ?>
                            <p><i class="fa fa-mobile"></i> <a href="tel:<?php echo esc_attr($mobile); ?>"><?php echo esc_html($mobile); ?></a></p>
                        <?php endif; ?>
                    </div>

                    <div class="custom-agent-footer">
                        <?php
                        // Get user's properties count
                        $properties_count = function_exists('rem_get_user_properties_count') ? rem_get_user_properties_count($user_id) : 0;
                        ?>
                        <a href="<?php echo esc_url(get_author_posts_url($user_id)); ?>" class="view-profile-btn">
                            View Business Listing<?php echo ($properties_count != 1) ? 's' : ''; ?>
                            <?php if ($properties_count > 0) : ?>
                                (<?php echo esc_html($properties_count); ?>)
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    echo '</div>';

    // Return the buffered output with styles included
    $output = ob_get_clean();

    // Add the CSS inline to ensure it's loaded
    $css = '
        <style>
            /* Version info display */
            .rem-version-info {
                font-size: 0.8em;
                text-align: right;
                color: #666;
                margin-bottom: 10px;
                font-style: italic;
            }

            /* Custom Agent Listings for Divi */
            .rem-custom-agents-container {
                width: 100%;
                margin: 0 auto;
            }

            .custom-agent-card {
                margin-bottom: 30px;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                overflow: hidden;
                background: #fff;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .custom-agent-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }

            .custom-agent-content {
                display: flex;
                padding: 20px;
            }

            .custom-agent-avatar {
                width: 180px;
                flex-shrink: 0;
            }

            .custom-agent-avatar img {
                border-radius: 5px;
                max-width: 100%;
                height: auto;
            }

            .custom-agent-info {
                flex-grow: 1;
                padding-left: 20px;
            }

            .custom-agent-name {
                margin: 0 0 5px;
                font-size: 1.4em;
                color: #333;
            }

            .custom-agent-company {
                margin: 0 0 5px;
                font-weight: 600;
                color: #444;
            }

            .custom-agent-title {
                margin: 0 0 8px;
                color: #666;
                font-style: italic;
            }

            .custom-agent-tagline {
                margin: 0 0 15px;
                color: #444;
                font-weight: 500;
                font-style: italic;
                font-size: 1.05em;
            }

            .custom-agent-bio {
                margin-bottom: 15px;
                line-height: 1.5;
                color: #555;
            }

            .custom-agent-details {
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .custom-agent-details p {
                margin: 5px 0;
            }

            .custom-agent-contact {
                margin-bottom: 15px;
            }

            .custom-agent-contact p {
                margin: 5px 0;
            }

            .custom-agent-contact i {
                width: 20px;
                text-align: center;
                margin-right: 8px;
                color: #0073aa;
            }

            .custom-agent-footer {
                margin-top: 15px;
            }

            .view-profile-btn {
                display: inline-block;
                padding: 7px 15px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 3px;
                transition: background 0.2s;
            }

            .view-profile-btn:hover {
                background: #005d8c;
                color: white;
            }

            /* Responsive adjustments */
            @media (max-width: 767px) {
                .custom-agent-content {
                    flex-direction: column;
                }

                .custom-agent-avatar {
                    width: 100%;
                    margin-bottom: 15px;
                    text-align: center;
                }

                .custom-agent-avatar img {
                    max-width: 180px;
                }

                .custom-agent-info {
                    padding-left: 0;
                }
            }
        </style>
    ';

    return $css . $output;
}
?>
