<?php

add_action( 'wp_enqueue_scripts', 'liquid_child_theme_style', 99 );

function liquid_parent_theme_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
function liquid_child_theme_style(){

    wp_enqueue_style( 'google-font-montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap', false ); 
    wp_enqueue_style( 'google-font-manrope', 'https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap', false );

    wp_enqueue_style( 'font-awesome', get_stylesheet_directory_uri() . '/font-awesome.min.css'); 
    wp_enqueue_style( 'custom-fonts-style', get_stylesheet_directory_uri() . '/fonts.css' );
    wp_enqueue_style( 'custom-fonts-style2', get_stylesheet_directory_uri() . '/fonts2.css' );

    wp_enqueue_style( 'child-hub-style', get_stylesheet_directory_uri() . '/style.css' );	
    wp_enqueue_style( 'child-hub-style2', get_stylesheet_directory_uri() . '/style2.css' ); 
    wp_enqueue_style( 'child-hub-resp1', get_stylesheet_directory_uri() . '/responsive.css' );
    wp_enqueue_script('customjs', get_stylesheet_directory_uri().'/js/custom.js', array(), null, '');

    //wp_enqueue_script( 'jquery-ui-datepicker' );
    //You need styling for the datepicker. For simplicity I've linked to the jQuery UI CSS on a CDN.
    //wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
    //wp_enqueue_style( 'jquery-ui' ); 

}

add_action('admin_enqueue_scripts', 'ds_admin_theme_style');
add_action('login_enqueue_scripts', 'ds_admin_theme_style');
function ds_admin_theme_style() {
    echo '<style>.error, .is-dismissible { display: none !important; }</style>';
}



/*---------------------------------------------------------------------------------------------------------------------------
# Remove query string (version) from static files
---------------------------------------------------------------------------------------------------------------------------*/
function remove_cssjs_ver( $src ) {
 if( strpos( $src, '?ver=' ) )
 $src = remove_query_arg( 'ver', $src );
 return $src;
}
add_filter( 'style_loader_src', 'remove_cssjs_ver', 10, 2 );



add_role('member', __(
   'Member'),
   array( 'read' => true, 'level_0' => true )
);

/*---------------------------------------------------------------------------------------------------------------------------*/
# Include Custom Files
/*---------------------------------------------------------------------------------------------------------------------------*/
require_once('inc/board_members_post_type.php');

/*---------------------------------------------------------------------------------------------------------------------------
# Shortcodes ============================================
---------------------------------------------------------------------------------------------------------------------------*/
require_once('inc/home_bod_section_shrt.php');
require_once('inc/events_page_shrt.php');
require_once('inc/news_page_shrt.php');
require_once('inc/sponshership_info_table_shrt.php');
require_once('inc/sponsorship_application.php');
require_once('inc/board_member_shrt.php');
require_once('inc/home_event_section_shrt.php');
require_once('inc/sponsorship-post-type.php');
require_once('inc/sponsorship_levels_shrt.php');
require_once('inc/membership_application.php');
require_once('inc/event_registration_form.php');

@ini_set( 'upload_max_size' , '256M' );
@ini_set( 'post_max_size', '256M');
@ini_set( 'max_execution_time', '300' );


// ======================================================================================================
// =============================================================================================
function el_custom_javascript() { ?>
    
    <script>
    
            const tv = document.querySelector("#home_row2");
            //let tvbg = tv.dataset.row-bg;

            const plant = document.getElementById('footer');
            plant.setAttribute('background-image','bgimg');

            var a = 2;
            console.log(a);
            console.log(tv);
           // console.log(tvbg);

    </script>


    <script type="text/javascript">
        jQuery('*[data-background-image]').each(function() {
            jQuery(this).css({
                'background-image': 'url(' + jQuery(this).data('background-image') + ')'
            });
        });
    </script>

<?php }
add_action('wp_footer', 'el_custom_javascript');

