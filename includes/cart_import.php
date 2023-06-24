<?php


   session_start();

   require('../config/database.php');
   require('functions.php');
   require('dbfunctions.php');

   $PDO = getDatabaseConnection();

   //Get variables posted
   $group = $_POST['GROUP'];
   $logName = $_POST['LOGNAME'];
   $line = $_POST['LINE'];
   $cart = $_POST['CART'];

   $username = $_SESSION["username"];
   $password = $_SESSION["password"];
   $fullname = $_SESSION["fullname"];
   $_RDWEB_API = $_SESSION["rdWebAPI"];

   // This is to support META voicetrack events that have a cart number of 0 
   if($cart == 0) {
      //Add cart and get cart number
      echo "Cart is NEW and does not exists.  Creating in group \"$group\"\n";
      $cart=rd_add_cart($cart,$group); 

      //Update the log in the database with the new cart number we retrieved.  
      rd_updateVTCart($PDO, $logName, $line, $cart,$username);

      //TODO - Update the Interface with the new cart number
   }

   //Write temporary file to server
   $filename = $_FILES['audio_data']['name']; //Temp name given from upload
   $filedata = $_FILES['audio_data']['tmp_name']; //File payload

   $dropbox_folder = "/tmp";
   $save_file     = $dropbox_folder . "/" . $cart . ".wav";

   //move the file from temp name to local folder using $output name
   move_uploaded_file($filedata, $save_file);
   $filedata = $save_file; //Reassign file payload to new location

   //echo "CART: $cart GROUP: $group FILE: $save_file\n";
   //echo "FILESIZE: $filesize FILENAME: $filename\n";
   //echo "FILETYPE: $filetype FILEDATA: $filedata\n";


   // Check that Cart and Cuts Exists
   // Some music schedulers put sequential carts in the logs and the cart does not exist
   // in Rivendell and must be created before importing audio into it. 
   if (!rd_cart_exists($cart)) {
      //Create cart and cut
      echo "Cart $cart does not exists.  Creating\n";
      rd_add_cart($cart, $group);
      rd_add_cut($cart);
   }
   else {
      //Check if a cut exist. This will be used for newly created carts from META events.
      //echo "Cart $cart  exists.  Checking Cuts.\n";
      if(!rd_cut_count($cart)) {
          echo "Cart $cart has no cuts.  Adding.\n";
          rd_add_cut($cart);
      }
   }

   //Set metadata on the cart
   $date = date("D M d, Y G:i");
   $artist = $fullname;
   $title = "Voice Track";
   $comment = "Recorded by $username on $date for log $logName"; 
   rd_edit_cart($cart, $artist, $title, $comment);
  
   //Create Parameter List for Upload
   $ch = curl_init();   
   $parameters = array(
      'COMMAND' => '2',
      'LOGIN_NAME' => $username,
      'PASSWORD'   => $password,
      'CART_NUMBER' => $cart,
      'CUT_NUMBER' => '1',
      'CREATE' => '1',
      'GROUP_NAME' => $group,
      'CHANNELS' => '2',
      'NORMALIZATION_LEVEL' => '-4',
      'USE_METADATA' => '0',
      'AUTOTRIM_LEVEL' => '-40',
      'TITLE' => 'Voicetrack',
   );

   //PHP 5.4 and earlier cannot handle multipart forms.  
   //There is a home-rolled function in the functions.php file included here
   //that deals with it.
   $files = array("FILENAME" => $save_file);
   $postfields = curl_custom_postfields($ch, $parameters, $files);

   curl_setopt($ch,CURLOPT_URL, $_RDWEB_API);
   $result = curl_exec($ch);

   //TODO - delete temporary audio file
   //Seems to do it automatically.

   //close connection
   curl_close($ch);

   //echo "Result: $result\n";

?>
