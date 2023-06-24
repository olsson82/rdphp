<?php

   //Functional on v3.0

   // Initialize the session
   session_start();
          
   // Check if the user is logged in, if not then redirect him to login page
   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
       header("location: ../index.php");
       exit;
   }  
  
  include('../config/database.php');
  include('../includes/functions.php');

  if(isset($_POST['service'], $_POST['grid'])){

    $grid = $_POST['grid'];
    $service = $_POST['service'];

    $sql = "UPDATE SERVICE_CLOCKS \n";
    $sql = $sql . " SET CLOCK_NAME = (case ";
    for($i = 0; $i < 168; $i++) {
       $clockName = $grid[$i];
       $sql = $sql . " when HOUR = '$i' then '$clockName' \n";
       //print "H: $i C: $clockName\n";
    }
    $sql = $sql . " end) WHERE SERVICE_NAME = '$service'";

    //Connect to DB and prepare statement
    $PDO = getDatabaseConnection();
    $stmt = $PDO->prepare($sql);


    //debug_to_console("SQL: $sql");

    if($stmt->execute()) {
      echo 'Successfully saved ' . $_POST['service'] . ' grid.';
    }
    else {
      die('Error executing statement (' . $stmt->errorCode() . ') '
          . $stmt->errorInfo());
    }

  }

?>
