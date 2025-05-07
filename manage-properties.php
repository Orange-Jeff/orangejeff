<div class="ich-settings-main-wrap">
	<input type="hidden" class="rem-ajax-url" value="<?php echo admin_url( 'admin-ajax.php' ); ?>">
	<div class="row">
		<div class="col-sm-12">
			<div class="row" style="margin-bottom:25px;">
				<div class="col-sm-3">
				<form action="#" method="GET">
					<select name="sort_by" class="form-control" onchange="this.form.submit()">
						<option value="all"><?php esc_attr_e( 'Display All', 'real-estate-manager' ); ?></option>
						<option value="publish" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'publish') ? 'selected' : '' ; ?>><?php esc_attr_e( 'Only Published', 'real-estate-manager' ); ?></option>
						<option value="pending" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'pending') ? 'selected' : '' ; ?>><?php esc_attr_e( 'Only Pending', 'real-estate-manager' ); ?></option>
						<option value="draft" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'draft') ? 'selected' : '' ; ?>><?php esc_attr_e( 'Only Draft', 'real-estate-manager' ); ?></option>
					</select>
					<input type="hidden" value="<?php echo (isset($_GET['rem_search_query'])) ? $_GET['rem_search_query'] : '' ; ?>" name="rem_search_query">
				</form>
				</div>
				<div class="col-sm-5 search-area">
				    	<form action="" method="GET">
						<input type="hidden" value="<?php echo (isset($_GET['sort_by'])) ? $_GET['sort_by'] : '' ; ?>" name="sort_by">
				    <div class="input-group">
					      <input type="text" value="<?php echo (isset($_GET['rem_search_query'])) ? $_GET['rem_search_query'] : '' ; ?>" name="rem_search_query" class="form-control" placeholder="<?php esc_attr_e( 'Search for...', 'real-estate-manager' ); ?>">
					      <span class="input-group-btn">
					        <button class="btn btn-default" type="submit"><?php esc_attr_e( 'Search', 'real-estate-manager' ); ?></button>
					      </span>
				    </div><!-- /input-group -->
				    	</form>
				</div>
				<div class="col-sm-4 text-right">
					<button class="btn btn-primary rem-publish-properties"><?php esc_attr_e( 'Publish', 'real-estate-manager' ); ?></button>
					<button class="btn btn-warning rem-draft-properties"><?php esc_attr_e( 'Unpublish', 'real-estate-manager' ); ?></button>
				</div>
			</div>
		</div>
	</div>
