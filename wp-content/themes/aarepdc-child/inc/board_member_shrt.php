<?php 
add_shortcode('aarep_dc_officers_shrt','aarep_dc_officers_shrt_sction');
function aarep_dc_officers_shrt_sction ($atts) { ob_start(); 
	$args = array(  
		'post_type' => 'board-member',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'id',
		'order' => 'desc',
		'id'=> '',
		'tax_query' => array(
         array(
        'taxonomy' => 'board-member-category',
                            'field' => 'slug', //can be set to ID
                            'terms' => 'aarep-dc-officers', //if field is ID you can reference by cat/term number
                          )
                    )
		
	);
 $cats = get_categories( $args );
?>
<?php
    $team_data_Query = new WP_Query( $args );
    if( $team_data_Query->have_posts() ) ?>	

<?php 
	while( $team_data_Query->have_posts() ) : $team_data_Query->the_post();  ?>
<div class="vc_col-sm-3 bod_member">
<?php
            $thumbnail_url = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full', true ); 
            
            if(empty($thumbnail_url))
            {
            	$bio_img = site_url()."/wp-content/uploads/2022/12/bod_placeholder.png";
            }
            else
            {
            	$bio_img = $thumbnail_url[0];
            }

			?>
	<div class="bod_member_image"><a class="bod_bio_image"  style="background-image: url(<?php echo $bio_img; ?>)" href="<?php the_field("linkedin_url");?>" target="_blank" >
		<div class="overlay_box">
			<div class="overlay_box_inner">
				<p><?php the_field("designation");?></p>
				<p><?php the_field("company_name");?></p>
			</div>
		</div>
	</a></div>
	<div class="bod_member_name"><?php the_title();?></div>
	<div class="overlay00">
		<div class="bod_linked_in"><a href="<?php the_field("linkedin_url");?>" class="linked_in_link docs-creator"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a></div>
	</div>
</div>
<?php 
	endwhile; ?>	

<?php 
 wp_reset_query();
 return ob_get_clean();
}



add_shortcode('aarep_current_bod_members_shrt','aarep_current_bod_members_shrt_sction');
function aarep_current_bod_members_shrt_sction ($atts) { ob_start(); 
	$args = array(  
		'post_type' => 'board-member',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'id',
		'order' => 'desc',
		'id'=> '',
		'tax_query' => array(
         array(
        'taxonomy' => 'board-member-category',
                            'field' => 'slug', //can be set to ID
                            'terms' => 'aarep-current-board-member', //if field is ID you can reference by cat/term number
                          )
                    )
		
	);
 $cats = get_categories( $args );
?>
<?php
    $current_team_data_Query = new WP_Query( $args );
    if( $current_team_data_Query->have_posts() ) ?>	

<?php 
	while( $current_team_data_Query->have_posts() ) : $current_team_data_Query->the_post();  ?>

<div class="vc_col-sm-3 bod_member">
<?php
            $thumbnail_url = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full', true );  
            if(empty($thumbnail_url))
            {
            	$bio_img = site_url()."/wp-content/uploads/2022/12/bod_placeholder.png";
            }
            else
            {
            	$bio_img = $thumbnail_url[0];
            }             
			?>
	<div class="bod_member_image"><a class="bod_bio_image"  style="background-image: url(<?php echo $bio_img; ?>)" href="<?php the_field("linkedin_url");?>" target="_blank" >		
		<div class="overlay_box">
			<div class="overlay_box_inner">
				<p><?php the_field("designation");?></p>
				<p><?php the_field("company_name");?></p>
			</div>
		</div>
	</a></div>
	<div class="bod_member_name"><?php the_title();?></div>
	<div class="overlay00">
		<div class="bod_linked_in"><a href="<?php the_field("linkedin_url");?>" class="linked_in_link docs-creator"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a></div>
	</div>
</div>

<?php 
	endwhile; ?>	

<?php 
 wp_reset_query();
 return ob_get_clean();
}