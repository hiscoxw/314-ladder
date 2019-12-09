<?php

require_once "../rest.php";
require_once "../lib.php";

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();

//connect to the database
//$db = new PDO("pgsql:dbname=ladder host=localhost password=314dev user=dev");
//XXX uncomment above and comment out below for dev environment
$db = new PDO("pgsql:dbname=wh_ladder host=localhost password=1392922 user=whiscox09");

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//TODO add login requirement

//view
if($request->isGet()) {
    
    $sql = "SELECT rank, username, name FROM player ORDER BY rank ASC;";
    
    $results = execute_sql_query($sql, [], $db);

    http_response_code (200);
}

echo(json_encode($results));

?>
