<?php

function randomString($len) {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}


function send_apns_notification_on_tagging($from_user_id, $to_user_id, $tag_1, $tag2, $today){

	ini_set('display_errors', 'Off');
	ini_set('display_startup_errors', 'Off');
	error_reporting(0);

	// TODO: Put your private key's passphrase here:
	$passphrase = 'Mehul2015';
	$cert = 'ckdev.pem';

//	$passphrase = 'Sample@1235';
//	$cert = 'ckdev.pem';
	
	//Get Push ID
	$sql = " SELECT pushid ";
	$sql .= " FROM fb_user ";
	$sql .= " WHERE user_id = " . $to_user_id;
	$sql .= " AND pushid IS NOT null ";
	$sql .= " AND pushid != ''";
	
	echo $sql;
	
	$rs = mysql_query($sql);
		
	if (mysql_num_rows($rs) > 0){
	
		$row = mysql_fetch_array($rs);
		$pushid = $row['pushid'];
		
		echo $pushid;
	
		//Get From User Information
		$sql = " SELECT fb_user.name ";
		$sql .= " FROM fb_user ";
		$sql .= " WHERE user_id = " . $from_user_id;
		
		$rs = mysql_query($sql);
		
		$row = mysql_fetch_array($rs);
		$name = $row['name'];

		//get friend id
		$sql = " SELECT friend.friend_id, (fb_user.badge+1) as badge ";
		$sql .= " FROM friend " ;
		$sql .= " JOIN fb_user ON (friend.my_user_id = fb_user.user_id) ";
		$sql .= " WHERE friend.my_user_id = " . $to_user_id;
		$sql .= " AND friend.friend_user_id = " . $from_user_id;
		
		echo $sql;
		
		$rs = mysql_query($sql);
		
		$row = mysql_fetch_array($rs);
		$friend_id = $row['friend_id'];
		$badge = $row['badge'];		

		//Select Tag Name
		$sql = " SELECT tag.name ";
		$sql .= " FROM tag ";
		$sql .= " WHERE tag_id = " . $tag_1 . " OR tag_id = ". $tag_2;
		
		$rs = mysql_query($sql);
		
		//first tag
		$row = mysql_fetch_array($rs);
		$tag_1_name = $row['name'];
		
		//second tag
		$row = mysql_fetch_array($rs);
		$tag_2_name = $row['name'];
				
		// Message to be sent
		$message = "Ny anmodning " . $name;
	
		$ctx = stream_context_create();	
		
		stream_context_set_option($ctx, 'ssl', 'local_cert', $cert);
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

		// Open a connection to the APNS server
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx); 
		
		if (!$fp) {
			echo "fail to connect " . $err;die;
			//exit("Failed to connect: $err $errstr" . PHP_EOL);
		}else{
			echo 'Connected to APNS' . PHP_EOL;	

			// Create the payload body
			$body['aps'] = array(
					'alert' => array('body' => $message, 'action-loc-key' => 'View'),
					'sound' => 'default',
					'badge' =>  $badge
				);
			$body['action'] = "tag_friend";					
			$body['friend_id'] = $friend_id;
			$body['tag_1'] = $tag_1;
			$body['tag_2'] = $tag_2;
			$body['tag_status'] = 0;
			$body['created_date'] = $today;
			
			// Encode the payload as JSON
			$payload = json_encode($body);				

			$token = $row['push_id'];
			$token = str_replace(" ", "", $token);
			//echo $token . "<BR>";				
			
			$msg = chr(0); 
			$msg .= pack('n', 32) ;
			$msg .= pack('H*', $token) ;
			$msg .= pack('n', strlen($payload)) ;
			$msg .=  $payload;
			
			// Send it to the server
			$result = fwrite($fp, $msg, strlen($msg));

			// Close the connection to the server
			fclose($fp);
			
			//Increase badge value
			$sql = "UPDARE fb_user SET ";
			$sql .= " badge = badge+1 ";
			$sql .= " WHERE user_id = " . $to_user_id;
			
			//echo $sql;
			
			mysql_query($sql);
		}	
	}
	
	return;
}

?>
