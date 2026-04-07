<?php 
global $wpdb;
$table = 'aal10_sponsorship_master';

if($_REQUEST['action']=='delete' || $_REQUEST['action2']=='delete')
{
	
	if(isset($_REQUEST['sponsorhip_ids']) && $_REQUEST['_wpnonce']!='')
	{
		foreach($_REQUEST['sponsorhip_ids'] as $item)
		{	
			$wpdb->delete($table, array('sponsorhip_id'=>$item));
			$_GET['msg']="delete";
		}
	}
	else if($_REQUEST['sponsorhip_id'])
	{	
		$wpdb->delete($table, array('sponsorhip_id'=>$_REQUEST['sponsorhip_id']));
		wp_redirect( 'admin.php?page=sponsorship-registration&msg=delete' );
	    //$_GET['msg']="delete";
	}
}
?>
<div class="wrap">
        <h2><?php  _e($headline,'photofolio'); ?><a class="add-new-h2" href="admin.php?page=add-sponsorship-details">Add New</a></h2>
		<?php if($_GET['msg'] == 'success'){ ?>
    <div class="updated notice notice-success is-dismissible" id="message">
        <p>Details Added successfully.</p>
        <button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this
                notice.</span></button>
    </div>
    <?php }?>
</div>

<?php
	if( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	
	class Sponsorship_List_Table extends WP_List_Table {
		
		function __construct(){
			global $status, $page;
				parent::__construct( array(
					'singular'  => "Sponsorship Information",     //singular name of the listed records
					'plural'    => "Sponsorship Information",   //plural name of the listed records
					'ajax'      => false        //does this table support ajax?
		
			) );	
		}
		
		function get_columns(){
			$columns = array( 
			//'cb' => '<input type="checkbox" />',
			'sponsorhip_type' => 'Sponsorhip Type',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'email_address' => 'Email',
			'date_added' => 'Date Added',
			'action' => 'Action',
			);
			return $columns;
		}
		
		function get_sortable_columns() {
			$sortable_columns = array(
				'sponsorhip_id' => array('sponsorhip_id',true),
				'sponsorhip_type' => array('sponsorhip_type',false),
				'first_name' => array('first_name',false),
				'last_name' => array('last_name',false),
				'email_address' => array('email_address',false),
				'date_added' => array('date_added',false),
				'action' => array('action',false),
			);
			return $sortable_columns;
		}
		
		function get_bulk_actions() {
			  $actions = array(
				'export-all' => 'Export All',
				//'delete' => 'Delete'
			  );
			  return $actions;
		}
		
		function process_bulk_action(){
            //echo "<script>console.log('Testing');</script>";
            if ( "export-all" === $this->current_action() ){
                global $wpdb;
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="sponsor-data-export.csv"');
    
                // clean out other output buffers
                ob_end_clean();
    
                $fp = fopen('php://output', 'w');
    
                // CSV/Excel header label
                $header_row = array(
                    0 => 'ID',
                    1 => 'Sponsorhip Type',
                    2 => 'First name',
                    3 => 'Last name',
					4 => 'Company name',
                    5 => 'Email',
                    6 => 'Billing Address',
                    7 => 'Billing City',
                    8 => 'Billing State',
                    9 => 'Billing PostalCode',
                    10 => 'Amount',
                    11 => 'Name on Card',
                    12 => 'Card Type',
                    13 => 'Date Added',
                );
    
                //write the header
                fputcsv($fp, $header_row);

                // retrieve any table data desired. Members is an example 
                $Table_Name   = 'aal10_sponsorship_master'; 
                $sql_query    = $wpdb->prepare("SELECT * FROM $Table_Name", 1) ;
                // $sql_query    = $wpdb->prepare("SELECT * FROM $Table_Name WHERE id IN($ids)", 1) ;
                $rows         = $wpdb->get_results($sql_query, ARRAY_A);
                if(!empty($rows)) 
                {
                    foreach($rows as $Record)
                    {  
		
                    	$OutputRecord = array($Record['sponsorhip_id'],
						            $Record['sponsorhip_type'],
                                    $Record['first_name'],
                                    $Record['last_name'],
									$Record['company_name'],
									$Record['email_address'],
                                    $Record['billing_address'],
                                    $Record['billing_city'],
                                    $Record['billing_state'],
                                    $Record['billing_postal_code'],
                                    $Record['amount_deduct'],
                                    $Record['card_name'],
                                    $Record['card_type'],  
                                    $Record['date_added']);
                    fputcsv($fp, $OutputRecord);       
                    }
                }
    
                fclose( $fp );
                exit;  
                // }              // Stop any more exporting to the file
            }
                
        }

		function column_cb($item) {
			return sprintf(
				'<input type="checkbox" name="sponsorship_id[]" value="%s" />', $item->sponsorhip_id
			);    
		}
				
		function column_sponsorhip_id($item){
			
            $class = '<span class="add_ui_class"></span>';
			//Build row actions
			$actions = array(
				'delete'    => sprintf('<a href="javascript:;" onClick="sponsorship_delete(%s);">Delete</a>',$item->sponsorhip_id),
			);
			
			//Return the title contents
			return sprintf('%1$s %2$s %3$s',
				/*$2%s*/ $item->sponsorhip_id,
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
				$where="where first_name LIKE '%".$search_text."%' or last_name LIKE '%".$search_text."%' or email_address LIKE '%".$search_text."%'";
			
			/* -- Preparing your query -- */
				$query = "SELECT * FROM aal10_sponsorship_master ".$where;
				//echo $query;
				
			/* -- Ordering parameters -- */
				//Parameters that are going to be used to order the result
				$orderby = !empty($_GET["orderby"]) ? ($_GET["orderby"]) : 'date_added';
				$order = !empty($_GET["order"]) ? ($_GET["order"]) : 'DESC';
				if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
			
				$this->process_bulk_action();	

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
				
				$_wp_column_headers[$screen->Id]=$columns;
				
				//echo $_wp_column_headers[$screen->id];
			/* -- Fetch the items -- */

				$this->items = $wpdb->get_results($query);
				//echo $query;
			
		}

		function column_default( $item, $column_name ) {
			global $wpdb;
			switch( $column_name ) { 
				case 'sponsorhip_type':
					return $item->sponsorhip_type;
				case 'first_name':											
					return $item->first_name;
				case 'last_name':
					return $item->last_name;			
				case 'email_address':
					return $item->email_address;
				case 'date_added':
					return date('m-d-Y', strtotime($item->date_added));	
				case 'action':
					echo '<a href="admin.php?page=view_sponsorship_information&action=view&id='.$item->sponsorhip_id.'">View</a>';
					echo ' | ';
					echo '<a href="javascript:;" onClick="sponsorship_delete('.$item->sponsorhip_id.');">Delete</a>';
	                break;
				return _e($item->$column_name,'sponsorship_master');
				default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
			}
		 }
	}
	$wp_list_table = new Sponsorship_List_Table();
	$wp_list_table->prepare_items();
	//echo $wp_list_table;
	?>
	
	<div class="wrap">
	<h2>Sponsorship Information</h2>
	  <div id="response-message">
	   <?php if($_GET['msg']){ ?>
       <div class="notice notice-success">
		<?php if($_GET['msg'] == 'delete'){ ?>
        	<p>Record successfully deleted.</p><?php 
		}?>
        </div>
		<?php }?>
       </div>
	  <form method="post">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $wp_list_table->search_box( 'search', 'search_id' );?>
		<?php $wp_list_table->display() ?>
	  </form>
	</div>
    <style>
    	.wp-list-table .column-cb { width: 5%; }
		.wp-list-table .column-sponsorhip_type { width: 15%; }
		.wp-list-table .column-first_name { width: 15%; }
		.wp-list-table .column-last_name { width: 20%; }		
		.wp-list-table .column-email_address  { width: 25%; }
    </style>
	<script type="text/javascript">
       var home_url = '<?php echo PP_PLUGIN_URL;?>';
       function sponsorship_delete(sponsorhip_id)
		{
			if(confirm("Are you sure you want to delete this record?"))
			{	
				location.href='?page=sponsorship-registration&action=delete&sponsorhip_id='+sponsorhip_id;
			}
		}
    </script>