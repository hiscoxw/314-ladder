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
//XXX   exit_on_failure(player_exists($username, $db), "THE REQUESTED PLAYER DOES NOT EXIST!");
   
   //create the query
   $sql = "select * from player where username = ?;";

   //get the results
   $results = execute_sql_query($sql, [$username], $db);
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
