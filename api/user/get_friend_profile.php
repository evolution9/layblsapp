<?php include('../includes/dbconnect.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	//read parameters
	$user_id = $data->{'user_id'};
	$access_key = $data->{'access_key'};
	$friend_id = $data->{'friend_id'};
	$friend_user_id = $data->{'friend_user_id'};
	
	//open databse connection
	$conn = openDBCon();
	
	//verify user existance
	$sql = "SELECT user_id ";
	$sql .= " FROM fb_user ";
	$sql .= " WHERE user_id = " . $user_id . " AND ";
	$sql .= " access_key='" . $access_key . "'";
	
	// echo $sql;
	
	$rs = mysql_query($sql);
	
	if (mysql_num_rows($rs)>0){ 
		
		//verify friend
		$sql = " SELECT friend_id ";
		$sql .= " FROM friend ";
		$sql .= " WHERE friend_id = " . $friend_id ;
		$sql .= " AND my_user_id = " . $user_id;
		$sql .= " AND friend_user_id = " . $friend_user_id;
		
		$rs = mysql_query($sql);
		
		if (mysql_num_rows($rs)>0){	
			
			$response = new Response(1,"success");	
		
			//get friend's profile
			$sql = "SELECT name, profile_picture, tag_1, tag_2 ";
			$sql .= " FROM fb_user " ;
			$sql .= " WHERE user_id = " . $friend_user_id;
			
			$rs = mysql_query($sql);
			
			//get record from result set
			$row = mysql_fetch_assoc($rs);
			
			$response->name = $row['name'];
			$response->profile_picture = $row['profile_picture'];
			$response->tag_1 = $row['tag_1'];
			$response->tag_2 = $row['tag_2'];
			
			$sql =  " SELECT fb_user.name, fb_user.profile_picture, ";
			$sql .= " friend.tag_1, friend.tag_2, ";
			$sql .= " tag1.name as tag_1_name, tag2.name as tag_2_name, ";
			$sql .= " friend.tag_status ";
			$sql .= " FROM friend ";
			$sql .= " JOIN fb_user ON (friend.my_user_id = fb_user.user_id) ";
			$sql .= " JOIN tag as tag1 ON (tag1.tag_id = friend.tag_1) ";
			$sql .= " JOIN tag as tag2 ON (tag2.tag_id = friend.tag_2) ";	
			$sql .= " WHERE friend.friend_user_id = " . $friend_user_id;
			$sql .= " AND friend.my_user_id <> " . $user_id;			
			$sql .= " AND friend.tag_status = 1 " ;
			$sql .= " AND (friend.tag_1 = " . $response->tag_1 ;
			$sql .= " OR friend.tag_1 = " . $response->tag_2 ;
			$sql .= " OR friend.tag_2 = " . $response->tag_1 ;
			$sql .= " OR friend.tag_2 = " . $response->tag_2 . ")";
			$sql .= " ORDER BY fb_user.name ";
			
			//echo $sql;
			
			$rs = mysql_query($sql);
			
			while($row = mysql_fetch_assoc($rs)) {
					$response->friends[] = $row; //Friends
			}
			
		}else{
			$response = new Response(0,"Access denied, invalid friend reference");	
		}
	}else{
		$response = new Response(0,"Access denied, invalid user reference");
	}
	
	
	//close databse connection
	closeDBCon($conn);
	
	//send response
	echo json_encode($response);
	
//response class definition
class Response{
	public $code;
	public $message;
	public $name;
	public $profile_picture;
	public $tag_1;
	public $tag_2;
	public $friends;
	
	public function __construct($iCode, $iMessage ){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
		
		$this->friends = array();		
	}
}		
	
?>