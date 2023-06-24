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

  if(isset($_POST['name'], $_POST['shortName'], $_POST['originalName'],
        $_POST['originalShortName'], $_POST['mode'], $_POST['events'], $_POST['colour'],
        $_POST['service'])){

    $PDO = getDatabaseConnection();

    //CASE 1 - NEW CLOCK (v3.0 safe)
    if($_POST['originalName'] == ''){

      //CHECK NAME EXISTS (IT SHOULDN'T)
      if(clockExists($PDO, $_POST['name']))
        die('Clock ' . $_POST['name'] . ' already exists, change it and try again');

      //AND CODE
      //Make sure we have a code, if not try first 3 chars of name
      if(strlen($_POST['shortName']) < 1)
        $_POST['shortName'] = substr($_POST['name'], 0, 3);
     
      if(clockCodeExists($PDO, $_POST['shortName']))
        die('Clock Code ' . $_POST['shortName']
            . ' already exists, change it and try again');

      //ADD CLOCK TO CLOCKS TABLE: TODO Artist Sep + Remarks
      addClock($PDO, $_POST['name'], $_POST['shortName'], $_POST['colour'], 5, '');

      //SAVE EVENTS TO NEW TABLE
      saveEvents($PDO, $_POST['name'], $_POST['events']);

      //ADD CLOCK TO CLOCKS PERMS under currently selected Service
      addClockPerms($PDO, $_POST['name'], $_POST['service']);

      echo 'Clock ' . $_POST['name'] . ' has been saved';

    }else if($_POST['mode'] == 'save'){
      
      //CASE 2 - Update existing (ported to v3 )
      if($_POST['originalName'] != $_POST['name']){

        //CHECK NAME EXISTS (IT SHOULDN'T)
        if(clockExists($PDO, $_POST['name']))
          die('Clock ' . $_POST['name'] . ' already exists, change it and try again');

        //CHECK NEW CODE EXISTS (IT SHOULDN'T)
        if($_POST['shortName'] != $_POST['originalShortName']){

          //Code is changing too, lets check for new code
          if(clockCodeExists($PDO, $_POST['shortName']))
            die('Clock Code ' . $_POST['shortName']
                . ' already exists, change it and try again');

        }

        //CHECK ORIGINAL EXISTS (IT SHOULD)
        if(!clockExists($PDO, $_POST['originalName']))
          die('Original clock ' . $_POST['originalName']
               . ' can\'t be found, cannot rename');

        //SAVE EVENTS TO ORIGINAL CLOCK TABLE
        saveEvents($PDO, $_POST['originalName'], $_POST['events']);

        //UPDATE CODE IF CHANGED
        if($_POST['originalShortName'] != $_POST['shortName'])
          updateClockCode($PDO, $_POST['originalName'], $_POST['shortName']);

        //UPDATE COLOUR IF CHANGED
        if($_POST['originalColour'] != $_POST['clockColour'])
          updateClockColour($PDO, $_POST['originalName'], $_POST['clockColour']);

        //RENAME IN CLOCK RULES
        renameClockRules($PDO, $_POST['originalName'], $_POST['name']);

        //RENAME IN CLOCKS
        renameClock($PDO, $_POST['originalName'], $_POST['name']);

        //RENAME IN CLOCK_PERMS
        renameClockPerms($PDO, $_POST['originalName'], $_POST['name']);

        //RENAME IN CLOCK_LINES
        renameClockLines($PDO, $_POST['originalName'], $_POST['name']);

        //RENAME IN GRIDS
        renameClockInGrids($PDO, $_POST['originalName'], $_POST['name']);

        echo 'Clock ' . $_POST['originalName'] . ' has been renamed to ' . $_POST['name'];

      }else{
        //Errors for duplicates, plus code update
        echo 'SAVE';
        //CHECK NAME EXISTS (IT SHOULD)
        if(!clockExists($PDO, $_POST['originalName']))
          die('Can\'t save as existing clock is missing from database: '
              . $_POST['originalName']);

        //NAME CHANGES ARE HANDLED ABOVE
        //CHECK SHORT NAME is SAME
        if($_POST['originalShortName'] != $_POST['shortName']){

          //Need to update short name
          //MAKE SURE ORIGINAL CODE EXISTS
          if(!clockCodeExists($PDO,$_POST['originalShortName']))
            die('Existing clock code is missing from database: '
                . $_POST['originalShortName']);

          //MAKE SURE NEW CODE DOESN'T EXIST
          if(clockCodeExists($PDO,$_POST['shortName']))
            die('Can\'t save new clock code as it already exists');

          //Checks complete, update code
          //AMEND SHORT NAME
          updateClockCode($PDO, $_POST['originalName'], $_POST['shortName']);

        }

        //SAVE EVENTS TO CLOCK TABLE
        saveEvents($PDO, $_POST['originalName'], $_POST['events']);

      }

    }else if($_POST['mode'] == 'saveas'){

      if($_POST['originalName'] != $_POST['name']){


        //CHECK NAME EXISTS (IT SHOULDN'T)
        if(!clockExists($PDO,$_POST['originalName']))
          die('Can\'t save this clock, the original clock is missing from the database');

        if(clockExists($PDO,$_POST['name']))
          die('Can\'t save as this clock, it already exists: ' . $_POST['clockName']);

        if(clockCodeExists($PDO,$_POST['shortName']))
          die('Can\'t save as this code, it already exists: ' . $_POST['shortName']);

        //ADD CLOCK TO CLOCKS TABLE: TODO Artist Sep + Remarks
        addClock($PDO, $_POST['name'], $_POST['shortName'], $_POST['colour'], 5, '');

        //COPY ORIGINAL PERMISSIONS
        copyClockPerms($PDO, $_POST['originalName'], $_POST['name']);

        //COPY ORIGINAL RULES
        copyClockRules($PDO, $_POST['originalName'], $_POST['name']);

        //SAVE EVENTS TO NEW TABLE
        //TODO - Seems to not be working
        saveEvents($PDO, $_POST['name'], $_POST['events']);

      }else{
        echo 'Can\'t save as same clock';
      }

    }

    $PDO = NULL;

  }else
    echo 'Incorrect usage';

?>
