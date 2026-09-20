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

    wp_enqueue_style( 'child-hub-style', get_stylesheet_directory_uri() . '/style.css?c=' . ( @filemtime( get_stylesheet_directory() . '/style.css' ) ?: '1' ) );
    wp_enqueue_style( 'child-hub-style2', get_stylesheet_directory_uri() . '/style2.css' ); 
    wp_enqueue_style( 'child-hub-resp1', get_stylesheet_directory_uri() . '/responsive.css' );
    wp_enqueue_style( 'aarepdc-membership', get_stylesheet_directory_uri() . '/aarepdc-membership.css?c=' . ( @filemtime( get_stylesheet_directory() . '/aarepdc-membership.css' ) ?: '4' ), array( 'child-hub-style' ), null );
    $custom_js_path = get_stylesheet_directory() . '/js/custom.js';
    wp_enqueue_script(
        'customjs',
        get_stylesheet_directory_uri() . '/js/custom.js',
        array(),
        file_exists( $custom_js_path ) ? (string) filemtime( $custom_js_path ) : null,
        true
    );

}

add_action( 'wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>' . "\n";
}, 1 );

add_action('admin_enqueue_scripts', 'ds_admin_theme_style');
add_action('login_enqueue_scripts', 'ds_admin_theme_style');
function ds_admin_theme_style() {
    echo '<style>.error, .is-dismissible { display: none !important; }</style>';
}



/*---------------------------------------------------------------------------------------------------------------------------
# Remove query string (version) from static files
---------------------------------------------------------------------------------------------------------------------------*/
function remove_cssjs_ver( $src ) {
 if( strpos( $src, '?ver=' ) !== false )
 $src = remove_query_arg( 'ver', $src );
 return $src;
}
add_filter( 'style_loader_src', 'remove_cssjs_ver', 10, 2 );



add_role('member', __(
   'Member'),
   array( 'read' => true, 'level_0' => true )
);

/*---------------------------------------------------------------------------------------------------------------------------*/
# Include Custom Files (defensive load — tolerate unreadable inc/ from WPE deploy quirk)
/*---------------------------------------------------------------------------------------------------------------------------*/
$aarepdc_inc_files = array(
    'board_members_post_type.php',
    'home_bod_section_shrt.php',
    'event_display_helpers.php',
    'events_page_shrt.php',
    'news_page_shrt.php',
    'sponshership_info_table_shrt.php',
    'sponsorship_application.php',
    'board_member_shrt.php',
    'home_event_section_shrt.php',
    'sponsorship-post-type.php',
    'sponsorship_levels_shrt.php',
    'membership_application.php',
    'event_registration_form.php',
    'gallery_lightbox.php',
);
foreach ( $aarepdc_inc_files as $aarepdc_inc_f ) {
    $aarepdc_inc_path = __DIR__ . '/inc/' . $aarepdc_inc_f;
    if ( @is_readable( $aarepdc_inc_path ) ) {
        require_once $aarepdc_inc_path;
    }
}
unset( $aarepdc_inc_files, $aarepdc_inc_f, $aarepdc_inc_path );

ini_set( 'upload_max_filesize', '256M' );
ini_set( 'post_max_size', '256M' );
ini_set( 'max_execution_time', '300' );


