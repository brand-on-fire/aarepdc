<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the main containers
 *
 * @package Hub theme
 */
?>

			<?php liquid_action( 'after_content' ); ?>
			</div>
			<?php liquid_action( 'after_contents_wrap' ); ?>
		</main>
		<?php
		liquid_action( 'before_footer' );
		liquid_action( 'footer' );
		liquid_action( 'after_footer' );
		?>

	</div>

	<?php liquid_action( 'after' ) ?>

<?php if( is_page(794) ) { ?>
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/semantic-ui/2.1.4/semantic.min.css" /> -->
<!-- <script type="text/javascript" src="<?php echo get_stylesheet_directory_uri(); ?>/js/jquery-1.11.1.js"></script> -->
<script type="text/javascript" src="<?php echo get_stylesheet_directory_uri(); ?>/js/jquery.validate.js"></script> 
<script type="text/javascript">
 
		jQuery.validator.setDefaults( {
			submitHandler: function () {
       // alert( "submitted!" );
        if (grecaptcha.getResponse() == ''){
             jQuery( '#google-captcha-error' ).html('This field is required.').show();
             return false;
            console.log('false');
        } else { 
          var dataString = jQuery("#event_registration_form").serialize();

            jQuery("#clients_loder1").show();
             
            jQuery.ajax({
              type: "POST",
              url:"<?php echo site_url(); ?>/wp-content/themes/aarepdc-child/inc/operation.php?mode=event_registration",
              data: dataString,
              success: function (result) {
                //alert(result);
                //var data=result.replace(/\s+/g, '');
                var data = result.split("_");
                if (data[0] == "success") {
                  jQuery("#clients_loder1").hide();
                  window.location.href = site_url + "/thank-you-event/";
                } else {
                  jQuery("#clients_loder1").hide();
                  alert(data);
                }
              },
            });
            return false; 

         }
				
            // $.ajax({
            //     type : "POST",
            //     url : "sendmail.php",
            //     data : $('#form').serialize(),
            //     success : function (data) {
            //         $('#message').html(data);
            //     }
            // });
			  }
		} );

		jQuery( document ).ready( function () {
			jQuery( "#event_registration_form" ).validate( {
				//ignore: ":hidden:not(#keycode)",
				rules: {
					first_name: "required",
					last_name: "required",
          email_address: {
							required: true,
							email: true,
					},
          company_name: "required",
          billing_address: "required",
          billing_city: "required",
          billing_state: "required",
          billing_postal_code: "required",
          card_name: "required",
          card_type: "required",
          card_number: "required",
          card_cvc: "required",
          // "hiddencode": {
          //     required: function() {
          //     if(grecaptcha.getResponse() == '') {
          //        console.log('true'); return true;
          //     } else { console.log('false'); return false; }
          //     } 
          //   }
          // hiddenRecaptcha: {
          //       required: function() {
          //       if(grecaptcha.getResponse() == '') {
          //           return true;
          //       } else {
          //           return false;
          //       }
          //   }
          //   },
				},
				messages: {
					// first_name: "Please enter your firstname",
					// last_name: "Please enter your lastname",
          // email_address: "Please enter a valid email address",
				},
				errorPlacement: function ( error, element ) {
					error.addClass( "ui red pointing label transition" );
					error.insertAfter( element.parent() );
				},
				highlight: function ( element, errorClass, validClass ) {
					jQuery( element ).parents( ".row" ).addClass( errorClass );
				},
				unhighlight: function (element, errorClass, validClass) {
					jQuery( element ).parents( ".row" ).removeClass( errorClass );
				}
			} );
		} );
	</script>
<?php } ?>
  <script type="text/javascript">
jQuery( document ).ready(function() { 

  jQuery('.business_organization').hide();
  jQuery('#ma_add_info').hide();
  
  <?php if(is_page('become-a-member')) { ?>
  // General Membership $200
  jQuery('#general_membership').click(function() {
      jQuery('#student_section').hide();
      jQuery('#todays_total').html('');
      var membrVal = <?php echo get_field('general_membership_price'); ?>;
      jQuery('#finale_amount').val(membrVal);
      jQuery('#mbrshp_val').html('General Membership');
      jQuery('#todays_total').html(membrVal.toFixed(2));
  });

  // Emerging Professional $100
  jQuery('#non_profit_government').click(function() {
      jQuery('#student_section').hide();
      jQuery('#todays_total').html(''); 
      var nonmembrVal = <?php echo get_field('non_profit_gov_membership_price'); ?>;
      jQuery('#finale_amount').val(nonmembrVal);
      jQuery('#mbrshp_val').html('Non-Profit / Government');
      jQuery('#todays_total').html(nonmembrVal.toFixed(2));
  });

  // Student Membership $0
  jQuery('#young_professional_student').click(function() {
      jQuery('#student_section').show();
      jQuery('#todays_total').html(''); 
      var youngmembrVal = <?php echo get_field('young_professional_membership_price'); ?>;
      jQuery('#finale_amount').val(youngmembrVal);
      jQuery('#mbrshp_val').html('Young Professional / Student');
      jQuery('#todays_total').html(youngmembrVal.toFixed(2));
  });
  <?php } ?>
  jQuery('#same_as_above').click(function() {
      jQuery('#company_address').val(jQuery('#address1').val());
      jQuery('#billing_city').val(jQuery('#city').val());
      jQuery('#billing_state').val(jQuery('#state').val());
      jQuery('#billing_postal_code').val(jQuery('#postal_code').val());
  }); 

  jQuery('#real_estate_industry_professional').on('change', function() {
      if(jQuery('#real_estate_industry_professional').val() == 'other'){
         jQuery('#ma_add_info').show();
      }
      else{
         jQuery('#ma_add_info').hide();  
         jQuery('#other01').val('');
      }
  });

  /* Radio Options */
  jQuery( "#mem_business" ).click(function() {
    jQuery('.business_organization').show();
  });
  jQuery( "#individual" ).click(function() {
    jQuery('.business_organization').hide();
    jQuery('#business_name').val('');
  });

  jQuery( "#personal_membership" ).click(function() {
    jQuery('.payment_section').show();
  });

  jQuery( "#business_membership" ).click(function() {
    jQuery('.payment_section').show();
  });

  jQuery( "#student_membership" ).click(function() {
    jQuery('.payment_section').hide();
  });  

}); 
</script>
	<?php wp_footer(); ?>
<script>
document.addEventListener( 'wpcf7mailsent', function( event ) {
location = '<?php echo site_url(); ?>/newsletter-thank-you/';
 }, false );
</script>
</body>
</html>
