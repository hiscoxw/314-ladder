<?php

require_once "../rest.php";

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();

//connect to the database
//:$db = new PDO("pgsql:dbname=ladder host=localhost password=314dev user=dev");
//XXX uncomment above and comment out below for dev environment
$db = new PDO("pgsql:dbname=wh_ladder host=localhost password=1392922 user=whiscox09");

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


/**
* Exits the program based on the results of a test that is passed in.
*
*@param bool $test is the result of the test that is passed in.
*@param string $msg is the message to be displayed if the test has failed.
*/
function exit_on_failure($test, $msg)
{
    if(!$test) {
        $results["error_text"] = $msg;
        echo(json_encode($results));
        http_response_code (400);
        exit();
    }
}


/**
* Checks to see if the variables needed for the operation have been correctly set.
*
* @param array(string) $variables is the array of variable names, as strings needed as keys in the $vars array.
* @param array $vars is the associative array of keys values passed in by the user.
*/
function vars_is_set($variables, $vars)
{
    $set = true;
    foreach($variables as $variable)
    {
        if (!isset($vars["$variable"])) {
            $set = false;
        }
    }

    return $set;
}


/**
* Runs an sql query and returns the results.
*
*@param string $sql the query to be run
*@param string[] $args the arguments of the query
*@param PDO $db the database on which to run the query
*/
function execute_sql_query($sql, $args, $db)
{
    try
    {
        $db->beginTransaction();

        $statement = $db->prepare($sql);
        $statement->execute((array)$args);
        $returnvalue = $statement->fetchAll(PDO::FETCH_ASSOC);

        $db->commit();
    }
    catch (Exception $e)
    {
        $returnvalue = array("error_text" => $e->getMessage());
    }
    return $returnvalue;
}


/**
* Tests to see if a player exists in the database
*
*@param string $username the username of the player to find
*@param PDO $db the database in which to search for the player
*/
function player_exists($username, $db)
{
    $sql = "SELECT EXISTS(SELECT * FROM player WHERE username = ?);";
    $exists = execute_sql_query($sql, [$username], $db)[0]["exists"];

    return $exists == 1;
}


/**
* Generates a new rank for a new player
*
* @param PDO $db the database where the new player will be added
*/
function generate_new_rank($db)
{
    $sql = "SELECT MAX(rank) FROM player;";
    $max = execute_sql_query($sql, [], $db)[0]["max"];

    return $max + 1;
}


/**
* Shifts ranks of all players up or down to account for changes in a single player's rank
*
*@param int $rank the rank below which to shift players
*@param bool $isup the direction to shift players (up or down in rank -- up is true)
*@param PDO $db the database on which to perform the shift
*/
function shift_all_ranks($rank, $isup, $db)
{
    $lower = execute_sql_query("SELECT MAX(rank) FROM player;", [], $db)[0]["max"];
    
    shift_rank_range($rank, $lower, $isup, $db);
}


/**
* Shifts ranks of all players within a range - from $upper rank to $lower rank, inclusive - up or down.
* 
*@param int $upper the top (lowest number) rank of the range, inclusive
*@param int $lower the bottom (highest number) rank of the range, inclusive
*@param bool $isup the direction to shift players (up or down in rank -- up is true)
*@param PDO $db the database on which to perform the shift
*/
function shift_rank_range($upper, $lower, $isup, $db)
{
        if ($isup)
        {
            //Move ranks up
            $sql = "UPDATE player SET rank = rank - 1 WHERE rank = ?;";

            $x = $upper;
        }
        else
        {
            //Move ranks down
            $sql = "UPDATE player SET rank = rank + 1 WHERE rank = ?;";

            $x = $lower;
        }

        
        while ($x <= $lower && $x >= $upper)
        {
            execute_sql_query($sql, [$x], $db);

            if ($isup)
            {
                $x++;
            }
            else
            {
                $x--;
            }
        }
}


/**
* Validates a phone number. It is valid if it is 10 numeric characters.
*
*@param string $phone the phone number to be validated
*/
function validate_phone($phone)
{
    $regex = '^[0-9]{10}^';

    return preg_match($regex, $phone);
}


/**
* Validates an email.
*
*@param string $email the email to be validated
*/
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}


//view
if($request->isGet()) {
   
    //make sure username is set
    exit_on_failure(vars_is_set(["username"], $vars), "THE USERNAME IS MISSING!");   

    //get the username from vars
    $username = $vars["username"];

    //make sure the player exists
    exit_on_failure(player_exists($username, $db), "THE REQUESTED PLAYER DOES NOT EXIST!");

    //create the queries
    $sql = "SELECT name, username, phone, email, rank,
      (
         SELECT CAST(COUNT(m.winner) AS FLOAT) AS wins
            FROM player AS p1 FULL OUTER JOIN match_view AS m ON p1.username = m.winner
            WHERE p.username = p1.username
      )
      /
      (
         SELECT CAST(COUNT(p2.username) AS FLOAT) AS total_matches
            FROM player AS p2, match_view as m
            WHERE p.username = p2.username AND (p2.username = m.winner OR p2.username = m.loser)
            GROUP BY p2.username
      ) AS match_win_percentage,
      (
         SELECT CAST(COUNT(g.winner) AS FLOAT(2)) AS games_won
            FROM game AS g
            WHERE p.username = g.winner
      )
      /
      (
         SELECT CAST(COUNT(g.played) AS FLOAT(2)) AS games_played
            FROM game as g
            WHERE p.username = g.loser OR p.username = g.winner
      ) AS game_win_percentage
      FROM player AS p WHERE username = ?;";
      
    $sql2 = "SELECT AVG(g.winner_score - g.loser_score) AS winning_margin
               FROM player as p, game as g
               WHERE g.winner = ?;";
         
    $sql3 = "SELECT AVG(g.winner_score - g.loser_score) AS losing_margin
               FROM player AS p, game AS g
               WHERE g.loser = ?
               GROUP BY g.loser;";


    //get the results
    $results = execute_sql_query($sql, [$username], $db)[0];
    $results["winning_margin"] = execute_sql_query($sql2, [$username], $db)[0]["winning_margin"];
    $results["losing_margin"] = execute_sql_query($sql3, [$username], $db)[0]["losing_margin"];

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
