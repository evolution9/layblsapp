<?php include('../includes/dbconnect.php'); ?>
<?php include('../includes/lib.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	$user_id = $data->{'user_id'};
	$access_key = $data->{'access_key'};
	
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
		
		$today = gmdate('Y-m-d H:i:s', time());
		
		//Update profile
		$sql = " UPDATE fb_user SET ";
		$sql .= " name = '" . str_replace("'", "''",$data->{'name'}) . "', ";
		$sql .= " profile_picture = '" . $data->{'profile_picture'} . "', ";
		$sql .= " modified_date = '" . $today . "' ";
		$sql .= " WHERE user_id = " . $user_id ;
		$sql .= " AND (name <> '" . $data->{'name'} . "'";
		$sql .= " OR profile_picture <> '" . $data->{'profile_picture'} . "') ";

		//echo $sql;
		
		mysql_query($sql);
		
		//Update friends (add/edit/delete)
		$friends = $data->{'friends'};
		
		for ($ele=0; $ele < count($friends); $ele++)
		{
			$friend_fb_user_id = number_format($friends[$ele]->{'uid'},0,'','');
			$name = str_replace("'", "''",$friends[$ele]->{'name'});
			$profile_picture = $friends[$ele]->{'pic_square'};
			
			//Verify whether friend is registered or not
			$sql = "SELECT user_id, is_registered, ";
			$sql .= " tag_1, tag_2, completed_requests "; 
			$sql .= " FROM fb_user WHERE fb_user_id = '" . $friend_fb_user_id . "'";
			
			//echo $sql;
		
			$rs = mysql_query($sql);
	
			if (mysql_num_rows($rs) > 0) //reference to this friend is already added in fb_user
			{
				$row = mysql_fetch_assoc($rs);
				
				$friend_user_id = $row['user_id'];
				$tag_1 = $row['tag_1'];
				$tag_2 = $row['tag_2'];
				$is_registered = $row['is_registered'];
				$completed_requests = $row['completed_requests'];
				
				//Update profile
				$sql = " UPDATE fb_user SET ";
				$sql .= " name = '" . $name . "', ";
				$sql .= " profile_picture = '" . $profile_picture . "', ";
				$sql .= " modified_date = '" . $today . "' ";
				$sql .= " WHERE user_id = " . $friend_user_id ;
				$sql .= " AND (name <> '" . $name . "'";
				$sql .= " OR profile_picture <> '" . $profile_picture . "') ";
		
				//echo $sql;
				
				mysql_query($sql);
				
				$sql = "SELECT friend_id FROM friend ";
				$sql .= " WHERE my_user_id = " . $user_id ;
				$sql .= " AND friend_user_id = " . $friend_user_id ;
				
				//echo $sql;
			
				$rs = mysql_query($sql);

				if (mysql_num_rows($rs) > 0){ //reference to this friend is already added in fb_user
					$row = mysql_fetch_assoc($rs);
					
					$friend_id = $row['friend_id'];
				}else{
					//Add Friend
					//==========
					$sql = " INSERT into friend ";
					$sql .= " (my_user_id, friend_user_id, created_date) values (";
					$sql .= $user_id . ",";			
					$sql .= $friend_user_id . ",";
					$sql .= "'" . $today . "')";
				
					//echo $sql;
							
					mysql_query($sql);
							
					$friend_id = mysql_insert_id();
				}
				
			}
			else //friend reference is not added in fb_user table
			{
				//add friend reference
				$sql = "insert into fb_user ";
				$sql .= " (fb_user_id, name, ";
				$sql .= " profile_picture, is_registered,  ";
				$sql .= " created_date, modified_date) values ( ";
				$sql .=	$friend_fb_user_id . ",";
				$sql .= "'" . $name . "',";
				$sql .= "'" . $profile_picture . "',";
				$sql .= "0,'" . $today . "','" . $today . "')";
				
				//echo $sql;
						
				mysql_query($sql);
						
				$friend_user_id = mysql_insert_id();
				$tag_1 = 0;
				$tag_2 = 0;
				$completed_requests = 0;
				$is_registered = 0;
				
				//Add Friend
				//==========
				$sql = " INSERT into friend ";
				$sql .= " (my_user_id, friend_user_id, created_date) values (";
				$sql .= $user_id . ",";			
				$sql .= $friend_user_id . ",";
				$sql .= "'" . $today . "')";
			
				//echo $sql;
						
				mysql_query($sql);
						
				$friend_id = mysql_insert_id();
				
			}
						
			
			$friend = new Friend();
			
			$friend->friend_id = $friend_id;
			$friend->friend_user_id = $friend_user_id;
			$friend->is_registered = $is_registered;
			$friend->completed_requests = $completed_requests;
			$friend->tag_1 = $tag_1;
			$friend->tag_2 = $tag_2;
			
			$response->friends[] = $friend;
		}
		
		//retrieve TAGs
		$sql = "SELECT tag_id, name FROM tag";
		
		//echo $sql;
			
		$rs = mysql_query($sql);
		
		while($row = mysql_fetch_assoc($rs)) 
		{
			$response->tags[] = $row; //Tag
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
	
	//list of friends
	public $friends;
	
	//list of tag master 
	public $tags;
	
	public function __construct($iCode, $iMessage ){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
		
		$this->friends = array();		
		$this->tags = array();

	}
}

class Friend{
	//unique identitication
	public $friend_id;
	
	//user_id of friend 
	public $friend_user_id;
	
	public $tag_1;
	public $tag_2;
	public $completed_requests;
	
	//whether friend is registered or not
	public $is_registered;
	
	public function __construct()
	{
	}
}	
?>	