$(document).ready(function() {

    function secToHMS(sec) {
        var time = sec;
        var h = parseInt(time / 3600);
        var m = parseInt((parseInt(time % 3600)) / 60);
        var s = time - (h * 3600) - (m * 60)
        h = h < 10 ? '0' + h : h;
        m = m < 10 ? '0' + m : m;
        s = s < 10 ? '0' + s : s;
        sec = s.toString();
        if (sec.length > 2) sec = sec.slice(0, 2);
        return h + ':' + m + ':' + sec;
    }

    function toPercent(num) {
        percent = num * 100;
        percent = percent.toString();
        if (percent.length > 6) {
            percent = percent.slice(0, 5);
        }
        percent += '%';
        return percent
    }

    //getting data from JSON after Button click.
    $("form").on('submit', (function() {
        event.preventDefault();
        var formData = $('form').serialize();
        var toupleArr = [];
        $.when($.getJSON('dataPoints.php', formData, function(result) {
            $(result).each(function(i, entry) {
                toupleArr.push({
                    x: entry['x'],
                    y: entry['y'],
                    matchid: entry['id'],
                    trackedStat: entry['other'],
                    stat: entry['stat']
                })
            })
        })).then(function() {
            //create chart
            chartType = '';
            yTitle = $('#trackedStat').val();
            yFormat = '';
            lineName = $('#trackedObject').val() + ' ' + $('#trackedStat option:selected').text();
            yMin = null;
            yMax = null;

            if ($('#dataFormat').val() == 'raw') {
                chartType = 'scatter';
            } else chartType = 'line';

            if ($('#trackedStat').val() == 'winrate') {
                yFormat = 'percent';
                yMax = 1;
                yMin = 0;
            } else if ($('#trackedStat').val() == 'duration') {
                yFormat = 'time';
            } else {
                yFormat = 'auto';
                yScale = 'auto';
            }

            Highcharts.chart('container', {

                chart: {
                    type: chartType,
                    zoomType: 'xy'
                },
                title: {
                    text: 'Testing'
                },

                subtitle: {
                    text: 'Source: datdota.com'
                },

                min: yMin,
                max: yMax,

                yAxis: {

                    title: {
                        text: yTitle
                    },
                    labels: {
                        formatter: function() {
                            if (yFormat == 'time') {
                                return secToHMS(this.value)
                            } else if (yFormat == 'percent') {
                                return toPercent(this.value);
                            } else return this.value;
                        }
                    }
                },

                xAxis: {
                    title: {
                        text: 'Date'
                    },
                    type: 'datetime'
                },
                tooltip: {
                    pointFormat: "<span style='color:{point.color}'>\u25CF</span> {series.name}: <b>{point.label}</b><br/>"
                },

                legend: {
                    layout: 'vertical',
                    align: 'right',
                    verticalAlign: 'middle'
                },

                plotOptions: {
                    series: {
                        label: {
                            connectorAllowed: false
                        }
                    }
                },

                series: [{
                    data: toupleArr,
                    turboThreshold: 0,
                    name: lineName
                }],

                responsive: {
                    rules: [{
                        condition: {
                            maxWidth: 500
                        },
                        chartOptions: {
                            legend: {
                                layout: 'horizontal',
                                align: 'center',
                                verticalAlign: 'bottom'
                            }
                        }
                    }]
                },

                tooltip: {
                    formatter: function() {
                        if (yFormat == 'time') {
                            return secToHMS(this.y) + '<br><br>' +
                                'Match ID: ' + this.point.matchid + '<br></br>' +
                                this.point.trackedStat + ': ' + secToHMS(this.point.stat)
                        } else if (yFormat == 'percent') {
                            return toPercent(this.y) + '<br><br>' +
                                'Match ID: ' + this.point.matchid + '<br></br>' +
                                this.point.trackedStat + ': ' + this.point.stat
                        }
                        return this.y + '<br><br>' +
                            'Match ID: ' + this.point.matchid + '<br></br>' +
                            this.point.trackedStat + ': ' + this.point.stat
                    }
                }

            })
        }); //end making chart


    }));
});