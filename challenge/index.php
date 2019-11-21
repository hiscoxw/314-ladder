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
        exit_on_failure(!isset($vars["challenger"]), "ONLY PLAYER OR CHALLENGER CAN BE SET; NOT BOTH!");

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
    else if(isset($vars["challenger"]))
    {
        //get the value of challenger
        $challenger = $vars["challenger"];

        //make sure the challenger is a player in the database
        exit_on_failure(player_exists($challenger, $db), "PLAYER $challenger DOES NOT EXIST!");

        //get the rank of the challenger
        $rank = execute_sql_query("SELECT rank FROM player WHERE username = ?;", [$challenger], $db)[0]["rank"];

        //generate the sql query
        $sql = file_get_contents("get_challengees.sql");
        
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

    //Check to make sure required variables are set
    exit_on_failure(vars_is_set(["challenger", "challengee", "scheduled"], $vars), "ONE OR MORE REQUIRED VARIABLES IS MISSING!");

    //Get required variables
    $challenger = $vars["challenger"];
    $challengee = $vars["challengee"];
    $scheduled = $vars["scheduled"];

    //Check to make sure that both challenger and challengee exist as players in the database
    exit_on_failure(player_exists($challenger, $db), "PLAYER $challenger DOESN'T EXIST IN THE DATABASE!");
    exit_on_failure(player_exists($challengee, $db), "PLAYER $challengee DOESN'T EXIST IN THE DATABASE!");

    //Check to make sure that the scheduled date is formatted correctly
    exit_on_failure(validate_date_time($scheduled), "THE SCHEDULED DATE IS FORMATTED INCORRECTLY!");

    //Make sure the challengee is valid for the challenger.
    exit_on_failure(check_valid_challengee($challenger, $challengee, $db), "THE CHALLENGER, $challenger, CANNOT CHALLENGE $challengee!");

    //TODO make sure the challenge is unique

    //generate the issued date/time as today's date/time in the correct format (YYYY-MM-DD HH:MM:SS)
    $issued = date("Y-m-d H:i:s");

    //generate and execute the sql query to add the challenge
    $sql = "INSERT INTO challenge (challenger, challengee, issued, scheduled) VALUES (?, ?, ?, ?);";
    execute_sql_query($sql, [$challenger, $challengee, $issued, $scheduled], $db);

    $results = array("error_text"=>"");

    http_response_code (202);
}

//delete
elseif($request->isDelete()) {
    //Make sure all the variables are set
    exit_on_failure(vars_is_set(["challenger", "challengee", "scheduled"], $vars), "MISSING ONE OR MORE REQUIRED VARIABLES!");

    //Get required variables
    $challenger = $vars["challenger"];
    $challengee = $vars["challengee"];
    $scheduled = $vars["scheduled"];
    
    //generate and execute the sql query
    $sql = "DELETE FROM challenge WHERE challenger = ? AND challengee = ? AND scheduled = ?;";
    execute_sql_query($sql, [$challenger, $challengee, $scheduled], $db);

    $results = array("error_text"=>"");

    http_response_code (200);
}

//update
elseif($request->isPut()) {
    //Make sure all necessary variables are set
    exit_on_failure(vars_is_set(["challenger", "challengee", "scheduled"], $vars), "ONE OR MORE REQUIRED VARIABLES IS MISSING!");
    exit_on_failure(key_exists("accepted", $vars), "THE accepted VARIABLE MUST BE SET, CAN BE NULL!");


    //get variables
    $challenger = $vars["challenger"];
    $challengee = $vars["challengee"];
    $scheduled = $vars["scheduled"];
    $accepted = $vars["accepted"];

    //Validate datetime format for scheduled
    exit_on_failure(validate_date_time($scheduled), "$scheduled IS NOT A VALID DATETIME FORMAT FOR SCHEDULED!");
    
    //update information
    $sql = "UPDATE challenge SET scheduled = ? WHERE challenger = ? AND challengee = ?;";
    execute_sql_query($sql, [$scheduled, $challenger, $challengee], $db);

    //TODO If accepted, all other outstanding challenges must be removed
    if ($accepted != "")
    {
        //Validate datetime format for accepted
        exit_on_failure(validate_date_time($accepted), "$accepted IS NOT A VALID DATETIME FORMAT FOR SCHEDULED!");
        
        //Set accepted
        $sql = "UPDATE challenge SET accepted = ? WHERE challenger = ? AND challengee = ?;";
        execute_sql_query($sql, [$accepted, $challenger, $challengee], $db);

        //Delete outstanding challenges for both challenger and challengee
        $sql = "DELETE FROM challenge WHERE challenger = ? AND accepted IS NULL;";
        execute_sql_query($sql, [$challenger], $db);
        execute_sql_query($sql, [$challengee], $db);
        $sql = "DELETE FROM challenge WHERE challengee = ? AND accepted IS NULL;";
        execute_sql_query($sql, [$challenger], $db);
        execute_sql_query($sql, [$challengee], $db);
    }

    $results = array("error_text"=>"");
}

echo(json_encode($results));

?>
