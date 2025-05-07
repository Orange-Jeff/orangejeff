<?php // v1.6 ?>
<style>
/* Registration title styling */
h2.rem-registration-title {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
    color: #333;
    padding: 10px;
    border-bottom: 2px solid #eee;
}
</style>
<div class="ich-settings-main-wrap">
	<form id="agent_login">
		<section id="rem-agent-page">
			<div class="row">
				<div class="col-sm-12">
					<?php
						global $rem_ob;
						$field_tabs = rem_get_agent_fields_tabs();
						$agent_fields = $rem_ob->get_agent_fields();

						// Determine member type based on page slug
						$current_page = get_post(get_the_ID());
						$page_slug = $current_page ? $current_page->post_name : ''; // Check if $current_page is valid
						$profile_type = 'MEMBER'; // Default type
						if ($page_slug === 'host-registration') {
							$profile_type = 'HOST'; // Use HOST for host registration
						} elseif ($page_slug === 'registration') {
							$profile_type = 'MEMBER'; // Use MEMBER for member registration
						}

						// Add the title that displays profile type
						echo '<h2 class="rem-registration-title">' . esc_html($profile_type) . ' Registration</h2>';

						$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
						if ($wpml_current_language) {
							echo '<input type="hidden" name="wpml_user_email_language" value="'.$wpml_current_language.'">';
						}
						// Set the profiles hidden field dynamically - THIS IS THE HIDDEN FIELD
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
				        // This loop generates the section headers based on the $field_tabs array
				        foreach ($field_tabs as $tab_name => $tab_title) {
							if (in_array($tab_name, $valid_tabs)) { ?>
								<div class="tab-wrap-<?php echo esc_attr($tab_name); ?>"></div>
								<div class="section-title line-style no-margin <?php echo esc_attr($tab_name); ?>">
									<h3 class="title"><?php echo esc_attr($tab_title); // This variable holds "Social Profiles", "Skills", etc. ?></h3>
								</div>
								<ul class="profile create">
									<?php foreach ($agent_fields as $field) {
										// Basic conditions: field is in the current tab and meant for registration display
										if (isset($field['tab']) && $field['tab'] == $tab_name && isset($field['display']) && in_array('register', $field['display'])) {

											// Skip rendering if this field is the 'profiles' field itself (already handled by hidden input)
											$field_key = isset($field['key']) ? $field['key'] : (isset($field['id']) ? $field['id'] : '');
											if ($field_key === 'profiles') {
												continue; // Skip to next field
											}

											// Conditional Rendering Logic based on $profile_type
											$render_field = false;
											// Example: Assume a field property 'show_for' exists, containing 'HOST', 'MEMBER', or 'BOTH'/'ALL'
											$show_for = isset($field['show_for']) ? strtoupper($field['show_for']) : 'BOTH'; // Default to show for both

											if ($show_for === 'BOTH' || $show_for === 'ALL') {
												$render_field = true;
											} elseif ($show_for === 'HOST' && $profile_type === 'HOST') {
												$render_field = true;
											} elseif ($show_for === 'MEMBER' && $profile_type === 'MEMBER') {
												$render_field = true;
											}

											// Render the field if the conditions are met
											if ($render_field) {
												$this->render_registration_field($field);
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
						<button class="btn btn-default signin-button" type="submit"><i class="far fa-hand-point-right"></i> <?php esc_attr_e( 'Sign up', 'real-estate-manager' ); ?></button>
					</div>
			</div>
		</section>
	</form>
</div>
