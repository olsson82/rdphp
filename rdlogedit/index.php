<?php

// Rivendell Web Interface
//
//   (C) Copyright 2019 Genesee Media Corporation <bmcglynn@geneseemedia>
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License version 2 as
//   published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public
//   License along with this program; if not, write to the Free Software
//   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

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

  //Open database connection
  $PDO = getDatabaseConnection();
  $serviceNames = getServiceNames($PDO);

  //Check for post with service change
  $selectedService = 0;

  if(isset($_POST['serviceName']) && $_POST['serviceName'] != 0)
    $selectedService = $_POST['serviceName'];
  if(isset($_GET['serviceName']))
    $selectedService = $_GET['serviceName'];

  $title = 'Rivendell Logs';
  $css = 'voicetracker.css'; 
  $nojs = 1; //Added to remove bug on rdlogedit

  include('../header.php');//Header HTML

?>
        <div id="services">
          <form id="serviceForm" method="post" action="index.php">
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
              <!-- Leftovers
              -->
           </div>
          </form>
        </div>
        <div id="logs">
          <h2>Logs</h2>
          <p><a href="../index.php">Main Menu</a></p>
          <p><a href="../logout.php">Sign Out</a></p>
          <table>
          <tr>
            <td>
               Log Name
            </td>
            <td>
               Description
            </td>
            <td>
               Exists 
            </td>
            <td>
               Music Merged
            </td>
            <td>
               Traffic Merged
            </td>
            <td>
               Voicetrack
            </td>
          </tr>
          

<?php

  // Get the logs for the selected service from Rivendell
  $logs = getRivendellLogs($PDO, $serviceNames[$selectedService]);


  // Loop through logs and print status
  foreach($logs as $log){

    $viewUrl = './?name=' . $log['name'];
    //Voicetracking URL
    $vtUrl   = 'voicetrack.php?log=' . $log['name'] . "&serviceName=" . $serviceNames[$selectedService] . "&hour=0"; //Added to start with 0 hour

    //TODO - Use Green/red icon for Yes or No
    //       Create URL to edit or view the log
    //       Make it look nice
    //       Create Locking Mechanism (or use it from the RD API)

    $logName = $log['name'];

    if($selectedService != 0)
      $viewUrl .= '&serviceName=' . $selectedService;

?>
          <tr>
            <td>
               <a href="<?php echo $vtUrl; ?>"><?php echo $logName; ?></a>
            </td>
            <td>
               <?php echo $log['description']; ?>
            </td>
            <td>
               <?php echo $log['exists']; ?>
            </td>
            <td>
               <?php echo $log['traffic_merged']; ?>
            </td>
            <td>
               <?php echo $log['music_merged']; ?>
            </td>
            <td>
               <a href="<?php echo $vtUrl; ?>">Voicetrack</a>
            </td>
          </tr>

<?php
  }
?>

    </table>

<?php

  //Close DB
  $PDO = NULL;

  include('../footer.php');//Footer HTML

?>
