<?php

echo "hello";

//$obj = json_decode(file_get_contents('php://input'));

$data = json_decode($_POST['data']);

//echo $obj;

echo $data->{'name'};
?>	