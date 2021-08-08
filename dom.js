$(document).ready(function() {
    //causes only needed fields to show
    $('#trackedObject, #trackedObjectLabel, #factionDiv, .factionStat, .playerStat, #winloss, #factioncheck').hide();
    $('.gameStat').show();

    $('#dataType').on('change', function() {
        $('#trackedObject').val('');
        if ($('#dataType').val() == 'matchData') {
            $('#trackedObject, #trackedObjectLabel, #factionDiv, .factionStat, .playerStat, #winloss, #factionCheck ').hide();
            $('.gameStat').show();

        } else if (($('#dataType').val() == 'faction')) {
            $('#trackedObject, #trackedObjectLabel, .playerStat, .gameStat, #factionCheck').hide();
            $('#factionDiv, .factionStat, #winloss').show();
        } else {
            $('#factionDiv, .factionStat, .gameStat').hide();
            $('#trackedObject, #trackedObjectLabel, .playerStat, #winloss, #factionCheck').show();
        }

    });
    $('#trackedStat').on('change', function() {
        if ($('#trackedStat').val() == 'winrate') {
            if ($('dataFormat').val() == 'raw') {
                $('#dataFormat').val('');
            }
            $('#raw').hide();
        } else $('#raw').show();
    });

    //dropdown filter menu
    $('.dropbtn').click(function() {
        $('#filterDiv').toggleClass('show');
    });
    $('#submit').click(function() {
        $('#filterDiv.show').toggleClass('show');
    });

    //autocomplete for textboxes
    $('#trackedObject').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "./gethint.php",
                dataType: "json",
                data: {
                    q: request.term,
                    value: $('#dataType').val()
                },
                success: function(data) {
                    response(data);
                }
            });
        }
    });

    $('#team').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "./gethint.php",
                dataType: "json",
                data: {
                    q: request.term,
                    value: 'Teams',
                },
                success: function(data) {
                    response(data);
                }
            });
        }
    });

    $('#withPlayer, #againstPlayer').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "./gethint.php",
                dataType: "json",
                data: {
                    q: request.term,
                    value: 'Players',
                },
                success: function(data) {
                    response(data);
                }
            });
        }
    });

    $('#withHero, againstHero').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "./gethint.php",
                dataType: "json",
                data: {
                    q: request.term,
                    value: 'Heroes',
                },
                success: function(data) {
                    response(data);
                }
            });
        }
    });
});