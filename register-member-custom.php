<?php // v1.1 - Dedicated Member Registration Template with updated field customizations ?>
<div class="ich-settings-main-wrap">
	<form id="agent_login">
		<section id="rem-agent-page">
			<div class="row">
				<div class="col-sm-12">
					<?php
						global $rem_ob;
						$field_tabs = rem_get_agent_fields_tabs();
						$agent_fields = $rem_ob->get_agent_fields();

						// Hard-coded member profile type
						$profile_type = 'MEMBER';

						// WPML support
						$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
						if ($wpml_current_language) {
							echo '<input type="hidden" name="wpml_user_email_language" value="'.$wpml_current_language.'">';
						}

						// Set the profiles hidden field to MEMBER
						echo '<input type="hidden" name="profiles" value="MEMBER">';

						// Custom title and description for members
						echo '<div class="section-title line-style welcome-message">';
						echo '<h3>Member Registration</h3>';
						echo '<p>Register as a Member to find retail spaces that match your business needs.</p>';
						echo '</div>';

						// Define fields to hide for members
						$hidden_fields = array(
							'agent_longitude',
							'agent_latitude',
							'agent_address',
							'property_status', // Status - hidden
							'property_id',
							'partner_business_type', // Partner Business Type - hidden
							'lease_term', // Lease Term - hidden
							'preferred_lease_duration', // Preferred leave duration - hidden for member
							'baths', // Bathrooms - hidden
							'size_shared_space', // Size of shared space - hidden
							'beds' // Rooms - hidden
							// Add more fields to hide as needed
						);

						// Define field label overrides for members
						$field_overrides = array(
							'property_name' => array(
								'label' => 'Business Name',
								'placeholder' => 'Enter your business name',
								'description' => 'The name of your business'
							),
							'property_type' => array(
								'label' => 'Preferred Property Type',
								'placeholder' => 'Type of space you prefer',
								'description' => 'What kind of space are you looking for?'
							),
							'price' => array(
								'label' => 'Budget Range',
								'placeholder' => 'Your monthly budget',
								'description' => 'Your expected budget for retail space'
							),
							'hours_days_operation' => array(
								'label' => 'Hours Available',
								'placeholder' => 'When you need the space',
								'description' => 'What hours are you available to use the space?'
							),
							'area' => array(
								'label' => 'Space Needed',
								'placeholder' => 'Square footage needed',
								'description' => 'How much space does your business require?'
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

											// Skip rendering if this field should be hidden for members
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

											// Render the field with member-specific modifications
											$rem_ob->render_registration_field($field);
										}
									} ?>
								</ul>
								<br>
							<?php } ?>
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
						<button class="btn btn-default signin-button" type="submit"><i class="far fa-hand-point-right"></i> <?php esc_attr_e( 'Join as Member', 'real-estate-manager' ); ?></button>
					</div>
			</div>
		</section>
	</form>
</div>
