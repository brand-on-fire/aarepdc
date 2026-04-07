<?php
global $wpdb;
$table = $wpdb->prefix.'pp_buyer_package';

if($_REQUEST['action']=='delete' || $_REQUEST['action2']=='delete')
{
	
	if(isset($_REQUEST['pak_ids']) && $_REQUEST['_wpnonce']!='')
	{
		foreach($_REQUEST['pak_ids'] as $item)
		{	
			$end_time = strtotime('now');
			$cr_select = 'select * from '.$wpdb->prefix.'pp_package_buyer_relation where package_id = "'.$item.'" and end_time >= "'.$end_time.'"';
			$cr_result =  $wpdb->get_row($cr_select);
			if(count($cr_result) > 0){
				$_GET['msg'] = 'user_booked';
			}
			else{
				$wpdb->delete($table, array('package_id'=>$item));
				$_GET['msg']="delete";
			}
		}
	}
	else if($_REQUEST['pak_id'])
	{	
		$end_time = strtotime('now');
		$cr_select = 'select * from '.$wpdb->prefix.'pp_package_buyer_relation where package_id = "'.$_REQUEST['pak_id'].'" and end_time >= "'.$end_time.'"';
		$cr_result =  $wpdb->get_row($cr_select);
		if(count($cr_result) > 0){
			$_GET['msg'] = 'user_booked';
		}
		else{
			$wpdb->delete($table, array('package_id'=>$_REQUEST['pak_id']));
			$_GET['msg']="delete";
		}
	}
}
if($_REQUEST['action']=='add' || $_REQUEST['action']=='edit')
{
	$headline = 'Add New Package ';
	$pac_name = '';
	$pac_price = '';
	$pac_duration = '';
	$pac_duration_type = '';
	$pac_discount = '';
	$pac_credit_balance = '';
	if($_REQUEST['action']=='edit')
	{	
		$id = $_REQUEST['package_id'];
		$row_pp = $wpdb->get_row("SELECT * FROM ".$table." where package_id='".$id."'" );
		if(count($row_pp) > 0)
		{
			$pac_name = $row_pp->package_name;
			$pac_price = $row_pp->package_price;
			$pac_duration = $row_pp->package_duration;
			$pac_duration_type = $row_pp->package_duration_type;
			$pac_discount = $row_pp->package_discount;
			$pac_credit_balance = $row_pp->package_credit_balance;
			$pac_status = $row_pp->package_status;
		}
		$headline = 'Edit Package ';
	}
	if(isset($_POST['add_new_field']) && $_REQUEST['add_new_field']=='true')
	{
		$package_name = $_REQUEST['package_name'];
		$package_price = $_REQUEST['package_price'];
		$package_duration = $_REQUEST['package_duration'];
		$package_duration_type = $_REQUEST['package_duration_type'];
		$package_discount = $_REQUEST['package_discount'];
		$package_credit_balance = $_REQUEST['package_credit_balance'];
		if($_REQUEST['package_id']!='')
		{
			$id = $_REQUEST['package_id'];
			$ms = $wpdb->update($table,array('package_name'=>$package_name,'package_price'=>$package_price,'package_duration'=>$package_duration,'package_duration_type'=>$package_duration_type,'package_discount'=>$package_discount,'package_discount_type'=>'percentage','package_credit_balance'=>$package_credit_balance,'package_user_id'=>get_current_user_id(),'package_status'=>$pac_status,'date_added'=>date("Y-m-d")),array('package_id'=>$id));
			$msg = 'updated';
		}
		else
		{
			$ms = $wpdb->insert($table,array('package_name'=>$package_name,'package_price'=>$package_price,'package_duration'=>$package_duration,'package_duration_type'=>$package_duration_type,'package_discount'=>$package_discount,'package_discount_type'=>'percentage','package_credit_balance'=>$package_credit_balance,'package_user_id'=>get_current_user_id(),'package_status'=>'1','date_added'=>date("Y-m-d")));
			if($ms!=false)
			{ 
				$msg = 'inserted';
			}	
			
		}
		if($msg){ ?>
			<script>
				var admin_url ='<?php echo 'admin.php?page='.$_REQUEST['page'].'&msg='.$msg; ?>';
				window.location = admin_url;
			</script><?php	
		}
	}
?>
     <div class="wrap">
        <h2><?php  _e($headline,'photofolio'); ?><a class="add-new-h2" href="admin.php?page=<?php echo $_GET['page']; ?>">Back</a></h2>
        <div class="form">
            <form method="post" action="" onSubmit="return package_validate();">
                <table class="form-table" >
                    <tr valign="top">
                    	<th scope="row"><label><?php _e('Package Name:','photofolio'); ?></label></th>
                    	<td><input type="text" name="package_name" id="package_name" value="<?php echo $pac_name; ?>" placeholder="<?php _e('Package Name','photofolio'); ?>" />
                    	</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Price:','photofolio'); ?></label></th>
                        <td>
                        <input type="text" name="package_price" id="package_price" placeholder="<?php _e('Price','photofolio'); ?>" value="<?php echo $pac_price; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Duration:','photofolio'); ?></label></th>
                        <td>
                        <select name="package_duration" id="package_duration">
                        	<option value="">-Select Duration-</option>
                            <?php
							for($i=1;$i<13;$i++){?>
                            <option value="<?php echo $i;?>" <?php if($i == $pac_duration){echo 'selected=selected';}?>><?php echo $i;?></option><?php
							}
                            ?>
                        </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Duration Type:','photofolio'); ?></label></th>
                        <td>
                        <select name="package_duration_type" id="package_duration_type">
                        	<option value="">-Select Duration Type-</option>
                            <option value="month" <?php if('month' == $pac_duration_type){echo 'selected=selected';}?>>Monthly</option>
                            <option value="year" <?php if('year' == $pac_duration_type){echo 'selected=selected';}?>>Yearly</option>
                        </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Discount:','photofolio'); ?></label></th>
                        <td>
                        <input type="text" name="package_discount" id="package_discount" placeholder="<?php _e('Discount','photofolio'); ?>" value="<?php echo $pac_discount; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Credit Balance:','photofolio'); ?></label></th>
                        <td>
                        <input type="hidden" name="add_new_field" value="true">
                        <input type="text" name="package_credit_balance" id="package_credit_balance" placeholder="<?php _e('Credit Balance','photofolio'); ?>" value="<?php echo $pac_credit_balance; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"></th>
                        <td>
                        <input type="submit" name="submit" value="<?php _e('Submit','photofolio'); ?>" class="button-primary" />
                        </td>
                    </tr>
                </table>
            </form>
        </div>
	</div><?php 
}
else
{
	if( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	class Package_List_Table extends WP_List_Table {
		
		function __construct(){
			global $status, $page;
				parent::__construct( array(
					'singular'  => "Package Panel List",     //singular name of the listed records
					'plural'    => "Package Panel Lists",   //plural name of the listed records
					'ajax'      => false        //does this table support ajax?
		
			) );	
		}
		
		function get_columns(){
			$columns = array(
			'cb' => '<input type="checkbox" />',
			'package_id' => 'Package Id',
			'package_name' => 'Pacakge Name',
			'package_price' => 'Price (&pound;)',
			'package_duration'  =>'Duration <br>(in month)',
			//'duration_type' => 'Duration Type',
			'package_discount' => 'Discount',
			'package_credit_balance' => 'Credit Balance',
			//'date_added' => 'Date Added',
			//'user_id' => 'User',
			'package_status' =>'Status',
			'package_prime' => 'Prime Package'
			//'action' =>'Action'
			);
			return $columns;
		}
		function get_sortable_columns() {
			$sortable_columns = array(
				'package_id'  => array('package_id',true),
				'package_name'  => array('package_name',false),
				'package_price'  => array('package_price',false),
				'package_duration'  => array('package_duration',false),
				'package_discount'  => array('package_discount',false),
				'package_credit_balance'  => array('package_credit_balance',false),
			);
			return $sortable_columns;
		}
		function get_bulk_actions() {
			  $actions = array(
				'delete' => 'Delete'
			  );
			  return $actions;
		}
		function column_cb($item) {
			return sprintf(
				'<input type="checkbox" name="pak_ids[]" value="%s" />', $item->package_id
			);    
		}
		
		function column_package_id($item){
			
            $class = '<span class="add_ui_class"></span>';
			//Build row actions
			$actions = array(
				'edit'      => sprintf('<a href="?page=%s&action=%s&package_id=%s">Edit</a>',$_REQUEST['page'],'edit',$item->package_id),
				'delete'    => sprintf('<a href="?page=%s&action=%s&pak_id=%s">Delete</a>',$_REQUEST['page'],'delete',$item->package_id),
			);
			
			//Return the title contents
			return sprintf('%1$s %2$s %3$s',
				/*$2%s*/ $item->package_id,
				/*$3%s*/ $this->row_actions($actions),
                $class                
			);
		}
		function prepare_items() {
			global $wpdb,$_wp_column_headers;
			$prefix=$wpdb->prefix;
			$screen = get_current_screen();
			
			$search_text=!empty($_POST['s'])?$_POST['s']:"";
			
			$where="";
			
			if(!empty($search_text))
				$where="where package_name LIKE '%".$search_text."%' ";
			
			/* -- Preparing your query -- */
				$query = "SELECT * FROM ".$prefix."pp_buyer_package ".$where;
				
			/* -- Ordering parameters -- */
				//Parameters that are going to be used to order the result
				$orderby = !empty($_GET["orderby"]) ? ($_GET["orderby"]) : 'sort_order';
				$order = !empty($_GET["order"]) ? ($_GET["order"]) : 'ASC';
				if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
		
			/* -- Pagination parameters -- */
				//Number of elements in your table?
				$totalitems = $wpdb->query($query); //return the total number of affected rows
				//How many to display per page?
				$perpage = 20;
				//Which page is this?
				$paged = !empty($_GET["paged"]) ? ($_GET["paged"]) : '';
				//Page Number
				if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
				//How many pages do we have in total?
				$totalpages = ceil($totalitems/$perpage);
				//adjust the query to take pagination into account
				if(!empty($paged) && !empty($perpage)){
					$offset=($paged-1)*$perpage;
					$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
				}
		
			/* -- Register the pagination -- */
				$this->set_pagination_args( array(
					"total_items" => $totalitems,
					"total_pages" => $totalpages,
					"per_page" => $perpage,
				) );
				//The pagination links are automatically built according to those parameters
		
			/* -- Register the Columns -- */
				$columns = $this->get_columns();
				$hidden = array();
				$sortable = $this->get_sortable_columns();
				
				$this->_column_headers = array($columns, $hidden, $sortable);
				
				$_wp_column_headers[$screen->id]=$columns;
				
				
			/* -- Fetch the items -- */
				$this->items = $wpdb->get_results($query);
				
			}

		function column_default( $item, $column_name ) {
			global $wpdb;
			switch( $column_name ) { 
				case 'package_id':
				case 'package_name':
				case 'package_price':
				case 'package_duration':
				case 'package_discount':
				case 'package_credit_balance':
				case 'date_added':
					return _e($item->$column_name,'photofolio');	
				case 'package_status':
					if($item->package_status == 1){
						$status = '<a href="javascript:;" id="'.$item->package_id.'" onclick="pp_status_off('.$item->package_id.')" title="Click to deactive"><img src="'.PP_PLUGIN_URL.'/assets/img/active.png" class="status_image" /></a>'; 	
					}
					else{
						$status = '<a href="javascript:;" id="'.$item->package_id.'" onclick="pp_status_on('.$item->package_id.')" title="Click to active"><img src="'.PP_PLUGIN_URL.'/assets/img/deactive.png" class="status_image" /></a>'; 
					}
					//$status .= '<img src="'.PP_PLUGIN_URL.'/assets/img/ajax-loader.gif" class="status_image" style="display:none;" />';
					return $status;
				/*case 'action':
					$data = '<a href="admin.php?page='.$_REQUEST['page'].'&package_id='.$item->package_id.'&action=edit" title="Edit Package">Edit</a>';
					return	$data;*/
				case 'package_prime':
					if($item->package_prime == 1){
						return '<input type="radio" id="prime_'.$item->package_id.'" value="1" name="package_prime" checked="checked" onclick="make_package_prime('.$item->package_id.')">';
					}
					else{
						return '<input type="radio" id="prime_'.$item->package_id.'" value="1" name="package_prime" onclick="make_package_prime('.$item->package_id.')">';
					}
				default:
					return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
			}
		 }
	}
	$wp_list_table = new Package_List_Table();
	$wp_list_table->prepare_items();
	?>
	
	<div class="wrap">
	<h2><?php _e('Package Panel List  ','photofolio'); ?><a href="?page=<?php echo $_REQUEST['page']; ?>&action=add" class="add-new-h2"> <?php _e('Add New Package','photofolio'); ?></a></h2>
	  <div id="response-message">
	   <?php if($_GET['msg']){ ?>
       <div class="notice notice-success">
	   <?php if($_GET['msg'] == 'inserted'){ ?>
        	<p>Package successfully added.</p><?php 
		}?>
		<?php if($_GET['msg'] == 'updated'){ ?>
        	<p>Package successfully updated.</p><?php 
		}?>
		<?php if($_GET['msg'] == 'delete'){ ?>
        	<p>Package successfully deleted.</p><?php 
		}?>
		<?php if($_GET['msg'] == 'user_booked'){ ?>
        	<p>You can't delete this package.</p><?php 
		}?>
        </div>
		<?php }?>
       </div>
	  <form method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $wp_list_table->search_box( 'search', 'search_id' );?>
		<?php $wp_list_table->display() ?>
	  </form>
	</div><?php 
}?>
<script type="text/javascript">
var home_url = '<?php echo PP_PLUGIN_URL;?>';
</script>