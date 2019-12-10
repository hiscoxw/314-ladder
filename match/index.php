<?php

require_once "../rest.php";
require_once "../lib.php";

//view
if($request->isGet()) {
  
    //make sure username is set
    exit_on_failure(vars_is_set(["username"], $vars), "THE USERNAME IS MISSING!");   

    //get the username from vars
    $username = $vars["username"];

    //make sure the player exists
    exit_on_failure(player_exists($username, $db), "THE REQUESTED PLAYER DOES NOT EXIST!");
    
    //make sure the player has played games
    exit_on_failure(player_has_match($username, $db), "THE REQUESTED PLAYER HASN'T PLAYED ANY GAMES YET!");

    //create the queries and get the results
    if (isset($vars["played"]))
    {
        $played = $vars["played"];

        //Validate played
        exit_on_failure(validate_date_time($played), "$played IS NOT A VALID DATE FORMAT!");

        $sql = "SELECT * FROM (SELECT * FROM game WHERE played = ?) AS sub WHERE winner = ? OR loser = ?;";
        $results = execute_sql_query($sql, [$played, $username, $username], $db);
    }
    else
    {
        $sql = "SELECT * FROM game WHERE winner = ? OR loser = ?;";
        $results = execute_sql_query($sql, [$username, $username], $db);
    }

    http_response_code (200);
}

//create
elseif($request->isPost()) {
    //Make sure all the variables are set
    exit_on_failure(vars_is_set(["games:", "played"], $vars), "ONE OR MORE REQUIRED VARIABLES IS MISSING!");

    //Get required variables
    $games = $vars["games:"];
    $played = $vars["played"];

    //Validate played
    exit_on_failure(validate_date_time($played), "$played IS NOT A VALID DATE FORMAT!");

    //Validate that there are three games to add
    exit_on_failure(validate_match_is_full($games), "THERE ARE NOT ENOUGH GAMES TO MAKE A FULL MATCH!"); 

    $number = 1;
    $players = array();

    //Generate and run an sql query for each game to add
    //Make sure there are only two player names used
    foreach($games as $game)
    {
        //Make sure the game has all the necessary variables
        exit_on_failure(vars_is_set(["winner", "loser", "winner_score", "loser_score"], $game), "ONE OR MORE GAMES IN THE MATCH IS MISSING VARIABLES!");

        $winner = $game["winner"];
        $loser = $game["loser"];
        $winner_score = $game["winner_score"];
        $loser_score = $game["loser_score"];

        exit_on_failure(player_exists($winner, $db), "PLAYER $winner DOES NOT EXIST IN THE DATABASE!");
        exit_on_failure(player_exists($loser, $db), "PLAYER $loser DOES NOT EXIST IN THE DATABASE!");
        
        exit_on_failure($winner != $loser, "THE WINNER AND LOSER CAN'T BE THE SAME!");
        

        if (sizeOf($players) < 2)
        {
            $players[$winner] = 0;
            $players[$loser] = 0;
        }
        else if (!(array_key_exists($winner, $players) and array_key_exists($loser, $players)))
        {
            exit_on_failure(false, "MUST USE THE SAME PLAYERS FOR ALL GAMES IN THE MATCH!");
        }

        $players[$winner]++;

        
        //create the sql query to insert the game into the game table
        $sql = "INSERT INTO game (winner, loser, played, number, winner_score, loser_score) VALUES (?, ?, ?, ?, ?, ?);";

        //run the query
        execute_sql_query($sql, [$winner, $loser, $played, $number, $winner_score, $loser_score] , $db);

        $number++;
    }

    //adjust rank of players based on the results of the games

    //Figure out who won the match
    $match_winner = array_search(max($players), $players);

    //Figure out who lost the match
    $match_loser = array_search(min($players), $players);

    //get the rank of the match winner
    $winner_rank = execute_sql_query("SELECT rank FROM player WHERE username = ?;", [$match_winner], $db)[0]["rank"];

    //get the rank of the match loser
    $loser_rank = execute_sql_query("SELECT rank FROM player WHERE username = ?;", [$match_loser], $db)[0]["rank"];

    //if the winner has a higher rank (lower value) than the loser...
    if($winner_rank > $loser_rank)
    {
        //set the winner's rank to zero
        execute_sql_query("UPDATE player SET rank = 0 WHERE username = ?;", [$match_winner], $db);

        //move everyone between the lower ranked player and the higher ranked player down in rank
        shift_rank_range($loser_rank, $winner_rank, false, $db);

        //assign the higher ranked players former rank to the lower ranked player.
        execute_sql_query("UPDATE player SET rank = ? WHERE username = ?;", [$loser_rank, $match_winner], $db);
    }

    $results = array("error_text"=>"");

    http_response_code (202);
}

//delete
elseif($request->isDelete()) {
    //Make sure all the variables are set
    exit_on_failure(vars_is_set(["player1", "player2", "played"], $vars), "MISSING REQUIRED VARIABLES!");

    //Get required variables
    $player1 = $vars["player1"];
    $player2 = $vars["player2"];
    $played = $vars["played"];

    //Make sure the usernames requested exist in the database
    exit_on_failure(player_exists($player1, $db), "PLAYER $player1 DOES NOT EXIST!");
    exit_on_failure(player_exists($player2, $db), "PLAYER $player2 DOES NOT EXIST!");

    //Remove all games related to the match
    $sql = "DELETE FROM game WHERE played = ? AND winner = ? AND loser = ?;";
    execute_sql_query($sql, [$played, $player1, $player2], $db);
    execute_sql_query($sql, [$played, $player2, $player1], $db);

    http_response_code (200);

    $results = array("error_text" => "");
}

echo(json_encode($results));

?>
