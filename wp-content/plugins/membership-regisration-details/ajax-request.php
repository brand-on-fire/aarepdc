<?php
add_action( 'wp_ajax_pp_status_function', 'pp_status_function' );
add_action( 'wp_ajax_nopriv_pp_status_function', 'pp_status_function' );  

function pp_status_function(){
	if(!empty($_POST['id'])){
		$mode = $_REQUEST['mode'];
		if($mode == 'status_on'){
			$status = 1;
		}
		if($mode == 'status_off'){
			$status = 0;
		}
		global $wpdb;
		$table = $wpdb->prefix.'pp_buyer_package';
		$id = $_REQUEST['id'];
		$ms = $wpdb->update($table,array('package_status'=>$status),array('package_id'=>$id));
		if($ms!=false)
		{ 
			echo '1';
			die;
		}	
	}
}

function employee_status_function(){
	if(!empty($_POST['id'])){
		$mode = $_REQUEST['mode'];
		if($mode == 'status_on'){
			$status = 1;
		}
		if($mode == 'status_off'){
			$status = 0;
		}
		global $wpdb;
		$table = $wpdb->prefix.'register_employee';
		$id = $_REQUEST['id'];
		$ms = $wpdb->update($table,array('status'=>$status),array('employee_id'=>$id));
		if($ms!=false)
		{ 
			echo '1';
			die;
		}	
	}
}

add_action( 'wp_ajax_pp_autocomplete', 'pp_autocomplete_word' );
add_action( 'wp_ajax_nopriv_pp_autocomplete', 'pp_autocomplete_word' );  

function pp_autocomplete_word(){
	if(!empty($_REQUEST['term'])){
		$mode = $_REQUEST['mode'];
		if($mode == 'get_word_from'){
			global $wpdb;
			$term = $_REQUEST['term'];
			$table = $wpdb->prefix.'users';
			$query = "SELECT * FROM ".$table." where user_login like '". $term ."%'";
			$result =  $wpdb->get_results($query);
			$data = array();
			if(!empty($result)){
                foreach($result as $row) {
					$data[] = array( "id"=>$row->ID ,"value"=>$row->user_login);
                } 
				echo json_encode(array("results"=>$data));
			}
			else{
				echo json_encode(array("results"=>''));
			}
			exit();
		}
	}
}

add_action( 'wp_ajax_make_package_prime', 'pp_make_package_prime' );
add_action( 'wp_ajax_nopriv_make_package_prime', 'pp_make_package_prime' ); 

function pp_make_package_prime(){
	if(!empty($_POST['id']) && $_REQUEST['mode'] == 'package_prime'){		
		global $wpdb;
		$table = $wpdb->prefix.'pp_buyer_package';
		$id = $_REQUEST['id'];
		$select = 'select * from '.$table.' where package_id != '.$id.' and package_prime = q1';
		$row = $wpdb->get_row($select);
		if(count($row > 0)){
			$ms = $wpdb->update($table,array('package_prime'=>'0'),array('package_id'=>$row->package_id));
		}
		$ms = $wpdb->update($table,array('package_prime'=>'1'),array('package_id'=>$id));
		if($ms!=false)
		{ 
			echo '1';
			die;
		}	
	}
}

add_action( 'wp_ajax_pp_user_credit', 'pp_user_credit_find' );
add_action( 'wp_ajax_nopriv_pp_user_credit', 'pp_user_credit_find' ); 

function pp_user_credit_find(){
	if(!empty($_REQUEST['user_name'])){
		$mode = $_REQUEST['mode'];
		if($mode == 'get_data_from_user'){
			global $wpdb;
			$term = $_REQUEST['user_name'];
			$table = $wpdb->prefix.'users';
			$query = "SELECT * FROM ".$table." where user_login like '". $term ."%'";
			$result =  $wpdb->get_results($query);
			$data = array();
			if(!empty($result)){
                foreach($result as $row) {
					$data[] = array( "id"=>$row->ID ,"value"=>$row->user_login);
                } 
				echo json_encode(array("results"=>$data));
			}
			else{
				echo json_encode(array("results"=>''));
			}
			exit();
		}
	}
}

add_action( 'wp_ajax_sort_package_order', 'pp_sort_package_order' );
add_action( 'wp_ajax_nopriv_sort_package_order', 'pp_sort_package_order' );

function pp_sort_package_order(){
    if(!empty($_REQUEST['data'])){
		global $wpdb;
		$data = $_REQUEST['data'];
        $table = $wpdb->prefix.'pp_buyer_package';
        $i = 1;
        foreach($data as $id){
    		$wpdb->update($table,array('sort_order'=>$i),array('package_id'=>$id));
            $i++;
        }
        echo '1';
		exit();
	}
}
?>