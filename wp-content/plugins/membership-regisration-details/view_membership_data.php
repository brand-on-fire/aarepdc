<?php
global $wpdb;
$table = 'aal10_membership_master';

if($_REQUEST['action']=='view')
{
  $headline = 'View Details';
  
  if($_REQUEST['action']=='view')
  { 
    $member_id = $_REQUEST['id'];
    $row_pp = $wpdb->get_row("SELECT * FROM ".$table." where membership_id='".$member_id."'" );
    if(count($row_pp) > 0)
    {
      $membership_type = $row_pp->membership_type;
      $first_name = $row_pp->first_name;
      $last_name = $row_pp->last_name;
      $company_name = $row_pp->company_name;
      $email_address = $row_pp->email_address;
      $phone_number = $row_pp->phone_number;
      $phone_type = $row_pp->phone_type;
      $address1 = $row_pp->address1;
      $address2 = $row_pp->address2;
      $city = $row_pp->city;
      $state = $row_pp->state;
      $postal_code = $row_pp->postal_code;
      $country = $row_pp->country;
      $college_university = $row_pp->college_university;
      $date_education = $row_pp->date_education;
      $degree_expected = $row_pp->degree_expected;
      $credits_completed = $row_pp->credits_completed;
      $relevant_experience = $row_pp->relevant_experience;
      $activity_anonymous = $row_pp->activity_anonymous;
      $message_support = $row_pp->message_support;
      $alternate_phone = $row_pp->alternate_phone;
      $fax = $row_pp->fax;
      $billing_address = $row_pp->billing_address;
      $billing_city = $row_pp->billing_city;
      $billing_state = $row_pp->billing_state;
      $billing_postal_code = $row_pp->billing_postal_code;
      $experience_real_estate = $row_pp->experience_real_estate;
      $other_real_estate_associat_membership = $row_pp->other_real_estate_associat_membership;
      $real_estate_industry_professional = $row_pp->real_estate_industry_professional;
      $comme_real_estate_sector = $row_pp->comme_real_estate_sector;
      $year_spent_in_indsustry = $row_pp->year_spent_in_indsustry;
      $committee_preference = $row_pp->committee_preference;
      $amount_deduct = $row_pp->amount_deduct;
      $card_name = $row_pp->card_name;
      $card_type = $row_pp->card_type;
      $date_added = $row_pp->date_added;
      $start_date = $row_pp->start_date;
      $end_date = $row_pp->end_date;
    }
    
  }
  
  
  
?>

<style type="text/css">
h2 {
    color: #664fa0 !important;
    border-top: 0px solid #c2c2c2;
    border-bottom: 1px solid #999999;
    padding: 10px 0;
}

.border {
    border-bottom: 1px solid #ccc;
}

/*
td { font-size: 14px; }
table.border, table.border td, table.border th {
  border-collapse:collapse;
  border:1px solid #666;
}
*/
</style>

<div class="wrap">
    <h2>
        <?php  _e($headline,'AAREP-PHL'); ?><a class="add-new-h2" href="admin.php?page=membership-registration">Back</a></h2>
    <div class="form">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #c2c2c2;padding: 10px;">
            <tr>
                <td align="left" valign="top">
                    <h2>Application Information</h2>
                </td>
            </tr>
            <tr>
                <td align="left" valign="top">
                    <table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
                        <tr>
                            <th width="170" align="left" valign="top">Membership Type:</th>
                            <td colspan="5" align="left" valign="top"><?php echo $membership_type; ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="left" valign="top">
                    <h2>General Information</h2>
                </td>
            </tr>
            <tr>
                <td align="left" valign="top">
                    <table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
                        <tr>
                            <th width="170" align="left" valign="top">First Name:</th>
                            <td colspan="5" align="left" valign="top"><?php echo $first_name; ?></td>
                        </tr>
                        <tr>
                            <th align="left" valign="top">Last Name:</th>
                            <td colspan="5" align="left" valign="top"><?php echo $last_name; ?></td>
                        </tr>
                        <tr>
                            <th align="left" valign="top">Company Name:</th>
                            <td colspan="5" align="left" valign="top"><?php if($company_name!="") { echo $company_name;} else { echo '-'; } ?></td>
                        </tr>
                        <th align="left" valign="top">E-mail Address:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $email_address; ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">Phone Number:</th>
                <td colspan="5" align="left" valign="top">
                    <?php if($phone_number!="") { echo $phone_number;} else { echo '-'; } ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">Phone Type:</th>
                <td colspan="5" align="left" valign="top">
                    <?php if($phone_type!="") { echo $phone_type;} else { echo '-'; }?></td>
            </tr>
            <tr>
                <th align="left" valign="top">Address1:</th>
                <td colspan="5" align="left" valign="top"><?php echo $address1; ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">Address2:</th>
                <td colspan="5" align="left" valign="top">
                    <?php if($address2!="") { echo $address2; } else { echo '-'; } ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">City:</th>
                <td colspan="5" align="left" valign="top"><?php echo $city; ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">State::</th>
                <td colspan="5" align="left" valign="top"><?php echo $state; ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">postal_code:</th>
                <td colspan="5" align="left" valign="top"><?php echo $postal_code; ?></td>
            </tr>
            <tr>
                <th align="left" valign="top">Country:</th>
                <td colspan="5" align="left" valign="top"><?php echo $country; ?></td>
            </tr>
            </tr>
        </table>
        </td>
        </tr>
        <?php if($membership_type == "Young Professional / Student") {?>
        <tr>
            <td align="left" valign="top">
                <h2>Student Information</h2>
            </td>
        </tr>
        <tr>
            <td align="left" valign="top">
                <table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
                    <tr>
                        <th width="170" align="left" valign="top">College/University:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $college_university; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Date of graduation:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $date_education; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Degree Expected:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $degree_expected; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Credits Completed:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $credits_completed; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Relevant Real Estate Experience:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $relevant_experience; ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php }?>
        <tr>
            <td align="left" valign="top">
                <h2>Billing Information</h2>
            </td>
        </tr>
        <tr>
            <td align="left" valign="top">
                <table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
                    <tr>
                        <th width="170" align="left" valign="top">Billing Address:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $billing_address; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">City:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $billing_city; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">State:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $billing_state; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Postal Code:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $billing_postal_code; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Years of experience in real estate industry:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $experience_real_estate; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Professional Level:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $real_estate_industry_professional; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Real Estate Sector:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $comme_real_estate_sector; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Committee preference:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $committee_preference; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Amount:</th>
                        <td colspan="5" align="left" valign="top"><?php echo '$'. number_format( $amount_deduct, 2); ?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td align="left" valign="top">
                <h2>Payment Information</h2>
            </td>
        </tr>
        <tr>
            <td align="left" valign="top">
                <table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
                  <?php if($card_type == 'Check') { ?>
                    <tr>
                        <th width="170" align="left" valign="top">Payment Type:</th>
                        <td colspan="5" align="left" valign="top"><?php echo 'Check'; ?></td>
                    </tr>
                  <?php } else if($card_type == 'Cash') { ?>
                    <tr>
                        <th width="170" align="left" valign="top">Payment Type:</th>
                        <td colspan="5" align="left" valign="top"><?php echo 'Cash'; ?></td>
                    </tr>
                  <?php } else { ?>    
                    <tr>
                        <th width="170" align="left" valign="top">Name on Card:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $card_name; ?></td>
                    </tr>
                    <tr>
                        <th align="left" valign="top">Card Type:</th>
                        <td colspan="5" align="left" valign="top"><?php echo $card_type; ?></td>
                    </tr>
                    <?php } ?>
                </table>
            </td>
        </tr>
        <tr>
            <td align="left" valign="top">
                <h2>Date Submission</h2>
            </td>
        </tr>
        <tr>
            <td align="left" valign="top">
                <table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
                    <tr>
                        <th width="170" align="left" valign="top">Date:</th>
                        <td colspan="5" align="left" valign="top"><?php echo date('m-d-Y', strtotime($date_added)); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        </table>
    </div>
</div>
<?php 
}

?>
<script type="text/javascript">
var home_url = '<?php echo PP_PLUGIN_URL;?>';
</script>