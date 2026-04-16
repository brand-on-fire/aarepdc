<?php 
global $wpdb;
$table = 'aal10_event_registration_master';

$action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';
$action2 = isset($_REQUEST['action2']) ? sanitize_text_field($_REQUEST['action2']) : '';

if($action === 'delete' || $action2 === 'delete')
{
	if(isset($_REQUEST['event_IDs']) && !empty($_REQUEST['_wpnonce']))
	{
		foreach($_REQUEST['event_IDs'] as $item)
		{	
			$wpdb->delete($table, array('event_registration_id'=> (int) $item));
			$_GET['msg']="delete";
		}
	}
	else if(isset($_REQUEST['event_ID']) && $_REQUEST['event_ID'])
	{	
		$wpdb->delete($table, array('event_registration_id'=> (int) $_REQUEST['event_ID']));
		wp_redirect( 'admin.php?page=event-registration&msg=delete' );
	}
}
?>
<?php
	if( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	
	class Event_List_Table extends WP_List_Table {
		
		function __construct(){
			global $status, $page;
				parent::__construct( array(
					'singular'  => "Event Information",     //singular name of the listed records
					'plural'    => "Event Information",   //plural name of the listed records
					'ajax'      => false        //does this table support ajax?
		
			) );	
		}
		
		function get_columns(){
			$columns = array( 
			'cb' => '<input type="checkbox" />',
			'event_name' => 'Event Name',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'email_address' => 'Email',
			'action' => 'Action',
			);
			return $columns;
		}
		
		function get_sortable_columns() {
			$sortable_columns = array(
				//'id' => array('id',true),
				'event_name' => array('event_name',false),
				'first_name' => array('first_name',false),
				'last_name' => array('last_name',false),
				'email_address' => array('email_address',false),
				'action' => array('action',false),
			);
			return $sortable_columns;
		}
		
		function get_bulk_actions() {
			  $actions = array(
				'export-all' => 'Export All',
				'delete' => 'Delete'
			  );
			  return $actions;
		}



		function process_bulk_action(){
            echo "<script>console.log('Testing');</script>";
            if ( "export-all" === $this->current_action() ){
                global $wpdb;
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="event-data-export.csv"');
    
                // clean out other output buffers
                ob_end_clean();
    
                $fp = fopen('php://output', 'w');
    
                // CSV/Excel header label
                $header_row = array(
                    0 => 'ID',
                    1 => 'Event Name',
                    2 => 'First name',
                    3 => 'Last name',
                    4 => 'Email',
                    5 => 'Billing Address',
                    6 => 'Billing City',
                    7 => 'Billing State',
                    8 => 'Billing PostalCode',
                    9 => 'Fee',
                    10 => 'Name on Card',
                    11 => 'Card Type',
                    12 => 'Date Added',
                );
    
                //write the header
                fputcsv($fp, $header_row);

				foreach( $wpdb->get_results("SELECT post_title FROM `adc10_posts` WHERE `ID` = ".$item->event_id."") as $key => $row) {
					// each column in your row will be accessible like this
					$my_column = $row->post_title;
				}

                // retrieve any table data desired. Members is an example 
                $Table_Name   = 'aal10_event_registration_master'; 
                $sql_query    = $wpdb->prepare("SELECT * FROM $Table_Name", 1) ;
                // $sql_query    = $wpdb->prepare("SELECT * FROM $Table_Name WHERE id IN($ids)", 1) ;
                $rows         = $wpdb->get_results($sql_query, ARRAY_A);
                if(!empty($rows)) 
                {
                    foreach($rows as $Record)
                    {  
						foreach( $wpdb->get_results("SELECT post_title FROM `adc10_posts` WHERE `ID` = ".$Record['event_id']."") as $key => $row) {
							// each column in your row will be accessible like this
							$event_name = $row->post_title;
						}

                    	$OutputRecord = array($Record['event_registration_id'],
									$event_name,
                                    $Record['first_name'],
                                    $Record['last_name'],
									$Record['email_address'],
                                    $Record['billing_address'],
                                    $Record['billing_city'],
                                    $Record['billing_state'],
                                    $Record['billing_postal_code'],
                                    $Record['event_fees'],
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
				'<input type="checkbox" name="event_IDs[]" value="%s" />', $item->event_registration_id
			);    
		}
				
		function column_apply_id($item){
			
            $class = '<span class="add_ui_class"></span>';
			//Build row actions
			$actions = array(
				'delete'    => sprintf('<a href="javascript:;" onClick="event_delete(%s);">Delete</a>',$item->event_registration_id),
			);
			
			//Return the title contents
			return sprintf('%1$s %2$s %3$s',
				/*$2%s*/ $item->id,
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
				$query = "SELECT * FROM aal10_event_registration_master ".$where;
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
				case 'event_name':
					foreach( $wpdb->get_results("SELECT post_title FROM `adc10_posts` WHERE `ID` = ".$item->event_id."") as $key => $row) {
						// each column in your row will be accessible like this
						$my_column = $row->post_title;
					}
					return $my_column;
				case 'first_name':											
					return $item->first_name;
				case 'last_name':
					return $item->last_name;			
				case 'email_address':
					return $item->email_address;
					case 'action':
						echo '<a href="admin.php?page=view_event_information&action=view&id='.$item->event_registration_id.'">View</a>';
						echo ' | ';
						echo '<a href="javascript:;" onClick="event_delete('.$item->event_registration_id.');">Delete</a>';
	                break;
				return _e($item->$column_name,'event_master');
				default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
			}
		 }
	}
	$wp_list_table = new Event_List_Table();
	$wp_list_table->prepare_items();
	//echo $wp_list_table;
	?>
	
	<div class="wrap">
	<h2>Event Information</h2>
	  <div id="response-message">
	   <?php if(isset($_GET['msg']) && $_GET['msg']){ ?>
       <div class="notice notice-success">
		<?php if($_GET['msg'] === 'delete'){ ?>
        	<p>Record successfully deleted.</p><?php 
		}?>
        </div>
		<?php }?>
       </div>
	  <form method="post">
		<input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : ''; ?>" />
		<?php $wp_list_table->search_box( 'search', 'search_id' );?>
		<?php $wp_list_table->display() ?>
	  </form>
	</div>
    <style>
    	.wp-list-table .column-cb { width: 5%; }
		.wp-list-table .column-event_name { width: 20%; }
		.wp-list-table .column-first_name { width: 15%; }
		.wp-list-table .column-last_name { width: 20%; }		
		.wp-list-table .column-email_address  { width: 25%; }
    </style>
	<script type="text/javascript">
       var home_url = '<?php echo PP_PLUGIN_URL;?>';
       function event_delete(event_registration_id)
		{
			if(confirm("Are you sure you want to delete this record?"))
			{	
				location.href='?page=event-registration&action=delete&event_ID='+event_registration_id;
			}
		}
    </script>