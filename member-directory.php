<?php
/**
 * Member Directory Display for Divi Text Module
 * Version: 1.2
 *
 * Standalone PHP snippet for displaying all users with role "member"
 * alongside their property listings in a clean two-column format
 */

// Display version at top for testing - DELETE AFTER TESTING
echo '<div style="background:#f8f8f8; padding:5px 10px; margin-bottom:15px; border-left:4px solid #0073aa; font-size:14px; color:#333;">Member Directory Version: 1.2</div>';

// Verify we're in WordPress
if (!function_exists('get_users')) {
    echo '<p>This code must be run within WordPress.</p>';
    return;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<p>Please log in to view the member directory.</p>';
    return;
}

// Set up member query - get ALL members, not just a limited number
$args = array(
    'role' => 'member',
    'number' => 500,  // Large number to effectively get all
    'orderby' => 'display_name',
    'order' => 'ASC',
);

// Debugging information
$show_debug = false; // Set to true to enable debugging
$members = get_users($args);

// Begin output
echo '<div class="rss-member-directory">';

// Debugging header if enabled
if ($show_debug) {
    echo '<div class="rss-debug-info">';
    echo '<h3>Debug Information:</h3>';
    echo '<p>Total members found: ' . count($members) . '</p>';
    echo '</div>';
}

