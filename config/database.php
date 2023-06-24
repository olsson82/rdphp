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


  /* Database access details and connection functions
   */

  /**
   * Change DB user name/host/password here for Rivendell as necessary
   * @return array with username, password & hostname
   */
  function getDBDetails(){


    $DB = Array();

    $ini_file = "/etc/rd.conf";

    if(!is_readable($ini_file)) {
       echo "$ini_file DOES NOT exist";
       exit(-1);
    }
    $ini_array = parse_ini_file($ini_file,true,INI_SCANNER_RAW);

    $DB['username'] = $ini_array['mySQL']['Loginname'];
    $DB['password'] = $ini_array['mySQL']['Password'];
    $DB['hostname'] = $ini_array['mySQL']['Hostname'];
    $DB['database'] = $ini_array['mySQL']['Database'];
    return $DB;

  }


  /**
   * Gets a PDO connection to MySQL
   * @return PDO MySQL connection, set this to NULL to close connection
   */
  function getDatabaseConnection(){

    $PDO = NULL;

    $DB = getDBDetails();

    try{

      $PDO = new PDO('mysql:host=' . $DB['hostname'] . ';dbname='
          . $DB['database'], $DB['username'], $DB['password']);

      //Set Exception mode
      $PDO->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    }catch(PDOException $e){

      die('Error connecting to database: ' . $e->getMessage());

    }
 
    // TODO - Check Rivendell Database Version for compatibility

    return $PDO;

  }

?>