// ======================================================================================================
// =============================================================================================
function my_custom_function(){
    ?>
    <script>       

        // var list = document.getElementsByClassName('.content_bottom_banner');
        // var src = list.getAttribute('data-row-bg');
        // elem.getAttribute( "checked" )

        // $( "#content_bottom_banner" ).attr( "alt", "Beijing Brush Seller" );

        // list.style.backgroundImage="url('" + src + "')";
        // console.log('Hello World!');
        
        // Your function here
        // jQuery(window).load(function(){
        //     console.log('Hello World!');
        // });
    </script>
    <?php
}
add_action('wp_footer', 'my_custom_function');

// Sponsorship Page Shortcode
add_shortcode( 'sponsorship_thankyou', 'sponsorship_thankyou_shortcode' );
function sponsorship_thankyou_shortcode() { ob_start(); ?>
    We received your African American Real Estate Professionals DC order for <strong><?php echo $_GET['type']; ?>!</strong> <br />
    If you need assistance or have any questions, please email us at <a href="mailto:info@aarepdc.org">info@aarepdc.org</a>.<br />
    Thank you for your purchase.

<?php
    return ob_get_clean();
}

// Membership Page Shortcode
add_shortcode( 'membership_thankyou', 'membership_thankyou_shortcode' );
function membership_thankyou_shortcode() { ob_start(); ?>
    We received your African American Real Estate Professionals DC order for <strong><?php echo $_GET['type']; ?>!</strong> <br />
    If you need assistance or have any questions, please email us at <a href="mailto:info@aarepdc.org">info@aarepdc.org</a>.<br />
    Thank you for your purchase.

<?php
    return ob_get_clean();
}

add_filter( 'wpcf7_validate', 'email_already_in_db', 10, 2 );

function email_already_in_db ( $result, $tags ) {
    // retrieve the posted email
    $form  = WPCF7_Submission::get_instance();
    $email = $form->get_posted_data('your-email');
    // if already in database, invalidate
    if( email_exists( $email ) ) // email_exists is a WP function
        $result->invalidate('your-email', 'Your email exists in our database');
    // return the filtered value
    return $result;
}

/* Add Custom Column to Event Listing */

/*add_filter('manage_tribe_events_posts_columns', function($columns) {
    return array_merge($columns, ['total_reg' => __('Total Registration', 'textdomain')]);
});

add_action('manage_tribe_events_posts_custom_column', function($column_key, $post_id) {
    if(tribe_get_cost()) {
        if ($column_key == 'total_reg') {
            
            $query = 'SELECT * FROM `aal10_event_registration_master` WHERE `event_id` = '.$post_id.' ';

            $post_id = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE (meta_key = 'mfn-post-link1' AND meta_value = '". $from ."')");

        }    
    }
    else{
        echo "--";
    }
    
}, 10, 2);*/
function my_login_logo_one() { 
    ?> 
    <style type="text/css"> 
    body.login div#login h1 a {
     background-image: url(<?php echo site_url();?>/wp-content/uploads/2022/10/footer_logo.png); 
     background-size: contain!important;
     width: 100%!important;
    } 
    </style>
     <?php 
    } add_action( 'login_enqueue_scripts', 'my_login_logo_one' );

//changing the url on the logo to redirect them
function mb_login_url() {  return home_url(); }
add_filter( 'login_headerurl', 'mb_login_url' );


// Redirect to Page After Login
function my_login_redirect( $redirect_to, $request, $user ) {
    //is there a user to check?
    global $user;
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {

        if ( in_array( 'member', $user->roles ) ) {
            // redirect them to the default place
            //$data_login = get_option('axl_jsa_login_wid_setup');
            return home_url('/whats-happening');
            //return get_permalink($data_login[0]);
        } else {
            return admin_url();
        }
    } else {
        return $redirect_to;
    }
}
add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );

add_action( 'send_headers', function() {
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    header( 'X-Accel-Expires: 0' );
}, 1 );

