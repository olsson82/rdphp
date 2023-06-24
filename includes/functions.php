<?php

  //error_reporting(E_ALL);
  //ini_set('display_errors', 'off');

  /**
   * Converts Milliseconds from start to HH::MM::SS 
   */
  function msToHHMMSS_fromMID($milliSeconds){

     $seconds = $milliSeconds / 1000;   

     return gmdate("H:i:s", $seconds);

  }

  /**
   * Converts Milliseconds to HH::MM::SS 
   */
  function msToHHMMSS($milliSeconds){

     $seconds = $milliSeconds / 1000;   

     if($seconds > 3600) {
       return gmdate("H:i:s", $seconds);
     }
     else {
       return gmdate("i:s", $seconds);
     }

  }


  /**
   * Converts millis to MM:SS.s
   * @param $millis millis to convert
   * @return millis in MM:SS.s format e.g. 30000 = 00:30
   */
  function getDuration($millis){

    //minutes
    $mins = 0;

    if($millis >= 60000)
      $mins = (int)($millis / 60000);

    while(strlen($mins) < 2)
      $mins = '0' . $mins;

    $millis = $millis - ($mins * 60000);

    //seconds
    $secs = 0;

    if($millis >= 1000)
      $secs = (int)($millis / 1000);

    while(strlen($secs) < 2)
      $secs = '0' . $secs;

    $millis = $millis - ($secs * 1000);


    $time = $mins . ':' . $secs;

    if($millis > 0)
      $time .= '.' . (int)($millis / 100);

    return $time;

  }

/**
* For safe multipart POST request for PHP5.3 ~ PHP 5.4.
* 
* @param resource $ch cURL resource
* @param array $assoc "name => value"
* @param array $files "name => path"
* @return bool
*/
function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {
    
    // invalid characters for "name" and "filename"
    static $disallow = array("\0", "\"", "\r", "\n");
    
    // build normal parameters
    foreach ($assoc as $k => $v) {
        $k = str_replace($disallow, "_", $k);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"",
            "",
            filter_var($v), 
        ));
    }
    
    // build file parameters
    foreach ($files as $k => $v) {
        switch (true) {
            case false === $v = realpath(filter_var($v)):
            case !is_file($v):
            case !is_readable($v):
                continue 2; // or return false, throw new InvalidArgumentException
        }
        $data = file_get_contents($v);
        $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
        $k = str_replace($disallow, "_", $k);
        $v = str_replace($disallow, "_", $v);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
            "Content-Type: application/octet-stream",
            "",
            $data, 
        ));
    }
    
    // generate safe boundary 
    do {
        $boundary = "---------------------" . md5(mt_rand() . microtime());
    } while (preg_grep("/{$boundary}/", $body));
    
    // add boundary for each parameters
    array_walk($body, function (&$part) use ($boundary) {
        $part = "--{$boundary}\r\n{$part}";
    });
    
    // add final boundary
    $body[] = "--{$boundary}--";
    $body[] = "";
    
    // set options
    return @curl_setopt_array($ch, array(
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => implode("\r\n", $body),
        CURLOPT_HTTPHEADER => array(
            "Expect: 100-continue",
            "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
        ),
    ));
}

//See if a cart number exists
function rd_cart_exists($cartNumber) {

   //This skips session checking assuming that already happened in the main web page
   $rd_username = $_SESSION["username"];
   $rd_password = $_SESSION["password"];
   $rd_web_api = $_SESSION["rdWebAPI"];
   
   $ch = curl_init();
   $headers = array("Content-Type:multipart/form-data");
   $parameters = array(
      'COMMAND' => '7',
      'LOGIN_NAME' => $rd_username,
      'PASSWORD'   => $rd_password,
      'CART_NUMBER' => $cartNumber,
      'INCLUDE_CUTS' => '1',
    );
   $options = array(
        CURLOPT_URL => $rd_web_api,
        CURLOPT_HEADER => false,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $parameters,
        CURLOPT_RETURNTRANSFER => true
    ); 
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);

    //echo "List Cart\n $result \n";

    curl_close($ch);

    if(preg_match('/ResponseCode>404</', $result, $matches)) {
       //echo "Cart $cart does not exist\n";
       return 0;
    }
    else {
       return 1;
    }

}

function rd_cut_count($cartNumber) {

   //This skips session checking assuming that already happened in the main web page
   $rd_username = $_SESSION["username"];
   $rd_password = $_SESSION["password"];
   $rd_web_api = $_SESSION["rdWebAPI"];

   $ch = curl_init();
   $headers = array("Content-Type:multipart/form-data");
   $parameters = array(
      'COMMAND' => '7',
      'LOGIN_NAME' => $rd_username,
      'PASSWORD'   => $rd_password,
      'CART_NUMBER' => $cartNumber,
      'INCLUDE_CUTS' => '1',
    );
   $options = array(
        CURLOPT_URL => $rd_web_api,
        CURLOPT_HEADER => false,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $parameters,
        CURLOPT_RETURNTRANSFER => true
    ); 
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);

    //echo "Cart $cartNumber Cuts:\n$result \n";

    curl_close($ch);

    //Look for <cutQuantity>1</cutQuantity>
    if(preg_match_all('/<cutQuantity>([0-9]+)<\/cutQuantity>/', $result, $matches)) {
       $count = $matches[1][0];
       //echo "Cart $cartNumber has $count cuts\n";
       return $count;
    }
    else {
       return 0;
    }

}

