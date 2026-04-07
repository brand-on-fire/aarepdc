<?php
if(isset($_POST['user_submit'])){
	$id = $_POST['pp_user_id'];
	$username = $_POST['pp_user_credit_name'];
}
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class User_Credit_List extends WP_List_Table {
	
	function __construct(){
		global $status, $page;
			parent::__construct( array(
				'singular'  => "User Credit List",     //singular name of the listed records
				'plural'    => "User Credit Lists",   //plural name of the listed records
				'ajax'      => false        //does this table support ajax?
	
		) );	
	}
	
	function get_columns(){
		$columns = array(
		//'cb' => '<input type="checkbox" />',
		'user_login' => 'User Name',
		'package_name' => 'Package Name',
		'balance' => 'Balance',
		'balance_type' => 'Balance Type',
		'notes' => 'Notes',
		);
		return $columns;
	}
	function get_sortable_columns() {
		$sortable_columns = array(
			'user_login'  => array('user_login',false),
		);
		return $sortable_columns;
	}
	function prepare_items() {
		global $wpdb,$_wp_column_headers;
		$prefix=$wpdb->prefix;
		$screen = get_current_screen();
		$search_text=!empty($_POST['s'])?$_POST['s']:"";
		$where="";
		
		if(!empty($search_text))
			$where="and u.user_login LIKE '%".$search_text."%' ";
		
		/* -- Preparing your query -- */
			$query = "SELECT p.package_name, u.*, c.* FROM ".$prefix."users as u, ".$prefix."pp_credit_balance as c, ".$prefix."pp_buyer_package as p, ".$prefix."pp_package_buyer_relation as r where u.ID=c.user_id and c.user_id = r.buyer_id and r.package_id = p.package_id ".$where;
			
			if(isset($_REQUEST['mode'])){
				if($_REQUEST['mode'] == 'find_user_records'){
					$query = "SELECT p.package_name, u.*, c.* FROM ".$prefix."users as u, ".$prefix."pp_credit_balance as c, ".$prefix."pp_buyer_package as p, ".$prefix."pp_package_buyer_relation as r where u.ID=c.user_id and c.user_id = '".$_REQUEST['pp_user_id']."' and c.user_id = r.buyer_id and r.package_id = p.package_id ".$where;
					//$query = "SELECT u.*, c.* FROM ".$prefix."users as u, ".$prefix."pp_credit_balance as c where u.ID=c.user_id and c.user_id = '".$_REQUEST['pp_user_id']."' ".$where;	
				}
			}
		/* -- Ordering parameters -- */
			//Parameters that are going to be used to order the result
			$orderby = !empty($_GET["orderby"]) ? ($_GET["orderby"]) : 'c.id';
			$order = !empty($_GET["order"]) ? ($_GET["order"]) : 'DESC';
			if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
	
		/* -- Pagination parameters -- */
			//Number of elements in your table?
			$totalitems = $wpdb->query($query); //return the total number of affected rows
			//How many to display per page?
			$perpage = 20;
			//Which page is this?
			$paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
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
			case 'user_login':
				return '<a href="user-edit.php?user_id='.$item->ID.'">'.$item->$column_name.'</a>';	
			case 'package_name':
			case 'balance':
			case 'balance_type':
			case 'notes':
				return _e($item->$column_name,'photofolio');	
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	 }
}
$wp_list_table = new User_Credit_List();
$wp_list_table->prepare_items();
?>

<div class="wrap">
  <h2>
    <?php  _e('User Credit List','photofolio'); ?>
    <a class="add-new-h2" href="admin.php?page=package-panel">Back to Main Page</a> 
  </h2>
  <form method="post" action="" onSubmit="return find_user_credit();">
    <table class="form-table" >
      <tr valign="top">
        <th scope="row"><label>
            <?php _e('User Name:','photofolio'); ?>
          </label></th>
        <td><input type="text" name="user_credit_id" id="user_credit_id"  placeholder="<?php _e('User Name','photofolio'); ?>" autocomplete="off" value="<?php echo $username;?>" />
          <input type="hidden" name="pp_user_id" id="pp_user_id" value="<?php echo @$id;?>" />
          <input type="hidden" name="pp_user_credit_name" id="pp_user_credit_name" value="<?php echo @$username;?>" />
          <input type="submit" name="user_submit" value="<?php _e('Search','photofolio'); ?>" class="button-primary" />
          <input type="hidden" name="mode" value="find_user_records" />
          <?php wp_nonce_field("user_credit_find","pp_user_credit_list");?>
          <div id="suggesstion-box"></div></td>
      </tr>
    </table>
  </form>
  <div class="form">
    <form method="post">
      <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
      <?php $wp_list_table->search_box( 'search', 'search_id' );?>
      <?php $wp_list_table->display() ?>
    </form>
  </div>
</div>
<script type="text/javascript">
var home_url = '<?php echo PP_PLUGIN_URL;?>';
</script>