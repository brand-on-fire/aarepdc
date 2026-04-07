<?php
global $wpdb;
$table = 'aal10_event_registration_master';

if($_REQUEST['action']=='view')
{
  $headline = 'View Details';
  
  if($_REQUEST['action']=='view')
  { 
    $event_id = $_REQUEST['id'];
    $row_pp = $wpdb->get_row("SELECT * FROM ".$table." where event_registration_id ='".$event_id."'" );
    if(count($row_pp) > 0)
    {
      
      
      $company_address = $row_pp->company_address;
      $company_type = $row_pp->company_type;
      $event_id = $row_pp->event_id;
      
      $first_name = $row_pp->first_name;
      $last_name = $row_pp->last_name;
      $billing_address = $row_pp->billing_address;
      $billing_city = $row_pp->billing_city;
      $billing_state = $row_pp->billing_state;
      $billing_postal_code = $row_pp->billing_postal_code;
      $email_address = $row_pp->email_address;
      $event_fees = $row_pp->event_fees;
      $card_name = $row_pp->card_name;
      $card_type = $row_pp->card_type;
      $date_added = $row_pp->date_added;

    }
    
  }

  foreach( $wpdb->get_results("SELECT post_title FROM `adc10_posts` WHERE `ID` = ".$event_id."") as $key => $row) {
    // each column in your row will be accessible like this
    $my_column = $row->post_title;
  }
?>

<style type="text/css">
h2  { color: #664fa0!important; border-top: 0px solid #c2c2c2;
    border-bottom: 1px solid #999999;
    padding: 10px 0;}
.border{border-bottom:1px solid #ccc;}

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
    <?php  _e($headline,'AAREP-PHL'); ?><a class="add-new-h2" href="admin.php?page=sponsor_info">Back</a></h2>
    <div class="form">
       <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #c2c2c2;padding: 10px;">
      <tr>
        <td align="left" valign="top"><h2>General Information</h2></td>
      </tr>
      <tr>
        <td align="left" valign="top"><table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
        <tr>
            <th width="170" align="left" valign="top">Event Name:</th>
            <td colspan="5" align="left" valign="top"><?php echo $my_column; ?></td>
        </tr>
        <tr>
            <th width="170" align="left" valign="top">First Name:</th>
            <td colspan="5" align="left" valign="top"><?php echo $first_name; ?></td>
        </tr>
          <tr>
            <th align="left" valign="top">Last Name:</th>
            <td colspan="5" align="left" valign="top"><?php echo $last_name; ?></td>
            </tr>
            <th align="left" valign="top">E-mail Address:</th>
            <td colspan="5" align="left" valign="top"><?php echo $email_address; ?></td>
            </tr>  
          <th align="left" valign="top">Billing Address:</th>
            <td colspan="5" align="left" valign="top"><?php echo $billing_address; ?></td>
            </tr>
            <th align="left" valign="top">City:</th>
            <td colspan="5" align="left" valign="top"><?php echo $billing_city; ?></td>
            </tr>
            <th align="left" valign="top">State:</th>
            <td colspan="5" align="left" valign="top"><?php echo $billing_state; ?></td>
            </tr>
            <th align="left" valign="top">Postal Code:</th>
            <td colspan="5" align="left" valign="top"><?php echo $billing_postal_code; ?></td>
            </tr>   
        </table></td>
      </tr>
      <tr>
        <td align="left" valign="top"><h2>Payment Information</h2></td>
      </tr>
      <tr>
        <td align="left" valign="top"><table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
        
        <?php if($card_type == "Cheque") { ?>
        <tr>
            <th align="left" valign="top" style="width: 16%;">Payment Type:</th>
            <td colspan="5" align="left" valign="top"><?php echo $card_type; ?></td>
        </tr> 
        <?php } else { ?>
        <tr>
            <th width="170" align="left" valign="top">Amount:</th>
            <td colspan="5" align="left" valign="top"><?php echo '$'.$event_fees; ?></td>
        </tr>
        <tr>
            <th width="170" align="left" valign="top">Card Name:</th>
            <td colspan="5" align="left" valign="top"><?php echo $card_name; ?></td>
        </tr>
        <tr>
            <th align="left" valign="top">Card Type:</th>
            <td colspan="5" align="left" valign="top"><?php echo $card_type; ?></td>
        </tr>   
        <?php } ?>  
        </table></td>
      </tr>

      
      
      <tr>
        <td align="left" valign="top"><h2>Date Submission</h2></td>
      </tr>
      <tr>
        <td align="left" valign="top"><table width="100%" border="0" cellpadding="5" cellspacing="0" class="border">
          <tr>
            <th width="170" align="left" valign="top">Date:</th>
            <td colspan="5" align="left" valign="top"><?php echo date('m-d-Y', strtotime($date_added)); ?></td>
            </tr>
        </table></td>
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