// Check if any members were found
if (empty($members)) {
    echo '<div class="rss-no-members">No members found.</div>';
} else {
    // Loop through each member
    foreach ($members as $member) {
        $user_id = $member->ID;

        // Get profile data
        $name = $member->display_name;
        // Don't get email for display
        $avatar = get_avatar($user_id, 200, '', $name, array('class' => 'rss-member-profile-image'));
        $bio = get_user_meta($user_id, 'description', true);
        $company = get_user_meta($user_id, 'company', true);
        $city = get_user_meta($user_id, 'city', true);
        $state = get_user_meta($user_id, 'state', true);
        $phone = get_user_meta($user_id, 'phone', true);
        $website = get_user_meta($user_id, 'user_url', true) ? get_user_meta($user_id, 'user_url', true) : $member->user_url;

        // Extract tagline from bio
        $tagline = '';
        if (!empty($bio)) {
            $first_period = strpos($bio, '.');
            $tagline = ($first_period !== false) ?
                substr($bio, 0, $first_period + 1) :
                substr($bio, 0, 100) . '...';
        }

        // Flag to check if user has any listings
        $has_listing = false;
        $listing = array(
            'id' => 0,
            'title' => '',
            'images' => array(),
            'description' => '',
            'permalink' => '',
            'price' => '',
            'address' => '',
            'preferred_lease_duration' => '',
            'features' => array(),
            'tags' => array(),
            'video_url' => '',
        );

        // Get user's properties
        if (function_exists('rem_get_user_properties')) {
            // Try the REM function to get all properties
            $properties = rem_get_user_properties($user_id);

            if ($show_debug) {
                echo '<div class="rss-debug-member">';
                echo "Properties for user {$name} (ID: {$user_id}): " . (is_array($properties) ? count($properties) : 'None/Error');
                echo '</div>';
            }

            // If we got properties
            if (!empty($properties) && is_array($properties)) {
                $has_listing = true;
                $property = $properties[0]; // Get first property
                $property_id = $property->ID;

                // Basic property details
                $listing['id'] = $property_id;
                $listing['title'] = $property->post_title;
                $listing['description'] = wp_trim_words($property->post_content, 30);
                $listing['permalink'] = get_permalink($property_id);

                // Get property featured image
                if (has_post_thumbnail($property_id)) {
                    $listing['images'][] = get_the_post_thumbnail_url($property_id, 'medium');
                }

                // Get additional property images
                $gallery_images = get_post_meta($property_id, 'rem_property_images', true);
                if (!empty($gallery_images) && is_array($gallery_images)) {
                    foreach ($gallery_images as $image_id) {
                        $image_url = wp_get_attachment_image_url($image_id, 'medium');
                        if ($image_url) {
                            $listing['images'][] = $image_url;
                        }
                    }
                }

                // Get property meta data
                $listing['price'] = get_post_meta($property_id, 'rem_property_price', true);
                $listing['address'] = get_post_meta($property_id, 'rem_property_address', true);
                $listing['preferred_lease_duration'] = get_post_meta($property_id, 'preferred_lease_duration', true);
                $listing['video_url'] = get_post_meta($property_id, 'rem_property_video', true);

                // Get features
                $features_string = get_post_meta($property_id, 'rem_property_features', true);
                if (!empty($features_string)) {
                    $listing['features'] = explode(',', $features_string);
                }

                // Get tags
                $terms = get_the_terms($property_id, 'rem_property_tag');
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $listing['tags'][] = $term->name;
                    }
                }
            } else {
                // For debugging - try to get information about the user's properties through other means
                if ($show_debug) {
                    // Alternative property check
                    $property_args = array(
                        'post_type' => 'rem_property',
                        'author' => $user_id,
                        'posts_per_page' => 1,
                    );
                    $property_query = new WP_Query($property_args);
                    echo '<div class="rss-debug-member">';
                    echo "Alternative property check for user {$name}: " . $property_query->post_count;
                    echo '</div>';
                }
            }
        } else {
            // REM function doesn't exist, use WP_Query directly
            $property_args = array(
                'post_type' => 'rem_property',
                'author' => $user_id,
                'posts_per_page' => 1,
            );
            $property_query = new WP_Query($property_args);

            if ($property_query->have_posts()) {
                $has_listing = true;
                $property_query->the_post();
                $property_id = get_the_ID();

                $listing['id'] = $property_id;
                $listing['title'] = get_the_title();
                $listing['description'] = wp_trim_words(get_the_content(), 30);
                $listing['permalink'] = get_permalink();

                // Get featured image
                if (has_post_thumbnail()) {
                    $listing['images'][] = get_the_post_thumbnail_url($property_id, 'medium');
                }

                // Get property meta
                $listing['price'] = get_post_meta($property_id, 'rem_property_price', true);
                $listing['address'] = get_post_meta($property_id, 'rem_property_address', true);
                $listing['preferred_lease_duration'] = get_post_meta($property_id, 'preferred_lease_duration', true);

                wp_reset_postdata();
            }
        }

        // Output member card with two-column layout
        ?>
        <div class="rss-member-item">
            <!-- Member Profile -->
            <div class="rss-member-profile">
                <div class="rss-member-avatar">
                    <?php echo $avatar; ?>
                </div>
                <h3 class="rss-member-name"><?php echo esc_html($name); ?></h3>

                <?php if (!empty($company)) : ?>
                <div class="rss-member-company"><?php echo esc_html($company); ?></div>
                <?php endif; ?>

                <?php if (!empty($tagline)) : ?>
                <div class="rss-member-tagline"><?php echo esc_html($tagline); ?></div>
                <?php endif; ?>

                <?php if (!empty($bio)) : ?>
                <div class="rss-member-bio">
                    <?php echo wpautop(esc_html($bio)); ?>
                </div>
                <?php endif; ?>

                <div class="rss-member-contact">
                    <?php if (!empty($city) || !empty($state)) : ?>
                    <div class="rss-member-location">
                        <i class="fa fa-map-marker"></i>
                        <?php echo esc_html(trim($city . ($city && $state ? ', ' : '') . $state)); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phone)) : ?>
                    <div class="rss-member-phone">
                        <i class="fa fa-phone"></i>
                        <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($website)) : ?>
                    <div class="rss-member-website">
                        <i class="fa fa-globe"></i>
                        <a href="<?php echo esc_url($website); ?>" target="_blank">Website</a>
                    </div>
                    <?php endif; ?>

                    <?php if (function_exists('get_author_posts_url')) : ?>
                    <div class="rss-member-profile-link">
                        <a href="<?php echo esc_url(get_author_posts_url($user_id)); ?>" class="rss-link-btn">
                            View Profile Page
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Business Listing -->
            <div class="rss-business-section">
                <?php if ($has_listing) : ?>
                    <h3 class="rss-business-title"><?php echo esc_html($listing['title']); ?></h3>

                    <?php if (!empty($listing['images'])) : ?>
                    <div class="rss-business-images">
                        <?php 
                        // Limit to only display one image (the primary one)
                        $primary_image = reset($listing['images']); 
                        ?>
                        <div class="rss-image-item rss-primary-image">
                            <img src="<?php echo esc_url($primary_image); ?>" 
                                 alt="<?php echo esc_attr($listing['title']); ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($listing['description'])) : ?>
                    <div class="rss-business-description">
                        <?php echo esc_html($listing['description']); ?>
                    </div>
                    <?php endif; ?>

                    <?php 
                    // Only show business details section if we have any data to display
                    $has_business_details = !empty($listing['preferred_lease_duration']) || 
                                           !empty($listing['price']) || 
                                           !empty($listing['address']);
                    
                    if ($has_business_details) : 
                    ?>
                    <div class="rss-business-details">
                        <?php if (!empty($listing['preferred_lease_duration'])) : ?>
                        <div class="rss-detail-item">
                            <span class="rss-label">Preferred Lease:</span>
                            <span class="rss-value"><?php echo esc_html($listing['preferred_lease_duration']); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($listing['price'])) : ?>
                        <div class="rss-detail-item">
                            <span class="rss-label">Price:</span>
                            <span class="rss-value">$<?php echo number_format((float)$listing['price']); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($listing['address'])) : ?>
                        <div class="rss-detail-item">
                            <span class="rss-label">Address:</span>
                            <span class="rss-value"><?php echo esc_html($listing['address']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($listing['tags'])) : ?>
                    <div class="rss-business-tags">
                        <span class="rss-label">Tags:</span>
                        <div class="rss-tags-list">
                            <?php foreach ($listing['tags'] as $tag) : ?>
                            <span class="rss-tag"><?php echo esc_html($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($listing['features'])) : ?>
                    <div class="rss-business-features">
                        <span class="rss-label">Features:</span>
                        <ul class="rss-features-list">
                            <?php foreach ($listing['features'] as $feature) : ?>
                            <li><?php echo esc_html(trim($feature)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($listing['video_url'])) : ?>
                    <div class="rss-business-video">
                        <a href="<?php echo esc_url($listing['video_url']); ?>" target="_blank" class="rss-video-btn">
                            <i class="fa fa-play-circle"></i> Watch Business Video
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="rss-business-footer">
                        <a href="<?php echo esc_url($listing['permalink']); ?>" class="rss-button">
                            View Full Listing
                        </a>
                    </div>
                <?php else : ?>
                    <div class="rss-no-listing">
                        <div class="rss-no-listing-icon">
                            <i class="fa fa-building"></i>
                        </div>
                        <h4>No Business Listing</h4>
                        <p>This member does not currently have any business listings.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

