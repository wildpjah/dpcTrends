<?php
require_once 'dbconfig.php';
$connection = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
  }
    $q=$_REQUEST["q"]; 
    $value=$_REQUEST["value"];

    switch($value){
        case 'Players':
            $query="SELECT `Nickname` FROM `Players` WHERE Nickname LIKE '%$q%'";
            $result = mysqli_query($connection, $query);
        
            $json=array();
        
            while($row = mysqli_fetch_array($result)) {
                if(!in_array($row['Nickname'], $json)){
                    array_push($json, $row['Nickname']);
                }
            }
            break;
        case 'Heroes':
            $query="SELECT `HeroName` FROM `Heroes` WHERE HeroName LIKE '%$q%'";
            $result = mysqli_query($connection, $query);
        
            $json=array();
        
            while($row = mysqli_fetch_array($result)) {
              array_push($json, $row['HeroName']);
            }
            break;
        case 'Teams':
            $query="SELECT `name` FROM `Teams` WHERE name LIKE '%$q%'";
            $result = mysqli_query($connection, $query);
        
            $json=array();
        
            while($row = mysqli_fetch_array($result)) {
              if(!in_array($row['name'], $json)){
                  array_push($json, $row['name']);
              }
            }
            break;
    }

    echo json_encode($json);
?>
