<?php
//ini_set('display_errors', 1); error_reporting(-1);
//connect to server
require_once 'dbconfig.php';
$connection = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
  die("Connection failed: " . $connection->connect_error);
}

function queueTotal($queue)
{
  $total = 0;
  foreach ($queue as $item) {
    $total += $item;
  }
  return $total;
}

//establishing variables from Inputted data
$trackedObject = $_GET['trackedObject'];
$trackedStat = $_GET['trackedStat'];
$format = $_GET['dataFormat'];
$games = 0;
$data_points = [];
$totalGames = 0;
$dataType = $_GET['dataType'];
$startDate = strtotime($_GET['startDate']) * 1000;
$endDate = strtotime($_GET['endDate']) * 1000;
$dateFilter = '';
$patch = $_GET['patch'];
$team = $_GET['team'];
$withPlayer = $_GET['withPlayer'];
$againstPlayer = $_GET['againstPlayer'];
$withHero = $_GET['withHero'];
$againstHero = $_GET['againstHero'];
$winCheck = ($_GET['wins'] === 'true');
$lossCheck = ($_GET['losses'] === 'true');
$radiant = ($_GET['radCheck'] === 'true');
$dire = ($_GET['direCheck'] === 'true');
$winloss = false;
$regionList = array('china', 'weu', 'eeu', 'na', 'sa', 'sea');
$teamFilter = '';
$patchFilter = '';
$heroArr = array();
$playerArr = array();
$withPlayerCon = false;
$withHeroCon = false;
$againstHeroCon = false;
$againstPlayerCon = false;
$matchArr = array();
$factionCon = false;

if ($dataType == 'faction') {
  $faction = $_GET['factionButtons'];
}

//modify dataset by inputted data
switch ($dataType) {
  case 'Teams':
    $condition = ' AND Teams.name = ' . "'$trackedObject'";
    break;
  case 'faction':
    $condition = " AND Teams.ValveID = MatchData." . $faction . "TeamId";
    break;
  default:
    $condition = '';
}

//set date range
if ($startDate != false) {
  $startDateFilter = " AND MatchData.start_date > $startDate";
}
if ($endDate != false) {
  $endDateFilter = "AND MatchData.start_date < $endDate";
}


//filters
if ($patch) {
  $patchFilter = " AND MatchData.patch = '$patch'";
}

if ($team) {
  $teamFilter = " AND Teams.name = '$team'";
}


$regionFilter = '';
$i = 0;
foreach ($regionList as $region) {
  if ($_GET[$region] === 'true') {
    if ($i == 0) {
      $regionFilter = " AND (";
    }
    $regionFilter = $regionFilter . "Leagues.Region = '$region' OR ";

    $i++;
  }
}
$regionFilter = substr_replace($regionFilter, ")", -3);


//query database for data
$query = "SELECT * FROM MatchData
INNER JOIN PlayerPerformances on PlayerPerformances.match_id = MatchData.match_id
INNER JOIN Heroes on PlayerPerformances.HeroID = Heroes.HeroID
INNER JOIN Players on PlayerPerformances.PlayerEntryID = Players.PlayerEntryID
INNER JOIN Teams on Players.TeamEntryID = Teams.TeamEntryID
INNER JOIN Leagues on Leagues.LeagueID = MatchData.league_id
WHERE MatchData.has_error = 0 " .
  $condition . $patchFilter . $startDateFilter . $endDateFilter . $teamFilter . $regionFilter . "
ORDER BY MatchData.match_id";
$result = mysqli_query($connection, $query);