echo '</div>'; // End .rss-member-directory
?>

<style>
/* Member Directory Styles - Version 1.2 */
.rss-member-directory {
    max-width: 100%;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.rss-debug-info {
    background: #f5f5f5;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 4px solid #0073aa;
}

.rss-debug-member {
    background: #f9f9f9;
    padding: 5px 10px;
    margin-bottom: 5px;
    font-size: 12px;
    color: #666;
}

.rss-member-item {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 40px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.rss-member-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.rss-member-profile {
    flex: 1;
    min-width: 300px;
    padding: 25px;
    background: #f9f9f9;
    border-right: 1px solid #e0e0e0;
}

.rss-business-section {
    flex: 2;
    min-width: 350px;
    padding: 25px;
    position: relative;
}

.rss-member-avatar {
    text-align: center;
    margin-bottom: 20px;
}

/* Fixed avatar image styling */
.rss-member-profile-image {
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 200px !important; /* Force the width to be no more than 200px */
    height: auto !important;
    width: auto !important;
    display: block !important;
    margin: 0 auto !important;
}

.rss-member-name {
    margin: 0 0 10px;
    font-size: 24px;
    color: #333;
    text-align: center;
}

.rss-member-company {
    margin: 0 0 10px;
    font-size: 18px;
    font-weight: 600;
    color: #444;
    text-align: center;
}

.rss-member-tagline {
    margin: 15px 0;
    padding: 10px 0;
    border-top: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    color: #555;
    font-style: italic;
    text-align: center;
    font-size: 1.05em;
}

.rss-member-bio {
    margin: 20px 0;
    line-height: 1.6;
    color: #444;
}

.rss-member-contact {
    margin-top: 20px;
}

.rss-member-contact > div {
    margin: 10px 0;
}

.rss-member-contact i {
    width: 20px;
    text-align: center;
    margin-right: 10px;
    color: #0073aa;
}

.rss-member-profile-link {
    margin-top: 20px;
    text-align: center;
}

.rss-link-btn {
    display: inline-block;
    padding: 8px 16px;
    background: #0073aa;
    color: white !important;
    text-decoration: none !important;
    border-radius: 4px;
    font-size: 14px;
    transition: background 0.2s;
}

.rss-link-btn:hover {
    background: #005d8c;
}

.rss-business-title {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 24px;
    color: #333;
}

.rss-business-images {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}

.rss-image-item {
    flex: 1;
    min-width: 150px;
    max-width: 150px; /* Reduced from 200px */
    margin-bottom: 10px;
}

.rss-image-item img {
    width: 100%;
    height: auto;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.rss-primary-image {
    flex: 2;
    max-width: 150px; /* Reduced from 350px */
}

.rss-business-description {
    margin: 20px 0;
    line-height: 1.6;
    color: #444;
}

.rss-business-details {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.rss-detail-item {
    margin-bottom: 10px;
}

.rss-label {
    font-weight: 600;
    color: #333;
    margin-right: 5px;
}

.rss-business-tags {
    margin: 20px 0;
}

.rss-tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 8px;
}

.rss-tag {
    background: #f0f0f0;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 13px;
}

.rss-business-features {
    margin: 20px 0;
}

.rss-features-list {
    margin: 10px 0;
    padding-left: 25px;
}

.rss-features-list li {
    margin-bottom: 5px;
}

.rss-business-video {
    margin: 20px 0;
}

.rss-video-btn {
    display: inline-block;
    padding: 8px 16px;
    background: #f4f4f4;
    color: #333 !important;
    text-decoration: none !important;
    border-radius: 4px;
    transition: background 0.2s;
}

.rss-video-btn:hover {
    background: #e0e0e0;
}

.rss-business-footer {
    margin-top: 25px;
}

.rss-button {
    display: inline-block;
    padding: 10px 20px;
    background: #0073aa;
    color: white !important;
    text-decoration: none !important;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.rss-button:hover {
    background: #005d8c;
    transform: translateY(-2px);
}

.rss-no-listing {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 40px 20px;
    text-align: center;
    background: #f9f9f9;
    border-radius: 5px;
}

.rss-no-listing-icon {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 20px;
}

.rss-no-listing h4 {
    margin: 0 0 10px;
    color: #666;
}

.rss-no-listing p {
    color: #888;
}

.rss-no-members {
    padding: 30px;
    text-align: center;
    background: #f5f5f5;
    border-radius: 5px;
    color: #666;
}

@media (max-width: 768px) {
    .rss-member-item {
        flex-direction: column;
    }

    .rss-member-profile {
        border-right: none;
        border-bottom: 1px solid #e0e0e0;
    }

    .rss-business-images {
        justify-content: center;
    }

    .rss-primary-image {
        flex-basis: 100%;
        max-width: 150px; /* Reduced from 100% */
    }
}
</style>
