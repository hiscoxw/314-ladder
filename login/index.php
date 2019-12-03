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


//view
if($request->isGet()) {
    session_start();
    if(isset($_SESSION["username"]))
    {
        $results["username"] = $_SESSION["username"];
        http_response_code (200);
    }
    else
    {
        $results["error_text"] = "NO CURRENT SESSION!";
        http_response_code (403);
    }
}

//create
elseif($request->isPost()) {
    //make sure a username and password are entered
    exit_on_failure(vars_is_set(["username"], $_POST), "USERNAME IS MISSING!");
    exit_on_failure(vars_is_set(["password"], $_POST), "NO PASSWORD ENTERED!");
    
    //Get parameters
    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $valid_login = execute_sql_query("SELECT password FROM player WHERE username = ?;", [$username], $db)[0]["password"];

    //check login for correct credentials
    if ($password == $valid_login)
    {
        //if correct
            //Create a new session
            session_start();
            $_SESSION["username"] = $_POST["username"];
            http_response_code (200);
            $results["error_text"] = "";
    }
    else
    {
        $results["error_text"] = "ACCESS DENIED!";
        //else return http code 403
        http_response_code (403);
    }
}

//delete
elseif($request->isDelete()) {
    session_start();
    session_unset();
    session_destroy();
}

echo(json_encode($results));

?>
