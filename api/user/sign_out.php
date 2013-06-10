<?php include('../includes/dbconnect.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	
	$access_key= $data->{'access_key'};
	$user_id = $data->{'user_id'};
	
	//open databse connection
	$conn = openDBCon();
	
	//verify user existance
	$sql = "SELECT user_id,sessions ";
	$sql .= " FROM fb_user ";
	$sql .= " WHERE user_id = " . $user_id . " AND ";
	$sql .= " access_key='" . $access_key . "'";
	
	// echo $sql;
	
	$rs = mysql_query($sql);
	
	if (mysql_num_rows($rs)>0){ 
		
		$row = mysql_fetch_assoc($rs);
		
		$sql = " UPDATE fb_user SET ";
		
		if ($row['sessions'] == 1){
			$sql .= " access_key = null, " ;
		}
		$sql .= " sessions = sessions-1 " ;
		$sql .= " WHERE user_id = " . $user_id;
		
		// echo $sql;
	   
		$rs=mysql_query($sql);
		
		$response= new response(1,"Success");
						
	}else{
		
		$response= new response(0,"Access denied, invalid user reference");
			
	}
	   
	closeDBCon($conn);
	   
	echo json_encode($response);
	   
	   
class Response{
	public $code;
	public $message;

	public function __construct($iCode, $iMessage ){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
	}
}


?>