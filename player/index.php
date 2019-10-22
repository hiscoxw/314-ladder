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
   $results = array("resource" => "player", "method" => "get", "request_vars" => $vars);//XXX
}

//create
elseif($request->isPost())
{
   //TODO implement Post
   $results = array("resource" => "player", "method" => "post", "request_vars" => $vars);//XXX

}

//delete
elseif($request->isDelete())
{
   //TODO implement Delete
   $results = array("resource" => "player", "method" => "delete", "request_vars" => $vars);//XXX
}

//update
elseif($request->isPut())
{
   //TODO implement Put
   $results = array("resource" => "player", "method" => "put", "request_vars" => $vars);//XXX
}

*/
echo(json_encode($results));
?>
