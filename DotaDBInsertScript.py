import mysql.connector
import requests
import json
import time
from string import punctuation
from future.builtins import isinstance


myDb = mysql.connector.connect(
    host="",
    user="",
    passwd="",
    database=""
)
myCursor = myDb.cursor()
valveAPIKey = '0F562F42FE6E83E3F184A2A31641A17C'


def getMatchJson(matchId):
    """Uses match ID to get json full of match data from datdota.com"""
    time.sleep(1)
    response = requests.get(
        "https://datdota.com/api/matches/{}".format(matchId))
    newMatchJson = json.loads(response.text)
    return newMatchJson


def insert_string(str, char, pos):
    return str[:pos] + char + str[pos:]


def getPlayerPerformances(teamEntryID, performances, match_id):
    for entry in performances:
        player = entry['player']
        playerName = str(player['nickname'])

        # checking for special characters in player names
        pos = 0
        specChars = 0
        posList = []
        for char in playerName:
            if char in punctuation:
                specChars += 1
                posList.append(pos)
            pos += 1
        adder = 0
        for pos in posList:
            playerName = insert_string(playerName, '\\', pos + adder)

        playerSteam32 = str(player['steam32'])
        # insert player data and grab entryID
        formatString = playerSteam32 + ', "' + playerName + '", ' + teamEntryID
        addString = 'INSERT INTO Players (valveID, Nickname, TeamEntryID) VALUES ({})'.format(
            formatString)
        myCursor.execute(addString)
        myDb.commit()
        checkString = 'SELECT MAX(PlayerEntryID) FROM Players'
        myCursor.execute(checkString)
        output = myCursor.fetchall()
        playerEntryID = str(output[0][0])

        # grab player performance data
        playerPerformance = entry['performance']
        playerHero = playerPerformance['hero']
        heroID = str(playerHero['valve_id'])
        heroName = str(playerHero['short_name'])
        playerLevel = str(playerPerformance['level'])
        playerKills = str(playerPerformance['kills'])
        playerDeaths = str(playerPerformance['deaths'])
        playerAssists = str(playerPerformance['assists'])
        playerXPM = str(playerPerformance['xpm'])
        playerGPM = str(playerPerformance['gpm'])
        buildingDamage = str(playerPerformance['building_damage'])
        heroDamage = str(playerPerformance['hero_damage'])
        heroHealing = str(playerPerformance['hero_healing'])
        endGameGold = str(playerPerformance['end_game_gold'])
        goldSpent = str(playerPerformance['gold_spent'])
        items = playerPerformance['items']

        # insert Hero into table if not already present
        checkString = 'SELECT * FROM Heroes WHERE HeroID = {}'.format(heroID)
        myCursor.execute(checkString)
        output = myCursor.fetchall()
        if not len(output) >= 1:
            formatString = heroID + ', "' + heroName + '"'
            executionString = 'INSERT INTO Heroes (HeroID, HeroName) VALUES ({})'.format(
                formatString)
            myCursor.execute(executionString)
            myDb.commit()

        # insert player performance data that is not item or ability data
        formatString = '"' + match_id + '", ' + heroID + ', ' + playerLevel + ', ' + playerKills + ', ' + playerDeaths + ', ' + playerAssists + ', ' + playerGPM + \
            ', ' + playerXPM + ', ' + buildingDamage + ', ' + heroDamage + ', ' + \
            heroHealing + ', ' + endGameGold + ', ' + goldSpent + ', ' + playerEntryID
        playerExecutionString = 'INSERT INTO PlayerPerformances (match_id, heroID, Level, kills, Deaths, assists, gpm, xpm, buildingDamage, heroDamage, heroHealing, endGameGold, goldSpent, playerEntryID) VALUES ({})'.format(
            formatString)
        myCursor.execute(playerExecutionString)
        myDb.commit()
        # grab EntryID
        checkString = 'SELECT MAX(PlayerPerformanceID) FROM PlayerPerformances'
        myCursor.execute(checkString)
        playerPerformanceID = str(myCursor.fetchall()[0][0])

        # grab and insert item data
        for purchase in items:
            item_id = str(purchase['item_id'])
            time = str(purchase['time'])
            name = purchase['name']
            checkString = 'SELECT * FROM Items WHERE itemID = {}'.format(
                item_id)
            myCursor.execute(checkString)
            output = myCursor.fetchall()
            if not len(output) >= 1:
                formatString = item_id + ', "' + name + '"'
                itemExecutionString = 'INSERT INTO Items (itemID, itemName) VALUES ({})'.format(
                    formatString)
                myCursor.execute(itemExecutionString)
                myDb.commit()

            formatString = time + ', ' + playerPerformanceID + ', ' + item_id
            itemExecutionString = 'INSERT INTO ItemPurchases (time, playerPerformanceID, ItemID) VALUES ({});'.format(
                formatString)
            myCursor.execute(itemExecutionString)
            myDb.commit()

        # grab and insert ability data
        abilities = playerPerformance['abilities']
        for skill in abilities:
            ability_id = str(skill['ability_id'])
            time = str(skill['time'])
            name = str(skill['name'])
            checkString = 'SELECT * FROM Abilities WHERE abilityID = {}'.format(
                ability_id)
            myCursor.execute(checkString)
            output = myCursor.fetchall()
            if not len(output) >= 1:
                formatString = ability_id + ', "' + name + '"'
                itemExecutionString = 'INSERT INTO Abilities (AbilityID, abilityName) VALUES ({})'.format(
                    formatString)
                myCursor.execute(itemExecutionString)
                myDb.commit()

            formatString = time + ', ' + playerPerformanceID + ', ' + ability_id
            abilityExecutionString = 'INSERT INTO AbilitiesSkilled (time, playerPerformanceID, AbilityID) VALUES ({})'.format(
                formatString)
            myCursor.execute(abilityExecutionString)
            myDb.commit()
    print(str(match_id) + ' inserted player data')


