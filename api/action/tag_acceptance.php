<?php include('../includes/dbconnect.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	$access_key= $data->{'access_key'};
	$from_user_id = $data->{'from_user_id'};
	$to_user_id = $data->{'to_user_id'};
	$action_flag = $data->{'action_flag'};
	
	//open databse connection
	$conn = openDBCon();
	
	//verify user existance
	$sql = "SELECT user_id ";
	$sql .= " FROM fb_user ";
	$sql .= " WHERE user_id = " . $from_user_id . " AND ";
	$sql .= " access_key='" . $access_key . "'";
	
	// echo $sql;
	
	$rs = mysql_query($sql);
	
	if (mysql_num_rows($rs)>0){ 
		
		$sql = "SELECT friend_id ";
		$sql .= " FROM friend " ;
		$sql .= " WHERE my_user_id = " . $to_user_id;
		$sql .= " AND friend_user_id = " . $from_user_id ;
		
		// echo $sql;
	   
		$rs=mysql_query($sql);
		
		$today = gmdate('Y-m-d H:i:s', time());
		
		if(mysql_num_rows($rs) > 0){
		
			$row = mysql_fetch_assoc($rs);
			
			$friend_id = $row["friend_id"];
						
			$response = new Response(1,"Sucess"); 
			
			if ($action_flag == 1){ //accept tag
			
				$sql = " UPDATE friend SET ";
				$sql .= " tag_status = 1,";
				$sql .= " tag_acceptance_date = '" . $today . "', ";
				$sql .= " modified_date = '" . $today . "' ";
				$sql .= " WHERE friend_id = " . $friend_id;
				
				//echo $sql;
				
				mysql_query($sql);
				
			}else{//reject tag
				$sql = " UPDATE friend SET ";
				$sql .= " tag_1 = 0,";
				$sql .= " tag_2 = 0,"; 
				$sql .= " tag_status = 0, ";
				$sql .= " tag_acceptance_date = '" . $today . "', ";
				$sql .= " modified_date = '" . $today . "' ";
				$sql .= " WHERE friend_id = " . $friend_id;				
				
				//echo $sql;
				
				mysql_query($sql);
			}
			
			
			//find out maximum tag accepted
			$sql = " SELECT max_tag.tag, tag.name, max(cntr) as mcntr FROM ( ";
			$sql .= " 	SELECT tag, ifnull(sum(cnt),0) as cntr FROM ( ";
			$sql .= " 		SELECT tag_1 as tag , count(friend_id) as cnt ";
			$sql .= " 		FROM friend ";
			$sql .= " 		WHERE friend_user_id = " . $from_user_id;
			$sql .= " 		AND tag_status = 1 ";
			$sql .= " 		GROUP BY tag_1 ";
			$sql .= " 		UNION ";
			$sql .= " 		SELECT tag_2 as tag , count(friend_id) as cnt ";
			$sql .= " 		FROM friend ";
			$sql .= " 		WHERE friend_user_id = " . $from_user_id;
			$sql .= " 		AND tag_status = 1 ";
			$sql .= " 		GROUP BY tag_2 ";
			$sql .= " 		) as tagging ";
			$sql .= " 	GROUP BY tag ";
			$sql .= " 	) as max_tag ";
			$sql .= " JOIN tag ON (tag.tag_id = max_tag.tag) ";
			$sql .= " GROUP BY tag.name ";
			
			//echo $sql;
			
			$rs=mysql_query($sql);
			
			$row = mysql_fetch_assoc($rs);
			
			$tag_1 = $row["tag"];
			
			$row = mysql_fetch_assoc($rs);
			
			$tag_2 = $row["tag"];
			
			$response->tag_1 = $tag_1;
			$response->tag_2 = $tag_2;
			
			//Update frien'd profile
			$sql = " UPDATE fb_user SET ";
			$sql .= " tag_1 = " . $tag_1 . ",";
			$sql .= " tag_2 = " . $tag_2 . ",";
			$sql .= " completed_requests = completed_requests + 1, ";
			$sql .= " modified_date = '" . $today . "' ";
			$sql .= " WHERE user_id = " . $from_user_id; 

			//echo $sql;
			
			mysql_query($sql);
			
		}else{
			
			$response= new Response(0,"Access denied, invalid friend reference");
		}		
	}else{
		
		$response= new response(0,"Access denied, invalid user reference");
			
	}
	   
	closeDBCon($conn);
	   
	echo json_encode($response);
	   
	   
class Response{
	public $code;
	public $message;
	public $tag_1;
	public $tag_2;

	public function __construct($iCode, $iMessage ){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
	}
}


?>