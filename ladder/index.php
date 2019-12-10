<?php

require_once "../rest.php";
require_once "../lib.php";

//Load whatever php session may exist
session_start();

//TODO add login requirement

//view
if($request->isGet()) {
    
    $sql = "SELECT rank, username, name FROM player ORDER BY rank ASC;";
    
    $results = execute_sql_query($sql, [], $db);

    http_response_code (200);
}

echo(json_encode($results));

?>
