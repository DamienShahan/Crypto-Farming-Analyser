<?php

// DB
setlocale(LC_ALL, "en_EN.utf8");

$sql = new mysqli('localhost', 'robinhoo_crypto', 'xfGnyXCEPXxQTrGdAgW8f4U');
// check connection
if ($sql->connect_errno) {
    printf("Connect failed: %s\n", $sql->connect_error);
    exit();
}

$sql->select_db("robinhoo_crypto");
$sql->query("set names 'UTF8'"); 

// Get data from DB
$select="SELECT *, DATE_FORMAT(lastUpdate, '%d-%m-%Y') as lastDate FROM gpus WHERE name='".$_GET['name']."' ORDER BY lastDate";
$result = $sql->query($select);

?>
<!doctype html>
<html style="overflow: hidden;width:300px;height:200px;">
<head>
    <meta charset="utf-8">
    <title>Crypto Mining Spreadsheet</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap-theme.min.css" integrity="sha384-6pzBo3FDv/PJ8r2KRkGHifhEocL+1X2rVCTTkUfGk7/0pbek5mMa1upzvWbrUbOZ" crossorigin="anonymous">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <link href="./css/style.css" rel="stylesheet" type="text/css"><!-- CSS file -->

    <?php
    // Get data for google.js
    $chartData = [];
    $chartHeader = ['Update', 'CHF'];
    #array_push($chartData, $chartHeader);
    while($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $dataRow = [date('d-m-Y', strtotime($row['lastDate'])), intval($row['price'])];
        array_push($chartData, $dataRow);
    }
    #print_r($chartData);
    ?>
 
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">

    // Load the Visualization API library and the piechart library.
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart(chartData) {

        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Update'); // Implicit domain column.
        data.addColumn('number', 'CHF'); // Implicit data column.

        data.addRows(
            <?php echo json_encode($chartData);?> 
        );

        var options = {
            title: '<?php echo $name; ?> Price Timeline',
            //curveType: 'function',
            legend: { position: 'bottom' },
            'chartArea': {
                'width': '90%', 
                'height': '60%', 
                right:'2%'
            },
            legend:{textStyle:{fontSize:12}},
            tooltip:{textStyle:{fontSize:12}},
            titleTextStyle:{fontSize:16},
            'width':600,
            'height':350,
            hAxis: {
                format: 'dd/MM',
                gridlines: {count: 15},
                textStyle:{fontSize:12},
                slantedText: true,
                slantedTextAngle: 45,
            },
            vAxis: {
                textStyle:{fontSize:12},
                minValue: 0
            },
            displayLegendDots: true,
            displayLegendValues: true
        };

        //var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        var chart = new google.visualization.SteppedAreaChart(document.getElementById('chart_div'));

        chart.draw(data, options);
    }
    </script>
</head>

<body>
    <div id="chart_div"></div>	
</body>
</html>