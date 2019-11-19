<?php

require_once "../rest.php";
require_once "../lib.php";

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();

//connect to the database
$db = new PDO("pgsql:dbname=ladder host=localhost password=314dev user=dev");
//XXX uncomment above and comment out below for dev environment
//$db = new PDO("pgsql:dbname=wh_ladder host=localhost password=1392922 user=whiscox09");

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//view
if($request->isGet()) {
   
    //if the player key is set, return any challenge in which the player is either a challenger or challengee
    if(isset($vars["player"]))
    {
        //ensure that "challenger" isn't also set
        exit_on_failure(isset($vars["challenger"]), "ONLY PLAYER OR CHALLENGER CAN BE SET; NOT BOTH!");

        //get the value of player
        $player = $vars["player"];

        //make sure the player exists in the database
        exit_on_failure(player_exists($player, $db), "PLAYER $player DOES NOT EXIST!");

        //generate the sql query
        $sql = "SELECT challenger, challengee, issued, accepted, scheduled FROM challenge WHERE challengee = ? OR challenger = ?;";

        //run the sql query
        $results["challenges"] = execute_sql_query($sql, [$player, $player], $db);
    }
    //else if the challenger key is set, return the players available for the challenger to challenge
    else if(isset($vars["challenger"])
    {
        //get the value of challenger
        $challenger = $vars["challenger"];

        //make sure the challenger is a player in the database
        exit_on_failure(player_exists($challenger, $db), "PLAYER $challenger DOES NOT EXIST!");

        //get the rank of the challenger
        $rank = execute_sql_query("SELECT rank FROM player WHERE username = ?;", [$challenger], $db);

        //generate the sql query
        $sql = file_get_contents("get_challengees.php");

        //run the sql query
        $results["candidates"] = execute_sql_query($sql, [$rank, $rank], $db);
    }
    else
    {
        exit_on_failure(false, "MISSING REQUIRED PARAMETERS!");
    }

    http_response_code (200);
}

//create
elseif($request->isPost()) {
    //Make sure all the variables are set
    exit_on_failure(vars_is_set(["name", "email", "phone", "username", "password"], $vars), "ONE OR MORE REQUIRED VARIABLES IS MISSING!");

    //Get required variables
    $name = $vars["name"];
    $email = $vars["email"];
    $phone = $vars["phone"];
    $username = $vars["username"];
    $password = password_hash($vars["password"], PASSWORD_DEFAULT);

    //validate phone number structure
    exit_on_failure(validate_phone($phone), "THE PHONE NUMBER GIVEN WAS NOT FORMATTED CORRECTLY!");
    
    //validate email structure
    exit_on_failure(validate_email($email), "THE EMAIL GIVEN WAS NOT FORMATTED CORRECTLY!");

    //assign a rank
    $rank = generate_new_rank($db);

    //generate and run sql query to add new player
    $sql = "INSERT INTO player (name, email, phone, username, password, rank) VALUES (?, ?, ?, ?, ?, ?);";

    execute_sql_query($sql, [$name, $email, $phone, $username, $password, $rank], $db);

    $results = array("error_text"=>"");

    http_response_code (202);
}

//delete
elseif($request->isDelete()) {
    //Make sure all the variables are set
    exit_on_failure(vars_is_set(["username"], $vars), "CAN'T DELETE WITHOUT A USERNAME!");

    //Get required variables
    $username = $vars["username"];

    //Make sure the username requested exists in the database
    exit_on_failure(player_exists($username, $db), "THE REQUESTED PLAYER DOES NOT EXIST!");

    //Remove all outstanding challenges and games
    execute_sql_query("DELETE FROM challenge WHERE challenger = ? OR challengee = ?;", [$username, $username], $db);
    execute_sql_query("DELETE FROM game WHERE winner = ? OR loser = ?;", [$username, $username], $db);

    //Get the player's rank
    $rank = execute_sql_query("SELECT rank FROM player WHERE username = ?", [$username], $db)['0']["rank"];

    //Delete the player
    execute_sql_query("DELETE FROM player WHERE username = ?;", [$username], $db);

    //Update the rank of every lower-ranked player
    shift_all_ranks($rank, true, $db);

    $results = array("error_text"=>"");

    http_response_code (200);
}

//update
elseif($request->isPut()) {
    //Make sure all necessary variables are set
    exit_on_failure(vars_is_set(["username", "name", "email", "rank", "phone"], $vars), "ONE OR MORE REQUIRED VARIABLES IS MISSING!");
    //get variables
    $username = $vars["username"];
    $name = $vars["name"];
    $email = $vars["email"];
    $phone = $vars["phone"];
    $new_rank = $vars["rank"];

    //check to make sure the player exists
    exit_on_failure(player_exists($username, $db), "THE REQUESTED PLAYER DOES NOT EXIST!");

    //Get the player's old rank
    $old_rank  = execute_sql_query("SELECT rank FROM player WHERE username = ?;", [$username], $db)[0]["rank"];
    
    //validate phone number structure
    exit_on_failure(validate_phone($phone), "THE PHONE NUMBER GIVEN WAS NOT FORMATTED CORRECTLY!");

    //validate email structure
    exit_on_failure(validate_email($email), "THE EMAIL GIVEN WAS NOT FORMATTED CORRECTLY!");
    
    //if the rank is unchanged, update everything else
    if ($old_rank == $new_rank)
    {
        $sql = "UPDATE player SET name = ?, email = ?, phone = ? WHERE username = ?;";
        execute_sql_query($sql, [$name, $email, $phone, $username], $db);
    }
    else
    {

        //Build and execute the sql query to move the selected player to rank 0 for now
        $sql = "UPDATE player SET name =  ?, email = ?, rank = 0, phone = ? WHERE username = ?;";
        execute_sql_query($sql, [$name, $email, $phone, $username], $db);

        //Make space for the new location of the player
        
        //if the old rank for the player is higher than the new rank i.e. $old_rank < $new_rank
        if ($old_rank < $new_rank)
        {
            //Move ranks in between the old rank and the new rank up to fill in the old rank position
            shift_rank_range($old_rank, $new_rank, true, $db);
        }
        //else if the old rank is lower thank the new rank ($old_rank > $new_rank)
        else if ($old_rank > $new_rank)
        {
            //Move ranks in between the old rank and the new rank down to fill in the old rank position
            shift_rank_range($new_rank, $old_rank, false, $db);
        }
    
        //Update the rank of the player
        $sql = "UPDATE player SET rank = ? WHERE username = ?;";
        execute_sql_query($sql, [$new_rank, $username], $db);
        
    }

    $results = array("error_text"=>"");
}

echo(json_encode($results));

?>
