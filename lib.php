<?php

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
        print_r($sql);//XXX Prints out the failed sql query. Useful for debugging.

        $db->rollback();
         exit_on_failure(false, $e->getMessage());
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
    $exists = execute_sql_query($sql, [$username], $db);

    return $exists[0]["exists"] == 1;
}


/**
* Tests to see if a player has played any matches.
*
*@param string $username the username of the player to check
*@param PDO $db the database in which to check
*/
function player_has_match($username, $db)
{
    $sql = "SELECT EXISTS(SELECT * FROM match_view WHERE winner = ? OR loser = ?);";
    $exists = execute_sql_query($sql, [$username, $username], $db);

    return $exists[0]["exists"] == 1;
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


/**
* Checks the date and time format
*
*@param string $date the date and time to validate
*/
function validate_date_time($date)
{
    $regex = '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}^';

    return preg_match($regex, $date);
}


/**
* Checks to make sure a match has all 3 games and no more.
*
*@param string[] $games is the array of games in the match
*/
function validate_match_is_full($games)
{
    return sizeOf($games) == 3;
}


/**
*TODO
*/
function check_valid_challengee($challenger, $challengee, $db)
{
    //Get the challenger's rank
    $rank = execute_sql_query("SELECT rank FROM player WHERE username = ?;", [$challenger], $db)[0]["rank"];

    //Get the list of valid challengees for the challenger's rank
    $sql = file_get_contents("get_challengees.sql");
    $candidates = execute_sql_query($sql, [$rank, $rank], $db);

    //check each valid candidate to see if challengee is listed
    $valid = false;
    
    foreach ($candidates as $candidate)
    {
        
        if($candidate["username"] == $challengee)
        {
            $valid = true;
        }
    }

    return $valid;
}

?>
