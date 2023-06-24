<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?php echo $title; ?></title>
<?php
  //Insert JS Scripts
if (!$nojs == 1) { //added to remove errors in rdlogedit
  foreach($js as $script){

?>
    <script src="../js/<?php echo $script; ?>" type="text/javascript"></script>
<?php } }?>
    <script src="../js/jquery-2.2.2.min.js" type="text/javascript"></script>
    <link href="../css/<?php echo $css; ?>" rel="stylesheet" type="text/css">
  </head>
  <body>
  <div id="content">
