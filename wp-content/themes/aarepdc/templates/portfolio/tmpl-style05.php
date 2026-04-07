<div class="carousel-item col-xs-12 col-sm-6 col-md-4 <?php $this->entry_term_classes() ?>">

	<div class="carousel-item-inner">
		<div class="carousel-item-content">

			<article<?php echo liquid_helper()->html_attributes( $attributes ) ?>>
				<div class="lqd-pf-item-inner lqd-overlay">
		
					<div class="lqd-pf-img h-100 w-100 pos-rel overflow-hidden">
						<?php $this->entry_thumbnail(); ?>
						<span class="lqd-pf-overlay-bg lqd-overlay">
						</span>
					</div>
		
					<div class="lqd-pf-details lqd-overlay d-flex justify-content-end ps-4 pe-4 px-4">
						<div class="text-vertical ps-3 pl-3">
							<?php the_title( '<h2 class="mt-0 mb-0 h5">', '</h2>'  ); ?>
							<?php $this->entry_cats() ?>
						</div>
					</div>
		
					<?php $this->get_overlay_button(); ?>
		
				</div>
			</article>

		</div>
	</div>
	
</div>