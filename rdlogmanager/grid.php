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
  $nojs = 0; //Added for fix bug
  $title = 'Rivendell Grid';
  $js = ['grid.js'];
  $css = 'grid.css';

  include('../header.php');//Header HTML

?>
        <div id="services">
          <form id="serviceForm" method="post" action="grid.php">
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
              <button class="red" type="button" onClick="emptyGrid()">Clear</button>
              <button class="green" type="button" onClick="saveGrid('<?php echo $serviceNames[$selectedService]; ?>')">Save</button>
           </div>
          </form>
        </div>
        <div id="clocks">
          <h2>Clocks</h2>
          <div class="deleteClock" id="deleteClock" draggable="true">Delete</div>
          <div id="deleteClock_name" class="deleteClockName">Delete Clock from Grid</div>

<?php

  $grid = getGrid($PDO, $serviceNames[$selectedService]);
  $clocks = getRivendellClocks($PDO, $serviceNames[$selectedService]);

  foreach($clocks as $clock){

?>
          <div style="background: <?php echo $clock['COLOR']; ?>"
               id="<?php echo $clock['SHORT_NAME']; ?>" class="rivclock"
               draggable="true"><?php echo $clock['SHORT_NAME']; ?></div>
          <div id="<?php echo $clock['SHORT_NAME']; ?>_name" class="clockName"><?php echo $clock['NAME']; ?></div>
<?php
  }
?>
          <div class="spacer"></div>
        </div>
        <div id="grid">
          <h2 class="left"><?php echo $serviceNames[$selectedService]; ?> Grid</h2>
<?php

    $clockNo = -1;
    $days=array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');

    foreach($days as $day){

?>
          <div id="<?php echo $day; ?>">
              <h2><?php echo $day; ?></h2>
<?php

        for($hour = 0; $hour < 24; $hour++){

          $clockNo++;

          $display = 'none';
          $color = 'white';
          $data = '';

          //Hours are 0-167 for the week

          $display = 'block';
          $color = $grid[$clockNo]['COLOR'];
          $data  = $grid[$clockNo]['SHORT_NAME'];

          //TODO - Handle Nulls

?>
              <div style="background: <?php echo $color; ?>;" class="clock" id="clock<?php echo $clockNo; ?>">
                <div>
<?php echo sprintf('%02d', $hour) . '-' . sprintf('%02d', $hour + 1); ?>
                </div>
                <div style="display: <?php echo $display; ?>;" class="data" id="clock<?php echo $clockNo; ?>Data"><?php echo $data; ?></div>
              </div>
<?php
        }//End Hour For Loop
?>
            <div class="spacer"></div>
          </div>
<?php
    }//End days loop
?>
      </div>
<?php

  //Close DB
  $PDO = NULL;

  include($_SERVER['DOCUMENT_ROOT']. '/footer.php');//Footer HTML

?>
