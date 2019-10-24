<?php

include_once("../rest.php");

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();
$type = $request->getRequestType();//XXX
$resource = "challenge";

//connect to the database
$db = new PDO("pgsql:dbname=ladder host=localhost password=1392922 user=whiscox09");
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

$results = array("resource" => $resource, "method" => $type, "request_vars" => $vars);//XXX

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
