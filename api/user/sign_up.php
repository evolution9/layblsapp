<?php include('../includes/dbconnect.php'); ?>
<?php include('../includes/lib.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	//open databse connection
	$conn = openDBCon();

	$fb_user_id = number_format($data->{'fb_user_id'},0,'','');
		
	//verify user existance
	$sql = "SELECT user_id,is_registered FROM fb_user ";
	$sql .= " WHERE fb_user_id = " . $fb_user_id ;
	
	//echo $sql;
		
	$rs = mysql_query($sql);
	
	if (mysql_num_rows($rs) > 0)
	{
		$row = mysql_fetch_assoc($rs);
		
		$user_id = $row['user_id'];
		$is_registered = $row['is_registered'];
	}else{
		$user_id = 0;
		$is_registered = 0;	
	}	
	
	if ($is_registered == 1)
	{
		$response = new Response(0,"User is already registered");
	}
	else
	{
		$response = new Response(1,"Success");
		
		$name = str_replace("'", "''",$data->{'name'});
		$profile_picture = $data->{'profile_picture'};
		$platform = $data->{'platform'};
		$udid = $data->{'udid'};
		$pushid = $data->{'pushid'};
		
		//get Access Key
		$access_key = randomString(20);
		
		$response->access_key = $access_key;
		
		//get current time
		$response->last_updates = $today = gmdate('Y-m-d H:i:s', time());
		
		if ($user_id != 0){ // user entry is already added but was not registered
			$sql = " SELECT ";
			$sql .= " fb_user.user_id as from_user_id, fb_user.name, fb_user.profile_picture, ";
			$sql .= " friend.tag_1, friend.tag_2, ";
			$sql .= " tag1.name as tag_1_name, tag2.name as tag_2_name ";
			$sql .= " FROM friend ";
			$sql .= " JOIN fb_user ON (fb_user.user_id = friend.my_user_id) ";
			$sql .= " JOIN tag as tag1 ON (tag1.tag_id = friend.tag_1) ";
			$sql .= " JOIN tag as tag2 ON (tag2.tag_id = friend.tag_2) ";			
			$sql .= " WHERE friend.friend_user_id = " . $user_id;
			$sql .= " ORDER BY friend.created_date desc LIMIT 1";
		
			$rs = mysql_query($sql);
			
			if (mysql_num_rows($rs) > 0) //The user got invitation
			{	
				$row = mysql_fetch_assoc($rs);
				
				$response->is_invited = 1;
				
				$response->invitation = $row;
			}
		
			$sql = "UPDATE fb_user SET ";
			$sql .= "name = '" . $name . "',";
			$sql .= "profile_picture = '" . $profile_picture . "',";
			$sql .= "is_registered = 1,access_key = '" . $access_key . "',";		
			$sql .= "platform = " . $platform . ",";
			$sql .= "udid = '" . $udid . "',";
			$sql .= "pushid = '" . $pushid . "',"; 
			$sql .= "modified_date = '" . $today . "'"; 
			$sql .= " WHERE user_id = " . $user_id;
			
			mysql_query($sql);
			
		}else{			
			$sql = "insert into fb_user ";
			$sql .= " (fb_user_id, name, ";
			$sql .= " profile_picture, is_registered, sessions, access_key, ";
			$sql .= " platform, udid, pushid, created_date, modified_date) values ( ";
			$sql .=	$fb_user_id . ",";
			$sql .= "'" . $name . "',";
			$sql .= "'" . $profile_picture . "',1,";
			$sql .= "1,'" . $access_key . "',";		
			$sql .= $platform . ",";
			$sql .= "'" . $udid . "',";
			$sql .= "'" . $pushid . "',";
			$sql .= "'" . $today . "',";			
			$sql .= "'" . $today . "')";
			
			//echo $sql;
					
			mysql_query($sql);
					
			$user_id = mysql_insert_id();			
		}
		
		$response->user_id = $user_id;
		
		//proces friends
		//==============
		$friends = $data->{'friends'};
		
		for ($ele=0; $ele < count($friends); $ele++)
		{
			$friend_fb_user_id = number_format($friends[$ele]->{'uid'},0,'','');
			$name = str_replace("'", "''",$friends[$ele]->{'name'});
			$profile_picture = $friends[$ele]->{'pic_square'};
			
			//Verify whether friend is registered or not
			$sql = "SELECT user_id, is_registered, ";
			$sql .= " tag_1, tag_2, completed_requests "; 
			$sql .= " FROM fb_user WHERE fb_user_id = " . $friend_fb_user_id ;
			
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
			}
			
			//Add Friend
			//==========
			$sql = " INSERT into friend ";
			$sql .= " (my_user_id, friend_user_id, created_date, modified_date) values (";
			$sql .= $user_id . ",";
			
			if ($friend_user_id == 0)
			{
				$sql .= "null,";
			}
			else
			{
				$sql .= $friend_user_id . ",";
			}
			
			$sql .= "'" . $today . "','" . $today . "')";
		
			//echo $sql;
					
			mysql_query($sql);
					
			$friend_id = mysql_insert_id();
						
			$friend = new Friend();
			
			$friend->friend_id = $friend_id;
			$friend->friend_user_id = $friend_user_id;
			$friend->is_registered = $is_registered;
			$friend->completed_requests = $completed_requests;
			$friend->tag_1 = $tag_1;
			$friend->tag_2 = $tag_2;
			
			$response->friends[] = $friend;
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
		
		//retrieve TAGs
		$sql = "SELECT tag_id, name FROM tag";
		
		//echo $sql;
			
		$rs = mysql_query($sql);
		
		while($row = mysql_fetch_assoc($rs)) 
		{
			$tag = new Tag();
			
			$tag->tag_id = $row["tag_id"];
			$tag->name = $row["name"];
			
			$response->tags[] = $tag; //Tag
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
	
	//your unique registration id
	public $user_id;
	
	//your access_key to API
	public $access_key;
	
	public $is_invited;
	
	public $last_updates;
	
	public $invitation;
		
	//list of friends
	public $friends;

	//list of friends
	public $tag_received;
	
	//list of tag master 
	public $tags;
	
	public function __construct($iCode, $iMessage ){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
		$this->is_invited = 0;
		$this->friends = array();		
		$this->tag_received = array();
		$this->tags = array();

	}
}

class Invitation{
	public $from_user_id;
	
	public $name;
	
	public $profile_picture;

	//if ZERO meaning there is no tag given you
	public $tag_1;
	//if ZERO meaning there is no tag given you	
	public $tag_2;
	
	public function __construct()
	{
	}
}
class Friend{
	//unique identitication
	public $friend_id;
	
	//user_id of friend 
	public $friend_user_id;
	
	public $tag_1;
	public $tag_2;
	public $got_tag_1;
	public $got_tag_2;
	
	public $completed_requests;
	
	//whether friend is registered or not
	public $is_registered;
	
	public function __construct()
	{
	}
}	

class Tag{
	public $tag_id;
	public $name;
	
	public function __construct()
	{
	}	
}
?>	