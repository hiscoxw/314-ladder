<?php

include_once("../rest.php");

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();

//connect to the database
//$db = new PDO("pgsql:dbname=ladder host=localhost password=314dev user=dev");
//XXX uncomment above and comment out below for dev environment
$db = new PDO("pgsql:dbname=wh_ladder host=localhost password=1392922 user=whiscox09");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


function exit_on_failure($test, $msg)
{
   if(!$test)
   {
      $results["error_text"] = $msg;
      echo(json_encode($results));
      exit();
   }
}


function execute_sql_query($sql, $args, $db)
{
   $statement = $db->prepare($sql);
   $statement->execute((array)$args);
   $returnvalue = $statement->fetchAll(PDO::FETCH_ASSOC);

   return $returnvalue;
}

//TODO finish function
function player_exists($username, $db)
{
   return true;
}


//view
if($request->isGet())
{
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

}

//create
elseif($request->isPost())
{
   //TODO implement Post

}

//delete
elseif($request->isDelete())
{
   //TODO implement Delete
}

//update
elseif($request->isPut())
{
   //TODO implement Put
}

echo(json_encode($results));
?>
