<?php // v1.1 - Flexible Registration Template for REM ?>
<div class="ich-settings-main-wrap">
	<form id="agent_login">
		<section id="rem-agent-page">
			<div class="row">
				<div class="col-sm-12">
					<?php
						global $rem_ob, $rem_flexible_register_atts;
						$field_tabs = rem_get_agent_fields_tabs();
						$agent_fields = $rem_ob->get_agent_fields();

						// Get profile type from shortcode attributes
						$profile_type = isset($rem_flexible_register_atts['type']) ?
							strtoupper(trim($rem_flexible_register_atts['type'])) : 'MEMBER';

						// Set button text from shortcode attributes
						$button_text = !empty($rem_flexible_register_atts['button_text']) ?
							$rem_flexible_register_atts['button_text'] :
							($profile_type == 'HOST' ?
								__('Register as Host', 'real-estate-manager') :
								__('Register as Member', 'real-estate-manager'));

						// Get optional required text from shortcode attributes
						$required_text = isset($rem_flexible_register_atts['required_text']) ?
							$rem_flexible_register_atts['required_text'] : '';

                        // Get title and description from shortcode attributes
						$title = isset($rem_flexible_register_atts['title']) ? $rem_flexible_register_atts['title'] : '';
						$description = isset($rem_flexible_register_atts['description']) ? $rem_flexible_register_atts['description'] : '';

						// Get field customization arrays
						$field_defaults = isset($rem_flexible_register_atts['field_defaults']) ? $rem_flexible_register_atts['field_defaults'] : array();
						$field_labels = isset($rem_flexible_register_atts['field_labels']) ? $rem_flexible_register_atts['field_labels'] : array();
						$field_placeholders = isset($rem_flexible_register_atts['field_placeholders']) ? $rem_flexible_register_atts['field_placeholders'] : array();
						$field_descriptions = isset($rem_flexible_register_atts['field_descriptions']) ? $rem_flexible_register_atts['field_descriptions'] : array();
						$hide_fields = isset($rem_flexible_register_atts['hide_fields']) ? $rem_flexible_register_atts['hide_fields'] : array();
						$show_fields = isset($rem_flexible_register_atts['show_fields']) ? $rem_flexible_register_atts['show_fields'] : array();

						// Add WPML support
						$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
						if ($wpml_current_language) {
							echo '<input type="hidden" name="wpml_user_email_language" value="'.$wpml_current_language.'">';
						}

						// Set the profiles hidden field dynamically
						echo '<input type="hidden" name="profiles" value="' . esc_attr($profile_type) . '">';

						// checking for the tabs which have fields
						$valid_tabs = array();
				        foreach ($field_tabs as $tab_key => $tab_title) {
				            foreach ($agent_fields as $field) {
				                $field_tab = (isset($field['tab'])) ? $field['tab'] : '' ;
				                if ($field_tab == $tab_key && !in_array($field_tab, $valid_tabs)) {
									$valid_tabs[] = $field_tab;
								}
				            }
				        }

						// Display custom title and description if provided
						if ($title || $description) {
							echo '<div class="section-title line-style welcome-message">';
							if ($title) {
								echo '<h3>' . esc_html($title) . '</h3>';
							}
							if ($description) {
								echo '<p>' . esc_html($description) . '</p>';
							}
							echo '</div>';
						} else {
							// Default welcome messages based on profile type
							echo '<div class="section-title line-style welcome-message">';
							echo '<h3>' . ($profile_type == 'HOST' ? __('Host Registration', 'real-estate-manager') : __('Member Registration', 'real-estate-manager')) . '</h3>';
							echo '<p>' . ($profile_type == 'HOST' ?
								__('Register as a Host to list your retail spaces.', 'real-estate-manager') :
								__('Register as a Member to find retail spaces.', 'real-estate-manager')) . '</p>';
							echo '</div>';
						}

				        // This loop generates the section headers based on the $field_tabs array
				        foreach ($field_tabs as $tab_name => $tab_title) {
							if (in_array($tab_name, $valid_tabs)) { ?>
								<div class="tab-wrap-<?php echo esc_attr($tab_name); ?>"></div>
								<div class="section-title line-style no-margin <?php echo esc_attr($tab_name); ?>">
									<h3 class="title"><?php echo esc_attr($tab_title); ?></h3>
								</div>
								<ul class="profile create">
									<?php foreach ($agent_fields as $field) {
										// Basic conditions: field is in the current tab and meant for registration display
										if (isset($field['tab']) && $field['tab'] == $tab_name && isset($field['display']) && in_array('register', $field['display'])) {
											// Get field identifiers
											$field_key = isset($field['key']) ? $field['key'] : (isset($field['id']) ? $field['id'] : '');
											$field_name = isset($field['name']) ? $field['name'] : $field_key;

											// Skip rendering if this field is the 'profiles' field itself (already handled by hidden input)
											if ($field_key === 'profiles') {
												continue; // Skip to next field
											}

											// Check if we should hide this field specifically
											if (!empty($hide_fields) && in_array($field_key, $hide_fields)) {
												continue; // Skip this field
											}

											// Check if we're filtering to show only specific fields
											if (!empty($show_fields) && !in_array($field_key, $show_fields)) {
												continue; // Skip this field
											}

											// Apply custom field labels if specified
											if (!empty($field_labels) && isset($field_labels[$field_key])) {
												$field['label'] = $field_labels[$field_key];
											}

											// Apply custom field placeholders if specified
											if (!empty($field_placeholders) && isset($field_placeholders[$field_key])) {
												$field['placeholder'] = $field_placeholders[$field_key];
											}

											// Apply custom field descriptions if specified
											if (!empty($field_descriptions) && isset($field_descriptions[$field_key])) {
												$field['description'] = $field_descriptions[$field_key];
											}

											// Apply default values if specified
											if (!empty($field_defaults) && isset($field_defaults[$field_key])) {
												$field['value'] = $field_defaults[$field_key];
											}

											// Conditional Rendering Logic based on $profile_type
											$render_field = true;

											// Check if field has show_for property that restricts display
											if (isset($field['show_for'])) {
												$show_for = strtoupper(trim($field['show_for']));

												if ($show_for === 'HOST' && $profile_type !== 'HOST') {
													$render_field = false;
												} elseif ($show_for === 'MEMBER' && $profile_type !== 'MEMBER') {
													$render_field = false;
												}
											}

											// Custom placeholders and labels based on profile type
											if ($render_field) {
												// Modify field for specific profile types if needed (if not already overridden by shortcode attributes)
												if ($profile_type == 'HOST') {
													// Only apply if we don't have a custom label already
													if (!isset($field_labels[$field_key]) && isset($field['host_label'])) {
														$field['label'] = $field['host_label'];
													}
													// Only apply if we don't have a custom placeholder already
													if (!isset($field_placeholders[$field_key]) && isset($field['host_placeholder'])) {
														$field['placeholder'] = $field['host_placeholder'];
													}
													// Only apply if we don't have a custom description already
													if (!isset($field_descriptions[$field_key]) && isset($field['host_description'])) {
														$field['description'] = $field['host_description'];
													}
												} else {
													// Only apply if we don't have a custom label already
													if (!isset($field_labels[$field_key]) && isset($field['member_label'])) {
														$field['label'] = $field['member_label'];
													}
													// Only apply if we don't have a custom placeholder already
													if (!isset($field_placeholders[$field_key]) && isset($field['member_placeholder'])) {
														$field['placeholder'] = $field['member_placeholder'];
													}
													// Only apply if we don't have a custom description already
													if (!isset($field_descriptions[$field_key]) && isset($field['member_description'])) {
														$field['description'] = $field['member_description'];
													}
												}

												// Render the field with modifications
												$rem_ob->render_registration_field($field);
											}
										}
									} ?>
								</ul>
								<br>
							<?php } ?>
						<?php } ?>
					<?php if (rem_get_option('agent_location') == 'enable') { ?>
						<input type="hidden" id="agent_longitude" name="agent_longitude">
						<input type="hidden" id="agent_latitude" name="agent_latitude">
						<div class="tab-wrap-location"></div>
						<div class="section-title line-style no-margin location">
							<h3 class="title"><?php esc_attr_e( 'Location', 'real-estate-manager' ); ?></h3>
						</div>
						<ul class="profile create">
							<?php if (rem_get_option('use_map_from', 'leaflet') == 'google_maps') { ?>
								<input type="text" class="form-control" id="search-map" placeholder="<?php esc_attr_e( 'Type to Search...', 'real-estate-manager' ); ?>">
							<?php } ?>
								<div id="map-canvas" style="height: 300px"></div>
							<div id="position"><i class="fa fa-map-marker-alt"></i> <?php esc_attr_e( 'Drag the pin to the location on the map', 'real-estate-manager' ); ?></div>
						</ul>
						<br>
					<?php } ?>
				</div>
				<?php if (rem_get_option('captcha_on_registration') == 'on') { ?>
					<script src='https://www.google.com/recaptcha/api.js'></script>
					<div class="col-sm-12">
						<div class="g-recaptcha" data-sitekey="<?php echo rem_get_option('captcha_site_key', '6LcDhUQUAAAAAFAsfyTUPCwDIyXIUqvJiVjim2E9'); ?>"></div>
					</div>
				<?php } ?>
				<?php if ($required_text != '') { ?>
					<div class="col-sm-12">
						<label><input type="checkbox" required> <?php echo wp_kses_post($required_text); ?></label>
					</div>
				<?php } ?>
				<?php do_action( 'rem_register_agent_before_register_button' ); ?>
					<div class="col-sm-12">
						<button class="btn btn-default signin-button" type="submit"><i class="far fa-hand-point-right"></i> <?php echo esc_attr($button_text); ?></button>
					</div>
			</div>
		</section>
	</form>
</div>

<?php if (isset($rem_flexible_register_atts['redirect']) && !empty($rem_flexible_register_atts['redirect'])) : ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Store the redirect URL
    var redirectURL = "<?php echo esc_js($rem_flexible_register_atts['redirect']); ?>";

    // Add success handler to redirect after successful registration
    $(document).on('rem_agent_register_success', function(event, response) {
        if (redirectURL) {
            window.location.href = redirectURL;
        }
    });
});
</script>
<?php endif; ?>
