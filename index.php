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

//TODO
//1. Check for correct versions of PHP
//2. Check for presence of configuration files

session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: welcome.php");
    exit;
}
 
// Include config file
require "config/database.php";
$PDO = getDatabaseConnection();
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(! trim($_POST["username"])){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(! trim($_POST["password"])){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
   
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT LOGIN_NAME, FULL_NAME, ENABLE_WEB, PASSWORD FROM USERS WHERE LOGIN_NAME = :uid";

        $stmt=$PDO->prepare($sql);
        $stmt->bindParam(':uid', $username);  //Bind parameters to avoid SQL Injection
            
        // Set parameters
        $param_username = $username;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                $stmt->setFetchMode(PDO::FETCH_ASSOC); 
                
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1) {                    
                    if($row = $stmt->fetch()){

                        //get clear-text, unsecure passwords from Rivendell
                        $rdDsername=$row['LOGIN_NAME']; 
                        $rdPassword=$row['PASSWORD'];
                        $rdWeb=$row['ENABLE_WEB'];
                        $fullname=$row['FULL_NAME'];

                        //Is password correct and web enabled
                        if(($rdPassword == base64_encode($password)) && ($rdWeb == "Y")){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $username;                            
                            $_SESSION["username"] = $username;                            
                            $_SESSION["password"] = $rdPassword;                            
                            $_SESSION["fullname"] = $fullname;                            

                            //TODO - Put this somewhere else
                            $_SESSION["rdWebAPI"] = "http://localhost/rd-bin/rdxport.cgi";                            
                            
                            // TODO - Create Ticket from username and store the ticket in the session
                              
                            // Redirect user to welcome page
                            header("location: welcome.php");
                        } else{
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        
    // Close connection
    $PDO = NULL;
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
	<h1>Rivendell Web</h1>
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
        </form>
    </div>    
    <a href="https://seal.beyondsecurity.com/vulnerability-scanner-verification/rdbeta4.ddns.net"><img src="https://seal.beyondsecurity.com/verification-images/rdbeta4.ddns.net/vulnerability-scanner-2.gif" alt="Website Security Test" border="0"></a>
</body>
</html>
