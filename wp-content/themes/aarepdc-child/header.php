<?php
/**
 * The template for displaying the header
 *
 * @package Hub theme
 */

?><!DOCTYPE html>
<html <?php language_attributes( 'html' ); ?>>
<head <?php liquid_helper()->attr( 'head' ); ?>>

	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>

	<script type="text/javascript">
		function showDetails(animal) {
		  var animalType = animal.getAttribute("data-animal-type");
		  alert("The " + animal.innerHTML + " is a " + animalType + ".");
		}
	</script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<!-- <script src="https://code.jquery.com/jquery-3.6.0.js"></script> -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
</head>

<body <?php body_class(); ?> <?php liquid_helper()->attr( 'body' ); ?>>
	
	<?php
		if (function_exists('wp_body_open')) {
			wp_body_open();
		}
	?>

	<?php liquid_action( 'before' ) ?>

	<div id="wrap">

		<?php
			liquid_action( 'before_header' );
			liquid_action( 'header' );
			liquid_action( 'after_header' );
		?>

		<main <?php liquid_helper()->attr( 'content' ); ?>>

		<?php if (  tribe_is_event() || is_singular( 'tribe_events' ) ) { ?>
		<!-- Banner Code Start=============================================================================================== -->	
		<section id="inner_banner" data-row-bg="<?php echo get_field('event_banner', 13); ?>" 
			style="background-position: center top !important; background-repeat: no-repeat !important; background-size: cover !important;" 
				data-bg-image="url" class="vc_row inner_banner spo_inner_banner vc_custom_1667468301801 liquid-row-shadowbox-6363a839616ad vc_row-has-fill vc_row-has-bg vc_column-gap-0 lqd-has-bg-markup row-bg-appended"><span class="row-bg-loader"></span><div class="row-bg-wrap">
		<div class="row-bg-inner"><figure class="row-bg" ></figure></div></div>
		<div class="ld-container container-fluid"><div class="row ld-row ld-row-outer"><div class="wpb_column vc_column_container vc_col-sm-12 liquid-column-6363a839705ad"><div class="vc_column-inner  " ><div class="wpb_wrapper"  data-custom-animations="true" data-ca-options='{"triggerHandler":"inview","animationTarget":"all-childs","duration":1600,"delay":250,"ease":"power4.inOut","direction":"forward","initValues":{"opacity":0,"translateX":150},"animations":{"opacity":1,"translateX":0}}'><style>#inner_banner_title div{color:rgb(255, 255, 255);}#inner_banner_title .lqd-highlight-inner{height:0.275em!important;bottom:0px!important;}</style><div id="inner_banner_title" class="ld-fancy-heading text-center inner_banner_title ld_fancy_heading_6363a83970775">
		<div class="ld-fh-element lqd-highlight-underline lqd-highlight-grow-left text-decoration-default"   >  Event</div></div></div></div></div></div></div></section>
		<!-- Banner Code End======================================================================================================== -->
		<?php } ?>

		<?php if (is_singular('post')) { ?>
		<!-- Banner Code Start=============================================================================================== -->	
		<section id="inner_banner" data-row-bg="<?php echo get_field('news_banner', 179); ?>" 
			style="background-position: center top !important; background-repeat: no-repeat !important; background-size: cover !important;" 
				data-bg-image="url" class="vc_row inner_banner spo_inner_banner vc_custom_1667468301801 liquid-row-shadowbox-6363a839616ad vc_row-has-fill vc_row-has-bg vc_column-gap-0 lqd-has-bg-markup row-bg-appended"><span class="row-bg-loader"></span><div class="row-bg-wrap">
		<div class="row-bg-inner"><figure class="row-bg" ></figure></div></div>
		<div class="ld-container container-fluid"><div class="row ld-row ld-row-outer"><div class="wpb_column vc_column_container vc_col-sm-12 liquid-column-6363a839705ad"><div class="vc_column-inner  " ><div class="wpb_wrapper"  data-custom-animations="true" data-ca-options='{"triggerHandler":"inview","animationTarget":"all-childs","duration":1600,"delay":250,"ease":"power4.inOut","direction":"forward","initValues":{"opacity":0,"translateX":150},"animations":{"opacity":1,"translateX":0}}'><style>#inner_banner_title div{color:rgb(255, 255, 255);}#inner_banner_title .lqd-highlight-inner{height:0.275em!important;bottom:0px!important;}</style><div id="inner_banner_title" class="ld-fancy-heading text-center inner_banner_title ld_fancy_heading_6363a83970775">
		<div class="ld-fh-element lqd-highlight-underline lqd-highlight-grow-left text-decoration-default"   >  News</div></div></div></div></div></div></div></section>
		<!-- Banner Code End======================================================================================================== -->
		<?php } ?>

		<?php if (is_singular('job_listing')) { ?>
		<!-- Banner Code Start=============================================================================================== -->	
		<section id="inner_banner" data-row-bg="<?php echo get_field('job_board_banner', 16); ?>" 
			style="background-position: center top !important; background-repeat: no-repeat !important; background-size: cover !important;" 
				data-bg-image="url" class="vc_row inner_banner spo_inner_banner vc_custom_1667468301801 liquid-row-shadowbox-6363a839616ad vc_row-has-fill vc_row-has-bg vc_column-gap-0 lqd-has-bg-markup row-bg-appended"><span class="row-bg-loader"></span><div class="row-bg-wrap">
		<div class="row-bg-inner"><figure class="row-bg" ></figure></div></div>
		<div class="ld-container container-fluid"><div class="row ld-row ld-row-outer"><div class="wpb_column vc_column_container vc_col-sm-12 liquid-column-6363a839705ad"><div class="vc_column-inner  " ><div class="wpb_wrapper"  data-custom-animations="true" data-ca-options='{"triggerHandler":"inview","animationTarget":"all-childs","duration":1600,"delay":250,"ease":"power4.inOut","direction":"forward","initValues":{"opacity":0,"translateX":150},"animations":{"opacity":1,"translateX":0}}'><style>#inner_banner_title div{color:rgb(255, 255, 255);}#inner_banner_title .lqd-highlight-inner{height:0.275em!important;bottom:0px!important;}</style><div id="inner_banner_title" class="ld-fancy-heading text-center inner_banner_title ld_fancy_heading_6363a83970775">
		<div class="ld-fh-element lqd-highlight-underline lqd-highlight-grow-left text-decoration-default"   >  Jobs Board</div></div></div></div></div></div></div></section>
		<!-- Banner Code End======================================================================================================== -->
		<?php } ?>

			<?php liquid_action( 'before_contents_wrap' ); ?>

			<div <?php liquid_helper()->attr( 'contents_wrap' ); ?>>

			<?php liquid_action( 'before_content' ); ?>



