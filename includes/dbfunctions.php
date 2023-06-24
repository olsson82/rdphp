<?php


  /*
  getVoiceTrackGroup(SERVICE) - Returns Voicetrack Group for a service 
  SELECT `TRACK_GROUP` FROM `SERVICES` WHERE NAME = 'SERVICE';

  */

  //Updates a Voicetrak marker in the log to an actual cart number that has been
  //Imported and sets the Voicetrack counter
  function rd_updateVTCart($PDO, $log, $line, $cart, $username) {
    //Variables to update in LOG_LINES:
    // CART_NUMBER 
    // TYPE (to 0)
    // ORIGIN_USER - to username
    // ORIGIN_DATETIME - to current time (Format: 2019-07-06 10:47:39) -- Not always set
    // SOURCE - to 4  (not sure what this is or if it is necessary), sets prior and afer from 3 to 2

    $sql = "UPDATE LOG_LINES SET CART_NUMBER = '$cart', TYPE='0', ORIGIN_USER = '$username', SOURCE = '4' WHERE (LOG_LINES.LOG_NAME = '$log' AND LOG_LINES.LINE_ID = '$line')"; 

    $stmt = $PDO->prepare($sql);
    $stmt->execute();

    //Step 1 - Get completed tracks
    $sql = "SELECT COMPLETED_TRACKS FROM `LOGS` WHERE NAME='log'";
    $stmt=$PDO->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();
    $row = $stmt->fetch();
    $completed = $row['COMPLETED_TRACKS'];
    $completed++;

    //Step 2 - Update Completed tracks
    $sql = "UPDATE LOGS SET COMPLETED_TRACKS='$completed' WHERE NAME='$log'";
    $stmt = $PDO->prepare($sql);
    $stmt->execute();

  }

  function getVoicetrackInformation($PDO,$service){
  
  
    $groupSet = array();
    //Get the group
    $sql = "SELECT TRACK_GROUP FROM SERVICES WHERE NAME = '$service'";
  
    $stmt = $PDO->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();
    $row = $stmt->fetch();
    $trkGrp = $row['TRACK_GROUP'];

    //Get upper and lower limit
    $sql = "SELECT DEFAULT_LOW_CART, DEFAULT_HIGH_CART FROM GROUPS WHERE NAME = '$trkGrp'";

    $stmt = $PDO->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();

    while($row = $stmt->fetch()) {
      $groupSet[] =array(
          'group' => $trkGrp,
          'default_low_cart' => $row['DEFAULT_LOW_CART'],
          'default_high_cart' => $row['DEFAULT_HIGH_CART'],
      );
    }

    $stmt = NULL;

    return $groupSet;

  }
  //Gets the database version
  function getDBVersion($PDO) {

      $sql = "SELECT DB FROM VERSION WHERE 1";
 
      $stmt=$PDO->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $row = $stmt->fetch();
      $version = $row['DB'];
      
      // Close connection
      $PDO = NULL;
  
      return $version;

  }

  //Gets the full name of a user
  function getFullUsername($PDO, $username) {
     

      $sql = "SELECT FULL_NAME FROM USERS WHERE LOGIN_NAME='$username'";
 
      $stmt=$PDO->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $row = $stmt->fetch();
      $fullname = $row['FULL_NAME'];
      
      // Close connection
      $PDO = NULL;
  
      if($fullname == "") { 
         $fullname=$username;
      }

      return $fullname;

  }
  //Gets a user password for security verification
  function getUserPassword($PDO, $username) {
     

      $sql = "SELECT PASSWORD FROM USERS WHERE LOGIN_NAME='$username'";
 
      $stmt=$PDO->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $row = $stmt->fetch();
      $password = $row['PASSWORD'];
      
      // Close connection
      $PDO = NULL;

      return $password;

  }

  //Gets a key/value list of groups and their colors
  function getGroupInformation($PDO){
  
    $groupSet = array();
 
    $sql = "SELECT NAME, DEFAULT_LOW_CART, DEFAULT_HIGH_CART, COLOR
            FROM GROUPS";
  
    $stmt = $PDO->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();

    while($row = $stmt->fetch()) {
      $groupSet[] =array(
          'name' => $row['NAME'],
          'default_low_cart' => $row['DEFAULT_LOW_CART'],
          'default_high_cart' => $row['DEFAULT_HIGH_CART'],
          'color' => $row['COLOR'],
      );
    }

    $stmt = NULL;

    return $groupSet;

  }

  /**
   * Gets the Logs for the Given Rivendell service
   * @param $PDO: PDO Connection to use
   * @param $service: Service to lookup
   * @return array of clocks (name, short_name, color)
   */
  function getRivendellLog($PDO, $logname,$hour){

    $logSet = array();
    $sql = "";

    //If there is an hour passed-in, put a restriction on it
    $lowerMS = 0;
    $upperMS = 86400000;

    if($hour) {
       //Convert hour to Millisecond ranges
       $lowerMS = $hour * 3600 * 1000;
       $upperMS = $lowerMS + ((3600 * 1000) - 1);
    }
   

    //For version 2.x of Rivendell
    $dbVer = getDBVersion($PDO);
    if($dbVer < 300) {
      $logTableName = $logname . '_LOG';
      $sql = "SELECT COUNT, CART.ARTIST, CART.TITLE, CART.GROUP_NAME, CART.AVERAGE_LENGTH, 
            ID, SOURCE, log.TYPE, START_TIME, 
            CART_NUMBER, COMMENT, EVENT_LENGTH, LINK_EVENT_NAME, 
            LINK_START_TIME, LINK_LENGTH, EXT_START_TIME, EXT_CART_NAME
            FROM $logTableName log
            LEFT JOIN CART ON log.CART_NUMBER=CART.NUMBER
            WHERE START_TIME BETWEEN $lowerMS AND $upperMS ORDER BY START_TIME ASC";

    }
    else {
    //For version 3.x of Rivendell, logs are in a single table called LOGLINE
      $sql = "SELECT COUNT, CART.ARTIST, CART.TITLE, CART.GROUP_NAME, CART.AVERAGE_LENGTH, 
            ID, SOURCE, log.TYPE, START_TIME, LINE_ID,
            CART_NUMBER, COMMENT, EVENT_LENGTH, LINK_EVENT_NAME, 
            LINK_START_TIME, LINK_LENGTH, EXT_START_TIME, EXT_CART_NAME
            FROM LOG_LINES log
            LEFT JOIN CART ON log.CART_NUMBER=CART.NUMBER
            WHERE log.LOG_NAME='$logname' AND
            START_TIME BETWEEN $lowerMS AND $upperMS ORDER BY START_TIME ASC";
    }

    $stmt = $PDO->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();

    if($dbVer < 300) {
       while($row = $stmt->fetch()) {
          $logSet[] =array(
             'count' => $row['COUNT'],
             'line_id' => $row['ID'], //For 2.x
             'cart' => $row['CART_NUMBER'],
             'artist' => $row['ARTIST'],
             'title' => $row['TITLE'],
             'group' => $row['GROUP_NAME'],
             'length' => $row['AVERAGE_LENGTH'],
             'type' => $row['TYPE'],
             'comment' => $row['COMMENT'],
             'start_time' => $row['START_TIME'],
         );
       }
    }
    else {
       while($row = $stmt->fetch()) {
          $logSet[] =array(
             'count' => $row['COUNT'],
             'line_id' => $row['LINE_ID'],//For 3.x
             'cart' => $row['CART_NUMBER'],
             'artist' => $row['ARTIST'],
             'title' => $row['TITLE'],
             'group' => $row['GROUP_NAME'],
             'length' => $row['AVERAGE_LENGTH'],
             'type' => $row['TYPE'],
             'comment' => $row['COMMENT'],
             'start_time' => $row['START_TIME'],
         );
       }
    }

    $stmt = NULL;

    return $logSet;

  }
  /**
   * Gets the Logs for the Given Rivendell service
   * @param $PDO: PDO Connection to use
   * @param $service: Service to lookup
   * @return array of clocks (name, short_name, color)
   */
  function getRivendellLogs($PDO, $service){

    $logSet = array();

    //Get the clocks from the perms table
    $sql = 'SELECT `NAME`, `LOG_EXISTS`, `DESCRIPTION`, `MUSIC_LINKED`, `TRAFFIC_LINKED` FROM `LOGS`
            WHERE `SERVICE` = :service
            ORDER BY `NAME` ASC';

    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':service', $service);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();

    while($row = $stmt->fetch()) {

      $logSet[] =array(
          'name' => $row['NAME'],
          'description' => $row['DESCRIPTION'],
          'exists' => $row['LOG_EXISTS'],
          'music_merged' => $row['MUSIC_LINKED'],
          'traffic_merged' => $row['TRAFFIC_LINKED'],
      );
    }

    $stmt = NULL;

    return $logSet;

  }

  function getServiceNames($PDO){

    $services = array();

    $sql = 'SELECT `NAME` FROM `SERVICES` ORDER BY `NAME` ASC';

    $results = $PDO->query($sql);
    $results->setFetchMode(PDO::FETCH_ASSOC);

    while($row = $results->fetch()){

      foreach($row as $field)
        $services[] = $field;

    }

    $results = NULL;

    return $services;

  }

  /**
   * Return a quoted table name but with ` instead of default ' from PDO->quote()
   * @param $PDO PDO connection to use
   * @param $name Name of table to escape
   * @return `$name_properly_escaped?`;
   */
  function escapeTableName($PDO, $name){

    /* PDO kind of sucks with table names you can't use statements and the quote method
     * returns as 'TABLE_NAME' and you can't use apostrophes in CREATE 'TABLE_NAME'
     * So we have to remove the first and last ' and replace with `.
     */
    $name = $PDO->quote($name);

    $name = substr($name, 1);
    $name = substr($name, 0, -1);

    return '`' . $name . '`';

  }

?>
