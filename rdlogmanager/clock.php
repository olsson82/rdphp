<?php

   // Initialize the session
   session_start();
          
   // Check if the user is logged in, if not then redirect him to login page
   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
       header("location: ../index.php");
       exit;
   }  

  //Include files
  include('../config/database.php');
  include('../includes/dbfunctions.php');
  include('../includes/clockFunctions.php');
  include('../includes/functions.php');

  //Open database connection
  $PDO = getDatabaseConnection();
  $serviceNames = getServiceNames($PDO);

  //Check for post with service change
  $selectedService = 0;

  if(isset($_POST['serviceName']) && $_POST['serviceName'] != 0)
    $selectedService = $_POST['serviceName'];
  else if(isset($_GET['serviceName']))
    $selectedService = $_GET['serviceName'];
  $nojs = 0; //Added for fix bug
  $title = 'Rivendell Clocks';
  $js = ['clock.js', 'jscolor.min.js'];
  $css = 'clock.css';

  include('../header.php');//Header HTML

?>
        <div id="working">
          <div>Working&hellip;</div>
        </div>
        <div id="services">
          <form id="serviceForm" method="post" action="clock.php">
            <label for="serviceList">Services:
              <select name="serviceName">
<?php

  $i = -1;

  foreach($serviceNames as $name){

    $i++;
    $selected='';

    if($selectedService == $i)
      $selected = 'selected ';

?>
                <option <?php echo $selected; ?>value="<?php echo $i; ?>"><?php echo $name; ?></option>
<?php
  } //End foreach services
?>
              </select>
            </label>
            <input type="submit" value="Change Service">
            <div id="gridform">
              <button class="return" type="button" onClick="returnToMain()">Return</button>
              <button class="delete" type="button" onClick="deleteClock()">Delete</button>
              <button class="empty" type="button" onClick="emptyClock()">Clear</button>
              <button class="save" type="button" onClick="saveClock()">Save</button>
              <button class="saveas" type="button" onClick="saveAsClock()">Save As</button>
           </div>
          </form>
        </div>
        <div id="clocks">
          <h2>Clocks</h2>

<?php

  $clocks = getRivendellClocks($PDO, $serviceNames[$selectedService]);

  //Add a "new/add" clock button
  $addClock = array();
  $addClock['NAME'] = 'Add New Clock';
  $addClock['COLOR'] = '#8cec8c';
  $addClock['SHORT_NAME'] = 'ADD';

  $clocks = array_reverse($clocks, true);
  $clocks['Add New Clock'] = $addClock;
  $clocks = array_reverse($clocks, true);

  //Check GET for clock references
  $selectedClock = '';

  if(isset($_GET['name']))
    $selectedClock = $clocks[$_GET['name']]['NAME'];

  foreach($clocks as $clock){

    $url = 'clock.php?name=' . $clock['NAME'];

    if($selectedService != 0)
      $url .= '&serviceName=' . $selectedService;

?>
          <a href="<?php echo $url; ?>">
          <div style="background: <?php echo $clock['COLOR']; ?>"
               id="<?php echo $clock['SHORT_NAME']; ?>" class="rivclock"
               ><?php echo $clock['SHORT_NAME']; ?></div>
          <div id="<?php echo $clock['SHORT_NAME']; ?>_name" class="clockName"><?php echo $clock['NAME']; ?></div>
          </a>
<?php
  }

?>
          <div class="spacer"></div>
        </div>
        <div id="events">
          <h2>Events</h2>

<?php

  $events = getRivendellEvents($PDO, $serviceNames[$selectedService]);

  $deleteEvent = array();
  $deleteEvent['NAME'] = 'Delete Event';
  $deleteEvent['COLOR'] = 'lightgrey';
  $deleteEvent['PROPERTIES'] = '';

  array_unshift($events, $deleteEvent);

  foreach($events as $event){
?>
          <div draggable="true" id="<?php echo $event['NAME']; ?>" class="event" style="background: <?php echo $event['COLOR']; ?>">
            <div class="eventName"><?php echo $event['NAME']; ?></div>
            <!-- <div class="eventProperties"><?php //echo $event['PROPERTIES']; ?></div> -->
            <!-- TODO - make this variable or remove from the specification -->
            <div class="eventTime">4:00</div>
          </div>
<?php
  } //End events for each
