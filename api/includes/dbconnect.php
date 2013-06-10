<?php

	// Local DB
	define('HOST', "192.168.1.11");
	define('USERNAME', "laybls");
	define('PASSWORD', "test");
	define('DATABASE', "laybls");
	define('BASE_URL', "http://192.168.1.11:8080/laybls/");
	
	define('debug_mode',true);
	
	if(debug_mode){
		error_reporting(E_ALL); 
		ini_set('log_errors','1'); 
		ini_set('display_errors','1'); 
		ini_set('error_log', 'errors.log');
	}else{
		error_reporting(E_ALL); 
		ini_set('log_errors','1'); 
		ini_set('display_errors','0');
	}
	
	function openDBCon(){
		$conn = mysql_connect(HOST,USERNAME,PASSWORD);
		mysql_select_db(DATABASE) or die(mysql_error());
		return $conn;
	}	
	
	function closeDBCon($conn){
		mysql_close($conn);
	}
	
?>
