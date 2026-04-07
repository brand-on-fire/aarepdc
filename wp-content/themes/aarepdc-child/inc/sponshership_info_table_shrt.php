<?php	
add_shortcode("sponshership_info_table_shrt","sponshership_info_table_shrt_function");
	function sponshership_info_table_shrt_function ($atts) { ob_start(); 

		if( have_rows('sponsorship_data') ):
			    while( have_rows('sponsorship_data') ) : the_row(); ?>
			        <div class="vc_col-sm-3 spon_price_box">
						<div class="spon_price_box_price_content">
							<div class="spon_price_box_price">$<?php echo number_format( get_sub_field('sponsorship_price') ); ?></div>
							<div class="spon_price_box_title"><?php echo get_sub_field('sponsorship_title'); ?></div>
							<div class="spon_price_box_desc"><?php echo get_sub_field('sponsorship_description'); ?></div>
							<div class="spon_price_box_button"><a href="<?php echo site_url(); ?>/sponsorship-information/?price=<?php echo get_sub_field('sponsorship_price'); ?>&type=<?php echo get_sub_field('sponsorship_title'); ?>" class="site_button">Buy Now</a></div>
						</div>	
					</div>
			    <?php    
			    endwhile;
		else : ?>
				<h1>No Sponsorship Available</h1>
		<?php		
		endif;
	return ob_get_clean();
}

