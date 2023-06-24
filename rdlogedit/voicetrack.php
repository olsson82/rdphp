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

   // Check if the user is logged in, if not then redirect him to login page
   session_start();
   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
       header("location: ../index.php");
       exit;
   }
   $userName = $_SESSION["username"];
 

  //Include files
  include('../config/database.php');
  include('../includes/dbfunctions.php');
  include('../includes/functions.php');

  //Check for post with service change
  $selectedService = 0;

  if(isset($_POST['serviceName']) && $_POST['serviceName'] != 0)
    $selectedService = $_POST['serviceName'];
  else if(isset($_GET['serviceName']))
    $selectedService = $_GET['serviceName'];

  //Open database connection
  $PDO = getDatabaseConnection();
  $serviceNames = getServiceNames($PDO);
  $groupInfo = getGroupInformation($PDO);
  $vtInfo = getVoicetrackInformation($PDO,$selectedService);

  // Voicetrack cart limits
  // Somestimes voicetracks come in from the Music Log without a Group set.  
  // This will set a range to look for Voicetrack markers where they do not exist
  // Based on the log.  In the future, we can also query the RD Service and look for
  // the Voicetrack Cart Range

  //This is all set in the VOICETRACK Table
  $vtLower = $vtInfo[0]['default_low_cart']; 
  $vtUpper = $vtInfo[0]['default_high_cart'];
  $vtGroup = $vtInfo[0]['group'];


  $title = 'Rivendell Logs';
  $css = 'voicetracker.css'; 
  $nojs = 1; //Added to remove bug on rdlogedit
  include('../header.php');//Header HTML

  
  $logName = $_GET['log'];
  //Set 00 as default hour
  $hour = $_GET['hour'];
  if(!$hour) {
     $hour=0;
  }
  $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);

  // Recreate the Base URL to strip-off hours before adding them
  $baseURL = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] 
     . explode('?', $_SERVER['REQUEST_URI'], 2)[0];
  $baseURL = $baseURL . "?log=$logName&serviceName=$selectedService";

  //Make CSS Colors
  $groupCSS ="";
  $opacity = ".3";
  foreach($groupInfo as $group){
     $gName = $group['name'];
     $gColor = $group['color'];
     $rgba = hex2rgba($gColor, $opacity); //Use RGBA to set background opacity
     $groupCSS = $groupCSS . "tr.$gName {\n";
     $groupCSS = $groupCSS . "   background-color: $rgba;\n";
     $groupCSS = $groupCSS . "}\n";
   }
?>
        <style>
           <?php echo $groupCSS; ?>
        </style>
        <script>
           //Pass some runtime variables into JavaScript
           var logName =  "<?php echo $logName; ?>"
           var userName = "<?php echo $userName; ?>"
        </script>
        <div id="header">
          <h1>Rivendell Voicetracker</h1>
          <h2>Play Log - <?php echo $logName; ?></h2>
          <p><a href="../index.php">Main Menu</a></p>
          <p><a href="index.php">Return</a></p>
          <p><a href="../logout.php">Sign Out</a></p>
        </div>
        <div id="hours">
<?php       
        //Build hour buttons 
        for($i=0; $i < 24 ; $i++) {
           $buttonLink = $baseURL . "&hour=$i";
           $buttonOnclick =  "window.location.href='$buttonLink';";
           $buttonClass = "hourButton";
           if($hour == $i) {
             $buttonClass = "hourButtonLive";
           }
           echo "<button class=\"$buttonClass\" onclick=\"$buttonOnclick\">$i</button>\n";

        }
?>
        </div>
        <div id="loglines">
          <table>
          <tr class="headings">
            <td>
               Start Time
            </td>
            <td>
               Cart Number
            </td>
            <td>
               Group 
            </td>
            <td>
               Artist
            </td>
            <td>
               Title
            </td>
            <td>
               Length
            </td>
          </tr>
          

<?php

   
  // Get the logs for the selected service from Rivendell
  if($hour){
     $logs = getRivendellLog($PDO, $logName, $hour);
  }
  else {
     //Defatult to Midnight
     $logs = getRivendellLog($PDO, $logName,"00");
  }

  //Convert Start-Time into HH::MM::SS

  // Loop through log and print status
  // Need to process TYPEs =  0-audio, 1-meta, 2-macro
  foreach($logs as $log){

     $type = $log['type'];
     $cart = $log['cart'];
     $group = $log['group'];
     $artist = $log['artist'];
     $title = $log['title'];
     $comment = $log['comment'];
     $count = $log['count'];
     $lineid = $log['line_id'];
     $length =msToHHMMSS($log['length']);
     $startTime = msToHHMMSS_fromMID($log['start_time']);
     // Some voicetracks do not have a group set - or are Meta Markers (future use)
     // Look for them and forcibly set the group
     if( ($vtLower <= $cart) && ($cart <= $vtUpper)) {
        $group = $vtGroup;
     }

     //Create a widget for Voicetracking a new cart
     if($type == 6) {
         //echo "<td></td><td></td>\n";
         //echo "<td>". $log['comment'] . "</td>";
         echo "    <tr class=\"$vtGroup\">\n"; 
         echo "<!-- Count: $count LineID: $lineid -->\n";
         echo "<td>$startTime</td>\n";
         echo "<td>New</td>\n";
         echo "<td><div id=\"vt-$lineid\"><button onclick=\"addRecordingControls($lineid,'$vtGroup',0)\">Insert Voicetrack</button></div></td>\n"; //Stop/Start/Save Controls
         echo "<td><div id=\"vc-$lineid\"></div></td>\n"; //Controls
         echo "<td><div id=\"pl-$lineid\"></div></td>\n"; //Player
         echo "<td><div id=\"st-$lineid\"></div></td>\n"; //Status Indicator
         echo "    </tr>\n";
     }
     else if($type == 2) {
        //Skip Macro Events
     } 
     //Create a widget for Voicetracking an existing cart
     //Use the cart number for the ID so it can be unique and identified
     else if ($group == $vtGroup) {
         echo "    <tr class=\"$vtGroup\">\n"; 
         echo "<td>$startTime</td>\n";
         echo "<td>$cart</td>\n";
         echo "<td><div id=\"vt-$lineid\"><button onclick=\"addRecordingControls($lineid,'$group',$cart)\">Insert Voicetrack</button></div></td>\n"; //Stop/Start/Save Controls
         echo "<td><div id=\"vc-$lineid\">$artist</div></td>\n"; //Controls
         echo "<td><div id=\"pl-$lineid\">$title</div></td>\n"; //Player
         echo "<td><div id=\"st-$lineid\"></div>$length</td>\n"; //Status Indicator
         echo "    </tr>\n";
     }
     else if($cart != 0) {
?>
          <tr class="<?php echo $group; ?>">
            <td>
               <?php echo $startTime; ?>
            </td>
            <td>
               <?php echo $cart; ?>
            </td>
            <td>
               <?php echo $group; ?>
            </td>
            <td>
               <?php echo $artist; ?>
            </td>
            <td>
               <?php echo $title; ?>
            </td>
            <td>
               <?php echo $length; ?>
            </td>
          </tr>
<?php
     } // End else-case
     else {}

   } //End foreach
?>
    </table>

    <!-- inserting these scripts at the end to be able to use all the elements in the DOM -->
    <script src="../js/WebAudioRecorder.min.js"></script>
    <script src="../js/RDRecorder.js"></script>

<?php

  //Close DB
  $PDO = NULL;

  include('../footer.php');//Footer HTML

?>
