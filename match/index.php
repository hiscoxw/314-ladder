<?php

include_once("../rest.php");

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();

//connect to the database
$db = new PDO("pgsql:dbname=ladder host=localhost password=314dev user=dev");
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


//view
if($request->isGet())
{
   //TODO implement Get
   $results = array("error_text" => "Get not yet implemented!");
}

//create
elseif($request->isPost())
{
   //TODO implement Post
   $results = array("error_text" => "Post not yet implemented!");
}

//delete
elseif($request->isDelete())
{
   //TODO implement Delete
   $results = array("error_text" => "Delete not yet implemented!");
}

//update
elseif($request->isPut())
{
   //TODO implement Put
   $results = array("error_text" => "Put not yet implemented!");
}


echo(json_encode($results));
?>
