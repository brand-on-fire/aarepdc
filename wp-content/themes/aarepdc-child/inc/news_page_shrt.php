<?php 
add_shortcode('news_page_shrt','news_page_shrt_sction');
function news_page_shrt_sction ($atts) { ob_start(); 
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	$args = array(  
		'post_type' => 'post',
		'post_status' => 'publish',
		'posts_per_page' => 4,
		'orderby' => 'id',
		'order' => 'desc',
		'id'=> '',
		'paged' => $paged,  
	  );
?>
<?php
  $blog_page_shr = new WP_Query( $args );
  if( $blog_page_shr->have_posts() ) {
    while( $blog_page_shr->have_posts() ) { $blog_page_shr->the_post();
            
                                             $attachment_id = get_post_thumbnail_id(); 
                                             $image_url = wp_get_attachment_image_src( $attachment_id, ''  ); ?>
	<div class="vc_col-sm-6 news_block">
		<div class="news_title"><?php the_date('F j, Y'); ?></div>
		<div class="news_info"><?php the_title();?></div>
		<div class="news_details"><?php the_field("news_short_description");?></div>
		<div class="news_button"><a href="<?php echo the_permalink();?>" class="site_button">Read More</a></div>
	</div>	
     
	<?php  } ?>
  <div class="pagination news_pagination">
   <?php 
   
   $total_pages = $blog_page_shr->max_num_pages;

    if ($total_pages > 1){

        $current_page = max(1, get_query_var('paged'));

        echo paginate_links(array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => '/page/%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text'    => __('« prev'),
            'next_text'    => __('next »'),
        ));
    }
?>
</div>
<?php    }
  ?>

<?php wp_reset_query();
  return ob_get_clean();
}