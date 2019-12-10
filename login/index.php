<?php

require_once "../rest.php";
require_once "../lib.php";

session_start();

//view
if($request->isGet()) {
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
    exit_on_failure(vars_is_set(["username"], $vars), "USERNAME IS MISSING!");
    exit_on_failure(vars_is_set(["password"], $vars), "NO PASSWORD ENTERED!");
   
    //Get parameters
    $username = $vars["username"];
    $password = $vars["password"];

    exit_on_failure(player_exists($username, $db), "PLAYER $username DOES NOT EXIST!");

    $valid_login = execute_sql_query("SELECT password FROM player WHERE username = ?;", [$username], $db)[0]["password"];

    //check login for correct credentials
    if (password_verify($password, $valid_login))
    {
        //if correct
            //Create a new session
            $_SESSION["username"] = $vars["username"];
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
    session_unset();
    session_destroy();
    http_response_code (200);
}

echo(json_encode($results));

?>
