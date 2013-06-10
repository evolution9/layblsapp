<?php include('../includes/dbconnect.php'); ?>
<?php
	header('Content-type: application/json');
	
	//read json from request
	$data = json_decode($_POST['data']);
	$user_id=$data->{'user_id'};
	$access_key= $data->{'access_key'};
	$tag_name=$data->{'tag_name'};
	
	//open databse connection
	$conn = openDBCon();
	
	//verify user existance
	$sql = "SELECT user_id ";
	$sql .= " FROM fb_user ";
	$sql .= " WHERE user_id = " . $user_id . " AND ";
	$sql .= " access_key='" . $access_key . "'";
	
	// echo $sql;
		
	$rs = mysql_query($sql);
	// echo mysql_num_rows($rs) . "First query" ."</br>";
	if (mysql_num_rows($rs) > 0)
	{ 
			$sql = "SELECT tag_id FROM tag WHERE UPPER(name) ='" . strtoupper($tag_name) . "'" ;
			//	echo $sql;
	   
			$rs=mysql_query($sql);
			
			$response= new Response(1,"Sucess"); 
			
			//echo $count=mysql_num_rows($rs); 
			if (mysql_num_rows($rs) > 0)
			{
				$row = mysql_fetch_assoc($rs);

				$tag_id = $row["tag_id"];
			}
			else
			{
				$today = gmdate('Y-m-d H:i:s', time());

				$sql= " Insert into tag (name,created_date) values (";
				$sql .= "'" . $tag_name . "',";
				$sql .= "'" . $today . "')"; 
				
			   	// echo $sql;
				$rs=mysql_query($sql);
				
				$tag_id = mysql_insert_id();
			} 
			
			$response->tag_id = $tag_id;
	   }
	else
	   {
		$response= new response(0,"Access denied, invalid user reference");
	   }

	   
closeDBCon($conn);
	   
echo json_encode($response);
	   	   
class Response{
	public $code;
	public $message;
	public $tag_id;

	public function __construct($iCode, $iMessage){
		$this->code = $iCode;
		$this->message = array();
		$this->message[] = $iMessage;
	}
}
?>