//filling data_points array
$wins = 0;
$avg = 0;
$gameResult = '';
$totalStat = 0;
$matchId = 0;
$startDate = 0;
$stat = 0;
$gameQueue = new SplQueue();
$radiantWin = false;
while ($row = mysqli_fetch_array($result)) {
  $rowMatchId = $row['match_id'];
  //after storing all heroes and players in an array,
  //sees the next row is from a different match so 
  //stops to parse the last couple rows that were the same match
  if ($matchId != $rowMatchId && $matchId != 0) {
    //goes through player array and hero array to set filter conditions
    //I think this solution is imperfect but it works for now.

    if ($winCheck && $lossCheck) {
      $winLoss = true;
    } elseif ($winCheck xor $lossCheck) {
      if ($dataType == 'Heroes' && in_array($trackedObject, array_keys($heroArr))) {
        if ($heroArr[$trackedObject][0] == $heroArr[$trackedObject][2] && $radiantWin && $winCheck && !$lossCheck) $winLoss = true;
        if ($heroArr[$trackedObject][0] == $heroArr[$trackedObject][2] && !$radiantWin && !$winCheck && $lossCheck) $winLoss = true;
        if ($heroArr[$trackedObject][0] != $heroArr[$trackedObject][2] && !$radiantWin && $winCheck && !$lossCheck) $winLoss = true;
        if ($heroArr[$trackedObject][0] != $heroArr[$trackedObject][2] && $radiantWin && !$winCheck && $lossCheck) $winLoss = true;
      } elseif ($dataType == 'matchData') {
        $winLoss = true;
      } elseif ($dataType == 'Teams') {
        if ($heroArr[0][1] == $trackedObject) {
          if ($heroArr[0][0] == $heroArr[0][2] && $radiantWin && $winCheck && !$lossCheck) $winLoss = true;
          if ($heroArr[0][0] == $heroArr[0][2] && !$radiantWin && !$winCheck && $lossCheck) $winLoss = true;
          if ($heroArr[0][0] != $heroArr[0][2] && !$radiantWin && $winCheck && !$lossCheck) $winLoss = true;
          if ($heroArr[0][0] != $heroArr[0][2] && $radiantWin && !$winCheck && $lossCheck) $winLoss = true;
        } else {
          if ($heroArr[0][0] != $heroArr[0][2] && $radiantWin && $winCheck && !$lossCheck) $winLoss = true;
          if ($heroArr[0][0] != $heroArr[0][2] && !$radiantWin && !$winCheck && $lossCheck) $winLoss = true;
          if ($heroArr[0][0] == $heroArr[0][2] && !$radiantWin && $winCheck && !$lossCheck) $winLoss = true;
          if ($heroArr[0][0] == $heroArr[0][2] && $radiantWin && !$winCheck && $lossCheck) $winLoss = true;
        }
      } elseif ($dataType == 'faction') {
        if ($faction == 'dire' && !$radiantWin) {
          $winLoss = true;
        }
        if ($faction == 'radiant' && $radiantWin) $winLoss = true;
      } elseif ($dataType == 'Players' && in_array($trackedObject, array_keys($playerArr))) {
        if ($playerArr[$trackedObject][0] == $playerArr[$trackedObject][2] && $radiantWin && $winCheck && !$lossCheck) $winLoss = true;
        if ($playerArr[$trackedObject][0] == $playerArr[$trackedObject][2] && !$radiantWin && !$winCheck && $lossCheck) $winLoss = true;
        if ($playerArr[$trackedObject][0] != $playerArr[$trackedObject][2] && !$radiantWin && $winCheck && !$lossCheck) $winLoss = true;
        if ($playerArr[$trackedObject][0] != $playerArr[$trackedObject][2] && $radiantWin && !$winCheck && $lossCheck) $winLoss = true;
      }
    }

    if ($radiant && $dire) {
      $factionCon = true;
    } elseif ($radiant xor $dire) {
      if ($dataType == 'Heroes' && in_array($trackedObject, array_keys($heroArr))) {
        if ($heroArr[$trackedObject][0] == $heroArr[$trackedObject][2] && $radiant && !$dire) $factionCon = true;
        if ($heroArr[$trackedObject][0] != $heroArr[$trackedObject][2] && !$radiant && $dire) $factionCon = true;
      } elseif ($dataType == 'matchData') {
        $factionCon = true;
      } elseif ($dataType == 'Teams') {
        if ($heroArr[0][1] == $trackedObject) {
          if ($heroArr[0][0] == $heroArr[0][2] && $radiant && !$dire) $factionCon = true;
          if ($heroArr[0][0] != $heroArr[0][2] && !$radiant && $dire) $factionCon = true;
        } else {
          if ($heroArr[0][0] != $heroArr[0][2] && $radiant && !$dire) $factionCon = true;
          if ($heroArr[0][0] == $heroArr[0][2] && !$radiant && $dire) $factionCon = true;
        }
      } elseif ($dataType == 'faction') {
        if ($faction == 'dire' && $dire) {
          $factionCon = true;
        }
        if ($faction == 'radiant' && $radiant) $factionCon = true;
      } elseif ($dataType == 'Players' && in_array($trackedObject, array_keys($playerArr))) {
        if ($playerArr[$trackedObject][0] == $playerArr[$trackedObject][2] && $radiant && !$dire) $factionCon = true;
        if ($playerArr[$trackedObject][0] != $playerArr[$trackedObject][2] && !$radiant && $dire) $factionCon = true;
      }
    }

    if ($withHero != '' && in_array($withHero, array_keys($heroArr))) {
      if ($dataType == 'Heroes' && in_array($trackedObject, array_keys($heroArr))) {
        if ($heroArr[$withHero][0] == $heroArr[$trackedObject][0]) $withHeroCon = true;
      } elseif ($dataType == 'matchData') {
        $withHeroCon = true;
      } elseif ($dataType == 'Teams') {
        if ($heroArr[$withHero][1] == $trackedObject) $withHeroCon = true;
      } elseif ($dataType == 'faction') {
        if ($heroArr[$withHero][0] == $heroArr[$withHero][2] && $faction == 'radiant') {
          $withHeroCon = true;
        } else if ($heroArr[$withHero][0] != $heroArr[$withHero][2] && $faction == 'dire') {
          $withHeroCon = true;
        }
      } elseif ($dataType == 'Players' && in_array($trackedObject, array_keys($playerArr))) {
        if ($heroArr[$withHero][0] == $playerArr[$trackedObject][0]) $withHeroCon = true;
      }
    } elseif ($withHero == '') {
      $withHeroCon = true;
    }
    if ($withPlayer != '' && in_array($withPlayer, array_keys($playerArr))) {
      if ($dataType == 'Heroes' && in_array($trackedObject, array_keys($heroArr))) {
        if ($playerArr[$withPlayer][0] == $heroArr[$trackedObject][0]) $withPlayerCon = true;
      } elseif ($dataType == 'matchData') {
        $withPlayerCon = true;
      } elseif ($dataType == 'Teams') {
        if ($playerArr[$withHero][1] == $trackedObject) $withPlayerCon = true;
      } elseif ($dataType == 'faction') {
        if ($playerArr[$withPlayer][0] == $playerArr[$withPlayer][2] && $faction == 'radiant') {
          $withPlayerCon = true;
        } else if ($playerArr[$withPlayer][0] != $playerArr[$withPlayer][2] && $faction == 'dire') {
          $withPlayerCon = true;
        }
      } elseif ($dataType == 'Players' && in_array($trackedObject, array_keys($playerArr))) {
        if ($playerArr[$withPlayer][0] == $playerArr[$trackedObject][0]) $withPlayerCon = true;
      }
    } elseif ($withPlayer == '') {
      $withPlayerCon = true;
    }
    if ($againstPlayer != '' && in_array($aginstPlayer, array_keys($playerArr))) {
      if ($dataType == 'Heroes' && in_array($trackedObject, array_keys($heroArr))) {
        if ($playerArr[$againstPlayer][0] != $heroArr[$trackedObject][0]) $againstPlayerCon = true;
      } elseif ($dataType == 'matchData') {
        $againstPlayerCon = true;
      } elseif ($dataType == 'Teams') {
        if ($playerArr[$againstPlayer][1] != $trackedObject) $againstPlayerCon = true;
      } elseif ($dataType == 'faction') {
        if ($playerArr[$againstPlayer][0] != $playerArr[$withPlayer][2] && $faction == 'radiant') {
          $againstPlayerCon = true;
        } else if ($playerArr[$withPlayer][0] == $playerArr[$withPlayer][2] && $faction == 'dire') {
          $againstPlayerCon = true;
        }
      } elseif ($dataType == 'Players' && in_array($trackedObject, array_keys($playerArr))) {
        if ($playerArr[$againstPlayer][0] != $playerArr[$trackedObject][0]) $againstPlayerCon = true;
      }
    } elseif ($againstPlayer == '') {
      $againstPlayerCon = true;
    }
    if ($againstHero != '' && in_array($againstHero, array_keys($heroArr))) {
      if ($dataType == 'Heroes' && in_array($trackedObject, array_keys($heroArr))) {
        if ($heroArr[$againstHero][0] != $heroArr[$trackedObject][0]) $againstHeroCon = true;
      } elseif ($dataType == 'matchData') {
        $againstHeroCon = true;
      } elseif ($dataType == 'Teams') {
        if ($heroArr[$againstHero][1] == $trackedObject) $againstHeroCon = true;
      } elseif ($dataType == 'faction') {
        if ($heroArr[$againstHero][0] == $heroArr[$againstHero][2] && $faction == 'radiant') {
          $againstHeroCon = true;
        } else if ($heroArr[$againstHero][0] != $heroArr[$againstHero][2] && $faction == 'dire') {
          $againstHeroCon = true;
        }
      } elseif ($dataType == 'Players' && in_array($trackedObject, array_keys($playerArr))) {
        if ($heroArr[$againstHero][0] == $playerArr[$trackedObject][0]) $againstHeroCon = true;
      }
    } elseif ($againstHero == '') {
      $againstHeroCon = true;
    }


    //checks that filter conditions are met, then pushes stats to array depending on user entries.
    if ($withPlayerCon && $withHeroCon && $againstHeroCon && $withHeroCon && $factionCon && $winLoss) {
      switch ($format) {
        case 'avg':
          if ($dataType == 'Players' || $dataType == 'Heroes') {
            foreach ($matchArr as $playerStats) {
              $valveID = $playerStats['ValveID'];
              $radId = $playerStats['radiantTeamId'];
              $radVictory = $playerStats['radiant_victory'];
              if ($trackedObject == $playerStats['Nickname'] || $trackedObject == $playerStats['HeroName']) {
                if ($trackedStat != 'winrate') {
                  $stat += $playerStats["$trackedStat"];
                } else {
                  if ($trackedStat == 'winrate' && ($valveID == $radID && $radVictory == 1) || ($valveID != $radID && $radVictory == 0)) {
                    $stat = 1;
                    $gameResult = 'Win';
                  } elseif ($trackedStat == 'winrate') {
                    $gameResult = 'Loss';
                    $stat = 0;
                  }
                }
                $totalGames += 1;
                $totalStat += $stat;
                $avg = $totalStat / $totalGames;
                if ($trackedStat == 'winrate') {
                  $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "Result", 'stat' => $gameResult);
                } else $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "$trackedStat", 'stat' => $stat);
                array_push($data_points, $point);
              }
            }
          } else {
            if ($trackedStat == 'winrate' && ($valveID == $radID && $radVictory == 1) || ($valveID != $radID && $radVictory == 0)) {
              $stat += 1;
              $gameResult = 'Win';
            } else $gameResult = 'Loss';
            $totalGames += 1;
            $totalStat += $stat;
            $avg = $totalStat / $totalGames;
            if ($trackedStat == 'winrate') {
              $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "Restult", 'stat' => $gameResult);
            } else $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "$trackedStat", 'stat' => $stat);
            array_push($data_points, $point);
          }
          break;
        case 'movingAvg':
          /*
                TODO NOT READY YET
                still working on this part. Not necessary for main functionality
                *****************************************************
                if($dataType == 'Players' || $dataType == 'Heroes'){
                  foreach($matchArr as $playerStats){
                    $valveID = $playerStats['ValveID'];
                    $radId = $playerStats['radiantTeamId'];
                    $radVictory = $playerStats['radiant_victory'];
                    $queueCount = $gameQueue->count();
                    if ($queueCount >= 20){
                      if($gameQueue->dequeue() == 'Win'){
                        $wins -= 1;
                      }
                    }
                    if($trackedObject == $playerStats['Nickname'] || $trackedObject == $playerStats['HeroName']){
                      if($trackedStat != 'winrate'){
                        //oops I never say what stat is lol.
                        $gameQueue->enqueue($stat);
                      } else{
                        if($trackedStat == 'winrate' && ($valveID == $radID && $radVictory == 1) || ($valveID != $radID && $radVictory == 0)){
                          $gameQueue->enqueue('Win');
                          $wins += 1;
                        } elseif($trackedStat == 'winrate'){
                          $gameQueue->enqueue('Loss');
                        }
                      }
                      $queueCount = $gameQueue->count();
                      $avg = $wins / $queueCount;
                      if($trackedStat == 'winrate'){
                        $avg = $wins / $queueCount;
                        $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "Result", 'stat' => $gameResult);
                      } else{
                        $avg = $stat / $queueCount;
                        $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "$trackedStat", 'stat' => $stat);
                      }
                      array_push($data_points, $point);
                    }
                  }
                } else{
                  if($trackedStat != 'winrate'){
                    $gameQueue->enqueue($stat);
                    $avg = queueTotal($gameQueue) / $queueCount;
                  } else{
                    if($trackedStat == 'winrate' && ($valveID == $radID && $radVictory == 1) || ($valveID != $radID && $radVictory == 0)){
                      $gameQueue->enqueue('Win');
                      $wins += 1;
                    } elseif($trackedStat == 'winrate'){
                      $gameQueue->enqueue('Loss');
                    }
                  }
                  $queueCount = $gameQueue->count();
                  $avg = $wins / $queueCount;
                  if($trackedStat == 'winrate'){
                    $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "Result", 'stat' => $gameResult);
                  } else $point = array('x' => $startDate, 'y' => $avg, 'id' => $matchId, 'other' => "$trackedStat", 'stat' => $stat);
                  array_push($data_points, $point);
                }*/
          break;
        case 'rawData':
          //TODO NOT STARTED YET
          //not necessary for main functionality
          break;
      }
    }
    //resetting variables before going through each row again
    $stat = 0;
    $matchArr = array();
    $heroArr = array();
    $playerArr = array();
    $withPlayerCon = false;
    $withHeroCon = false;
    $againstHeroCon = false;
    $againstPlayerCon = false;
    $factionCon = false;
    if ($row['radiant_victory'] == 1) {
      $radiantWin = true;
    } else $radiantWin = false;
    $winLoss = false;
  }
  //stores values of rows that are necessary to track later.
  $valveID = $row['ValveID'];
  $radID = $row['radiantTeamId'];
  $radVictory = $row['radiant_victory'];
  $startDate = $row['start_date'];
  $matchId = $row['match_id'];
  if ($trackedStat != 'winrate' && $dataType != 'Players' && $dataType != 'Heroes') {
    if($trackedStat == 'duration'){
      $stat = $row["$trackedStat"];
    }else $stat += $row["$trackedStat"];
  }
  array_push($matchArr, $row);
  $playerArr[$row['Nickname']] = array($row['ValveID'], $row['name'], $row['radiantTeamId']);
  $heroArr[$row['HeroName']] = array($row['ValveID'], $row['name'], $row['radiantTeamId']);
}


/* Encode this array in JSON form */
echo json_encode($data_points, JSON_NUMERIC_CHECK);
//print_r($data_points);

mysqli_close($connection);
