<?php

  /**
   * Gets the full grid for the specified service
   * @param $PDO: PDO Connection to use
   * @param $service: Service Name to get
   * @return array of service including all 168 clocks
   */
  //v3.0 Safe
  function getGrid($PDO, $service){

    //$grid = NULL;
    $grid = array();

    //TODO - Could drift.  Assumes an array from 0-167 for each hour.  Missing hours could be
    //       a major issue.  Need to adjust the grid.php code to handle that as well
    $sql = "SELECT * FROM SERVICE_CLOCKS grid LEFT JOIN CLOCKS clk ON grid.CLOCK_NAME=clk.NAME WHERE grid.SERVICE_NAME LIKE :services ORDER BY grid.HOUR";

    $stmt = $PDO->prepare($sql);
    //$stmt->bindParam(':serviceName', $service);
    $stmt->execute([':services' => $service]);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);

    while($row = $stmt->fetch()){
  
      $hour = $row['HOUR'];
      $grid[$hour] = $row;

    }

    $stmt = NULL;

    return $grid;

  }

  /**
   * Gets the clocks for the Given Rivendell service
   * @param $PDO: PDO Connection to use
   * @param $service: Service to lookup
   * @return array of clocks (name, short_name, color)
   */
   //V3.0 safe
  function getRivendellClocks($PDO, $service){
    
    $clocks = array();

    //Get the clocks from the perms table
    $sql = 'SELECT `CLOCK_NAME` FROM `CLOCK_PERMS`
            WHERE `SERVICE_NAME` = :service
            ORDER BY `CLOCK_NAME` ASC';

    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':service', $service);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute();
    
    while($row = $stmt->fetch())
      $clocks[] = $row['CLOCK_NAME'];

    $stmt = NULL;
   
    /* Format into a csv and requery CLOCK table for clocks we have
     * permission to see.
     */
    $clockNames = join(',', array_fill(0, count($clocks), '?'));
    //TODO - Port over to 3.x version
    $sql = 'SELECT `NAME`, `SHORT_NAME`, `COLOR` FROM `CLOCKS`
            WHERE `NAME` IN (' . $clockNames . ')'; //2.x Query

    $stmt = $PDO->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute($clocks);

    $clocks = array();
    
    while($row = $stmt->fetch())
      $clocks[$row['NAME']] = $row;

    $stmt = NULL;

    return $clocks;

  }

  /**
   * Gets the Events for the associated service
   * @param $PDO PDO connection to use
   * @param $serviceName Service to find events for
   * @return array of events matching Riv Events table
   */
   //V3.0 safe
  function getRivendellEvents($PDO, $serviceName){
    
    $events = array();

    $sql = 'SELECT * FROM `EVENTS` ORDER BY `NAME` ASC';
    
    $results = $PDO->query($sql);
    $results->setFetchMode(PDO::FETCH_ASSOC);
    
    while($row = $results->fetch())
      $events[$row['NAME']] = $row;
      
    $results = NULL;
    
    return $events;
    
  }

  /**
   * Compares the clocks array to the name given
   * We can't bindParam table names so we have to check this name against
   * known clocks that couldn't be user manipulated in the same way
   * @param $clocks array of clocks from clock pallete
   * @param $name name to check for existance in $clocks[?]['NAME'];
   * @return true if it exists, false if not
   */
   //V3.0 safe
  function isValidClock($clocks, $name){

    $valid = FALSE;

    if(isset($clocks[$name]))
      $valid = TRUE;

    return $valid;

  }

  /**
   * Gets the events for a clock
   * @param $PDO PDO Connection to use
   * @param $clockName name of the clock you want (relates to the table name)
   * return array of events for clock [ID, EVENT_NAME, START_TIME, LENGTH]
   */
   //V3.0 safe
  function getClock($PDO, $clocks, $clockName){
    
    $events = array();
    
    /* We can't bind a table name so we have to check against known clocks
     * and escape appropriately */
    if(isValidClock($clocks, $clockName)){

      $sql = "SELECT * FROM CLOCK_LINES WHERE CLOCK_NAME LIKE '" . $clockName . "' ORDER BY `START_TIME` ASC";

      $results = $PDO->query($sql);
      $results->setFetchMode(PDO::FETCH_ASSOC);

      while($row = $results->fetch())
        $events[] = $row;

      $results = NULL;

    }//End valid clock check

    return $events;

  }


  /** 
   * Checks if a given clock exists
   * @param $PDO PDO Connection to use
   * @param $name Name of clock to check
   * @return true if exists
   */ 
   //V3.0 safe
  function clockExists($PDO, $name){

    $exists = false;
      
    $sql = 'SELECT `NAME` AS `CLOCK_COUNT` FROM `CLOCKS` WHERE `NAME` = ?';

    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(1, $name);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
      
    if($stmt->rowCount() > 0)
      $exists = true;

    $stmt = NULL;

    return $exists;

  }

  /**
   * Updates the SERVICE_CLOCKS table to rename a given clock in the grid
   * @param $PDO PDO Connection
   * @param $oldName Original name of clock
   * @param $newName Name to change it to
   * NB: Renaming to '' is how Rivendell deletes from the Grid
   */
  function renameClockInGrids($PDO, $oldName, $newName){
   

    $sql = 'UPDATE `SERVICE_CLOCKS` SET `CLOCK_NAME` = :newName
            WHERE `CLOCK_NAME` = :oldName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newName', $newName);
    $stmt->bindParam(':oldName', $oldName);

    if($stmt->execute() === FALSE)
 
      die('Error renaming clock in SERVICE_CLOCKS table: ' . $oldName . ' to ' . $newName);

    $stmt = NULL;

  }  

  /**
   * Adds a clock to the CLOCKS table
   * @param $name Name of Clock
   * @param $shortName Code/Short name for this clock
   * @param $colour HTML hex colour for this clock
   * @param $artistSeparation Number of artists to separate by
   * @param $remarks Comments/Remarks for this clock
   */
   //V3.0 safe
  function addClock($PDO, $name, $shortName, $colour, $artistSeparation, $remarks){

    if(substr($colour, 0, 1) != '#')
      $colour = '#' . $colour;

    $sql = 'INSERT INTO `CLOCKS` (`NAME`, `SHORT_NAME`, `ARTISTSEP`, `COLOR`, `REMARKS`)
            VALUES (:name, :shortName, :artistSeparation, :colour, :remarks)';

    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':shortName', $shortName);
    $stmt->bindParam(':artistSeparation', $artistSeparation);
    $stmt->bindParam(':colour', $colour);
    $stmt->bindParam(':remarks', $remarks);

    if($stmt->execute() === FALSE || $stmt->rowCount() != 1) {
        error_log("Error adding a clock");
        error_log("SQL: $sql  Rows: $stmt->rowCount()");
      die('Error inserting ' . $name . ' into CLOCKS table');
    }

    $stmt = NULL;

  }

  /**
   * Checks if a given clock exists
   * @param $PDO PDO Connection to use
   * @param $name Name of clock code to check
   * @return true if exists
   */
   //V3.0 safe
  function clockCodeExists($PDO, $name){

    $exists = false;

    $sql = 'SELECT `NAME` AS `CLOCK_COUNT` FROM `CLOCKS` WHERE `SHORT_NAME` = ?';

    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(1, $name);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);

    if($stmt->rowCount() > 0)
      $exists = true;

    $stmt = NULL;

    return $exists;

  }

  /**
   * Creates this clocks _CLK and _RULES table
   * Correct as of Riv 2.10.3, not compatible with github code switch to CLOCKS_METADATA
   * @param $PDO PDO Connection to use
   * @param $name name of clock to create (will trim whitespace)
   * Will die on error (may change this later)
   */
  function createClockTables($PDO, $name){


    //TODO - port to v3.0
    //Will require updating CLOCK_LINES table

    //Create Clock Table _CLK
    $name = str_replace(' ', '_', trim($name));//substitute spaces and trim whitespace
    $tableName = escapeTableName($PDO, $name . '_CLK'); //sanitise name, can't use statements

    $sql = 'CREATE TABLE ' . $tableName . ' (
              ID int unsigned auto_increment not null primary key,
              EVENT_NAME char(64) not null,
              START_TIME int not null,
              LENGTH int not null,
              INDEX EVENT_NAME_IDX (EVENT_NAME)
            )';

    if($PDO->query($sql) === FALSE)
      die('Error creating Clock Table: ' . $tableName);

    //Create Rules Table _RULES
    $tableName = escapeTableName($PDO, $name . '_RULES');
    $sql = 'CREATE TABLE ' . $tableName . ' (
              CODE varchar(10) not null primary key,
              MAX_ROW int unsigned,
              MIN_WAIT int unsigned,
              NOT_AFTER varchar(10),
              OR_AFTER varchar(10),
              OR_AFTER_II varchar(10)
            )';

    if($PDO->query($sql) === FALSE)
      die('Error creating Clock Table: ' . $tableName);

  }

  /**
   * Saves Events to the given Clock table
   * @param $PDO PDO connection to use
   * @param $name Name of clock these events belong to
   * @param $events array of events to use for this clock
   *   [EVENT_NAME, START_TIME, LENGTH]
   * Will die on error
   */
  //v3.0 Safe
  function saveEvents($PDO, $name, $events){



    if(sizeof($events) == 0)
      die('No events to save');

    //Remove events before update
    removeClockEvents($PDO, $name);

    $sql = 'INSERT INTO CLOCK_LINES (`CLOCK_NAME`, `EVENT_NAME`, `START_TIME`, `LENGTH`)
            VALUES ';

    foreach($events as $event)
      $sql .= "(?, ?, ?, ?),";

    //Remove trailing ,
    $sql = substr($sql, 0, -1);
    
    $stmt = $PDO->prepare($sql);

    $paramNo = 1;

    foreach($events as $event){

      $stmt->bindParam($paramNo, $name);//CLOCK NAME
      $stmt->bindParam($paramNo + 1, $event[0]);//EVENT NAME
      $stmt->bindParam($paramNo + 2, $event[1]);//START
      $stmt->bindParam($paramNo + 3, $event[2]);//LENGTH
      $paramNo += 4;

    }

    if($stmt->execute() === FALSE)
      die('Error inserting clock events: ' . $tableName);

    if($stmt->rowCount() != sizeof($events))
      die('Error, insert count does not match number of events was ' . $stmt->rowCount
          . ' should have been ' . sizeof($events));

    $stmt = NULL;

  }

  /**
   * Emptys the given clock of all events
   * Riv seems to delete all events then insert new ones upon save
   * @param $PDO PDO connection to use
   * @param $name Name of clock to remove events
   * Will die on error
   */
   //v3.0 safe
  function removeClockEvents($PDO, $name){

    $sql = "DELETE FROM CLOCK_LINES WHERE CLOCK_NAME = '$name'";

    if($PDO->query($sql) === FALSE)
      die('Error removing old events from clock: ' . $name);

  }

  /**
   * Emptys the given clock of all rules 
   * Riv seems to delete all events then insert new ones upon save
   * @param $PDO PDO connection to use
   * @param $name Name of clock to remove events
   * Will die on error
   */
   //v3.0 safe
  function removeClockRules($PDO, $name){

    $sql = "DELETE FROM RULE_LINES WHERE CLOCK_NAME = '$name'";

    if($PDO->query($sql) === FALSE)
      die('Error removing rules from clock: ' . $name);

  }

  /**
   * Emptys the given clock of all permissions
   * Riv seems to delete all events then insert new ones upon save
   * @param $PDO PDO connection to use
   * @param $name Name of clock to remove events
   * Will die on error
   */
   //v3.0 safe
  function removeClockPerms($PDO, $name){

    $sql = "DELETE FROM CLOCK_PERMS WHERE CLOCK_NAME = '$name'";

    if($PDO->query($sql) === FALSE)
      die('Error removing permissions from clock: ' . $name);

  }

  /**
   * Renames a clocks Rules
   * @param $PDO PDO Connection to use
   * @param $oldName Old/Current clock name
   * @param $newName Name you want to rename to
   * dies on error
   */
  function renameClockRules($PDO, $oldName, $newName){

    $sql = 'UPDATE `RULE_LINES` SET `CLOCK_NAME` = :newName
            WHERE `CLOCK_NAME` = :oldName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newName', $newName);
    $stmt->bindParam(':oldName', $oldName);

    if($stmt->execute() === FALSE)
      die('Error renaming clock in RULE_LINES table: ' . $oldName . ' to ' . $newName);

    $stmt = NULL;

  }

  /**
   * Renames a clocks Lines 
   * @param $PDO PDO Connection to use
   * @param $oldName Old/Current clock name
   * @param $newName Name you want to rename to
   * dies on error
   */
  function renameClockLines($PDO, $oldName, $newName){

    $sql = 'UPDATE `CLOCK_LINES` SET `CLOCK_NAME` = :newName
            WHERE `CLOCK_NAME` = :oldName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newName', $newName);
    $stmt->bindParam(':oldName', $oldName);

    if($stmt->execute() === FALSE)
      die('Error renaming clock in RULE_LINES table: ' . $oldName . ' to ' . $newName);

    $stmt = NULL;

  }


  /**
   * Renames a given clock in the CLOCKS table
   * @param $PDO PDO Connection to use
   * @param $oldName Old/Current clock name
   * @param $newName Name you want to rename to
   * dies on error
   */
  function renameClock($PDO, $oldName, $newName){

    $sql = 'UPDATE `CLOCKS` SET `NAME` = :newName WHERE `NAME` = :oldName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newName', $newName);
    $stmt->bindParam(':oldName', $oldName);

    if($stmt->execute() === FALSE)
      die('Error renaming clock in CLOCKS table: ' . $oldName . ' to ' . $newName);

    $stmt = NULL;

  }

  /**
   * Renames a given clock in the CLOCK_PERMS table
   * @param $PDO PDO Connection to use
   * @param $oldName Old/Current clock name
   * @param $newName Name you want to rename to
   * dies on error
   */
  function renameClockPerms($PDO, $oldName, $newName){

    $sql = 'UPDATE `CLOCK_PERMS` SET `CLOCK_NAME` = :newName
            WHERE `CLOCK_NAME` = :oldName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newName', $newName);
    $stmt->bindParam(':oldName', $oldName);

    if($stmt->execute() === FALSE)
      die('Error renaming clock in CLOCK_PERMS table: ' . $oldName . ' to ' . $newName);

    $stmt = NULL;

  }

  /**
   * Copies a given clock rules to a new clock, includes the data this contains
   * @param $PDO PDO Connection to use
   * @param $sourceName Name of Clock to copy
   * @param $copyName Name of Copy
   * dies on error
   */
   //v3.0 safe
  function copyClockRules($PDO, $sourceName, $copyName){

    //Select Rules from the old clock
    $sql = 'SELECT * FROM RULE_LINES WHERE `CLOCK_NAME` = :sourceName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':sourceName', $sourceName);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);

    // Only do this when enough records are there
    $totalCount = $stmt->rowCount();
    if($totalCount > 0) {
      
      $sql = 'INSERT INTO `RULE_LINES` (`CLOCK_NAME`, `CODE`, `MAX_ROW`, `MIN_WAIT`, `NOT_AFTER`, `OR_AFTER`, `OR_AFTER_II`)
              VALUES ';

      $i=1;

      while($row = $stmt->fetch()){
 
        $code      = $row['CODE'];
        $max_row   = $row['MAX_ROW'];
        $min_wait  = $row['MIN_WAIT'];
        $not_after = $row['NOT_AFTER'];
        $or_after  = $row['OR_AFTER'];
        $or_after2 = $row['OR_AFTER_II'];
        $sql .= "('$copyName','$code','$max_row','$min_wait','$not_after','$or_after','$or_after2')"; 

        // Use semicolon for 
        if($i == $totalCount) {
           $sql .= ";";
        } 
        else {
           $sql .=",";
        }

        $i++;
    
      }

      //error_log("SQL: $sql");
      $stmt = $PDO->prepare($sql);

      if($stmt->execute() === FALSE || $stmt->rowCount() < 1) {
        error_log("Error inserting CLOCK_RULES"); 
        error_log("SQL: $sql  Rows: $stmt->rowCount()");
        die('Error inserting ' . $name . ' into CLOCK_RULES table');
      }

      $stmt = NULL;
    }
  }

  /**
   * Copies a given clock perms to a new clock, includes the data this contains
   * @param $PDO PDO Connection to use
   * @param $sourceName Name of Clock to copy
   * @param $copyName Name of Copy
   * dies on error
   */
  function copyClockPerms($PDO, $sourceName, $copyName){

    //Select Permissions from the old clock
    $sql = 'SELECT * FROM CLOCK_PERMS WHERE `CLOCK_NAME` = :sourceName GROUP BY SERVICE_NAME';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':sourceName', $sourceName);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);

    // Only do this when enough records are there
    $totalCount = $stmt->rowCount();
    if($totalCount > 0) {
      
      $sql = 'INSERT INTO `CLOCK_PERMS` (`CLOCK_NAME`, `SERVICE_NAME`)
              VALUES ';

      $i=1;


      while($row = $stmt->fetch()){
 
        $svc_name  = $row['SERVICE_NAME'];
        $sql .= "('$copyName','$svc_name')"; 

        // Use semicolon for 
        if($i == $totalCount) {
           $sql .= ";";
        } 
        else {
           $sql .=",";
        }

        $i++;
    
      }

      $stmt = $PDO->prepare($sql);

      if($stmt->execute() === FALSE || $stmt->rowCount() < 1) {
        error_log("Error inserting CLOCK_PERMS");
        error_log("SQL: $sql  Rows: $stmt->rowCount()");
        die('Error inserting ' . $copyName . ' into CLOCK_PERMS table');
      }

      $stmt = NULL;
    }

  }

  /**
   * Copies a given clock table to a new table, includes the data this contains
   * @param $PDO PDO Connection to use
   * @param $sourceName Name of Clock to copy
   * @param $copyName Name of Copy
   * dies on error
   */
  function copyClockTable($PDO, $sourceName, $copyName){

    $sourceTableName = str_replace(' ', '_', $sourceName);
    $sourceTableName = escapeTableName($PDO, $sourceTableName . '_CLK');
    $copyTableName = str_replace(' ', '_', $copyName);
    $copyTableName = escapeTableName($PDO, $copyTableName . '_CLK');

    //Create tables with indexes etc
    $sql = 'CREATE TABLE ' . $copyTableName . ' LIKE ' . $sourceTableName;

    if($PDO->query($sql) === FALSE)
      die('Error copying clock table from ' . $sourceTableName . ' to ' . $copyTableName);

    //Insert records from source to copy
    $sql = 'INSERT ' . $copyTableName . ' SELECT * FROM ' . $sourceTableName;

    if($PDO->query($sql) === FALSE)
      die('Error copying clock data from ' . $sourceTableName . ' to ' . $copyTableName);

  }

  /**
   * Copies a given clocks rules to a new table
   * @param $PDO PDO Connection to use
   * @param $sourceName Name of Clock to copy
   * @param $copyName Name of Copy
   * dies on error
   */
  function copyClockRulesTable($PDO, $sourceName, $copyName){

    $sourceTableName = str_replace(' ', '_', $sourceName);
    $sourceTableName = escapeTableName($PDO, $sourceTableName . '_RULES');
    $copyTableName = str_replace(' ', '_', $copyName);
    $copyTableName = escapeTableName($PDO, $copyTableName . '_RULES');

    //Create tables with indexes etc
    $sql = 'CREATE TABLE ' . $copyTableName . ' LIKE ' . $sourceTableName;

    if($PDO->query($sql) === FALSE)
      die('Error copying clock rules table from ' . $sourceTableName . ' to '
          . $copyTableName);

    //Insert records from source to copy
    $sql = 'INSERT ' . $copyTableName . ' SELECT * FROM ' . $sourceTableName;

    if($PDO->query($sql) === FALSE)
      die('Error copying clock rules from ' . $sourceTableName . ' to ' . $copyTableName);

  }

  /**
   * Adds a clock to the CLOCK_PERMS table
   * @param $name CLOCK_NAME
   * @param $service SERVICE_NAME
   * Dies on error
   */
  function addClockPerms($PDO, $name, $service){

    $sql = 'INSERT INTO `CLOCK_PERMS` (`CLOCK_NAME`, `SERVICE_NAME`)
            VALUES (:name, :service)';

    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':service', $service);

    if($stmt->execute() === FALSE)
      die('Error inserting clock into CLOCK_PERMS table: ' . $name);

    $stmt = NULL;

  }

  /**
   * Updates a given clocks short name/code to a new code
   * Check with code exists before using this
   * @param $PDO PDO connection to use
   * @param $clockName Name of clock to update
   * @param $newCode Code to change clock too
   * dies on error
   */
  function updateClockCode($PDO, $clockName, $newCode){

    $sql = 'UPDATE `CLOCKS` SET `SHORT_NAME` = :newCode WHERE `NAME` = :clockName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newCode', $newCode);
    $stmt->bindParam(':clockName', $clockName);

    if($stmt->execute() === FALSE)
      die('Error updating CLOCK ' . $clockName . ' code to ' . $newCode);

    $stmt = NULL;

  }

  /**
   * Updates a given clocks short name/code to a new code
   * Check with code exists before using this
   * @param $PDO PDO connection to use
   * @param $clockName Name of clock to update
   * @param $newCode Code to change clock too
   * dies on error
   */
  function updateClockColour($PDO, $clockName, $newColour){

    $sql = 'UPDATE `CLOCKS` SET `COLOR` = :newColour WHERE `NAME` = :clockName';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':newColour', $newColour);
    $stmt->bindParam(':clockName', $clockName);

    if($stmt->execute() === FALSE)
      die('Error updating CLOCK ' . $clockName . ' colour to ' . $newColour);

    $stmt = NULL;

  }

  //DELETE TABLE + RULES
  function deleteClockTables($PDO, $clockName){

    //replace whitespace with _ and sanitise names
    $clockName = str_replace(' ', '_', trim($clockName));
    $clkTable = escapeTableName($PDO, $clockName . '_CLK');
    $rulesTable = escapeTableName($PDO, $clockName . '_RULES');

    $sql = 'DROP TABLE ';

    if($PDO->query($sql . $clkTable) === FALSE)
      die('Error dropping CLK table: ' . $clkTable . ' ' . $PDO->errorInfo());

    if($PDO->query($sql . $rulesTable) === FALSE)
      die('Error dropping RULES table: ' . $rulesTable . ' ' . $PDO->errorInfo());

  }

  //DELETE FROM CLOCKS AND CLOCK_PERMS
  function deleteClock($PDO, $clockName){

    $sql = 'DELETE FROM CLOCKS WHERE NAME = :name';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':name', $clockName);

    if($stmt->execute() === FALSE)
      die('Could not delete from CLOCKS table: ' . $stmt->errorInfo());

    $stmt = NULL;

    $sql = 'DELETE FROM CLOCK_PERMS WHERE CLOCK_NAME = :name';
    $stmt = $PDO->prepare($sql);
    $stmt->bindParam(':name', $clockName);

    if($stmt->execute() === FALSE)
      die('Could not delete from CLOCK_PERMS table: ' . $stmt->errorInfo());

    $stmt = NULL;

  }

?>