<div id="user-profile">
	<div class="table-responsive property-list">
		<table class="table-striped table-hover">
		  <thead>
			<tr>
				<th><input type="checkbox" class="check-all-cbs"></th>
				<th><?php esc_attr_e( 'Thumbnail', 'real-estate-manager' ); ?></th>
				<th><?php esc_attr_e( 'Title', 'real-estate-manager' ); ?></th>
				<th class="hidden-xs"><?php esc_attr_e( 'Type', 'real-estate-manager' ); ?></th>
				<th class="hidden-xs"><?php esc_attr_e( 'Purpose', 'real-estate-manager' ); ?></th>
				<th><?php esc_attr_e( 'Status', 'real-estate-manager' ); ?></th>
				<th><?php esc_attr_e( 'Agent', 'real-estate-manager' ); ?></th>
				<th><?php esc_attr_e( 'Actions', 'real-estate-manager' ); ?></th>
			</tr>
		  </thead>
		  <tbody>
			<?php
				if (isset($_GET['sort_by']) && $_GET['sort_by'] != '') {
					$statuses = array($_GET['sort_by']);
				} else {
					$statuses = array( 'pending', 'draft', 'future', 'publish' );
				}
				$current_user_data = wp_get_current_user();
				$args = array(
					'post_type' => 'rem_property',
					'posts_per_page' => -1,
					'post_status' => $statuses
				);
				if (isset($_GET['rem_search_query'])) {
					$args['s'] = $_GET['rem_search_query'];
				}
		    	if (is_front_page()) {
		    		$paged = ( get_query_var('page') ) ? get_query_var('page') : 1;
		    	} else {
					$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
		    	}
				$args['paged'] = $paged;

				$myproperties = new WP_Query( $args );
				if( $myproperties->have_posts() ){
					while( $myproperties->have_posts() ){
						$myproperties->the_post(); ?>
							<tr>
								<td class="id-cb-wrap"><input type="checkbox" value="<?php echo get_the_id(); ?>" class="action-cb"></td>
								<td class="img-wrap">
									<?php do_action( 'rem_property_picture', get_the_id(), 'thumbnail' ); ?>
								</td>

								<td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <?php echo get_post_meta(get_the_id(),'rem_property_address', true); ?></td>
								<td class="hidden-xs"><?php echo ucfirst(get_post_meta(get_the_id(),'rem_property_type', true )); ?></td>
								<td class="hidden-xs"><?php echo ucfirst(get_post_meta(get_the_id(),'rem_property_purpose', true )); ?></td>
								<td>
									<?php
										$p_status = get_post_status(get_the_id());
										$status_class = ($p_status == 'publish') ? 'label-success' : 'label-info' ;
									?>
									<span class="label <?php echo esc_attr($status_class); ?>"><?php esc_attr_e( ucfirst($p_status), 'real-estate-manager' ); ?></span>
								</td>
								<td><?php echo the_author_posts_link(); ?></a></td>
								<td>
									<a target="_blank" href="<?php the_permalink(); ?>" class="btn btn-info btn-sm" title="<?php esc_attr_e( 'Preview', 'real-estate-manager' ); ?>">
										<i class="fas fa-eye"></i>
									</a>
									<?php
									// Add single property publish/unpublish button based on current status
									$p_status = get_post_status(get_the_id());
									if ($p_status == 'publish') { ?>
										<button class="btn btn-warning btn-sm single-property-status" data-pid="<?php echo get_the_id(); ?>" data-status="draft" title="<?php esc_attr_e( 'Unpublish', 'real-estate-manager' ); ?>">
											<i class="fas fa-eye-slash"></i>
										</button>
									<?php } else { ?>
										<button class="btn btn-success btn-sm single-property-status" data-pid="<?php echo get_the_id(); ?>" data-status="publish" title="<?php esc_attr_e( 'Publish', 'real-estate-manager' ); ?>">
											<i class="fas fa-check-circle"></i>
										</button>
									<?php } ?>
									<a class="btn btn-danger btn-sm delete-property" data-pid="<?php echo get_the_id(); ?>" href="#" title="<?php esc_attr_e( 'Delete', 'real-estate-manager' ); ?>">
										<i class="fa fa-trash"></i>
									</a>
								</td>
							</tr>
						<?php
					}
					wp_reset_postdata();
				}
			?>
		  </tbody>
		</table>
	</div>
</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle Check all checkbox
    $('.check-all-cbs').on('change', function() {
        $('.action-cb').prop('checked', $(this).prop('checked'));
    });

    // Handle Publish button
    $('.rem-publish-properties').on('click', function() {
        var selectedIds = [];
        $('.action-cb:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one property to publish');
            return;
        }

        $.ajax({
            url: $('.rem-ajax-url').val(),
            type: 'POST',
            data: {
                action: 'rem_change_property_status',
                property_ids: selectedIds,
                status: 'publish',
                security: '<?php echo wp_create_nonce("rem_property_status_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Properties published successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    });

    // Handle Unpublish button
    $('.rem-draft-properties').on('click', function() {
        var selectedIds = [];
        $('.action-cb:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one property to unpublish');
            return;
        }

        $.ajax({
            url: $('.rem-ajax-url').val(),
            type: 'POST',
            data: {
                action: 'rem_change_property_status',
                property_ids: selectedIds,
                status: 'draft',
                security: '<?php echo wp_create_nonce("rem_property_status_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Properties unpublished successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    });

    // Handle single property status change button
    $('.single-property-status').on('click', function() {
        var propertyId = $(this).data('pid');
        var status = $(this).data('status');

        $.ajax({
            url: $('.rem-ajax-url').val(),
            type: 'POST',
            data: {
                action: 'rem_change_property_status',
                property_ids: [propertyId],
                status: status,
                security: '<?php echo wp_create_nonce("rem_property_status_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    });

    // Handle Delete property
    $('.delete-property').on('click', function(e) {
        e.preventDefault();
        var propertyId = $(this).data('pid');

        if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
            $.ajax({
                url: $('.rem-ajax-url').val(),
                type: 'POST',
                data: {
                    action: 'rem_delete_property',
                    property_id: propertyId,
                    security: '<?php echo wp_create_nonce("rem_delete_property_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Property deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Server error. Please try again.');
                }
            });
        }
    });
});
</script>
