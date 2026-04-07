<?php 
date_default_timezone_set('US/Eastern');
if (isset($_POST['ers_add_event'] ) && wp_verify_nonce($_POST['ers_add_event'], 'event_details' )){
	global $wpdb;

	/* Your Details */	
	$membership_type=sanitize_text_field($_POST["membership_type"]);
	$general_option=sanitize_text_field($_POST["general_option"]);
	//$business_name=$_POST["business_name"];  
	$first_name=sanitize_text_field($_POST["first_name"]);
	$last_name=sanitize_text_field($_POST["last_name"]);

	$full_name = $first_name." ".$last_name;

	//$user_name= sanitize_text_field($_POST["user_name"]);
	$password= wp_generate_password();

	$company_name=sanitize_text_field($_POST["company_name"]);
	$email_address=sanitize_text_field($_POST["email_address"]);
	$phone_number=sanitize_text_field($_POST["phone_number"]);

	if($phone_number != "") {
		$phone_type=sanitize_text_field($_POST["phone_type"]);
	}
	
	$address1=sanitize_text_field($_POST["address1"]);
	$address2=sanitize_text_field($_POST["address2"]);
	$city=sanitize_text_field($_POST["city"]);
	$state=sanitize_text_field($_POST["state"]);
	$postal_code=sanitize_text_field($_POST["postal_code"]);
	$country=sanitize_text_field($_POST["country"]);

	if($membership_type == "Young Professional / Student"){
		$college_university=sanitize_text_field($_POST["college_university"]);
		$date_education=sanitize_text_field($_POST["date_education"]);
		$degree_expected=sanitize_text_field($_POST["degree_expected"]);
		$credits_completed=sanitize_text_field($_POST["credits_completed"]);
		$relevant_experience=sanitize_text_field($_POST["relevant_experience"]);
	}	

	$message_support=sanitize_text_field($_POST["message_support"]);

	$company_address=sanitize_text_field($_POST["company_address"]);

	$billing_city=sanitize_text_field($_POST["billing_city"]);
	$billing_state=sanitize_text_field($_POST["billing_state"]);
	$billing_postal_code=sanitize_text_field($_POST["billing_postal_code"]);

	//$other_real_estate_associat_membership=$_POST["other_real_estate_associat_membership"]; 03 Dec2022

	$experience_real_estate=sanitize_text_field($_POST["experience_real_estate"]);
	
	//$website_member=$_POST["website_member"]; 03 Dec2022

	$real_estate_industry_professional=sanitize_text_field($_POST["real_estate_industry_professional"]);
		
	$comme_real_estate_sector = sanitize_text_field($_POST["comme_real_estate_sector"]);

	//$year_spent_in_indsustry = $_POST["year_spent_in_indsustry"]; 03 Dec2022

	//$career_professionals = $_POST["career_professionals"]; Hide on 31 Oct2022

	$committee_preference = implode(", ", $_POST["committee_preference"]);
	$card_name=sanitize_text_field($_POST["card_name"]);
	$card_type=sanitize_text_field($_POST["payment_type"]);
	$card_number=substr($_POST['card_number'],-4);
	$expiry_month=$_POST["expiry_month"];
	$expiry_year=$_POST["expiry_year"];
	
	$amount_deduct = $_POST['finale_amount'];
	
	$start_date = date('Y-m-d');	
	$end_date = date('Y-m-d', strtotime('+1 year'));

	$date_added = date('Y-m-d H:i:s');

	// $ms = $wpdb->insert('aal10_event_registration_master',array('first_name'=>$first_name,'last_name'=>$last_name,'email_address'=>$email_address,'billing_address'=>$billing_address,'billing_city'=>$billing_city,'billing_state'=>$billing_state,'billing_postalcode'=>$billing_postalcode,'date_added'=>$date_added));

  $ms =  $wpdb->query("insert into aal10_membership_master set
    membership_type = '".$membership_type."',
    first_name = '".$first_name."',
    last_name = '".$last_name."',
    company_name = '".$company_name."',
    email_address = '".$email_address."',
    phone_number = '".$phone_number."',
    phone_type = '".$phone_type."',
    address1 = '".$address1."',
    address2 = '".$address2."',
    city = '".$city."',
    state = '".$state."',
    postal_code = '".$postal_code."',
    country = '".$country."',
    college_university = '".$college_university."',
    date_education = '".$date_education."',
    degree_expected = '".$degree_expected."',
    credits_completed = '".$credits_completed."',
    relevant_experience = '".$relevant_experience."',
    billing_address = '".$company_address."',
    billing_city = '".$billing_city."',
    billing_state = '".$billing_state."',
    billing_postal_code = '".$billing_postal_code."',
    experience_real_estate = '".$experience_real_estate."',
    real_estate_industry_professional = '".$real_estate_industry_professional."',
    comme_real_estate_sector = '".$comme_real_estate_sector."',
    committee_preference = '".$committee_preference."',
    amount_deduct = '".$amount_deduct."',
    			   
    card_type = '".$card_type."',
    
    start_date = '".$start_date."',
    end_date = '".$end_date."',
    date_added = '".$date_added."'
  ");
	  
  if($ms!=false) { 

            $user_info = array(
                "user_pass"     => $password,
                "user_login"    => $email_address,
                "user_nicename" => $first_name,
                "user_email"    => $email_address,
                "display_name"  => $full_name,
                "first_name"    => $first_name,
                "last_name"     => $last_name,
                "role" 			=> 'member'
            );

            $insert_user_info = wp_insert_user( $user_info );

            update_user_meta( $insert_user_info, 'billing_address', $company_address );
            update_user_meta( $insert_user_info, 'billing_city', $billing_city );
            update_user_meta( $insert_user_info, 'billing_state', $billing_state );
            update_user_meta( $insert_user_info, 'billing_postal_code', $billing_postal_code );
            update_user_meta( $insert_user_info, 'phone_number', $phone_number );
            update_user_meta( $insert_user_info, 'membership_start_date', $start_date );
            update_user_meta( $insert_user_info, 'membership_end_date', $end_date );

            $member_thankyou = get_field('membership_email_content', 850);
    
            // SEND MAIL TO user	
			$to = $email_address;
			$from = "info@aarepdc.org";
			$subject = "Thank you for your membership to The AAREP Washington DC";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			$msgbody = "";

            $msgbody .=  'Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',';
			$msgbody .= $member_thankyou;

			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">

			// 	<tr><td>Order Confirmation from African American Real Estate Professionals - DC  </td></tr>				
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>We received your membership for AAREP-DC.<br />
			// 	</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>1 x $'.number_format($amount_deduct).'</td></tr>
			// 	<tr><td><strong>Total $'.number_format($amount_deduct).'</strong></td></tr>
			// 	<tr><td><strong>Payment Method: Check</strong><br /></td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>Please use the username and password below to activate your online account. Please use your username and password to register for all AAREP-DC events for a discounted rate.  </td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Username: '.$email_address.' <br />
			// 	Password: '.$password.' <br />	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thank you for your support of AAREP-DC!</td></tr>
            //     <tr><td height="20"></td></tr>
			// 	<tr><td>If you need assistance or have any questions, please email us at info@aarepdc.org. We are happy to help!
			// 	</td></tr>
			// 	';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br>African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			
			mail($to, $subject, $msgbody, $headers);
    
    
    $msg = 'success'; ?>
        <script>
			var admin_url ='<?php echo 'admin.php?page=membership-registration&msg='.$msg; ?>';
			window.location = admin_url;
		</script>
  <?php
	}	
}
?>
<style>
input[type="date"],
input[type="datetime-local"],
input[type="datetime"],
input[type="email"],
input[type="month"],
input[type="number"],
input[type="password"],
input[type="search"],
input[type="tel"],
input[type="text"],
input[type="time"],
input[type="url"],
input[type="week"] {
    padding: 6px 15px !important;
    line-height: 2;
    min-height: 30px;
    width: 240px;
}
</style>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<div class="wrap">
    <h2>
        <?php  _e('Add Membership Details','aarepdc'); ?>
        <a class="add-new-h2" href="admin.php?page=membership-registration">Back to Main Page</a>
    </h2>
    <?php if($msg){ ?>
    <?php if($msg == 'success'){ ?>
    <div class="updated notice notice-success is-dismissible" id="message">
        <p>Details Added successfully.</p>
        <button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this
                notice.</span></button>
    </div>
    <?php }?>
    <?php }?>
    <div class="form">
        <form id="membership_form" method="post" action="" onSubmit="return membership_validate();">
            <table class="form-table" style="width: 850px;">
                <tr valign="top">
                    <td style="width: 33%;"><input type="radio" value="General membership" id="general_membership" name="membership_type" checked=""><label for="general_membership"><strong>General Membership - $250</strong></label> </td>
                    <td style="width: 33%;"><input type="radio" value="Non-Profit / Government" id="non_profit_government" name="membership_type"><label for="non_profit_government"><strong>Government - $100</strong></label>
                    </td>
                    <td style="width: 33%;"><input type="radio" value="Young Professional / Student" id="young_professional_student" name="membership_type"><label for="young_professional_student"><strong>Young Professional / Student - 
										$100</strong></label></td>
                </tr>
                <tr valign="top">
                    <td style="width: 33%;"><input type="text" name="first_name" id="first_name"
                            placeholder="First name*"> </td>
                    <td style="width: 33%;"><input type="text" name="last_name" id="last_name" placeholder="Last name*">
                    </td>
                    <td style="width: 33%;"><input type="text" name="company_name" id="company_name"
                            placeholder="Company name"></td>
                </tr>

                <tr valign="top">
                    <td style="width: 33%;"><input type="email" name="email_address" id="email_address"
                            onblur="email_exists_val();" placeholder="Email address*"> </td>
                    <td style="width: 33%;"><input type="text" maxlength="14" onkeydown="phone_formate(event,this.id)"
                            name="phone_number" id="phone_number" placeholder="Phone number"> </td>
                    <td style="width: 33%;">
                        <input type="radio" value="Work" id="mdl_work" name="phone_type"><label
                            for="mdl_work">Work</label>
                        <input type="radio" value="Mobile" id="mdl_mobile" name="phone_type" checked=""><label
                            for="mdl_mobile">Mobile</label>
                    </td>
                </tr>

                <tr valign="top">
                    <td style="width: 33%;"><input type="text" name="address1" id="address1" value=""
                            placeholder="Address line 1*"> </td>
                    <td style="width: 33%;"><input type="text" name="address2" id="address2" value=""
                            placeholder="Address line 2"> </td>
                    <td style="width: 33%;"> <input type="text" name="city" id="city" placeholder="City*"> </td>
                </tr>

                <tr valign="top">
                    <td style="width: 33%;"><input type="text" name="state" id="state" placeholder="State/Province*">
                    </td>
                    <td style="width: 33%;"><input maxlength="10" type="text" name="postal_code" id="postal_code"
                            value="" placeholder="Postal code*"> </td>
                    <td style="width: 33%;"> <select name="country" id="country" value=""
                            style="width: 235px;padding: 5px 15px;">
                            <option value="" selected="">Select Country*</option>
                            <option value="CA">Canada</option>
                            <option value="AF">Afghanistan</option>
                            <option value="AL">Albania</option>
                            <option value="DZ">Algeria</option>
                            <option value="AS">American Samoa</option>
                            <option value="AD">Andorra</option>
                            <option value="AO">Angola</option>
                            <option value="AI">Anguilla</option>
                            <option value="AQ">Antarctica</option>
                            <option value="AG">Antigua and Barbuda</option>
                            <option value="AR">Argentina</option>
                            <option value="AM">Armenia</option>
                            <option value="AW">Aruba</option>
                            <option value="AU">Australia</option>
                            <option value="AT">Austria</option>
                            <option value="AZ">Azerbaijan</option>
                            <option value="BS">Bahamas</option>
                            <option value="BH">Bahrain</option>
                            <option value="BD">Bangladesh</option>
                            <option value="BB">Barbados</option>
                            <option value="BY">Belarus</option>
                            <option value="BE">Belgium</option>
                            <option value="BZ">Belize</option>
                            <option value="BJ">Benin</option>
                            <option value="BM">Bermuda</option>
                            <option value="BT">Bhutan</option>
                            <option value="BO">Bolivia</option>
                            <option value="BA">Bosnia and Herzegovina</option>
                            <option value="BW">Botswana</option>
                            <option value="BV">Bouvet Island</option>
                            <option value="BR">Brazil</option>
                            <option value="IO">British Indian Ocean Territory</option>
                            <option value="BN">Brunei</option>
                            <option value="BG">Bulgaria</option>
                            <option value="BF">Burkina Faso</option>
                            <option value="BI">Burundi</option>
                            <option value="KH">Cambodia</option>
                            <option value="CM">Cameroon</option>
                            <option value="CV">Cape Verde</option>
                            <option value="KY">Cayman Islands</option>
                            <option value="CF">Central African Republic</option>
                            <option value="TD">Chad</option>
                            <option value="CL">Chile</option>
                            <option value="CN">China</option>
                            <option value="CX">Christmas Island</option>
                            <option value="CC">Cocos (Keeling) Islands</option>
                            <option value="CO">Colombia</option>
                            <option value="KM">Comoros</option>
                            <option value="CG">Congo</option>
                            <option value="CK">Cook Islands</option>
                            <option value="CR">Costa Rica</option>
                            <option value="CI">Cote d'Ivoire</option>
                            <option value="HR">Croatia (Hrvatska)</option>
                            <option value="CU">Cuba</option>
                            <option value="CY">Cyprus</option>
                            <option value="CZ">Czech Republic</option>
                            <option value="CD">Congo (DRC)</option>
                            <option value="DK">Denmark</option>
                            <option value="DJ">Djibouti</option>
                            <option value="DM">Dominica</option>
                            <option value="DO">Dominican Republic</option>
                            <option value="TP">East Timor</option>
                            <option value="EC">Ecuador</option>
                            <option value="EG">Egypt</option>
                            <option value="SV">El Salvador</option>
                            <option value="GQ">Equatorial Guinea</option>
                            <option value="ER">Eritrea</option>
                            <option value="EE">Estonia</option>
                            <option value="ET">Ethiopia</option>
                            <option value="FK">Falkland Islands (Islas Malvinas)</option>
                            <option value="FO">Faroe Islands</option>
                            <option value="FJ">Fiji Islands</option>
                            <option value="FI">Finland</option>
                            <option value="FR">France</option>
                            <option value="GF">French Guiana</option>
                            <option value="PF">French Polynesia</option>
                            <option value="TF">French Southern and Antarctic Lands</option>
                            <option value="GA">Gabon</option>
                            <option value="GM">Gambia</option>
                            <option value="GE">Georgia</option>
                            <option value="DE">Germany</option>
                            <option value="GH">Ghana</option>
                            <option value="GI">Gibraltar</option>
                            <option value="GR">Greece</option>
                            <option value="GL">Greenland</option>
                            <option value="GD">Grenada</option>
                            <option value="GP">Guadeloupe</option>
                            <option value="GU">Guam</option>
                            <option value="GT">Guatemala</option>
                            <option value="GN">Guinea</option>
                            <option value="GW">Guinea-Bissau</option>
                            <option value="GY">Guyana</option>
                            <option value="HT">Haiti</option>
                            <option value="HM">Heard Island and McDonald Islands</option>
                            <option value="HN">Honduras</option>
                            <option value="HK">Hong Kong SAR</option>
                            <option value="HU">Hungary</option>
                            <option value="IS">Iceland</option>
                            <option value="IN">India</option>
                            <option value="ID">Indonesia</option>
                            <option value="IR">Iran</option>
                            <option value="IQ">Iraq</option>
                            <option value="IE">Ireland</option>
                            <option value="IL">Israel</option>
                            <option value="IT">Italy</option>
                            <option value="JM">Jamaica</option>
                            <option value="JP">Japan</option>
                            <option value="JO">Jordan</option>
                            <option value="KZ">Kazakhstan</option>
                            <option value="KE">Kenya</option>
                            <option value="KI">Kiribati</option>
                            <option value="KR">Korea</option>
                            <option value="KW">Kuwait</option>
                            <option value="KG">Kyrgyzstan</option>
                            <option value="LA">Laos</option>
                            <option value="LV">Latvia</option>
                            <option value="LB">Lebanon</option>
                            <option value="LS">Lesotho</option>
                            <option value="LR">Liberia</option>
                            <option value="LY">Libya</option>
                            <option value="LI">Liechtenstein</option>
                            <option value="LT">Lithuania</option>
                            <option value="LU">Luxembourg</option>
                            <option value="MO">Macao SAR</option>
                            <option value="MK">Macedonia</option>
                            <option value="MG">Madagascar</option>
                            <option value="MW">Malawi</option>
                            <option value="MY">Malaysia</option>
                            <option value="MV">Maldives</option>
                            <option value="ML">Mali</option>
                            <option value="MT">Malta</option>
                            <option value="MH">Marshall Islands</option>
                            <option value="MQ">Martinique</option>
                            <option value="MR">Mauritania</option>
                            <option value="MU">Mauritius</option>
                            <option value="YT">Mayotte</option>
                            <option value="MX">Mexico</option>
                            <option value="FM">Micronesia</option>
                            <option value="MD">Moldova</option>
                            <option value="MC">Monaco</option>
                            <option value="MN">Mongolia</option>
                            <option value="MS">Montserrat</option>
                            <option value="MA">Morocco</option>
                            <option value="MZ">Mozambique</option>
                            <option value="MM">Myanmar</option>
                            <option value="NA">Namibia</option>
                            <option value="NR">Nauru</option>
                            <option value="NP">Nepal</option>
                            <option value="NL">Netherlands</option>
                            <option value="AN">Netherlands Antilles</option>
                            <option value="NC">New Caledonia</option>
                            <option value="NZ">New Zealand</option>
                            <option value="NI">Nicaragua</option>
                            <option value="NE">Niger</option>
                            <option value="NG">Nigeria</option>
                            <option value="NU">Niue</option>
                            <option value="NF">Norfolk Island</option>
                            <option value="KP">North Korea</option>
                            <option value="MP">Northern Mariana Islands</option>
                            <option value="NO">Norway</option>
                            <option value="OM">Oman</option>
                            <option value="PK">Pakistan</option>
                            <option value="PW">Palau</option>
                            <option value="PA">Panama</option>
                            <option value="PG">Papua New Guinea</option>
                            <option value="PY">Paraguay</option>
                            <option value="PE">Peru</option>
                            <option value="PH">Philippines</option>
                            <option value="PN">Pitcairn Islands</option>
                            <option value="PL">Poland</option>
                            <option value="PT">Portugal</option>
                            <option value="PR">Puerto Rico</option>
                            <option value="QA">Qatar</option>
                            <option value="RE">Reunion</option>
                            <option value="RO">Romania</option>
                            <option value="RU">Russia</option>
                            <option value="RW">Rwanda</option>
                            <option value="WS">Samoa</option>
                            <option value="SM">San Marino</option>
                            <option value="ST">Sao Tome and Principe</option>
                            <option value="SA">Saudi Arabia</option>
                            <option value="SN">Senegal</option>
                            <option value="YU">Serbia and Montenegro</option>
                            <option value="SC">Seychelles</option>
                            <option value="SL">Sierra Leone</option>
                            <option value="SG">Singapore</option>
                            <option value="SK">Slovakia</option>
                            <option value="SI">Slovenia</option>
                            <option value="SB">Solomon Islands</option>
                            <option value="SO">Somalia</option>
                            <option value="ZA">South Africa</option>
                            <option value="GS">South Georgia and the South Sandwich Islands</option>
                            <option value="ES">Spain</option>
                            <option value="LK">Sri Lanka</option>
                            <option value="SH">St. Helena</option>
                            <option value="KN">St. Kitts and Nevis</option>
                            <option value="LC">St. Lucia</option>
                            <option value="PM">St. Pierre and Miquelon</option>
                            <option value="VC">St. Vincent and the Grenadines</option>
                            <option value="SD">Sudan</option>
                            <option value="SR">Suriname</option>
                            <option value="SJ">Svalbard and Jan Mayen</option>
                            <option value="SZ">Swaziland</option>
                            <option value="SE">Sweden</option>
                            <option value="CH">Switzerland</option>
                            <option value="SY">Syria</option>
                            <option value="TW">Taiwan</option>
                            <option value="TJ">Tajikistan</option>
                            <option value="TZ">Tanzania</option>
                            <option value="TH">Thailand</option>
                            <option value="TG">Togo</option>
                            <option value="TK">Tokelau</option>
                            <option value="TO">Tonga</option>
                            <option value="TT">Trinidad and Tobago</option>
                            <option value="TN">Tunisia</option>
                            <option value="TR">Turkey</option>
                            <option value="TM">Turkmenistan</option>
                            <option value="TC">Turks and Caicos Islands</option>
                            <option value="TV">Tuvalu</option>
                            <option value="UG">Uganda</option>
                            <option value="UA">Ukraine</option>
                            <option value="AE">United Arab Emirates</option>
                            <option value="GB">United Kingdom</option>
                            <option value="US" selected="">United States</option>
                            <option value="UM">United States Minor Outlying Islands</option>
                            <option value="UY">Uruguay</option>
                            <option value="UZ">Uzbekistan</option>
                            <option value="VU">Vanuatu</option>
                            <option value="VA">Vatican City</option>
                            <option value="VE">Venezuela</option>
                            <option value="VN">Viet Nam</option>
                            <option value="VG">Virgin Islands (British)</option>
                            <option value="VI">Virgin Islands</option>
                            <option value="WF">Wallis and Futuna</option>
                            <option value="YE">Yemen</option>
                            <option value="ZM">Zambia</option>
                            <option value="ZW">Zimbabwe</option>
                        </select> </td>
                </tr>
                
                <tr valign="top" class="student_section">
                    <td><strong style="font-size: 18px;">STUDENT INFORMATION</strong></td>
                    <td> &nbsp;  </td>
                    <td>&nbsp;</td>
                </tr>

                <tr valign="top" class="student_section">
                    <td style="width: 33%;"><input type="text" name="college_university" value="" id="college_university" placeholder="College/University*"> </td>
                    <td style="width: 33%;"><input type="text" name="date_education" value="" id="date_education" placeholder="Date of graduation - mm/dd/yyyy *" class="hasDatepicker"> </td>
                    <td style="width: 33%;"> <input type="text" name="degree_expected" id="degree_expected" placeholder="Degree Expected*"> </td>
                </tr>

                <tr valign="top" class="student_section">
                    <td style="width: 33%;"><input type="text" name="credits_completed" id="credits_completed" placeholder="# of credits completed"> </td>
                    <td style="width: 33%;" colspan="2"><textarea style="width: 450px;" id="relevant_experience" name="relevant_experience" placeholder="Relevant real estate Experience"></textarea> </td>
                    
                </tr>
                
                <tr valign="top" class="billing_information">
                    <td><strong style="font-size: 18px;">BILLING INFORMATION</strong></td>
                    <td> &nbsp;  </td>
                    <td>&nbsp;</td>
                </tr>
          
                <tr valign="top">
                    <td style="width: 50%;"><input type="checkbox" id="same_as_above" name="same_as_above"
                            value="1"><label for="same_as_above">Same as above</label> </td>
                    <td style="width: 50%;">&nbsp;</td>
                </tr>

                <tr valign="top">
                    <td style="width: 33%;"><input type="text" name="company_address" value="" id="company_address"
                            placeholder="Billing address*"> </td>
                    <td style="width: 33%;"><input type="text" name="billing_city" id="billing_city"
                            placeholder="City*"> </td>
                    <td style="width: 33%;"> <input type="text" name="billing_state" id="billing_state"
                            placeholder="State/Province*"> </td>
                </tr>

                <tr valign="top">
                    <td style="width: 50%;"><input maxlength="10" type="text" name="billing_postal_code"
                            id="billing_postal_code" value="" placeholder="Postal code*"> </td>
                    <td style="width: 50%;"><input type="text" name="experience_real_estate" value=""
                            id="experience_real_estate" placeholder="Years of experience in real estate industry*"
                            title="Years of experience in real estate industry"> </td>
                </tr>

                <tr valign="top">
                    <td> Professional Level* (please check one) </td>
                    <td><select style="width: 235px;padding: 5px 15px;" name="real_estate_industry_professional" id="real_estate_industry_professional"
                            class="rei_professional">
                            <option value="">Select</option>
                            <option value="Entry Level">Entry Level</option>
                            <option value="Associate">Associate</option>
                            <option value="Mid-Senior Level">Mid-Senior Level</option>
                            <option value="Executive">Executive</option>
                            <option value="CEO/Founder">CEO/Founder</option>
                            <option value="Retired">Retired</option>
                        </select> </td>
                </tr>

                <tr valign="top">
                    <td> Real Estate Sector* (please check one) </td>
                    <td><select style="width: 235px;padding: 5px 15px;" name="comme_real_estate_sector" id="comme_real_estate_sector" class="rei_professional">
                            <option value="">Select</option>
                            <option value="Architecture">Architecture</option>
                            <option value="Affordable housing">Affordable housing</option>
                            <option value="Brokerage">Brokerage</option>
                            <option value="Capital Markets">Capital Markets</option>
                            <option value="Construction">Construction</option>
                            <option value="Development">Development</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Finance">Finance</option>
                            <option value="Government">Government</option>
                            <option value="Green Building">Green Building</option>
                            <option value="Law">Law</option>
                            <option value="Property management">Property management</option>
                            <option value="Proptech">Proptech</option>
                        </select> </td>
                </tr>

                <tr valign="top">
                  <td colspan="2">Committee preference* (please check at least one)</td>
                </tr>

                <tr valign="top">
                  <td style="width: 33%;"><input type="checkbox" id="co_events" name="committee_preference[]" value="Programming"><label for="co_events"> Programming </label></td>
                  <td style="width: 33%;"><input type="checkbox" id="co_outreach" name="committee_preference[]" value="Community Engagement"><label for="co_outreach"> Community Engagement</label></td>
                  <td style="width: 33%;"><input type="checkbox" id="co_communications" name="committee_preference[]" value="Gala"><label for="co_communications"> Gala</label></td>
                </tr>

                <tr valign="top">
                  <td style="width: 33%;"><input type="checkbox" id="co_fundraising" name="committee_preference[]" value="Membership and Sponsorships"><label for="co_fundraising"> Membership and Sponsorships</label></td>
                  <td style="width: 33%;"><input type="checkbox" id="co_governance" name="committee_preference[]" value="Legislative / Advocacy"><label for="co_governance"> Legislative / Advocacy</label></td>
                  <td style="width: 33%;"><input type="checkbox" id="co_membership" name="committee_preference[]" value="Young Professionals"><label for="co_membership"> Young Professionals</label></td>
                </tr>

                <tr valign="top">
                  <td style="width: 33%;"><input type="checkbox" id="n_a" name="committee_preference[]" value="N/A"><label for="n_a"> N/A </label></td>
                  <td style="width: 33%;">&nbsp;</td>
                  <td style="width: 33%;">&nbsp;</td>
                </tr>

                <tr valign="top" class="billing_information">
                    <td><strong style="font-size: 18px;">PAYMENT INFORMATION</strong></td>
                    <td> &nbsp; </td>
                    <td>&nbsp;</td>
                </tr>
                 
                <tr valign="top">
                    <td>Select Payment Type</td>
                    <td>
                      <input type="radio" value="Check" id="payment_cheque" name="payment_type" checked=""><label for="payment_cheque"><strong>Check</strong></label> 
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"></th>
                    <td><input type="hidden" name="pp_event_id" id="pp_event_id" />
                    <div class="g-recaptcha" data-sitekey="6LfbOj4kAAAAAHukgHhTiCc9a2NbdWgDTLkq1PUn" style="margin-bottom: 25px;" ></div>
                    <input type="hidden" name="finale_amount" id="finale_amount" value="250" />
                        <input type="submit" name="submit" value="<?php _e('Submit','aarepdc'); ?>"
                            class="button-primary" />
                        <?php wp_nonce_field("event_details","ers_add_event");?>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<script type="text/javascript">
var plugin_url = '<?php echo PP_PLUGIN_URL;?>';
</script>