function el_custom_javascript() { ?>
    <script>
            const tv = document.querySelector("#home_row2");
            const plant = document.getElementById('footer');
            if (plant) plant.setAttribute('background-image','bgimg');
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

add_shortcode( 'sponsorship_thankyou', 'sponsorship_thankyou_shortcode' );
function sponsorship_thankyou_shortcode() { ob_start();
    $type = isset( $_GET['type'] ) ? esc_html( sanitize_text_field( $_GET['type'] ) ) : '';
    ?>
    We received your African American Real Estate Professionals DC order for <strong><?php echo $type; ?>!</strong> <br />
    If you need assistance or have any questions, please email us at <a href="mailto:info@aarepdc.org">info@aarepdc.org</a>.<br />
    Thank you for your purchase.

<?php
    return ob_get_clean();
}

add_shortcode( 'membership_thankyou', 'membership_thankyou_shortcode' );
function membership_thankyou_shortcode() { ob_start();
    $type = isset( $_GET['type'] ) ? esc_html( sanitize_text_field( $_GET['type'] ) ) : '';
    ?>
    We received your African American Real Estate Professionals DC order for <strong><?php echo $type; ?>!</strong> <br />
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
        #hb_mobile_text span.st_01,
        #hb_mobile_text span.st_02,
        #hb_mobile_text span.st_03,
        #hb_mobile_text span.st_04 {
            color:#fff!important;
            text-shadow:0 2px 8px rgba(0,0,0,0.6)!important;
        }
        #hb_mobile_text span.st_02 {
            padding-bottom:4px!important;
            margin-bottom:4px!important;
        }
    }
    @media (min-width: 992px) and (max-width: 1199px) {
        #hb_mobile_text span.st_01,
        #hb_mobile_text span.st_03,
        #hb_mobile_text span.st_04 { font-size:48px!important; line-height:1!important; }
        #hb_mobile_text span.st_02 { font-size:42px!important; line-height:1!important; }
    }
    @media (min-width: 768px) and (max-width: 991px) {
        #hb_mobile_text span.st_01,
        #hb_mobile_text span.st_03,
        #hb_mobile_text span.st_04 { font-size:36px!important; line-height:1!important; }
        #hb_mobile_text span.st_02 { font-size:32px!important; line-height:1!important; }
    }
    @media (max-width: 767px) {
        section#home_top_banner { height:45vw!important; }
        #hb_mobile_text span.st_01,
        #hb_mobile_text span.st_03,
        #hb_mobile_text span.st_04 { font-size:7vw!important; line-height:1!important; }
        #hb_mobile_text span.st_02 { font-size:5.5vw!important; line-height:1!important; padding-bottom:1vw!important; margin-bottom:0!important; }
        #hb_mobile_text span.st_02:before { height:2vw!important; background-size:50% auto!important; bottom:-1vw!important; }
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

/* Logged-in member submenu under "Membership" (child items tagged .aarepdc-member-only:
 * Membership / Member Directory / My Account / Log Out). Hide them when logged out so the
 * dropdown only appears for members, and resolve the Log Out item to a fresh nonce'd URL.
 * Replaces the old top-level nav logout — logout now lives in this submenu + the account page.
 * The AAREP National page remains in the public About Us submenu. */
add_filter( 'wp_nav_menu_objects', 'aarepdc_member_submenu', 10, 2 );
function aarepdc_member_submenu( $items, $args ) {
    $logged_in = is_user_logged_in();

    // Audience filtering:
    //   .aarepdc-member-only  => signed-in members only (Directory, Member Portal, Log Out)
    //   .aarepdc-guest-only   => signed-out visitors only (Member Account, which routes to login)
    foreach ( $items as $key => $item ) {
        if ( false !== strpos( (string) $item->url, 'aarepdc-logout' ) ) {
            $item->url = wp_logout_url( home_url() );
        }
        $classes = (array) $item->classes;
        $drop    = ( ! $logged_in && in_array( 'aarepdc-member-only', $classes, true ) )
                || (   $logged_in && in_array( 'aarepdc-guest-only',  $classes, true ) );
        if ( $drop ) {
            unset( $items[ $key ] );
        }
    }

    // Recompute the dropdown arrow from what actually survived. A parent keeps
    // menu-item-has-children only while it still has at least one visible child, so
    // "Membership" keeps its arrow for guests (Member Account) and for members.
    $parents_with_children = array();
    foreach ( $items as $item ) {
        if ( (int) $item->menu_item_parent ) {
            $parents_with_children[ (int) $item->menu_item_parent ] = true;
        }
    }
    foreach ( $items as $item ) {
        $has = isset( $parents_with_children[ (int) $item->ID ] );
        $classes = array_values( array_diff( (array) $item->classes, array( 'menu-item-has-children' ) ) );
        if ( $has ) {
            $classes[] = 'menu-item-has-children';
        }
        $item->classes = $classes;
    }

    return $items;
}
