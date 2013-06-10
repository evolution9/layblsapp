<?php include('../includes/dbconnect.php'); ?>
<?php include('../includes/lib.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	$user_id = $data->{'user_id'};
	$access_key = $data->{'access_key'};
	$last_updates = $data->{'last_updates'};
	
	//open databse connection
	$conn = openDBCon();
	
	//verify user existance
	$sql = "SELECT count(user_id) as cnt FROM fb_user ";
	$sql .= " WHERE user_id = " . $user_id ;
	$sql .= " AND access_key = '" . $access_key . "'";
	
	//echo $sql;
		
	$rs = mysql_query($sql);
	
	$row = mysql_fetch_assoc($rs);
	
	if ($row['cnt'] == 0)
	{
		$response = new Response(0,"Access denied");		
	}
	else
	{
		$response = new Response(1,"Success");
		
		//Retrieve Friends whose profile has changed
		$sql = " SELECT friend.friend_id, ";
		$sql .= " fb_user.name, fb_user.profile_picture, fb_user.is_registered, ";
		$sql .= " fb_user.tag_1, fb_user.tag_2 , ";
		$sql .= " friend.tag_1 as my_tag_1, friend.tag_2 as my_tag_2, ";
		$sql .= " friend.tag_status,";
		$sql .= " fb_user.completed_requests ";
		$sql .= " FROM friend ";
		$sql .= " JOIN fb_user ON (fb_user.user_id = friend.friend_user_id)";
		$sql .= " WHERE friend.my_user_id = " . $user_id;
		$sql .= " AND (friend.modified_date > '" . $last_updates . "'" ;
		$sql .= " OR fb_user.modified_date > '" . $last_updates . "') ";
		$sql .= " ORDER BY fb_user.name ";
		
		//echo $sql;
		
		$rs = mysql_query($sql);
		
		while($row = mysql_fetch_assoc($rs)) 
		{
			$response->friends[] = $row; //Friends
		}
		
		//Get tag received
		$sql = "SELECT friend.friend_id, ";
		$sql .= " ifnull(friend1.tag_1,0) as tag_1, ifnull(friend1.tag_2,0) as tag_2, ";
		$sql .= " ifnull(friend1.tag_status,0) as tag_status, friend1.created_date "; 
		$sql .= " FROM friend ";
		$sql .= " JOIN friend as friend1 ON (friend1.my_user_id = friend.friend_user_id ";
		$sql .= " AND friend1.friend_user_id = " . $user_id . ")";
		$sql .= " WHERE friend.my_user_id = " . $user_id;
		$sql .= " AND friend1.tag_1 <> 0 ";
		$sql .= " AND friend1.modified_Date > '" . $last_updates . "'";
		$sql .= " ORDER BY friend1.modified_Date";
		
		//echo $sql;
			
		$rs = mysql_query($sql);
		
		while($row = mysql_fetch_assoc($rs)) 
		{
			$response->tag_received[] = $row; //Friends
		}			
		
		//retrieve TAGs
		$sql = "SELECT tag_id, name FROM tag";
		
		//echo $sql;
			
		$rs = mysql_query($sql);
		
		while($row = mysql_fetch_assoc($rs)) 
		{
			$response->tags[] = $row; //Tag
		}
		
		$sql = "UPDATE fb_user SET ";
		$sql .= " platform = " . $data->{'platform'} . ",";
		$sql .= " udid = '" . $data->{'udid'} . "',";			
		$sql .= " pushid = '" . $data->{'pushid'} . "' ";
		$sql .= " WHERE user_id = " . $user_id;
		
		//echo $sql;
			
		mysql_query($sql);
		
		//get current time
		$response->last_updates = gmdate('Y-m-d H:i:s', time());		

	}

	//close databse connection
	closeDBCon($conn);
	
	//send response
	echo json_encode($response);

	
//response class definition
class Response{
	public $code;
	public $message;
	
	public $last_updates;	
	
	//list of friends
	public $friends;
	
	public $tag_received;	
	
	//list of tag master 
	public $tags;
	
	public function __construct($iCode, $iMessage ){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
		
		$this->friends = array();
		$this->tag_received = array();
		$this->tags = array();
	}
}	
?>