<?php

// Put your device token here (without spaces):
$deviceToken = 'b0afbe00e868c7e2beba7e28fc0bbb45930bff6347ffddd26a465eb18dee9155';
//$deviceToken = '14f4e3f110ea3bfeabdbde4cd94ed1a925d74f920071160fed4ee846556753a6';

// Put your private key's passphrase here:
$passphrase = 'Mehul2015';

// Put your alert message here:
$message = 'My first push notification!';
//$message = 'Skin Care on Wed, Feb 20 2013 12:45 AM';

////////////////////////////////////////////////////////////////////////////////

$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'ckdev.pem');
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
$fp = stream_socket_client(
	'ssl://gateway.sandbox.push.apple.com:2195', $err,
	$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

if (!$fp)
	exit("Failed to connect: $err $errstr" . PHP_EOL);

echo 'Connected to APNS' . PHP_EOL;

$badge = 11;
$appointment_id = 268;

// Create the payload body
/*
 $body['aps'] = array(
	'alert' => $message,
	'sound' => 'default',
	"badge" => $badge
	); 
*/
	
$body['aps'] = array(
		'alert' => $message, 
		'sound' => 'default',
		"badge" => $badge
	);
			
$body['appointment_id'] = $appointment_id;

	
// Encode the payload as JSON
$payload = json_encode($body);

// Build the binary notification
$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
$result = fwrite($fp, $msg, strlen($msg));

if (!$result)
	echo 'Message not delivered' . PHP_EOL;
else
	echo 'Message successfully delivered' . PHP_EOL;

// Close the connection to the server
fclose($fp);

?>