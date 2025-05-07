<?php
/**
 * Member Properties Display
 * Version: 1.9
 *
 * A simplified standalone file that adds a shortcode for displaying member properties
 * with their agent profiles side by side.
 */

if (!defined('ABSPATH')) {
    die();
}

// Add a debug message to check if this file is loaded
add_action('wp_footer', 'member_properties_debug');
function member_properties_debug() {
    echo '<!-- Member Properties Display plugin loaded v1.9 -->';
}

/**
 * Member Properties with Agent Profiles Shortcode - Debug version
 * Usage: [member_properties_display count="10" category="" style="1|2|3|divi"]
 */
function member_properties_display_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'count' => 10,         // Number of properties to display
            'category' => '',      // Property category
            'style' => '1',        // Style (1 = simple, 2 = normal, 3 = enhanced, divi = DIVI compatible)
        ),
        $atts,
        'member_properties_display'
    );

    // Start output buffering
    ob_start();

    // Query for properties
    $args = array(
        'post_type' => 'rem_property',
        'posts_per_page' => intval($atts['count']),
        'post_status' => 'publish',
    );

    // Only add category filter if specified
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'rem_property_category',
                'field' => 'slug',
                'terms' => $atts['category']
            )
        );
    }

    $properties = new WP_Query($args);

    echo '<div style="background:#f5f5f5; padding:10px; margin:10px 0; border:1px solid #ddd;">
        <h3>Debug Information:</h3>
        <p>Properties found: ' . $properties->post_count . '</p>
        <p>Category filter: ' . (empty($atts['category']) ? 'None' : $atts['category']) . '</p>
    </div>';

    if ($properties->have_posts()) {
        // Add a CSS class based on the style
        $container_class = 'member-properties-container';
        if ($atts['style'] == 'divi') {
            $container_class .= ' divi-ready';
        }

        echo '<div class="' . $container_class . '" style="margin:20px 0;">';

        $count = 1;
        while ($properties->have_posts()) {
            $properties->the_post();
            $property_id = get_the_ID();
            $author_id = get_post_field('post_author', $property_id);
            $author = get_user_by('ID', $author_id);

            // Simple table display for debugging
            echo '<div style="border:1px solid #ddd; margin-bottom:20px; padding:10px; background:#fff;">';
            echo '<h3>Property #' . $count . ': ' . get_the_title() . ' (ID: ' . $property_id . ')</h3>';

            // Property data dump
            echo '<table style="width:100%; border-collapse:collapse; margin-bottom:15px;">';
            echo '<tr><th style="border:1px solid #ccc; padding:5px; text-align:left; width:30%;">Field</th><th style="border:1px solid #ccc; padding:5px; text-align:left;">Value</th></tr>';

            // Featured image
            echo '<tr><td style="border:1px solid #ccc; padding:5px;">Featured Image</td><td style="border:1px solid #ccc; padding:5px;">';
            if (has_post_thumbnail()) {
                echo get_the_post_thumbnail($property_id, 'thumbnail');
            } else {
                echo 'No featured image';
            }
            echo '</td></tr>';

            // Check for the profiles field
            $profiles = get_post_meta($property_id, 'profiles', true);
            if (empty($profiles)) {
                $profiles = get_post_meta($property_id, 'properties_profiles', true);
            }
            echo '<tr><td style="border:1px solid #ccc; padding:5px;">Profiles Value</td><td style="border:1px solid #ccc; padding:5px;">' .
                (empty($profiles) ? 'Not found' : esc_html($profiles)) . '</td></tr>';

            // Common REM fields
            $fields_to_check = array(
                'rem_property_price' => 'Price',
                'rem_property_address' => 'Address',
                'rem_property_size' => 'Size',
                'rem_property_multiple_agents' => 'Multiple Agents',
                'rem_property_status' => 'Status',
                'rem_property_type' => 'Type'
            );

            foreach ($fields_to_check as $meta_key => $label) {
                $value = get_post_meta($property_id, $meta_key, true);
                echo '<tr><td style="border:1px solid #ccc; padding:5px;">' . $label . '</td><td style="border:1px solid #ccc; padding:5px;">' .
                    (empty($value) ? 'Not set' : esc_html($value)) . '</td></tr>';
            }

            // Get all taxonomy terms
            $taxonomies = array('rem_property_category', 'rem_property_status', 'rem_property_type', 'rem_property_tag');
            foreach ($taxonomies as $taxonomy) {
                $terms = get_the_terms($property_id, $taxonomy);
                echo '<tr><td style="border:1px solid #ccc; padding:5px;">' . ucfirst(str_replace('rem_property_', '', $taxonomy)) . '</td><td style="border:1px solid #ccc; padding:5px;">';
                if (!empty($terms) && !is_wp_error($terms)) {
                    $term_names = array();
                    foreach ($terms as $term) {
                        $term_names[] = $term->name;
                    }
                    echo esc_html(implode(', ', $term_names));
                } else {
                    echo 'None';
                }
                echo '</td></tr>';
            }

            // Author details
            echo '<tr><td style="border:1px solid #ccc; padding:5px;">Author</td><td style="border:1px solid #ccc; padding:5px;">';
            if ($author) {
                echo esc_html($author->display_name) . ' (ID: ' . $author_id . ')';
                echo '<br>Email: ' . esc_html($author->user_email);
                echo '<br>Roles: ' . esc_html(implode(', ', $author->roles));
            } else {
                echo 'Author not found';
            }
            echo '</td></tr>';

            echo '</table>';

            // If style is set to 2, show the agent and property side by side
            if ($atts['style'] == '2') {
                echo '<div style="display:flex; flex-wrap:wrap; gap:10px;">';

                // Agent card
                echo '<div style="flex:1; min-width:300px; padding:10px; border:1px solid #eee;">';
                echo '<h4>Agent: ' . ($author ? esc_html($author->display_name) : 'Unknown') . '</h4>';
                if ($author) {
                    echo get_avatar($author_id, 100);
                    echo '<p>Email: ' . esc_html($author->user_email) . '</p>';
                    echo '<p>Roles: ' . esc_html(implode(', ', $author->roles)) . '</p>';
                }
                echo '</div>';

                // Property card
                echo '<div style="flex:1; min-width:300px; padding:10px; border:1px solid #eee;">';
                echo '<h4>Property: ' . get_the_title() . '</h4>';
                if (has_post_thumbnail()) {
                    echo get_the_post_thumbnail($property_id, 'medium');
                }
                echo '<p>' . wp_trim_words(get_the_excerpt(), 20) . '</p>';
                echo '<p><a href="' . get_permalink() . '">View Property</a></p>';
                echo '</div>';

                echo '</div>';
            }
            // Enhanced display (style 3)
            elseif ($atts['style'] == '3') {
                echo '<div class="enhanced-property-agent">';
                echo '<div class="property-card">';
                echo '<div class="image-container">';
                if (has_post_thumbnail()) {
                    echo get_the_post_thumbnail($property_id, 'large', array('class' => 'property-featured-image'));
                } else {
                    echo '<div class="no-image">No Image Available</div>';
                }
                echo '</div>';

                // Property details
                echo '<div class="property-details">';
                echo '<h3 class="property-title">' . get_the_title() . '</h3>';

                $price = get_post_meta($property_id, 'rem_property_price', true);
                $address = get_post_meta($property_id, 'rem_property_address', true);
                $size = get_post_meta($property_id, 'rem_property_size', true);

                if (!empty($price)) {
                    echo '<div class="property-price">$' . number_format(floatval($price)) . '</div>';
                }

                if (!empty($address)) {
                    echo '<div class="property-address"><i class="fas fa-map-marker-alt"></i> ' . esc_html($address) . '</div>';
                }

                if (!empty($size)) {
                    echo '<div class="property-size"><i class="fas fa-ruler-combined"></i> ' . esc_html($size) . ' sq ft</div>';
                }

                // Get status and type
                $status_terms = get_the_terms($property_id, 'rem_property_status');
                $type_terms = get_the_terms($property_id, 'rem_property_type');

                echo '<div class="property-meta">';
                if (!empty($status_terms) && !is_wp_error($status_terms)) {
                    echo '<span class="property-status">' . esc_html($status_terms[0]->name) . '</span>';
                }
                if (!empty($type_terms) && !is_wp_error($type_terms)) {
                    echo '<span class="property-type">' . esc_html($type_terms[0]->name) . '</span>';
                }
                echo '</div>';

                echo '<a href="' . get_permalink() . '" class="view-property-btn">View Details</a>';
                echo '</div>'; // End property-details
                echo '</div>'; // End property-card

                // Agent card
                echo '<div class="agent-card">';
                if ($author) {
                    echo '<div class="agent-avatar">' . get_avatar($author_id, 150) . '</div>';
                    echo '<h4 class="agent-name">' . esc_html($author->display_name) . '</h4>';

                    // Get agent profile data if available
                    $agent_phone = get_user_meta($author_id, 'agent_phone', true);
                    $agent_position = get_user_meta($author_id, 'agent_position', true);

                    if (!empty($agent_position)) {
                        echo '<div class="agent-position">' . esc_html($agent_position) . '</div>';
                    }

                    echo '<div class="agent-contact">';
                    echo '<div class="agent-email"><i class="fas fa-envelope"></i> ' . esc_html($author->user_email) . '</div>';

                    if (!empty($agent_phone)) {
                        echo '<div class="agent-phone"><i class="fas fa-phone"></i> ' . esc_html($agent_phone) . '</div>';
                    }

                    echo '</div>'; // End agent-contact

                    // Check if there's an agent profile page
                    $agent_page_id = get_user_meta($author_id, 'agent_page', true);
                    if (!empty($agent_page_id)) {
                        echo '<a href="' . get_permalink($agent_page_id) . '" class="view-agent-btn">View Agent Profile</a>';
                    }
                } else {
                    echo '<div class="no-agent">Agent information not available</div>';
                }
                echo '</div>'; // End agent-card
                echo '</div>'; // End enhanced-property-agent
            }
            // DIVI compatible display
            elseif ($atts['style'] == 'divi') {
                // Start a row structure that DIVI can work with
                echo '<div class="et_pb_row property-agent-row">';

                // Property column - using DIVI's column classes
                echo '<div class="et_pb_column et_pb_column_1_2 property-column">';
                echo '<div class="property-divi-card">';

                // Property featured image
                echo '<div class="property-image-container">';
                if (has_post_thumbnail()) {
                    echo get_the_post_thumbnail($property_id, 'large', array('class' => 'property-image'));
                } else {
                    echo '<div class="no-property-image">No Image Available</div>';
                }
                echo '</div>';

                // Property content
                echo '<div class="property-content">';
                echo '<h3 class="property-title">' . get_the_title() . '</h3>';

                // Property meta information
                $price = get_post_meta($property_id, 'rem_property_price', true);
                $address = get_post_meta($property_id, 'rem_property_address', true);
                $size = get_post_meta($property_id, 'rem_property_size', true);

                echo '<div class="property-meta-info">';
                if (!empty($price)) {
                    echo '<div class="property-price"><strong>Price:</strong> $' . number_format(floatval($price)) . '</div>';
                }

                if (!empty($address)) {
                    echo '<div class="property-address"><strong>Address:</strong> ' . esc_html($address) . '</div>';
                }

                if (!empty($size)) {
                    echo '<div class="property-size"><strong>Size:</strong> ' . esc_html($size) . ' sq ft</div>';
                }
                echo '</div>';

                // Property excerpt
                echo '<div class="property-description">';
                echo wp_trim_words(get_the_excerpt(), 20);
                echo '</div>';

                // Property link button
                echo '<div class="property-link">';
                echo '<a href="' . get_permalink() . '" class="property-button">View Property Details</a>';
                echo '</div>';

                echo '</div>'; // End property-content
                echo '</div>'; // End property-divi-card
                echo '</div>'; // End property column

                // Agent column - using DIVI's column classes
                echo '<div class="et_pb_column et_pb_column_1_2 agent-column">';
                echo '<div class="agent-divi-card">';

                if ($author) {
                    // Agent avatar
                    echo '<div class="agent-avatar-container">';
                    echo get_avatar($author_id, 200, '', '', array('class' => 'agent-avatar-image'));
                    echo '</div>';

                    // Agent info
                    echo '<div class="agent-info">';
                    echo '<h3 class="agent-name">' . esc_html($author->display_name) . '</h3>';

                    // Get agent meta data
                    $agent_phone = get_user_meta($author_id, 'agent_phone', true);
                    $agent_position = get_user_meta($author_id, 'agent_position', true);
                    $agent_bio = get_user_meta($author_id, 'description', true);

                    if (!empty($agent_position)) {
                        echo '<div class="agent-position">' . esc_html($agent_position) . '</div>';
                    }

                    echo '<div class="agent-contact-info">';
                    echo '<div class="agent-email"><strong>Email:</strong> ' . esc_html($author->user_email) . '</div>';

                    if (!empty($agent_phone)) {
                        echo '<div class="agent-phone"><strong>Phone:</strong> ' . esc_html($agent_phone) . '</div>';
                    }
                    echo '</div>';

                    // Agent bio (if available)
                    if (!empty($agent_bio)) {
                        echo '<div class="agent-bio">';
                        echo '<h4>About the Agent</h4>';
                        echo '<p>' . wp_trim_words($agent_bio, 30) . '</p>';
                        echo '</div>';
                    }

                    // Check for agent profile page
                    $agent_page = get_user_meta($author_id, 'agent_page', true);
                    if (!empty($agent_page)) {
                        echo '<div class="agent-link">';
                        echo '<a href="' . get_permalink($agent_page) . '" class="agent-button">View Agent Profile</a>';
                        echo '</div>';
                    }

                } else {
                    echo '<div class="no-agent-info">Agent information unavailable</div>';
                }

                echo '</div>'; // End agent-info
                echo '</div>'; // End agent-divi-card
                echo '</div>'; // End agent column

                echo '</div>'; // End et_pb_row
            }

            echo '</div>'; // End property container

            $count++;
        }

        // Add CSS for enhanced styles
        if ($atts['style'] == '3' || $atts['style'] == 'divi') {
            echo '<style>
                /* General styles for enhanced and DIVI layouts */
                .enhanced-property-agent, .property-agent-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 20px;
                    margin-bottom: 30px;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }

                /* Enhanced Style (3) */
                .enhanced-property-agent .property-card {
                    flex: 2;
                    min-width: 300px;
                }

                .enhanced-property-agent .agent-card {
                    flex: 1;
                    min-width: 250px;
                    padding: 20px;
                    background: #f9f9f9;
                    text-align: center;
                }

                .enhanced-property-agent .property-featured-image {
                    width: 100%;
                    height: auto;
                    display: block;
                }

                .enhanced-property-agent .property-details {
                    padding: 20px;
                }

                .enhanced-property-agent .property-title {
                    font-size: 24px;
                    margin-bottom: 10px;
                }

                .enhanced-property-agent .property-price {
                    font-size: 22px;
                    color: #2ecc71;
                    font-weight: bold;
                    margin-bottom: 10px;
                }

                .enhanced-property-agent .property-meta {
                    margin: 15px 0;
                }

                .enhanced-property-agent .property-status,
                .enhanced-property-agent .property-type {
                    display: inline-block;
                    padding: 5px 10px;
                    background: #3498db;
                    color: white;
                    border-radius: 4px;
                    margin-right: 5px;
                    font-size: 12px;
                }

                .enhanced-property-agent .agent-avatar img {
                    border-radius: 50%;
                    margin-bottom: 15px;
                }

                .enhanced-property-agent .agent-name {
                    font-size: 20px;
                    margin-bottom: 5px;
                }

                .enhanced-property-agent .agent-position {
                    color: #7f8c8d;
                    margin-bottom: 15px;
                }

                .enhanced-property-agent .agent-contact {
                    margin-bottom: 20px;
                }

                .view-property-btn,
                .view-agent-btn {
                    display: inline-block;
                    padding: 10px 20px;
                    background: #3498db;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: bold;
                    transition: background 0.3s ease;
                }

                .view-property-btn:hover,
                .view-agent-btn:hover {
                    background: #2980b9;
                }

                /* DIVI Compatible Style */
                .property-divi-card,
                .agent-divi-card {
                    height: 100%;
                    background: #fff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 0 10px rgba(0,0,0,0.05);
                }

                .property-image-container {
                    position: relative;
                    overflow: hidden;
                }

                .property-image {
                    width: 100%;
                    height: auto;
                    display: block;
                    transition: transform 0.3s ease;
                }

                .property-divi-card:hover .property-image {
                    transform: scale(1.05);
                }

                .property-content,
                .agent-info {
                    padding: 20px;
                }

                .property-title,
                .agent-name {
                    font-size: 22px;
                    margin-bottom: 15px;
                    color: #333;
                }

                .property-meta-info > div {
                    margin-bottom: 8px;
                }

                .property-description,
                .agent-bio {
                    margin: 15px 0;
                    line-height: 1.6;
                }

                .agent-avatar-container {
                    text-align: center;
                    padding-top: 20px;
                }

                .agent-avatar-image {
                    border-radius: 50%;
                }

                .agent-position {
                    color: #7f8c8d;
                    font-style: italic;
                    margin-bottom: 15px;
                }

                .agent-contact-info > div {
                    margin-bottom: 8px;
                }

                .property-button,
                .agent-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background: #2ecc71;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }

                .property-button:hover,
                .agent-button:hover {
                    background: #27ae60;
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                }

                @media (max-width: 767px) {
                    .enhanced-property-agent,
                    .property-agent-row {
                        flex-direction: column;
                    }

                    .et_pb_column {
                        width: 100% !important;
                        margin-bottom: 20px;
                    }
                }
            </style>';
        }

        echo '</div>';
    } else {
        echo '<p>No properties found matching the criteria.</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('member_properties_display', 'member_properties_display_shortcode');
