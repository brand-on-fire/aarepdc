 <?php
global $wpdb;
$table = $wpdb->prefix.'register_employee';

if($_REQUEST['action']=='delete' || $_REQUEST['action2']=='delete')
{
	if(isset($_REQUEST['employee_ids']) && $_REQUEST['_wpnonce']!='')
	{
		foreach($_REQUEST['employee_ids'] as $item)
		{	
			$wpdb->delete($table, array('employee_id'=>$item));
			$_GET['msg']="delete";
		}
	}
	else if($_REQUEST['employee_id'])
	{	
		$wpdb->delete($table, array('employee_id'=>$_REQUEST['employee_id']));
	    $_GET['msg']="delete";
	}
}	
if($_REQUEST['action']=='add' || $_REQUEST['action']=='edit')
{
	$headline = 'Add New Employee ';
	$employee_first_name = '';
	$employee_last_name = '';
	$employee_email = '';
	if($_REQUEST['action']=='edit')
	{	
		$id = $_REQUEST['employee_id'];
		$row_pp = $wpdb->get_row("SELECT * FROM ".$table." where employee_id='".$id."'" );
		if(count($row_pp) > 0)
		{
			$emp_first_name = $row_pp->employee_first_name;
			$emp_middle_name = $row_pp->employee_middle_name;
			$emp_last_name = $row_pp->employee_last_name;
			$emp_email = $row_pp->employee_email;
			$emp_phone = $row_pp->employee_phone;
		}
		$headline = 'Edit Employee ';
	}
	if(isset($_POST['add_new_field']) && $_REQUEST['add_new_field']=='true')
	{
		$employee_first_name = $_REQUEST['employee_first_name'];
		$employee_middle_name = $_REQUEST['employee_middle_name'];
		$employee_last_name = $_REQUEST['employee_last_name'];
		$employee_email = $_REQUEST['employee_email'];
		$employee_phone = $_REQUEST['employee_phone'];
		
		if($_REQUEST['employee_id']!='')
		{
			$id = $_REQUEST['employee_id'];
			$ms = $wpdb->update($table,array('employee_first_name'=>$employee_first_name,'employee_middle_name'=>$employee_middle_name,'employee_last_name'=>$employee_last_name,'employee_email'=>$employee_email,'employee_phone'=>$employee_phone),array('employee_id'=>$id));
			$msg = 'updated';
		}
		else
		{
			$ms = $wpdb->insert($table,array('employee_first_name'=>$employee_first_name,'employee_middle_name'=>$employee_middle_name,'employee_last_name'=>$employee_last_name,'employee_email'=>$employee_email,'employee_phone'=>$employee_phone));
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
            <form method="post" action="" onSubmit="return employee_validate();">
                <table class="form-table" >
                    <tr valign="top">
                    	<th scope="row"><label><?php _e('First Name:','photofolio'); ?></label></th>
                    	<td><input type="text" name="employee_first_name" id="employee_first_name" value="<?php echo $emp_first_name; ?>" placeholder="<?php _e('First Name','photofolio'); ?>" />
                    	</td>
                    </tr>
					  <tr valign="top">
                        <th scope="row"><label><?php _e('Middle Name:','photofolio'); ?></label></th>
                        <td>
                        <input type="text" name="employee_middle_name" id="employee_middle_name" placeholder="<?php _e('Middle Name','photofolio'); ?>" value="<?php echo $emp_middle_name; ?>" />
                        </td>
                    </tr>
					  <tr valign="top">
                        <th scope="row"><label><?php _e('Last Name:','photofolio'); ?></label></th>
                        <td>
                        <input type="text" name="employee_last_name" id="employee_last_name" placeholder="<?php _e('Last Name','photofolio'); ?>" value="<?php echo $emp_last_name; ?>" />
                        </td>
                    </tr>
                  
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Email:','photofolio'); ?></label></th>
                        <td>
                        <input type="text" name="employee_email" id="employee_email" placeholder="<?php _e('Email','photofolio'); ?>" value="<?php echo $emp_email; ?>" />
                        </td>
                    </tr>
					
					
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Phone:','photofolio'); ?></label></th>
                        <td>
						 <input type="hidden" name="add_new_field" value="true">
                        <input type="text" name="employee_phone" id="employee_phone" placeholder="<?php _e('Phone','photofolio'); ?>" value="<?php echo $emp_phone; ?>" />
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
	class Employee_List_Table extends WP_List_Table {
		
		function __construct(){
			global $status, $page;
				parent::__construct( array(
					'singular'  => "Employee Panel List",     //singular name of the listed records
					'plural'    => "Employee Panel Lists",   //plural name of the listed records
					'ajax'      => false        //does this table support ajax?
		
			) );	
		}
		
		function get_columns(){
			$columns = array(
			'cb' => '<input type="checkbox" />',
			'employee_id' => 'Employee Id',
			'employee_first_name' => 'First Name',
			'employee_middle_name' => 'Middle Name',
			'employee_last_name' => 'Last Name',
			'employee_email' => 'Email',
			'employee_phone' => 'Phone',			
			);
			return $columns;
		}
		function get_sortable_columns() {
			$sortable_columns = array(
				'employee_id'  => array('employee_id',true),
				'employee_first_name'  => array('employee_first_name',false),
				'employee_middle_name'  => array('employee_middle_name',false),
				'employee_last_name'  => array('employee_last_name',false),
				'employee_email'  => array('employee_email',false),
				'employee_phone'  => array('employee_phone',false),
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
				'<input type="checkbox" name="employee_ids[]" value="%s" />', $item->employee_id
			);    
		}
		
		function column_employee_id($item){
			
            $class = '<span class="add_ui_class"></span>';
			//Build row actions
			$actions = array(
				'edit'      => sprintf('<a href="?page=%s&action=%s&employee_id=%s">Edit</a>',$_REQUEST['page'],'edit',$item->employee_id),
				//'delete'    => sprintf('<a href="?page=%s&action=%s&employee_id=%s">Delete</a>',$_REQUEST['page'],'delete',$item->employee_id),
				'delete'    => sprintf('<a href="javascript:;" onClick="employee_delete(%s);">Delete</a>',$item->employee_id),
			);
			
			//Return the title contents
			return sprintf('%1$s %2$s %3$s',
				/*$2%s*/ $item->employee_id,
				/*$3%s*/ $this->row_actions($actions),
                $class                
			);
		}
		function prepare_items() 
		{
			global $wpdb,$_wp_column_headers;
			$prefix=$wpdb->prefix;
			$screen = get_current_screen();
			
			$search_text=!empty($_POST['s'])?$_POST['s']:"";
			
			$where="";
			
			if(!empty($search_text))
				$where="where employee_first_name LIKE '%".$search_text."%' or employee_last_name LIKE '%".$search_text."%' or employee_middle_name LIKE '%".$search_text."%'  or employee_email LIKE '%".$search_text."%'";
			
			/* -- Preparing your query -- */
				$query = "SELECT * FROM ".$prefix."register_employee ".$where;
				
			/* -- Ordering parameters -- */
				//Parameters that are going to be used to order the result
				$orderby = !empty($_GET["orderby"]) ? ($_GET["orderby"]) : 'employee_id';
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
					"" => $totalitems,
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
				case 'employee_id':
				case 'employee_first_name':
				case 'employee_middle_name':
				case 'employee_last_name':
				case 'employee_email':	
				case 'employee_phone':		
				return _e($item->$column_name,'photofolio');			
				default:
					return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
			}
		 }
	}
	$wp_list_table = new Employee_List_Table();
	$wp_list_table->prepare_items();
	?>
	
	<div class="wrap">
	<h2><?php _e('Employee Panel List  ','photofolio'); ?><a href="?page=<?php echo $_REQUEST['page']; ?>&action=add" class="add-new-h2"> <?php _e('Add New Employee','photofolio'); ?></a></h2>
	  <div id="response-message">
	   <?php if($_GET['msg']){ ?>
       <div class="notice notice-success">
	  	 <?php if($_GET['msg'] == 'inserted'){ ?>
		<p>Employee successfully added.</p><?php }?>
		<?php if($_GET['msg'] == 'updated'){ ?>
		<p>Employee successfully updated.</p><?php }?>
		<?php if($_GET['msg'] == 'delete'){ ?>
		<p>Employee successfully deleted.</p><?php }?>		
        </div>
		<?php }?>
       </div>
	  <form method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $wp_list_table->search_box( 'search', 'search_id' );?>
		<?php $wp_list_table->display(); ?>
	  </form>
	</div><?php 
}?>
<script type="text/javascript">
var home_url = '<?php echo PP_PLUGIN_URL;?>';
</script>