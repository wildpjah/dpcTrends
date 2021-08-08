<?php
require_once 'dbconfig.php';
$connection = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
  }
    $q = $_REQUEST["q"]; 
    $q = "%$q%";
    $value = $_REQUEST["value"];

    switch($value){
        case 'Players':
            $stmt = $connection->prepare("SELECT `Nickname` FROM `Players` WHERE Nickname LIKE ?");
            $stmt->bind_param("s", $q);
            $stmt->execute();
            $result = $stmt->get_result();
            $json = array();
            foreach ($result as $row) {
                if(!in_array($row['Nickname'], $json)){
                    array_push($json, $row['Nickname']);
                }
            }
            break;
        case 'Heroes':
            $stmt= $connection-> prepare("SELECT `HeroName` FROM `Heroes` WHERE HeroName LIKE ?");
            $stmt->bind_param("s", $q);
            $stmt->execute();
            $result = $stmt->get_result();
            $json = array();
            foreach ($result as $row){
              array_push($json, $row['HeroName']);
            }
            break;
        case 'Teams':
            $stmt= $connection-> prepare("SELECT `name` FROM `Teams` WHERE name LIKE ?");
            $stmt->bind_param("s", $q);
            $stmt->execute();
            $result = $stmt->get_result();
            $json = array();
            foreach ($result as $row) {
                if(!in_array($row['name'], $json)){
                    array_push($json, $row['name']);
                }
            }
            break;
    }

    echo json_encode($json);
?>
