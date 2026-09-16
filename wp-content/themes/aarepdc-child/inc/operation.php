<?php
require_once("../../../../wp-load.php");
date_default_timezone_set('US/Eastern');

$mode = isset($_GET["mode"]) ? sanitize_text_field($_GET["mode"]) : '';

/*
 * Safety boundary for the legacy forms on the configured WP Engine staging
 * site. This must run before Stripe is loaded or configured so no request can
 * reach the legacy live-key payment code from staging.
 */
$aarepdc_home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

if ( 'aarepstg.wpengine.com' === $aarepdc_home_host ) {
	if ( ! headers_sent() ) {
		status_header( 403 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo 'This legacy endpoint is disabled on staging. Nothing was submitted.';
	exit;
}

/* During the final member snapshot and migration, pause only membership registration. This
 * default-off gate runs before Stripe loads and leaves sponsorship/event modes untouched. */
if ( 'membership_online' === $mode && '1' === (string) get_option( 'aarepdc_membership_freeze_enabled', '0' ) ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! headers_sent() ) {
		status_header( 503 );
		nocache_headers();
		header( 'Retry-After: 600' );
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo 'Membership registration is briefly paused while AAREP DC completes the portal update. Please try again shortly.';
	exit;
}

/* Production membership cutover is controlled by the same default-off option as the portal UI.
 * Block only the legacy membership mode, before Stripe loads. Sponsor and event modes are left
 * untouched because this launch is membership-only. */
if ( 'membership_online' === $mode && '1' === (string) get_option( 'aarepdc_membership_cutover_enabled', '0' ) ) {
	if ( ! headers_sent() ) {
		status_header( 403 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo 'Membership registration has moved to the AAREP DC member portal.';
	exit;
}

unset( $aarepdc_home_host );

require_once('stripe/init.php');

$ApiKey = get_field('stripe_live_key', 850);
\Stripe\Stripe::setApiKey($ApiKey); // Client Live Account Key

if($mode=="membership_online")
{	
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
	$card_type=sanitize_text_field($_POST["card_type"]);
	$card_number=substr($_POST['card_number'],-4);
	$expiry_month=$_POST["expiry_month"];
	$expiry_year=$_POST["expiry_year"];
	
	$amount_deduct = $_POST['finale_amount'];
	
	$start_date = date('Y-m-d');	
	$end_date = date('Y-m-d', strtotime('+1 year'));

	$date_added = date('Y-m-d H:i:s');
		
		/* Start Payment Method */		
		$chargeamount=round(($amount_deduct) * 100);
		$currencies_code = 'usd';	
				
		//Create Token
		try {
		$result = \Stripe\Token::create(
				  array(
					"card" => array(
						"name" => $card_name,
						'address_line1'   => $_POST['address1'],
						'address_city'    => $_POST['city'],
						'address_state'   => $_POST['state'],
						'address_zip'     => $_POST['postal_code'],
						'address_country' => 'US',
						"number" => $_POST['card_number'],
						"exp_month" => $_POST['expiry_month'],
						"exp_year" => $_POST['expiry_year'],
						"cvc" => $_POST['card_cvc']
					)
				  ));
		}
		catch(\Stripe\Exception\CardException $e) {
				echo $e->getError()->type . '\n'. $e->getError()->code;
				exit();	
		}
		catch(Exception $e) {  
					$api_error = $e->getMessage();  
		} 
		$token = $result['id'];

		// Create a Customer
		try {  
		$customer = \Stripe\Customer::create(array( 
							'email' => $email_address, 
							'source'  => $token 
			)); 
		}
		catch(\Stripe\Exception\CardException $e) {
			echo $e->getError()->type . '\n'. $e->getError()->code;
			exit();	
		}
		catch(Exception $e) {  
				$api_error = $e->getMessage();  
		} 

		// Create Charge One Time
		try {
		$charge = \Stripe\Charge::create(array(
						  'amount' => $chargeamount,
						  'currency' => $currencies_code,
						  "customer" => $customer->id,
						  'description' => $membership_type,
						  'statement_descriptor' => 'Membership Register',
		));
		}
		catch(\Stripe\Exception\CardException $e) {
			echo $e->getError()->type . '\n'. $e->getError()->code;
			exit();	
		}
		catch(Exception $e) {  
				$api_error = $e->getMessage();  
		}

		// Create a plan 
		/*try { 
			$plan = \Stripe\Plan::create(array(  
				"product" => array( 
					"name" => 'Yearly Membership' 
				), 
				"amount" => $chargeamount, 
				"currency" => $currencies_code, 
				"interval" => 'year', 
				"interval_count" => 1 
			)); 
		}
		catch(\Stripe\Exception\CardException $e) {
			echo $e->getError()->type . '\n'. $e->getError()->code;
			exit();	
		}
		catch(Exception $e) { 
			$api_error = $e->getMessage(); 
		}*/

		// Creates a new subscription
		/*try { 
			$charge = \Stripe\Subscription::create(array( 
					"customer" => $customer->id, 
					"items" => array( 
						array( 
							"plan" => $plan->id, 
						), 
					), 
				)); 
		}
		catch(\Stripe\Exception\CardException $e) {
			echo $e->getError()->type . '\n'. $e->getError()->code;
			exit();	
		}
		catch(Exception $e) { 
			$api_error = $e->getMessage(); 
		} */

		
		if(empty($api_error) && $charge)
			{	
		
				$wpdb->query("insert into aal10_membership_master set
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
				   card_name = '".$card_name."',
				   card_type = '".$card_type."',
				   card_number = '".$card_number."',
				   start_date = '".$start_date."',
				   end_date = '".$end_date."',
				   date_added = '".$date_added."'
				");

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

			// send e-mail to admin
			$from =$email_address;
			$to = "info@aarepdc.org";
			$Bcc = "executive@aarepdc.org";
			//$to = "haresh@contactapex.in";
			//$Bcc = "ambaliya.haresh02@gmail.com";
		
			$subject = "Memberships Details Received at AAREP Washington DC";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\nBCc:".$Bcc."\r\n";
			$msgbody = "";
			$msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="7" style="font-family:Arial; font-size:12px;" align="left">';
		
			$msgbody .= '<tr><td>Hello,<br>Memberships Details Received at AAREP Washington DC.</td></tr>';
			$msgbody .= '<tr><td><table border="1" bordercolor="#000000"  rowheight="30" width="500" cellspacing="0" cellpadding="7" style="font-family:Arial; font-size:12px;border-collapse:collapse;" align="left">';
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Membership Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $membership_type."</td></tr>";

			/*if($general_option == "Business/Organization"){
				$msgbody = $msgbody . '<tr>
				<td align="left"  height="30"><strong>Busine Name:</strong> </td>
				<td >';
				$msgbody = $msgbody . $business_name."</td></tr>";
			}*/
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>First Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $first_name."</td></tr>";
					
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Last Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $last_name."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Company Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_name."</td></tr>";
		
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Email Address:</strong> </td>
			<td >';
			$msgbody = $msgbody . $email_address."</td></tr>";

			/*$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>User Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $user_name."</td></tr>";*/
		
			if($phone_number != ""){
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Phone Number:</strong> </td>
			<td >';
			$msgbody = $msgbody . $phone_number."</td></tr>"; }

			if($phone_number != ""){
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Phone Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $phone_type."</td></tr>"; }

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Address Line 1:</strong> </td>
			<td >';
			$msgbody = $msgbody . $address1."</td></tr>";

			if($address2 != ""){
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Address Line 2:</strong> </td>
			<td >';
			$msgbody = $msgbody . $address2."</td></tr>";
			}

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>City:</strong> </td>
			<td >';
			$msgbody = $msgbody . $city."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>State/Province:</strong> </td>
			<td >';
			$msgbody = $msgbody . $state."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Postal code:</strong> </td>
			<td >';
			$msgbody = $msgbody . $postal_code."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Country:</strong> </td>
			<td >';
			$msgbody = $msgbody . $country."</td></tr>";

			if($membership_type == "Young Professional / Student"){
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>College/University:</strong> </td>
			<td >';
			$msgbody = $msgbody . $college_university."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Date of Graduation:</strong> </td>
			<td >';
			$msgbody = $msgbody . $date_education."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Degree Expected:</strong> </td>
			<td >';
			$msgbody = $msgbody . $degree_expected."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong># of credits completed:</strong> </td>
			<td >';
			$msgbody = $msgbody . $credits_completed."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Relevant real estate Experience:</strong> </td>
			<td >';
			$msgbody = $msgbody . $relevant_experience."</td></tr>";
			}
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Billing Address:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_address."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>City:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_city."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>State/Province:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_state."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Postal code:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_postal_code."</td></tr>";	
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Years of experience:</strong> </td>
			<td >';
			$msgbody = $msgbody . $experience_real_estate."</td></tr>";
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Real Estate Industry Professional:</strong> </td>
			<td >';
			$msgbody = $msgbody . $real_estate_industry_professional."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Commercial real estate sector:</strong> </td>
			<td >';
			$msgbody = $msgbody . $comme_real_estate_sector."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Committee Preference:</strong> </td>
			<td >';
			$msgbody = $msgbody . $committee_preference."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Total Charge:</strong> </td>
			<td >';
			$msgbody = $msgbody . '$'.$amount_deduct."</td></tr>";	
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Name on Card:</strong> </td>
			<td >';
			$msgbody = $msgbody . $card_name."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Card Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $card_type."</td></tr>";
			
			$msgbody .= '</table></td></tr>';
			$msgbody .= '<tr><td><br>With Regards,<br>AAREP Washington DC<br></td></tr>';
			$msgbody .= '</table>';
		
		    //mail($to, $subject, $msgbody, $headers);
		    wp_mail( $to, $subject, $msgbody, $headers );


		    // // SEND MAIL TO user	
			// $to = $email_address;
			// $from = "info@aarepdc.org";
			// $subject = "Thank you for your membership to The AAREP Washington DC";
			// $headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			// $msgbody = "";
			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">

			// 	<tr><td>Order Confirmation<br />
			// 	African American Real Estate Professionals - DC</td></tr>				
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>We received your African American Real Estate Professionals DC order for '.$membership_type.' membership! Thank you for your purchase.<br />
			// 	</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>1 x $'.number_format($amount_deduct).'</td></tr>
			// 	<tr><td><strong>Total $'.number_format($amount_deduct).'</strong></td></tr>
			// 	<tr><td><strong>Payment Method: Stripe</strong><br /></td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>Please use the username and password below to activate your online account that will allow you to make free event registration.</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Username: '.$email_address.' <br />
			// 	Password: '.$password.' <br />	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thanks your support of AAREP DC!<br />
			// 	If you need assistance or have any questions, please email us at info@aarepdc.org. We are happy to help!
			// 	</td></tr>
			// 	';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br>African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			// //mail($to, $subject, $msgbody, $headers);
			// mail($to, $subject, $msgbody, $headers);

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
			// 	<tr><td><strong>Payment Method: Stripe</strong><br /></td></tr>
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
			
			wp_mail($to, $subject, $msgbody, $headers);

			//echo "success";
			echo "success_".$_POST['finale_amount']."_".$_POST["membership_type"]."";
			exit;
		}
		else
		{
			 $statusMsg = "Charge creation failed! $api_error"; 
			 echo $statusMsg;
			 exit();	
		}
}

else if($mode=="sponsorship_online")
{	

	global $wpdb;

	/* Your Details */	
	$sponsorship_type= sanitize_text_field($_POST["sponsorship_type"]);
	$first_name= sanitize_text_field($_POST["first_name"]);
	$last_name= sanitize_text_field($_POST["last_name"]);
	$full_name = $first_name." ".$last_name;

	$company_name= sanitize_text_field($_POST["company_name"]);	

	//$user_name= sanitize_text_field($_POST["user_name"]);
	$password= wp_generate_password();

	$email_address= sanitize_text_field($_POST["email_address"]);
	$company_address= sanitize_text_field($_POST["company_address"]);

	$billing_city= sanitize_text_field($_POST["billing_city"]);
	$billing_state= sanitize_text_field($_POST["billing_state"]);
	$billing_postal_code= sanitize_text_field($_POST["billing_postal_code"]);

	$payment_option= sanitize_text_field($_POST["payment_option"]);	

	$card_name= sanitize_text_field($_POST["card_name"]);
	$card_type= sanitize_text_field($_POST["card_type"]);
	$card_number=substr($_POST['card_number'],-4);
	$expiry_month=$_POST["expiry_month"];
	$expiry_year=$_POST["expiry_year"];
	
	$amount_deduct = $_POST['finale_amount'];
	
	$start_date = date('Y-m-d');	
	$end_date = date('Y-m-d', strtotime('+1 year'));

	$date_added = date('Y-m-d H:i:s');
		
		/* Start Payment Method */		
		$chargeamount=round(($amount_deduct) * 100);
		$currencies_code = 'usd';	
	if($payment_option == "Credit Card") 
	{
		
		// Create Token
		try {
		$result = \Stripe\Token::create(
				  array(
					"card" => array(
						"name" => $card_name,
						'address_line1'   => sanitize_text_field($_POST['company_address']),
						'address_city'    => sanitize_text_field($_POST['billing_city']),
						'address_state'   => sanitize_text_field($_POST['billing_state']),
						'address_zip'     => sanitize_text_field($_POST['billing_postal_code']),
						'address_country' => 'US',
						"number" => $_POST['card_number'],
						"exp_month" => $_POST['expiry_month'],
						"exp_year" => $_POST['expiry_year'],
						"cvc" => $_POST['card_cvc']
					)
				  ));
		}
		catch(\Stripe\Exception\CardException $e) {
				echo $e->getError()->type . '\n'. $e->getError()->code;
				exit();	
		}
		catch(Exception $e) {  
					$api_error = $e->getMessage();  
		} 
		$token = $result['id'];

		// Create a Customer
		try {  
		$customer = \Stripe\Customer::create(array( 
							'email' => $email_address, 
							'source'  => $token 
			)); 
		}
		catch(\Stripe\Exception\CardException $e) {
			echo $e->getError()->type . '\n'. $e->getError()->code;
			exit();	
		}
		catch(Exception $e) {  
				$api_error = $e->getMessage();  
		} 

		// Create Charge One Time
		try {
		$charge = \Stripe\Charge::create(array(
						  'amount' => $chargeamount,
						  'currency' => $currencies_code,
						  "customer" => $customer->id,
						  'description' => $sponsorship_type,
						  'statement_descriptor' => $sponsorship_type,
		));
		}
		catch(\Stripe\Exception\CardException $e) {
			echo $e->getError()->type . '\n'. $e->getError()->code;
			exit();	
		}
		catch(Exception $e) {  
				$api_error = $e->getMessage();  
		}
		
		if(empty($api_error) && $charge)
			{	
		
				$wpdb->query("insert into aal10_sponsorship_master set
				   sponsorhip_type = '".$sponsorship_type."',
				   first_name = '".$first_name."',
				   last_name = '".$last_name."',
				   company_name = '".$company_name."',
			
				   email_address = '".$email_address."',
				   billing_address = '".$company_address."',
				   billing_city = '".$billing_city."',
				   billing_state = '".$billing_state."',
				   billing_postal_code = '".$billing_postal_code."',

				   amount_deduct = '".$amount_deduct."',				   
				   card_name = '".$card_name."',
				   card_type = '".$card_type."',
				   card_number = '".$card_number."',
				   start_date = '".$start_date."',
				   end_date = '".$end_date."',
				   date_added = '".$date_added."'
				");

				$user_info = array(
					"user_pass"     => $password,
					"user_login"    => $email_address,
					"user_nicename" => $first_name,
					"user_email"    => $email_address,
					"display_name"  => $full_name,
					"first_name"    => $first_name,
					"last_name"     => $last_name,
					"role" 			=> 'subscriber'
				);

				$insert_user_info = wp_insert_user( $user_info );
        		
			// send e-mail to admin
			$from = $email_address;
			$to = "info@aarepdc.org";
			$Bcc = "executive@aarepdc.org";
			//$to = "haresh@contactapex.in";
			//$Bcc = "ambaliya.haresh02@gmail.com";
		
			$subject = "Sponsorships Details Received at AAREP Washington DC";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\nBCc:".$Bcc."\r\n";
			$msgbody = "";
			$msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="7" style="font-family:Arial; font-size:12px;" align="left">';
		
			$msgbody .= '<tr><td>Hello,<br>Sponsorships Details Received at AAREP Washington DC.</td></tr>';
			$msgbody .= '<tr><td><table border="1" bordercolor="#000000"  rowheight="30" width="500" cellspacing="0" cellpadding="7" style="font-family:Arial; font-size:12px;border-collapse:collapse;" align="left">';
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Sponsorship Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $sponsorship_type."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>First Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $first_name."</td></tr>";
					
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Last Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $last_name."</td></tr>";

			if($company_name != ""){	
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Company Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_name."</td></tr>"; }

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Email:</strong> </td>
			<td >';
			$msgbody = $msgbody . $email_address."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Billing Address:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_address."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>City:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_city."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>State/Province:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_state."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Postal code:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_postal_code."</td></tr>";
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Total Charge:</strong> </td>
			<td >';
			$msgbody = $msgbody . '$'.number_format($amount_deduct)."</td></tr>";	
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Name on Card:</strong> </td>
			<td >';
			$msgbody = $msgbody . $card_name."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Card Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $card_type."</td></tr>";
			
			$msgbody .= '</table></td></tr>';
			$msgbody .= '<tr><td><br>With Regards,<br>AAREP Washington DC<br></td></tr>';
			$msgbody .= '</table>';
		
		    //mail($to, $subject, $msgbody, $headers);
		    wp_mail( $to, $subject, $msgbody, $headers );


		    // // SEND MAIL TO user	
			// $to = $email_address;
			// $from = "info@aarepdc.org";
			// $subject = "We received your order for ".$sponsorship_type."! Thank you for your purchase.";
			// $headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			// $msgbody = "";
			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">

			// 	<tr><td>Order Confirmation<br />
			// 	African American Real Estate Professionals - DC</td></tr>				
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>We received your African American Real Estate Professionals DC order for '.$sponsorship_type.'! Thank you for your purchase.<br />
			// 	</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>1 x $'.number_format($amount_deduct).'</td></tr>
			// 	<tr><td><strong>Total $'.number_format($amount_deduct).'</strong></td></tr>
			// 	<tr><td><strong>Payment Method: Stripe</strong><br /></td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Please use the username and password below to activate your online account that will allow you to post jobs on our website.</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Username: '.$email_address.' <br />
			// 	Password: '.$password.' <br />	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>You will be prompted to change your username and password upon initial sign in.  <br />	
			// 	Please note that jobs will be automatically purged 90 days from the original posting date.   
			// 	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thanks your support of AAREP DC!<br />
			// 	If you need assistance or have any questions, please email us at info@aarepdc.org. We are happy to help!
			// 	</td></tr>
			// 	';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br>African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			// //mail($to, $subject, $msgbody, $headers);
			// mail($to, $subject, $msgbody, $headers);

			if($sponsorship_type == "JOB BANK"){
				$sponsorship_thankyou = get_field('job_bank_email_content', 215);
			} else {	
				$sponsorship_thankyou = get_field('sponsorship_email_content', 215);
			}

			

			// SEND MAIL TO user	
			$to = $email_address;
			$from = "info@aarepdc.org";
			$subject = "We received your order for ".$sponsorship_type."! Thank you for your purchase.";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			$msgbody = "";
			$msgbody .= 'Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',';
			$msgbody .= $sponsorship_thankyou;
			
			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">

			// 	<tr><td>Order Confirmation from African American Real Estate Professionals - DC </td></tr>				
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>Thank you for your '.$sponsorship_type.' for African American Real Estate Professionals-DC.<br />
			// 	</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>1 x $'.number_format($amount_deduct).'</td></tr>
			// 	<tr><td><strong>Total $'.number_format($amount_deduct).'</strong></td></tr>
			// 	<tr><td><strong>Payment Method: Stripe</strong><br /></td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Please use the username and password below to activate your online account that will allow you to post jobs on our website.</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Username: '.$email_address.' <br />
			// 	Password: '.$password.' <br />	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>You will be prompted to change your password upon initial sign in.  <br />	
			// 	Please note that jobs will be automatically purged 90 days from the original posting date.   
			// 	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thanks your support of AAREP DC! If you need assistance or have any questions, please email us at info@aarepdc.org. We are happy to help!
			// 	</td></tr>
			// 	';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br />African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			
			wp_mail($to, $subject, $msgbody, $headers);

			echo "success_".$_POST['finale_amount']."_".$_POST["sponsorship_type"]."";
			exit;
		}
		else
		{
			 $statusMsg = "Charge creation failed! $api_error"; 
			 echo $statusMsg;
			 exit();	
		}
	} else {

		$wpdb->query("insert into aal10_sponsorship_master set
				   sponsorhip_type = '".$sponsorship_type."',
				   first_name = '".$first_name."',
				   last_name = '".$last_name."',
				   company_name = '".$company_name."',

				   email_address = '".$email_address."',
				   billing_address = '".$company_address."',
				   billing_city = '".$billing_city."',
				   billing_state = '".$billing_state."',
				   billing_postal_code = '".$billing_postal_code."',

				   amount_deduct = '".$amount_deduct."',				   
				   card_name = '".$card_name."',
				   card_type = 'Check',
				   card_number = '".$card_number."',
				   start_date = '".$start_date."',
				   end_date = '".$end_date."',
				   date_added = '".$date_added."'
				");

				$user_info = array(
					"user_pass"     => $password,
					"user_login"    => $email_address,
					"user_nicename" => $first_name,
					"user_email"    => $email_address,
					"display_name"  => $full_name,
					"first_name"    => $first_name,
					"last_name"     => $last_name,
					"role" 			=> 'subscriber'
				);

				$insert_user_info = wp_insert_user( $user_info );
        		
			// send e-mail to admin
			$from = $email_address;
			$to = "info@aarepdc.org";
			$Bcc = "executive@aarepdc.org";
			//$to = "haresh@contactapex.in";
			//$Bcc = "ambaliya.haresh02@gmail.com";
		
			$subject = "Sponsorships Details Received at AAREP Washington DC";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\nBCc:".$Bcc."\r\n";
			$msgbody = "";
			$msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="7" style="font-family:Arial; font-size:12px;" align="left">';
		
			$msgbody .= '<tr><td>Hello,<br>Sponsorships Details Received at AAREP Washington DC.</td></tr>';
			$msgbody .= '<tr><td><table border="1" bordercolor="#000000"  rowheight="30" width="500" cellspacing="0" cellpadding="7" style="font-family:Arial; font-size:12px;border-collapse:collapse;" align="left">';
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Sponsorship Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $sponsorship_type."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>First Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $first_name."</td></tr>";
					
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Last Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $last_name."</td></tr>";

			if($company_name != ""){	
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Company Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_name."</td></tr>"; }

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Email:</strong> </td>
			<td >';
			$msgbody = $msgbody . $email_address."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Billing Address:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_address."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>City:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_city."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>State/Province:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_state."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Postal code:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_postal_code."</td></tr>";
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Total Charge:</strong> </td>
			<td >';
			$msgbody = $msgbody . '$'.number_format($amount_deduct)."</td></tr>";	
			
			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Name on Card:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $card_name."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Payment Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . 'Check'."</td></tr>";
			
			$msgbody .= '</table></td></tr>';
			$msgbody .= '<tr><td><br>With Regards,<br>AAREP Washington DC<br></td></tr>';
			$msgbody .= '</table>';
		
		    //mail($to, $subject, $msgbody, $headers);
		    wp_mail( $to, $subject, $msgbody, $headers );


		    // // SEND MAIL TO user	
			// $to = $email_address;
			// $from = "info@aarepdc.org";
			// $subject = "We received your order for ".$sponsorship_type."! Thank you for your purchase.";
			// $headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			// $msgbody = "";
			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">

			// 	<tr><td>Order Confirmation<br />
			// 	African American Real Estate Professionals - DC</td></tr>				
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>We received your African American Real Estate Professionals DC order for '.$sponsorship_type.'! Thank you for your purchase.<br />
			// 	</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>1 x $'.number_format($amount_deduct).'</td></tr>
			// 	<tr><td><strong>Total $'.number_format($amount_deduct).'</strong></td></tr>
			// 	<tr><td><strong>Payment Method: Stripe</strong><br /></td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Please use the username and password below to activate your online account that will allow you to post jobs on our website.</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Username: '.$email_address.' <br />
			// 	Password: '.$password.' <br />	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>You will be prompted to change your username and password upon initial sign in.  <br />	
			// 	Please note that jobs will be automatically purged 90 days from the original posting date.   
			// 	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thanks your support of AAREP DC!<br />
			// 	If you need assistance or have any questions, please email us at info@aarepdc.org. We are happy to help!
			// 	</td></tr>
			// 	';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br>African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			// //mail($to, $subject, $msgbody, $headers);
			// mail($to, $subject, $msgbody, $headers);

			if($sponsorship_type == "JOB BANK"){
				$sponsorship_thankyou = get_field('job_bank_email_content', 215);
			} else {	
				$sponsorship_thankyou = get_field('sponsorship_email_content', 215);
			}

			// SEND MAIL TO user	
			$to = $email_address;
			$from = "info@aarepdc.org";
			$subject = "We received your order for ".$sponsorship_type."! Thank you for your purchase.";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			$msgbody = "";
			$msgbody .= 'Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',';
			$msgbody .= $sponsorship_thankyou;

			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">

			// 	<tr><td>Order Confirmation from African American Real Estate Professionals - DC </td></tr>				
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>Thank you for your '.$sponsorship_type.' for African American Real Estate Professionals-DC.<br />
			// 	</td></tr>
			// 	<tr><td height="10"></td></tr>
			// 	<tr><td>1 x $'.number_format($amount_deduct).'</td></tr>
			// 	<tr><td><strong>Total $'.number_format($amount_deduct).'</strong></td></tr>
			// 	<tr><td><strong>Payment Method: Check</strong><br /></td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Please use the username and password below to activate your online account that will allow you to post jobs on our website.</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Username: '.$email_address.' <br />
			// 	Password: '.$password.' <br />	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>You will be prompted to change your password upon initial sign in.  <br />	
			// 	Please note that jobs will be automatically purged 90 days from the original posting date.   
			// 	</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thanks your support of AAREP DC! If you need assistance or have any questions, please email us at info@aarepdc.org. We are happy to help!
			// 	</td></tr>
			// 	';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br />African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			
			wp_mail($to, $subject, $msgbody, $headers);

			echo "success_".$_POST['finale_amount']."_".$_POST["sponsorship_type"]."";
			exit;
	}
}

else if($mode=="check_user_exists") 
{
	$user_name = $_POST['user_name'];

	if ( username_exists( $user_name ) ) {
           echo "user_exists";
		   exit;
    }
}

else if($mode=="check_email_exists") 
{
	$email_address = $_POST['email_address'];

	if ( email_exists( $email_address ) ) {
           echo "email_exists";
		   exit;
    }
}

else if($mode=="event_registration")
{	

	global $wpdb;

	/* Your Details */	
		
	$first_name=sanitize_text_field($_POST["first_name"]);
	$last_name=sanitize_text_field($_POST["last_name"]);
	$email_address=sanitize_text_field($_POST["email_address"]);

	// $com_first_name=sanitize_text_field($_POST["com_first_name"]);
	// $com_last_name=sanitize_text_field($_POST["com_last_name"]);
	$company_name=sanitize_text_field($_POST["company_name"]);
	// $com_email_address=sanitize_text_field($_POST["com_email_address"]);

	$billing_address=sanitize_text_field($_POST["billing_address"]);
	$billing_city=sanitize_text_field($_POST["billing_city"]);
	$billing_state=sanitize_text_field($_POST["billing_state"]);
	$billing_postal_code=sanitize_text_field($_POST["billing_postal_code"]);

	$card_name=sanitize_text_field($_POST["card_name"]);
	$card_type=sanitize_text_field($_POST["card_type"]);
	$card_number=substr($_POST['card_number'],-4);
	$expiry_month=$_POST["expiry_month"];
	$expiry_year=$_POST["expiry_year"];
	
	$event_id = $_POST['event_id'];
	$event_title = get_the_title($event_id);
	$amount_deduct = $_POST['event_amount'];
	$member_check = $_POST['member_check'];
	
	$date_added = date('Y-m-d H:i:s');


		
		/* Start Payment Method */		
		$chargeamount=round(($amount_deduct) * 100);
		$currencies_code = 'usd';	
	

		if($amount_deduct != 0) {


			// Create Token
			try {
			$result = \Stripe\Token::create(
					  array(
						"card" => array(
							"name" => $card_name,
							'address_line1'   => sanitize_text_field($_POST['billing_address']),
							'address_city'    => sanitize_text_field($_POST['billing_city']),
							'address_state'   => sanitize_text_field($_POST['billing_state']),
							'address_zip'     => sanitize_text_field($_POST['billing_postal_code']),
							'address_country' => 'US',
							"number" => $_POST['card_number'],
							"exp_month" => $_POST['expiry_month'],
							"exp_year" => $_POST['expiry_year'],
							"cvc" => $_POST['card_cvc']
						)
					  ));
			}
			catch(\Stripe\Exception\CardException $e) {
					echo $e->getError()->type . '\n'. $e->getError()->code;
					exit();	
			}
			catch(Exception $e) {  
						$api_error = $e->getMessage();  
			} 
			$token = $result['id'];

			// Create Customer
			try {  
			$customer = \Stripe\Customer::create(array( 
								'email' => $email_address, 
								'source'  => $token 
				)); 
			}
			catch(\Stripe\Exception\CardException $e) {
				echo $e->getError()->type . '\n'. $e->getError()->code;
				exit();	
			}
			catch(Exception $e) {  
					$api_error = $e->getMessage();  
			} 

			// Create Charge One Time
			try {
			$charge = \Stripe\Charge::create(array(
							  'amount' => $chargeamount,
							  'currency' => $currencies_code,
							  "customer" => $customer->id,
							  'description' => 'Event Registration',
							  'statement_descriptor' => 'Event Registration',
			));
			}
			catch(\Stripe\Exception\CardException $e) {
				echo $e->getError()->type . '\n'. $e->getError()->code;
				exit();	
			}
			catch(Exception $e) {  
					$api_error = $e->getMessage();  
			}
			
			if(empty($api_error) && $charge)
				{		
					$wpdb->query("insert into aal10_event_registration_master set
					
					   first_name = '".$first_name."',
					   last_name = '".$last_name."',
					   email_address = '".$email_address."',
					   
					   company_name = '".$company_name."',  
					   					   
					   billing_address = '".$billing_address."',
					   billing_city = '".$billing_city."',
					   billing_state = '".$billing_state."',
					   billing_postal_code = '".$billing_postal_code."',
				   
					   card_name = '".$card_name."',
					   card_type = '".$card_type."',
					   card_number = '".$card_number."',
					   event_id = '".$event_id."',
					   event_fees = '".$amount_deduct."',
					   date_added = '".$date_added."'
					");

        		
			// send e-mail to admin
			$from = $email_address;
			$to = "info@aarepdc.org";
			$Bcc = "executive@aarepdc.org";
			//$to = "ambaliya.haresh@yahoo.com";
		
			$subject = "Event Registration Details Received at AAREP Washington DC";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\nBCc:".$Bcc."\r\n";
			$msgbody = "";
			$msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="7" style="font-family:Arial; font-size:12px;" align="left">';
		
			$msgbody .= '<tr><td>Hello,<br>Event Registration Details Received at AAREP Washington DC.</td></tr>';
			$msgbody .= '<tr><td><table border="1" bordercolor="#000000"  rowheight="30" width="500" cellspacing="0" cellpadding="7" style="font-family:Arial; font-size:12px;border-collapse:collapse;" align="left">';
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Event Title:</strong> </td>
			<td >';
			$msgbody = $msgbody . $event_title."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>First Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $first_name."</td></tr>";
					
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Last Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $last_name."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Email:</strong> </td>
			<td >';
			$msgbody = $msgbody . $email_address."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Company First Name:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $com_first_name."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Company Last Name:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $com_last_name."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Company Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_name."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Company Email:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $com_email_address."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Billing Address:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_address."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>City:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_city."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>State/Province:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_state."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Postal code:</strong> </td>
			<td >';
			$msgbody = $msgbody . $billing_postal_code."</td></tr>";
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Event Fees:</strong> </td>
			<td >';
			$msgbody = $msgbody . '$'.number_format($amount_deduct)."</td></tr>";	
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Name on Card:</strong> </td>
			<td >';
			$msgbody = $msgbody . $card_name."</td></tr>";
			
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Card Type:</strong> </td>
			<td >';
			$msgbody = $msgbody . $card_type."</td></tr>";
		
			$msgbody .= '</table></td></tr>';
			$msgbody .= '<tr><td><br>With Regards,<br>AAREP Washington DC<br></td></tr>';
			$msgbody .= '</table>';
		
		    //mail($to, $subject, $msgbody, $headers);
		    wp_mail( $to, $subject, $msgbody, $headers );

			$event_thankyou = get_field('event_email_content', 13);	

		    // SEND MAIL TO user	
			$to = $email_address;
			$from = "info@aarepdc.org";
			$subject = "Thank you for your event registration.";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			$msgbody = "";
			$msgbody .= 'Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',';
			$msgbody .= $event_thankyou;

			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thank you for your event registration!<br />
			// 		    WE look forward to having you involved in the AAREP Washington DC.
			// 	</td></tr>';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br>African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			//mail($to, $subject, $msgbody, $headers);
			
			wp_mail($to, $subject, $msgbody, $headers);

			echo "success_".$_POST['event_amount']."";
			exit;
		}
		else
		{
			 $statusMsg = "Charge creation failed! $api_error"; 
			 echo $statusMsg;
			 exit();	
		}

	} else {

		$wpdb->query("insert into aal10_event_registration_master set					
						first_name = '".$first_name."',
						last_name = '".$last_name."',
						email_address = '".$email_address."',
						
						company_name = '".$company_name."',  
												
						event_id = '".$event_id."',
						event_fees = '".$amount_deduct."',
						date_added = '".$date_added."'
					");

        		
			// send e-mail to admin
			$from = $email_address;
			$to = "info@aarepdc.org";
			$Bcc = "executive@aarepdc.org";
			//$to = "ambaliya.haresh@yahoo.com";
		
			$subject = "Event Registration Details Received at AAREP Washington DC";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\nBCc:".$Bcc."\r\n";
			$msgbody = "";
			$msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="7" style="font-family:Arial; font-size:12px;" align="left">';
		
			$msgbody .= '<tr><td>Hello,<br>Event Registration Details Received at AAREP Washington DC.</td></tr>';
			$msgbody .= '<tr><td><table border="1" bordercolor="#000000"  rowheight="30" width="500" cellspacing="0" cellpadding="7" style="font-family:Arial; font-size:12px;border-collapse:collapse;" align="left">';
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Event Title:</strong> </td>
			<td >';
			$msgbody = $msgbody . $event_title."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>First Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $first_name."</td></tr>";
					
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Last Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $last_name."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Email:</strong> </td>
			<td >';
			$msgbody = $msgbody . $email_address."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Company First Name:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $com_first_name."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Company Last Name:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $com_last_name."</td></tr>";

			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Company Name:</strong> </td>
			<td >';
			$msgbody = $msgbody . $company_name."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Company Email:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $com_email_address."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Billing Address:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $billing_address."</td></tr>";
			
			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>City:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $billing_city."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>State/Province:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $billing_state."</td></tr>";

			// $msgbody = $msgbody . '<tr>
			// <td align="left"  height="30"><strong>Postal code:</strong> </td>
			// <td >';
			// $msgbody = $msgbody . $billing_postal_code."</td></tr>";
						
			$msgbody = $msgbody . '<tr>
			<td align="left"  height="30"><strong>Event Fees:</strong> </td>
			<td >';
			$msgbody = $msgbody . '$'.number_format($amount_deduct)."</td></tr>";	
					
			$msgbody .= '</table></td></tr>';
			$msgbody .= '<tr><td><br>With Regards,<br>AAREP Washington DC<br></td></tr>';
			$msgbody .= '</table>';
		
		    //mail($to, $subject, $msgbody, $headers);
		    wp_mail( $to, $subject, $msgbody, $headers );

			$event_thankyou = get_field('event_email_content', 13);	

		    // SEND MAIL TO user	
			$to = $email_address;
			$from = "info@aarepdc.org";
			$subject = "Thank you for your event registration.";
			$headers = "From: ".$from."\r\nContent-type: text/html\r\nMIME-Version: 1.0\r\nBounce-to:$from\r\n";
			$msgbody = "";
			$msgbody .= 'Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',';
			$msgbody .= $event_thankyou;
			
			// $msgbody .= '<table border="0" bordercolor="#000000" rowheight="30" width="80%" cellspacing="1" cellpadding="1" style="font-family:Arial; font-size:12px;" align="left">
			// 	<tr><td>Dear '.ucwords($first_name)."&nbsp;".ucwords($last_name).',</td></tr>
			// 	<tr><td height="20"></td></tr>
			// 	<tr><td>Thank you for your event registration!<br />
			// 		    WE look forward to having you involved in the AAREP Washington DC.
			// 	</td></tr>';
			// $msgbody .= '<tr><td height="30"><br>Sincerely,<br>African American Real Estate Professionals DC <br />
			// 1325 G Street NW Suite 500, <br />
			// Washington, District of Columbia 20005, <br />
			// United States
			// </td></tr></table>';	
			//mail($to, $subject, $msgbody, $headers);
			
			wp_mail($to, $subject, $msgbody, $headers);

			echo "success_".$_POST['event_amount']."";
			exit;

	}	

}
