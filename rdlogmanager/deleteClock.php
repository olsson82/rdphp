<?php


   // Initialize the session
   session_start();
          
   // Check if the user is logged in, if not then redirect him to login page
   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
       header("location: ../index.php");
       exit;
   }  

  include('../config/database.php');
  include('../includes/clockFunctions.php');

  if(isset($_POST)){

    $PDO = getDatabaseConnection();

    //CHECK EXISTS
    if(!clockExists($PDO, $_POST['name']))
       die('Clock ' . $_POST['name'] . ' does not exist, can\'t delete');

    //die('debug we don\'t want to do this right now');

    //DELETE FROM CLOCK_LINES
    removeClockEvents($PDO, $_POST['name']);

    //DELETE FROM RULE_LINES
    removeClockRules($PDO, $_POST['name']);

    //DELETE FROM CLOCK_PERMS
    removeClockPerms($PDO, $_POST['name']);

    //DELETE FROM CLOCKS 
    deleteClock($PDO, $_POST['name']);
    
    //DELETE FROM GRIDS
    renameClockInGrids($PDO, $_POST['name'], '');

    echo 'Clock ' . $_POST['name'] . ' has been deleted';
       
  }

?>
