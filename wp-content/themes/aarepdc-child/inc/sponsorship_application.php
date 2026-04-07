<?php
add_shortcode( 'sponsorship_shrt', 'sponsorship_shortcode' );
function sponsorship_shortcode(){
ob_start(); ?>
<style type="text/css">
	/*#membership_form_wrapper #membership_form select {  display: block !important; }
#membership_form_wrapper #membership_form select + .ui-selectmenu-button.ui-button {  display: none !important; }*/
.memberships_shrt select {  display: block !important; }
.memberships_shrt select + .ui-selectmenu-button.ui-button {  display: none !important; }
.alredy{ color: #ff0000; }
.radio_wrapp label { text-align: left; }
.radio_inline {  display: flex!important; }
.radio_inline .radio_wrapp:not(:first-child) { margin-left: 15px!important; }
</style>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<div id="clients_loder1" style="display:none; position:fixed; vertical-align:middle; z-index:1050; background-color: #b5b5b5; height: 100%; left: 0; opacity: 0.8; position: fixed; top: 0; width: 100%; z-index: 1001;" align="center"><br>
  <br>  <br>  <br>  <br>  <br>  <br>  <br>  <br>  <br>  <br>
  <img src="<?php echo site_url('images/loader-img.gif'); ?>"></div>
<div class="mycontainer mycontent">
	<form onsubmit="return false;" method="post" action="" name="sponsorship_form" id="sponsorship_form" style="padding-top: 0;">
		<h2 class="text-center please_fillout">Please fill out ALL of your information.</h2>
		<!-- =============================================================================== -->
<?php 
	$price = $_GET['price'];
	$type = $_GET['type'];
?>
		<p style="margin-top: 0;" class="full-width text-center membership_parts"><strong>BILLING INFORMATION</strong></p>
		<div class="donate-block donate-block-fix" id="billing_info_data" >

			<div class="vc_row yd_row01">
					<div class="vc_col-sm-6"><input type="text" name="first_name" id="first_name" kl_virtual_keyboard_secure_input="on" placeholder="First name*"></div>
					<div class="vc_col-sm-6"><input type="text" name="last_name" id="last_name" kl_virtual_keyboard_secure_input="on" placeholder="Last name*"></div>
			</div>
			<!-- <div class="vc_row yd_row01">
					<div class="vc_col-sm-4"><input type="text" name="user_name" id="user_name" onblur="user_exists_val();" kl_virtual_keyboard_secure_input="on" placeholder="User name*"> <span id="loader_img" style="text-align: left; display: block;"></span></div>
					<div class="vc_col-sm-4"><input type="password" name="password" id="password" kl_virtual_keyboard_secure_input="on" placeholder="Password*"></div>
					<div class="vc_col-sm-4"><input type="password" name="confirm_password" id="confirm_password" kl_virtual_keyboard_secure_input="on" placeholder="Confirm Password*"></div>
			</div> -->
			<div class="vc_row yd_row01">
				<div class="vc_col-sm-6"><input type="text" name="company_name" id="company_name" placeholder="Company name*">	</div>
				<div class="vc_col-sm-6"><input type="text" name="email_address" id="email_address" onblur="email_exists_val();" placeholder="Email address*"><span id="loader_img_email" style="text-align: left; display: block;"></span></div>
			</div>

			<div class="vc_row ma_add_info" id="hide_comopany_info">
				<div class="vc_col-sm-6"><input type="text" name="company_address" value="" id="company_address" placeholder="Billing address*"></div>
				<div class="vc_col-sm-6"><input type="text" name="billing_city" id="billing_city" placeholder="City*"></div>
			</div>
     
      <div class="vc_row ma_add_info">
        <div class="vc_col-sm-6">
          <input maxlength="10" type="text" name="billing_postal_code"  id="billing_postal_code"  value="" placeholder="Postal code*">
        </div>
        <div class="vc_col-sm-6">
		<input type="text" name="billing_state" id="billing_state" placeholder="State/Province*">
        </div>
      </div>
		</div>
		<!-- =============================================================================== -->
		<p style="margin-top: 0;" class="full-width text-center membership_parts"><strong>Review</strong></p>
		<div class="donate-block donate-block-fix00 review_block" id="review_info">
			<!-- ================================================= -->
			<div class="row">
				<div class="col-lg-12 clearfix">
					<div class="review-box white-new">
						<div class="rebox-pads">
							<ul class="cart-views-list">								
								<li class="hidden-xs bottom-bline">
									<div class="row cart-sixfourty">
										<div class="col-xs-12 col-sm-6"><strong>ITEM</strong></div>
										<!-- <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 text-center"><strong>PRICE</strong></div>
										<div class="col-xs-12 col-sm-3 col-md-3 col-lg-1 text-center"><strong>QUANTITY</strong></div> -->
										<div class="col-xs-12 col-sm-6 text-right"><strong>TOTAL</strong></div>
									</div>
								</li>
								<li>
									<div class="row cart-sixfourty">
										<div class="col-xs-12 col-sm-6"><strong class="brk-line-long-txt" id="mbrshp_val"><?php echo $type; ?></strong></div>
										<div class="col-xs-12 col-sm-6 text-right"><strong>$</strong><strong id="todays_total"><?php echo number_format($price); ?></strong></div>
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>

        <div class="col-lg-12 clearfix" style="display: none;"><strong class="brk-line-long-txt payment_section" style="display: block; text-align: center; margin-top: 15px;">My sponsorhip will be automatically charged annually.</strong></div>

				<div class="clearfix"></div>				
			</div>
			<div class="clearfix"></div>
		</div>
		<!-- ================================================= -->
	
  <p class="full-width text-center membership_parts clear payment_section"><strong>Payment Information</strong></p>
  			<div class="donate-block donate-block-fix application_row">
  				<div class="vc_row radio_select_row1" id="top_radio_group1_new">
						 <div class="vc_col-sm-6">
						 		<strong>Select Payment Option: </strong>
						 </div>
						 <div class="vc_col-sm-6 radio_inline">
						 		<div class="radio_wrapp">
									<span class="fancy_radio"><input type="radio" value="Credit Card" id="creditcard_option" name="payment_option" checked><span class="checkmark"></span></span>
									<label for="creditcard_option"><strong>Credit Card</strong></label>
								</div>
						 		<div class="radio_wrapp">
									<span class="fancy_radio"><input type="radio" value="Check" id="check_option" name="payment_option" ><span class="checkmark"></span></span>
									<label for="check_option"><strong>Check</strong></label>
								</div>
						 		
						 </div>
				</div>	
			</div>	

  <div class="donate-block donate-block-fix payinfo fonts-fix payment_section" id="payment_info_data">
		<div class="form_row1">
			<div class="one_half"><input type="text" name="card_name" id="card_name" kl_virtual_keyboard_secure_input="on" placeholder="Name on card*" ></div>
			<div class="one_half last">
        <select name="card_type" id="card_type" class="txtbox">
  				<option value="">Card Type*</option>
  				<option value="Visa">Visa</option>
  				<option value="MasterCard">Mastercard</option>
  				<option value="American Express">American Express</option>
  				<option value="Discover">Discover</option>
		</select></div>
		</div>
		<div class="form_row1">
			<div class="one_half"><input type="text" maxlength="16" name="card_number" id="card_number" kl_virtual_keyboard_secure_input="on" placeholder="Card number*" onkeypress="return numbersonly(event);"></div>
			<div class="one_half last"><input type="password" maxlength="4" name="card_cvc" id="card_cvc" kl_virtual_keyboard_secure_input="on" placeholder="CVV number*" onkeypress="return numbersonly(event);"></div>
		</div>
		<div class="form_row1 cssfix00">
			<div class="full_row">
				<label class="cce_one" style="width: auto; float: left; margin-right: 50px;">Credit Card Expiration:*</label>
				<div class="date-combo-fix date-combo2">
					<table width="1%" cellspacing="0" cellpadding="2" border="0" class="vmiddle-tbl m0px-fix">
						<tbody>
							<tr>
								<td align="left" valign="middle">Month&nbsp;</td>
								<td align="left" valign="middle">
                  <select name="expiry_month" id="expiry_month" class="txtbox m0px-fix fnone-fix">
									<option value="01">01</option>
									<option value="02">02</option>
									<option value="03">03</option>
									<option value="04">04</option>
									<option value="05">05</option>
									<option value="06">06</option>
									<option value="07">07</option>
									<option value="08">08</option>
									<option value="09">09</option>
									<option value="10">10</option>
									<option value="11">11</option>
									<option value="12">12</option>
								</select></td>
								<td align="left" valign="middle">Year&nbsp;</td>
								<td align="left" valign="middle">
                  <select name="expiry_year" id="expiry_year" class="txtbox m0px-fix fnone-fix">
									
				  				<?php 
									$yearDigit = date("y");
									for ($i=0; $i < 12; $i++) { 
									echo '<option value="'.($yearDigit + $i).'">'.($yearDigit + $i).'</option>';
								}	?>
								</select></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			
		</div>
	</div>



	<div class="full-width submit_wrapp">
    <div class="one_half last H1236">
        <div class="g-recaptcha" data-sitekey="6LfbOj4kAAAAAHukgHhTiCc9a2NbdWgDTLkq1PUn" ></div>
    </div>
    <input type="hidden" name="sponsorship_type" id="sponsorship_type" value="<?php echo $type; ?>">
    <input type="hidden" name="finale_amount" id="finale_amount" value="<?php echo $price; ?>">
		<input style="margin-top: 17px;" type="submit" value="Submit" onclick="sponsorhip_validation();" name="donate" id="donate" class="donate-submit-btn dsb">
	</div>

</div>

</form>

<div class="full-width disclaimer_text" style="clear: both; ">
  <p>*If you do not meet the qualifications for membership you will be refunded and contacted to discuss your interests</p>
</div>
<?php
	return ob_get_clean();
}