add_action( 'wp_head', function() {
    if ( ! is_front_page() ) { ?>
    <style>
    #sb_instagram, .sbi { padding-top:20px!important; padding-bottom:20px!important; }
    .wpcf7-response-output[aria-hidden="true"]:empty { display:none!important; }
    </style>
    <?php return; } ?>
    <style>
    #sb_instagram, .sbi { padding-top:20px!important; padding-bottom:20px!important; }
    .wpcf7-response-output[aria-hidden="true"]:empty { display:none!important; }
    section.fucus_mobile_image { display:none!important; }
    @media (min-width: 1200px) {
        section#home_top_banner { min-height:43vw!important; }
    }
    @media (max-width: 1199px) {
        section#home_top_banner {
            position:relative!important;
            overflow:hidden!important;
            height:40vw!important;
        }
        section#home_top_banner .wpb_column,
        section#home_top_banner .vc_column-inner,
        section#home_top_banner .wpb_wrapper {
            position:static!important;
        }
        #slider_wrapper_id1,
        rs-layer#slider_layer_id1 { display:none!important; }
        #hb_mobile_text_row {
            position:absolute!important;
            top:50%!important;
            left:0!important; right:0!important;
            transform:translateY(-50%)!important;
            z-index:100!important;
            display:block!important;
            text-align:center!important;
            margin:0!important; padding:0!important;
        }
        #hb_mobile_text_row .ld-container,
        #hb_mobile_text_row .ld-row,
        #hb_mobile_text_row .wpb_column,
        #hb_mobile_text_row .vc_column-inner,
        #hb_mobile_text_row .wpb_wrapper,
        #hb_mobile_text,
        #hb_mobile_text .ld-fh-element {
            padding:0!important; margin:0!important;
        }
        #hb_mobile_text_row,
        #hb_mobile_text_row * {
            opacity:1!important;
            visibility:visible!important;
            animation:none!important;
            transition:none!important;
        }
        #hb_mobile_text .ld-fh-element,
        #hb_mobile_text .st_01,
        #hb_mobile_text .st_02,
        #hb_mobile_text .st_03,
        #hb_mobile_text .st_04 {
            color:#fff!important;
            text-shadow:0 2px 8px rgba(0,0,0,0.6)!important;
        }
    }
    @media (min-width: 992px) and (max-width: 1199px) {
        #hb_mobile_text .st_01,
        #hb_mobile_text .st_03,
        #hb_mobile_text .st_04 { font-size:48px!important; line-height:1.2!important; }
        #hb_mobile_text .st_02 { font-size:40px!important; line-height:1.2!important; }
    }
    @media (min-width: 768px) and (max-width: 991px) {
        #hb_mobile_text .st_01,
        #hb_mobile_text .st_03,
        #hb_mobile_text .st_04 { font-size:36px!important; line-height:1.2!important; }
        #hb_mobile_text .st_02 { font-size:30px!important; line-height:1.2!important; }
    }
    @media (max-width: 767px) {
        section#home_top_banner { height:55vw!important; }
        #hb_mobile_text .ld-fh-element { line-height:1!important; }
        #hb_mobile_text .st_01,
        #hb_mobile_text .st_03,
        #hb_mobile_text .st_04 { font-size:18px!important; line-height:1.1!important; }
        #hb_mobile_text .st_02 { font-size:14px!important; line-height:1.1!important; }
    }
    </style>
    <?php
}, 99 );

add_action( 'wp_footer', function() {
    ?>
    <script>
    (function(){
        var el = document.getElementById("current_year");
        if (el) el.textContent = new Date().getFullYear();

        var wrap = document.getElementById("rev_slider_1_1_wrapper");
        if (!wrap) return;
        var mod = wrap.querySelector("rs-module");
        var savedH = 0, restoring = false;

        if (window.innerWidth >= 992) {
            new MutationObserver(function() {
                if (restoring) return;
                var h = parseInt(wrap.style.height);
                if (h > 100) {
                    savedH = h;
                } else if (savedH > 100 && window.scrollY < 300) {
                    restoring = true;
                    wrap.style.height = savedH + "px";
                    if (mod) mod.style.height = savedH + "px";
                    if (window.revapi1 && typeof revapi1.revredraw === "function") revapi1.revredraw();
                    setTimeout(function() { restoring = false; }, 1000);
                }
            }).observe(wrap, {attributes: true, attributeFilter: ["style"]});
        } else {
            new MutationObserver(function() {
                var h = parseInt(wrap.style.height);
                if (h > 100) savedH = h;
            }).observe(wrap, {attributes: true, attributeFilter: ["style"]});
        }
    })();
    </script>
    <?php
}, 99 );

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'fa6-free', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );
}, 100 );