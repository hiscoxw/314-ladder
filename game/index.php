<?php

include_once("/home/dev/website/teach_yourself/rest.php");

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

function order_exists($db, $order_num)
{
   $sql = "select exists(select * from orders where order_num = ?);";

   $statement = $db->prepare($sql);
   $statement->execute([$order_num]);
   $exists = ($statement->fetch(PDO::FETCH_ASSOC)["exists"] == 1);

   return $exists;
}

//view product
if($request->isGet())
{
	//get the order number
	$order_num = $vars["order_number"];

   //check to see if the order exists
   exit_on_failure(order_exists($db, $order_num), "The order_number given does not exist!");

 	//create a query
   $sql = "select order_num as order_number, order_date as date, cust_id as customer_id from orders where order_num = ?";
   $sql2 = "select order_item, prod_id as product_id, quantity, item_price from orderitems where order_num = ?";

 	//prepare the statement i.e. make the statement
  	$statement = $db->prepare($sql);
  	$statement2 = $db->prepare($sql2);

   //run the query
   $statement->execute([$order_num]);
   $statement2->execute([$order_num]);

   //get the results
   $results = $statement->fetch(PDO::FETCH_ASSOC);
   $results["items"] = [$statement2->fetch(PDO::FETCH_ASSOC)];
}

//create product
elseif($request->isPost())
{
	if (isset($vars["order_date"], $vars["cust_id"], $vars["items"]))
	{
		//get the request variables
		$order_date = $vars["order_date"];
		$cust_id = $vars["cust_id"];
      $items = $vars["items"];
	
		try
		{
			$db->beginTransaction();
	      
         //create the new order number
         $sql = "select max(order_num) from orders;";
         $statement = $db->prepare($sql);
         $statment->execute();
         $order_num = $statement-fetch(PDO::FETCH_ASSOC)["max"] + 1;

	
			//create an insert statement
			$sql = "insert into orders (order_num, order_date, cust_id) values (?, ?, ?);";
		
			//prepare the statement
			$statement = $db->prepare($sql);
			
			//run the statement
			$statement->execute([$order_num, $order_date, $cust_id]);
			
         $itemnumber = 1;
         foreach($items as $item)
         {
//            if(product_exists($item["product_id"]))
//            {
               $sql = "insert into orderitems (order_item, prod_id, quantity, item_price) values (?, ?, ?, ?);";

               $statement = $db->prepare($sql);
               $statement->execute([$itemnumber, $item["product_id"], $item["quantity"], $item["item_price"]]);

 
               $itemnumber++;
//            }
         }
//         if($itemnumber != $items.length())
//         {
//            $results = array("error_text" => "One or more items was invalid.");
//         }
         $db->commit();
         $results["order_number"] = $order_num;	
			$results["error_text"] = "none - worked!";
		}
		catch(Exception $e)
		{
			if($e->getCode() == 23505)
			{
				$results = array("error_text" => "Cannot create a duplicate entry!");
			}
			else
			{
				$results = array("error_text" => $e->getMessage());
			}
		}
	}
	else
	{
		$results = array("error_text" => "Missing Information!");
	}
}

//delete product
elseif($request->isDelete())
{
	if(isset($vars["order_number"]))
	{
		//get request variables
		$order_num = $vars["order_number"];
		
		//TODO do a sql delete statement..
		
		try
		{
			$db->beginTransaction();
			//create a delete statement
			$sql = "delete from orders where order_num = ?";
	
			//prepare the statement
			$statement = $db->prepare($sql);
			
			//run the statement
			$statement->execute([$order_num]);
	
			$db->commit();
	
			$results = array("error_text" => "none - worked!");
		}
		catch(Exception $e)
		{
			//TODO Handle exceptions
			$results = array("error_text" => $e->getMessage());
		}
	}
	else
	{
		$results = array("error_text" => "Missing order_number!");
	}
}

//update product
elseif($request->isPut())
{
	//Get the request variables
	$order_num = $vars["order_number"];
	$order_date = $vars["order_date"];
	$cust_id = $vars["cust_id"];
	
	//TODO do a sql update statement...
	try
	{
		$db->beginTransaction();
		//create an update statement
		$sql = "update orders set order_date = ?, cust_id = ? where order_num = ?";

		//prepare the statement
		$statement = $db->prepare($sql);

		//run the statement
		$statement->execute([$order_date, $cust_id, $order_num]);

		$db->commit();

		$results = array("error_text"=>"none - worked!");
	}
	catch (Exception $e)
	{
		$results = array("error_text" => $e->getMessage());
	}
}


echo(json_encode($results));
?>
