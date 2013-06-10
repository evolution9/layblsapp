<?php include('../includes/dbconnect.php'); ?>
<?php include('../includes/lib.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	//get facebook user identification
	$fb_user_id = number_format($data->{'fb_user_id'},0,'','');
	
	//open databse connection
	$conn = openDBCon();
	
	$sql = " SELECT user_id, name, profile_picture, is_registered, tag_1, tag_2, sessions, access_key ";
	$sql .= " FROM fb_user ";
	$sql .= " WHERE fb_user_id = " . $fb_user_id ;
	
	//echo $sql;
		
	$rs = mysql_query($sql);
	
	if (mysql_num_rows($rs) == 0) //no records found
	{
		$response = new Response(0,"You are not registered user");
	}else{
		//get record from result set
		$row = mysql_fetch_assoc($rs);
		
		if ($row['is_registered'] == 0) //not a registered user
		{ 
			$response = new Response(0,"You are not registered user");
		}else //registered user
		{
			$response = new Response(1,"Success");
			
			$user_id = $row['user_id'];
			
			$response->user_id = $user_id;
			$response->name = $row['name'];
			$response->profile_picture = $row['profile_picture'];
			$response->tag_1 = $row['tag_1'];
			$response->tag_2 = $row['tag_2'];
			
			//Get Access Key
			if ($row['sessions'] == 0){
				$access_key = randomString(20);
			
				$response->access_key = $access_key;
			}else{
				$access_key = $row['access_key'];
				$response->access_key = $row['access_key'];
			}
			
			//Registered user has either uninstall the app or changed the device.
			if ($data->{'is_existing'} == 0){
				//Retrieve Friends
				$sql = "SELECT friend.friend_id, friend.friend_user_id, fb_user.fb_user_id,";
				$sql .= " fb_user.name, fb_user.profile_picture, fb_user.is_registered, ";
				$sql .= " fb_user.tag_1, fb_user.tag_2 , ";
				$sql .= " friend.tag_1 as my_tag_1, friend.tag_2 as my_tag_2, friend.tag_status,";
				$sql .= " fb_user.completed_requests ";
				$sql .= " FROM " ;
				$sql .= " friend ";
				$sql .= " JOIN fb_user ON (fb_user.user_id = friend.friend_user_id)";
				$sql .= " WHERE friend.my_user_id = " . $user_id;
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
				$sql .= " ORDER BY friend1.created_date desc ";
				
				//echo $sql;
					
				$rs = mysql_query($sql);
				
				while($row = mysql_fetch_assoc($rs)) 
				{
					$response->tag_received[] = $row; //Friends
				}
			}else{
				$last_updates = $data->{'last_updates'};
				
				//Retrieve Friends whose profile has changed
				$sql = " SELECT friend.friend_id, friend.friend_user_id, fb_user.fb_user_id,";
				$sql .= " fb_user.name, fb_user.profile_picture, fb_user.is_registered, ";
				$sql .= " fb_user.tag_1, fb_user.tag_2 , ";
				$sql .= " friend.tag_1 as my_tag_1, friend.tag_2 as my_tag_2, friend.tag_status,";
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
				
				//echo $sql;
					
				$rs = mysql_query($sql);
				
				while($row = mysql_fetch_assoc($rs)) 
				{
					$response->tag_received[] = $row; //Friends
				}				
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
			$sql .= " sessions = sessions+1, ";
			$sql .= " access_key = '" . $access_key . "', ";
			$sql .= " platform = " . $data->{'platform'} . ",";
			$sql .= " udid = '" . $data->{'udid'} . "',";			
			$sql .= " pushid = '" . $data->{'pushid'} . "' ";
			$sql .= " WHERE user_id = " . $user_id;
			
			//echo $sql;
				
			mysql_query($sql);
			
			//get current time
			$response->last_updates = gmdate('Y-m-d H:i:s', time());
		}
	}	
	
	//close databse connection
	closeDBCon($conn);
	
	//send response
	echo json_encode($response);
	
//response class definition
class Response{
	public $code;
	public $message;
	public $access_key;
	public $user_id;
	public $name;
	public $profile_picture;
	public $tag_1;
	public $tag_2;
	public $last_updates;
	public $friends;
	public $tag_received;
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