<?php

include_once("~/public_html/314-ladder/rest.php");

//make an object to process REST requests
$request = new RestRequest();

//get the request variables
$vars = $request->getRequestVariables();

//connect to the database
$db = new PDO("pgsql:dbname=teach_yourself host=localhost password=314dev user=dev");
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
}

//create
elseif($request->isPost())
{
}

//delete
elseif($request->isDelete())
{
}

//update
elseif($request->isPut())
{
}


echo(json_encode($results));
?>
