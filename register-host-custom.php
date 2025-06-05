<?php // v1.1 - Dedicated Host Registration Template with updated field customizations ?>
<div class="ich-settings-main-wrap">
	<form id="agent_login">
		<section id="rem-agent-page">
			<div class="row">
				<div class="col-sm-12">
					<?php
						global $rem_ob;
						$field_tabs = rem_get_agent_fields_tabs();
						$agent_fields = $rem_ob->get_agent_fields();

						// Hard-coded host profile type
						$profile_type = 'HOST';

						// WPML support
						$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
						if ($wpml_current_language) {
							echo '<input type="hidden" name="wpml_user_email_language" value="'.$wpml_current_language.'">';
						}

						// Set the profiles hidden field to HOST
						echo '<input type="hidden" name="profiles" value="HOST">';

						// Custom title and description for hosts
						echo '<div class="section-title line-style welcome-message">';
						echo '<h3>Host Registration</h3>';
						echo '<p>Register as a Host to list your retail spaces for our members.</p>';
						echo '</div>';

						// Define fields to hide for hosts
						$hidden_fields = array(
							'preferred_lease_duration', // Preferred leave duration - hidden for host
							// Add more fields to hide for hosts as needed
						);

						// Define field label overrides for hosts
						$field_overrides = array(
							'property_name' => array(
								'label' => 'Property Name',
								'placeholder' => 'Enter your property name',
								'description' => 'The name of your retail property'
							),
							'property_type' => array(
								'label' => 'Space Type',
								'placeholder' => 'Type of retail space available',
								'description' => 'What kind of retail space are you offering?'
							),
							'price' => array(
								'label' => 'Monthly Rate',
								'placeholder' => 'Monthly rental rate',
								'description' => 'How much do you charge per month?'
							),
							'hours_days_operation' => array(
								'label' => 'Hours Available',
								'placeholder' => 'When the space is available',
								'description' => 'What hours is your space available for use?'
							),
							'beds' => array(
								'label' => 'Maximum Occupancy',
								'placeholder' => 'Max number of occupants',
								'description' => 'Maximum number of people allowed in the space'
							),
							'baths' => array(
								'label' => 'Available Amenities',
								'placeholder' => 'Amenities offered',
								'description' => 'List the amenities available with this space'
							),
							'area' => array(
								'label' => 'Space Size',
								'placeholder' => 'Square footage available',
								'description' => 'Total square footage of the retail space'
							)
							// Add more field overrides as needed
						);

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

				        // Generate the section headers based on the $field_tabs array
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

											// Skip rendering if this field is the 'profiles' field itself (already handled by hidden input)
											if ($field_key === 'profiles') {
												continue; // Skip to next field
											}

											// Skip rendering if this field should be hidden for hosts
											if (in_array($field_key, $hidden_fields)) {
												continue; // Skip this field
											}

											// Override field properties if defined in our overrides array
											if (isset($field_overrides[$field_key])) {
												$override = $field_overrides[$field_key];

												if (isset($override['label'])) {
													$field['label'] = $override['label'];
												}

												if (isset($override['placeholder'])) {
													$field['placeholder'] = $override['placeholder'];
												}

												if (isset($override['description'])) {
													$field['description'] = $override['description'];
												}
											}

											// Render the field with host-specific modifications
											$rem_ob->render_registration_field($field);
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
							<h3 class="title"><?php esc_attr_e( 'Property Location', 'real-estate-manager' ); ?></h3>
						</div>
						<ul class="profile create">
							<?php if (rem_get_option('use_map_from', 'leaflet') == 'google_maps') { ?>
								<input type="text" class="form-control" id="search-map" placeholder="<?php esc_attr_e( 'Type to Search...', 'real-estate-manager' ); ?>">
							<?php } ?>
								<div id="map-canvas" style="height: 300px"></div>
							<div id="position"><i class="fa fa-map-marker-alt"></i> <?php esc_attr_e( 'Drag the pin to your property location', 'real-estate-manager' ); ?></div>
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
				<?php do_action( 'rem_register_agent_before_register_button' ); ?>
					<div class="col-sm-12">
						<button class="btn btn-default signin-button" type="submit"><i class="far fa-hand-point-right"></i> <?php esc_attr_e( 'Register as Host', 'real-estate-manager' ); ?></button>
					</div>
			</div>
		</section>
	</form>
</div>
