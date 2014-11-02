<?php
session_start();
unset($_SESSION['loggedIn']); // set it to false;
header("location:index.php");
?>