// Returns a cart number 
function rd_add_cart($cartNumber, $groupName) {

   //This skips session checking assuming that already happened in the main web page
   $rd_username = $_SESSION["username"];
   $rd_password = $_SESSION["password"];
   $rd_web_api = $_SESSION["rdWebAPI"];

   $ch = curl_init();
   $headers = array("Content-Type:multipart/form-data");
   $parameters = array(
      'COMMAND' => '12',
      'LOGIN_NAME' => $rd_username,
      'PASSWORD'   => $rd_password,
      'GROUP_NAME' => $groupName,
      'TYPE'       => 'audio',
      'CART_NUMBER' => $cartNumber,
   );

   //Override the parameters to omit CART_NUMBER if the cart is 0 (a new cart)
   if($cart == 0) {
     $parameters = array(
      'COMMAND' => '12',
      'LOGIN_NAME' => $rd_username,
      'PASSWORD'   => $rd_password,
      'GROUP_NAME' => $groupName,
      'TYPE'       => 'audio',
      );
   }

   $options = array(
        CURLOPT_URL => $rd_web_api,
        CURLOPT_HEADER => false,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $parameters,
        CURLOPT_RETURNTRANSFER => true
    ); 
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);

    //echo "Add Cart: $cartNumber to group $groupName\n$result \n";

    curl_close($ch);

    if(preg_match('/ResponseCode>404</', $result, $matches)) {
       //Error signalling
       $cart=0;
    }
    else if(preg_match_all('/<number>([0-9]+)<\/number>/', $result, $matches)) {
       $cart = $matches[1][0];
    }
    return $cart;
}

function rd_add_cut($cartNumber) {

   //This skips session checking assuming that already happened in the main web page
   $rd_username = $_SESSION["username"];
   $rd_password = $_SESSION["password"];
   $rd_web_api = $_SESSION["rdWebAPI"];
   
   $ch = curl_init();
   $headers = array("Content-Type:multipart/form-data");
   $parameters = array(
      'COMMAND' => '10',
      'LOGIN_NAME' => $rd_username,
      'PASSWORD'   => $rd_password,
      'CART_NUMBER' => $cartNumber,
    );
   $options = array(
        CURLOPT_URL => $rd_web_api,
        CURLOPT_HEADER => false,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $parameters,
        CURLOPT_RETURNTRANSFER => true
    ); 
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);

    //echo "Add Cut to Cart $cartNumber: \n$result \n";

    curl_close($ch);

    if(preg_match('/ResponseCode>404</', $result, $matches)) {
       //echo "Cart $cart does not exist\n";
       return 0;
    }
    else {
       return 1;
    }
}


//Set some cart data
//TODO - This should be upgraded to use a better data structure 
//       and loop through it to decide what to set.  
function rd_edit_cart($cartNumber, $artist, $title, $comment) {

   //This skips session checking assuming that already happened in the main web page
   $rd_username = $_SESSION["username"];
   $rd_password = $_SESSION["password"];
   $rd_web_api = $_SESSION["rdWebAPI"];
  
   //Parameters to be edited:
   //TITLE
   //ARTIST
   //YEAR
   //GROUP_NAME
   //USER_DEFINED
   //NOTES
 
   $ch = curl_init();
   $headers = array("Content-Type:multipart/form-data");
   $parameters = array(
      'COMMAND' => '14',
      'LOGIN_NAME' => $rd_username,
      'PASSWORD'   => $rd_password,
      'CART_NUMBER' => $cartNumber,
      'ARTIST' => $artist,
      'TITLE' => $title,
      'USER_DEFINED' => $comment,
    );
   $options = array(
        CURLOPT_URL => $rd_web_api,
        CURLOPT_HEADER => false,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $parameters,
        CURLOPT_RETURNTRANSFER => true
    ); 
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);

    echo "Edit Cart: $cartNumber: \n$result \n";

    curl_close($ch);

    if(preg_match('/ResponseCode>404</', $result, $matches)) {
       //echo "Cart $cart does not exist\n";
       return 0;
    }
    else {
       return 1;
    }
}

/* Convert hexdec color string to rgb(a) string */
function hex2rgba($color, $opacity = false) {
 
	$default = 'rgb(0,0,0)';
 
	//Return default if no color provided
	if(empty($color))
          return $default; 
 
	//Sanitize $color if "#" is provided 
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }
 
        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
 
        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);
 
        //Check if opacity is set(rgba or rgb)
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }
 
        //Return rgb(a) color string
        return $output;
}


  //Write debug statements to console
  function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

?>