?>

        </div>
        <div id="editor">
          <h2 class="left">
<?php
    $newClock = false;

    if(strlen($selectedClock) < 1)
      echo '&larr; Select Clock';
    else if($selectedClock == 'Add New Clock'){
      echo 'Adding New Clock';
      $newClock = true;
    }else
      echo 'Editing ' . $selectedClock;

    $colour = '#CCCCCC';

    if(isset($clocks[$selectedClock]['COLOR']))
      $colour = $clocks[$selectedClock]['COLOR'];
?>
          </h2>
          <input id="originalName" name="originalName" type="hidden" value="<?php if(!$newClock && isset($clocks[$selectedClock]))echo $clocks[$selectedClock]['NAME']; ?>">
          <input id="originalShortName" name="originalShortName" type="hidden" value="<?php if(!$newClock && isset($clocks[$selectedClock]))echo $clocks[$selectedClock]['SHORT_NAME']; ?>">
          <input id="originalColour" name="originalColour" type="hidden" value="<?php if(!$newClock && isset($clocks[$selectedClock]))echo $clocks[$selectedClock]['COLOR']; ?>">
          <input id="service" name="service" type="hidden" value="<?php echo $serviceNames[$selectedService]; ?>">
          <input id="serviceNo" name="serviceNo" type="hidden" value="<?php echo $selectedService; ?>">
          <label for="clockName">Clock Name:</label>
          <input id="clockName" name="clockName" type="text" maxlength="58" value="<?php if(!$newClock)echo $selectedClock; ?>">
          <label for="clockShortName">Clock Code:</label>
          <input id="clockShortName" name="clockShortName" type="text" maxlength="3" value="<?php if(!$newClock && isset($clocks[$selectedClock]))echo $clocks[$selectedClock]['SHORT_NAME']; ?>">
          <label for="clockColour">Colour:</label>
          <input id="clockColour" class="jscolor" name="clockColour" type="text" maxlength="7" value="<?php echo $colour; ?>">
          <label for="clockTimeLeft">Time Left:</label>
          <input id="clockTimeLeft" name="clockTimeLeft" type="text" maxlength="5" value="60:00">
          <div class="clear"></div>
<?php if(strlen($selectedClock) > 1){ ?>
          <div class="bookends" id="start"><p>Add Events to Start</p></div>
<?php

    //Loop through this clocks events
    if($selectedClock != 'Add New Clock'){
  
      $clockEvents = getClock($PDO, $clocks, $selectedClock);

      $i = 1;

      foreach($clockEvents as $event){

        $divId = '!JS!_-' . $i . '_' . $event['EVENT_NAME'];
        $color = $events[$event['EVENT_NAME']]['COLOR'];
        //$properties = $events[$event['EVENT_NAME']]['PROPERTIES'];
        $time = getDuration($event['LENGTH']);
?>
          <div id="<?php echo $divId; ?>" class="event" draggable="true" style="background: <?php echo $color; ?>">
            <div class="eventName"><?php echo $event['EVENT_NAME']; ?></div>
           <!-- <div class="eventProperties"><?php //echo $properties; ?></div> -->
            <div class="eventTime"><?php echo $time; ?></div>
          </div>
          <div id="post" class="post" parent="<?php echo $divId; ?>"></div>
<?php

        $i++;

      }//End For Each

    }//End add new clock

  }//End Selected Clock
 ?>
        </div>
<?php


  //Close DB
  $PDO = NULL;

  include('../footer.php');//Footer HTML

?>
