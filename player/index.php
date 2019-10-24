<?php

include_once("../rest.php");

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();
$type = $request->getRequestType();

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

$results = array("resource" => "player", "method" => "get", "request_vars" => $vars);//XXX

/*
//view
if($request->isGet())
{
   //TODO implement Get

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

*/
echo(json_encode($results));
?>