def insertMatchData(matchJson):
    # grab match data in order of appearance and convenience
    data = matchJson["data"]
    match_id = str(data["match_id"])
    duration = str(data["duration"])
    radiant_victory = data['radiant_victory']
    if radiant_victory == False:
        radiant_victory = str(0)
    else:
        radiant_victory = str(1)
    has_error = data['has_error']
    if has_error == False:
        has_error = str(0)
    else:
        has_error = str(1)
    patch = str(data['patch'])
    start_date = str(data['start_date'])
    state = data['state']
    state_web = state['web']
    if state_web == False:
        state_web = str(0)
    else:
        state_web = str(1)
    state_parser = state['parser']
    if state_parser == False:
        state_parser = str(0)
    else:
        state_parser = str(1)
    state_audio = state['audio']
    if state_audio == False:
        state_audio = str(0)
    else:
        state_audio = str(1)

    # grab league info
    league = data['league']
    league_id = str(league['league_id'])
    league_name = league['name']
    radiant = data['radiant']
    dire = data['dire']
    # log league if not in already
    checkString = "SELECT * FROM Leagues WHERE leagueID = {}".format(league_id)
    myCursor.execute(checkString)
    output = myCursor.fetchall()
    if not len(output) >= 1:
        formatString = league_id + ", '" + league_name + "'"
        addString = 'INSERT INTO Leagues (leagueID, LeagueName) VALUES ({})'.format(
            formatString)
        myCursor.execute(addString)
        myDb.commit()
        print(str(match_id) + "League logged")
    

    # grab team data
    radiant_team = radiant['team']
    radiant_team_name = radiant_team['name']
    radiantTeamID = str(radiant_team['valve_id'])
    dire_team = dire['team']
    dire_team_name = dire_team['name']
    direTeamID = str(dire_team['valve_id'])

    # insert match data into tables
    matchColumnString = "match_id, duration, radiant_victory, has_error, patch, start_date, state_web, state_parser, state_audio, league_id, radiantTeamID, direTeamID"
    matchValueString = "'" + match_id + "', " + duration + ", " + radiant_victory + ", " + has_error + ", " + patch + ", '" + start_date + \
        "', " + state_web + ", " + state_parser + ", " + state_audio + \
        ", " + league_id + ", " + radiantTeamID + ", " + direTeamID
    matchesExecutionString = "INSERT INTO MatchData ({}) VALUES ({})".format(
        matchColumnString, matchValueString)
    myCursor.execute(matchesExecutionString)
    myDb.commit()
    print(str(match_id) + " inserted match data")

    # insert Team Data and grab entryID
    formatString = radiantTeamID + ', "' + \
        radiant_team_name + '", "' + match_id + '"'
    addString = 'INSERT INTO Teams (ValveID, name, match_id) VALUES ({})'.format(
        formatString)
    myCursor.execute(addString)
    myDb.commit()
    checkString = 'SELECT MAX(TeamEntryID) FROM Teams'
    myCursor.execute(checkString)
    output = myCursor.fetchall()
    radiantEntryID = str(output[0][0])
    formatString = direTeamID + ', "' + dire_team_name + '", "' + match_id + '"'
    addString = 'INSERT INTO Teams (ValveID, name, match_id) VALUES ({})'.format(
        formatString)
    myCursor.execute(addString)
    myDb.commit()
    checkString = 'SELECT MAX(TeamEntryID) FROM Teams'
    myCursor.execute(checkString)
    output = myCursor.fetchall()
    direEntryID = str(output[0][0])
    print(str(match_id) + " inserted team data")

    # grab player data
    radiant_performances = radiant['player_performances']
    dire_performances = dire['player_performances']
    getPlayerPerformances(radiantEntryID, radiant_performances, match_id)
    getPlayerPerformances(direEntryID, dire_performances, match_id)

    print(str(match_id) + ' entered successfully')


# beginning of run code
leagueQuery = "SELECT LeagueID, Season FROM Leagues WHERE Season = (SELECT MAX(Season) FROM Leagues)"
myCursor.execute(leagueQuery)
leagueList = myCursor.fetchall()
matchesQuery = "Select match_id From DotaBase.MatchData"
myCursor.execute(matchesQuery)
myMatchResult = myCursor.fetchall()
myMatchIDList = []
for match in myMatchResult:
    myMatchIDList.append(match[0])

for league in leagueList:
    response = requests.get("http://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/v1?key={}&league_Id={}".format(
        (valveAPIKey), str(league[0])))
    matchList = response.json()
    for match in matchList["result"]["matches"]:
        matchID = match['match_id']
        if str(matchID) not in myMatchIDList:
            try:
                print("entering " + str(matchID))
                matchJson = getMatchJson(matchID)
                insertMatchData(matchJson)
            except:
                print(str(matchID) + "                               failed.")
        else:
            print("skipped " + str(matchID))

print("finished!")
