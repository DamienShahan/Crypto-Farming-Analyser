<?php
session_start();

// Coin abbreveation
$coin = 'rvn';
$defaultPowerCost = 0.21;
$defaultPoolfee = 0;

// Check if data is to be reset
if(isset($_GET['reset'])) {
        if (isset($_GET['reset']) == "true") {
        // Get GPU values from backup CSV file
        $gpuList = array_map('str_getcsv', file('backupgpus.csv'));
        array_walk($gpuList, function(&$a) use ($gpuList) {
            $a = array_combine($gpuList[0], $a);
        });

        // Write backup GPU data to csv file    
        // Open a file in write mode ('w')
        $fp = fopen('rvn-gpus.csv', 'w');
        
        // Loop through file pointer and a line
        foreach ($gpuList as $fields) {
            fputcsv($fp, $fields);
        }
        
        fclose($fp);
    }
}

include 'functions.php';

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

// Get USD CHF EUR data
$select="SELECT * FROM currency ORDER BY lastUpdate DESC Limit 1";
$result = $sql->query($select);
$currency = $result->fetch_assoc();

// Calculate USD->CHF and CHF->USD exchange rates
$exchange_rate_usd_chf = $currency['usd'] / $currency['chf'];
$exchange_rate_chf_usd = $currency['chf'] / $currency['usd'];

// Get rvn data
$select="SELECT * FROM rvn ORDER BY lastUpdate DESC Limit 1";
$result = $sql->query($select);
$row = $result->fetch_assoc();

// GPU CONFIGS
$deletedGpus = false;
$addedGpu = false;
// Form resubmit protection
if((isset($_POST['newGpuName']) || isset($_POST['delete'])) && $_POST['randcheck']==$_SESSION['rand']) {
    // If new GPU added
    if(isset($_POST['newGpuName'])) {
        // Get GPU values from CSV file gpus.csv
        $gpuList = array_map('str_getcsv', file('rvn-gpus.csv'));
        array_walk($gpuList, function(&$a) use ($gpuList) {
            $a = array_combine($gpuList[0], $a);
        });

        // Get new GPU data
        $newGpu = array(
            [$_POST['newGpuId'],$_POST['newGpuName'],$_POST['newGpuPrice'],$_POST['newGpuHashrate'],$_POST['newGpuWatt']]
        );

        // Merge gpuList with new GPU data
        $csvdata = array_merge($gpuList, $newGpu);
        
        // Open a file in write mode ('w')
        $fp = fopen('rvn-gpus.csv', 'w');
        
        // Loop through file pointer and a line
        foreach ($csvdata as $fields) {
            fputcsv($fp, $fields);
        }
        
        fclose($fp);

        $addedGpu = true;

        /*** Write new GPU data to DB */
        $insert = "INSERT INTO `rvnGpus` (`id`, `name`, `price`, `mhs`, `watt`) VALUES
        ('".$_POST['newGpuId']."','".$_POST['newGpuName']."',".$_POST['newGpuPrice'].",".$_POST['newGpuHashrate'].",".$_POST['newGpuWatt'].")
        ";
        $inserten = $sql->query($insert);
    }

    // If GPU removed
    if(isset($_POST['delete'])) {
        #print_r($_POST['delete']);

        // Get GPU values from CSV file gpus.csv
        $gpuList = array_map('str_getcsv', file('rvn-gpus.csv'));
        array_walk($gpuList, function(&$a) use ($gpuList) {
            $a = array_combine($gpuList[0], $a);
        });

        for ($counter = 0; $counter < sizeof($_POST['delete']); $counter++) {
            // default key to check against
            $key = -1;
            $gpuList = array_values($gpuList);

            // Find array key to be deleted
            $key = array_search($_POST['delete'][$counter], array_column($gpuList, 'id'));
            // if key found, delete gpu from gpuList
            if($key != -1) {
                unset($gpuList[$key]);
            }
        }

        // Open a file in write mode ('w')
        $fp = fopen('rvn-gpus.csv', 'w');
        
        // Loop through file pointer and a line
        foreach ($gpuList as $fields) {
            fputcsv($fp, $fields);
        }
        
        fclose($fp);

        $deletedGpus = true;
    }
}

// If personal values set
if(isset($_POST['powercost'])) {
    $powerCost = $_POST['powercost'];
    $poolfee = $_POST['poolfee'];

    for ($counter = 0; $counter < sizeof($_POST['name']); $counter++) {
        $gpudata = [
            "id" => $_POST['id'][$counter],
            "name" => $_POST['name'][$counter],
            "price" => $_POST['price'][$counter],
            "mhs" => $_POST['mhs'][$counter],
            "watt" => $_POST['watt'][$counter],
        ];
        $gpuList[] = $gpudata;

        /*** Write new GPU data to DB */
        $insert = "INSERT INTO `rvnGpus` (`id`, `name`, `price`, `mhs`, `watt`) VALUES
        ('".$_POST['id'][$counter]."','".$_POST['name'][$counter]."',".$_POST['price'][$counter].",".$_POST['mhs'][$counter].",".$_POST['watt'][$counter].")
        ";
        $inserten = $sql->query($insert);
    }

    // Write new GPU data to csv file
    // CSV header
    $header = array(
        ["id","name","price","mhs","watt"]
    );

    // All CSV data
    $csvdata = array_merge($header, $gpuList);
    
    // Open a file in write mode ('w')
    $fp = fopen('rvn-gpus.csv', 'w');
    
    // Loop through file pointer and a line
    foreach ($csvdata as $fields) {
        fputcsv($fp, $fields);
    }
    
    fclose($fp);
}
else {
    $powerCost = $defaultPowerCost;
    $poolfee = $defaultPoolfee;

    // Get GPU values from CSV file gpus.csv
    $gpuList = array_map('str_getcsv', file('rvn-gpus.csv'));
    array_walk($gpuList, function(&$a) use ($gpuList) {
        $a = array_combine($gpuList[0], $a);
    });
    array_shift($gpuList); # remove column header

    // Sort the array by 'name' in descending order
    usort($gpuList, function($a, $b) {
        return strcmp($b['name'], $a['name']); // Use strcmp for string comparison
    });
}

#print_r($gpuList[0]);
#echo sizeof($gpuList);

// Calculations
for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
#    echo "The number is: $x <br>";
    $gpuList[$counter] += [
        "rvnPerDay" => ($gpuList[$counter]['mhs']/1000/($row['networkHashRate']/1000000000))*(3600/$row['blockTime']*$row['rewardBlock']*24)*(1-($poolfee/100)),
        "powerCostPerDay" => ceil($gpuList[$counter]['watt']*24/1000*$powerCost*100)/100,
    ];
    $gpuList[$counter] += [
        "revenuePerDay" => round($gpuList[$counter]['rvnPerDay']*$row['rvnUsd'],2),
        "revenueCHFPerDay" => round($gpuList[$counter]['rvnPerDay']*$row['rvnUsd']*$exchange_rate_usd_chf,2),
        "profitPerDay" => round($gpuList[$counter]['rvnPerDay']*$row['rvnUsd']-($gpuList[$counter]['powerCostPerDay']*$exchange_rate_chf_usd),2),
        "profitCHFPerDay" => round($gpuList[$counter]['rvnPerDay']*$row['rvnUsd']*$exchange_rate_usd_chf-$gpuList[$counter]['powerCostPerDay'],2),
    ];
}

// Market conditions:

// Get AVG(rvnUSD) from yesterday
$select="select AVG(rvnUsd) as rvnUsd, WEEK(lastUpdate) as lastweek from rvn GROUP BY lastweek ORDER BY lastweek desc limit 1, 1";
$result = $sql->query($select);
$rvnPriceLastWeek = $result->fetch_assoc();

// Green Market
if($row['rvnUsd'] >= $rvnPriceLastWeek['rvnUsd']) {
    if($row['rvnUsd'] >= $rvnPriceLastWeek['rvnUsd']*1.05) {
        $marketcolor = "green-market.png";
    }
    else {
        $marketcolor = "yellow-up.png";
    }
}
// Red Market
else {
    if($row['rvnUsd'] <= $rvnPriceLastWeek['rvnUsd']*0.95) {
        $marketcolor = "red-market.png";
    }
    else {
        $marketcolor = "yellow-down.png";
    }
}

// Form resubmit (F5 spam) protection
$rand=rand();
$_SESSION['rand']=$rand;

// Get all used GPU names from DB
$select="SELECT distinct(name) FROM rvnGpus ORDER BY name ASC";
$names = $sql->query($select);

?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Crypto Mining Spreadsheet</title>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
<!-- Optional theme -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap-theme.min.css" integrity="sha384-6pzBo3FDv/PJ8r2KRkGHifhEocL+1X2rVCTTkUfGk7/0pbek5mMa1upzvWbrUbOZ" crossorigin="anonymous">
<!-- Latest compiled and minified JavaScript -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="./js/main.js"></script>

<link href="./css/style.css" rel="stylesheet" type="text/css"><!-- CSS file -->
</head>

<body>
<div class="wrapper">
<form action="/" method="post">
    <?php 
    // Successfully added new GPU
    if($addedGpu) {
    ?>
        <div class="alert alert-success" role="alert">
        Successfully added new GPU to the list.
        </div>
    <?php
    }

    // Successfully removed GPU
    if($deletedGpus) {
        ?>
            <div class="alert alert-success" role="alert">
            Successfully removed GPU(s) from the list.
            </div>
        <?php
        }

    // Warning when rvn block reward is unusally high
    if($row['rewardBlock']>3000) {
    ?>
        <div class="alert alert-warning" role="alert">
        <b>Careful!</b> Unusually high rvnerum block rewards at the moment: <?php echo $row['rewardBlock']; ?><br>
        rvnerum block rewards are usually around 2500<br>
        <br>
        Unusually high block rewards skew the profit and ROI numbers unrealistically. Please check back in 30 minutes.
        </div>
    <?php
    }
    ?>
    <div class="container content">
        <div class="col-sm-9 infobox">
            <div>
                <div class="col-sm-2">Power cost</div><div class="col-sm-10"><div class="input_units"><input type="number" step="0.01" id="powercost" name="powercost" value="<?php echo $powerCost; ?>"><label for="powercost" class="units">CHF/kWh</label></div></div>
                <div class="col-sm-2">Pool fee</div><div class="col-sm-10"><div class="input_units"><input type="number" id="poolfee" name="poolfee" value="<?php echo $poolfee; ?>"><label for="poolfee" class="units">%</label></div></div>
                <div class="col-sm-2">rvn price</div><div class="col-sm-3 infofixtext"><div class="input_units col-sm-10"><?php echo $row['rvnUsd']; ?><label class="units">USD</label></div><img class="col-sm-2" width="20px" src="<?php echo $marketcolor; ?>" title="Average rvn price last week: $<?php echo round($rvnPriceLastWeek['rvnUsd'],2); ?>"/></div><div class="col-sm-7 infofixtext">(Last update: <?php echo $row['lastUpdate']; ?>)</div>
                <div class="col-sm-2">USD->CHF</div><div class="col-sm-3 infofixtext"><div class="input_units"><?php echo floor($exchange_rate_usd_chf*1000)/1000; ?><label class="units">CHF</label></div></div><div class="col-sm-7 infofixtext">(Last update: <?php echo $currency['lastUpdate']; ?>)</div>
                
            </div>
        </div>
        <div class="col-sm-3 nopadding">
            <input type="submit" class="button green" value="Calculate with personal values" style="width:100%">
            <button style="margin-top: 5px;" class="yellow col-sm-5" type="button" id="newGpuButton">Add GPU</button>
            <div class="col-sm-2"></div>
            <button style="margin-top: 5px;" class="brown col-sm-5" type="button" id="removeGpuButton">Remove GPUs</button>
            <a href="/?reset=true" onClick="return confirm('Are you sure you want to reset all GPU values?');"><button style="margin-top: 5px;" class="purple" type="button">Reset values</button></a>
        </div>
    </div> 
    
    <div class="content col-sm-12">
        <div class="col-sm-3 leftitles">
            <div>
                <p class="inputrow hover a1">
                    <b>GPU Name</b> 
                    <svg style="margin-bottom: -3px;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book gpuNameDictButton" viewBox="0 0 16 16">
                        <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                    </svg>
                    <div class="hidden gpuNameDict"><pre>
<strong>List of GPU names used:</strong>
<?php
$counter = 0;
while($curGpuName = $names->fetch_assoc()) {
if($counter > 0) {
echo "<br>";
}
echo $curGpuName['name'];
$counter++;
}
?>
</pre></div>
                </p>
                <p class="inputrow a2 hover">GPU Price</p>
                <p class="hover a3 sidetitle">Profit per Day per GPU</p>
                <p class="hover a4">Before Power Cost</p>
                <p class="hover a5">After Power Cost</p>
                <p class="hover a6">Power Cost per Day per GPU</p>				
                <p class="hover a7 sidetitle">Return % per Day</p>
                <p class="hover a8">Before Power Cost</p>
                <p class="hover a9">After Power Cost</p>
                <p class="hover a10 sidetitle">Return % per Month</p>
                <p class="hover a11">Before Power Cost</p>
                <p class="hover a12"> After Power Cost</p>
                <p class="hover a13 sidetitle">Return % per Year</p>
                <p class="hover a14">Before Power Cost</p>
                <p class="hover a15">After Power Cost</p>
                <p class="hover a16 sidetitle">Days to Break-Even</p>
                <p class="hover a17">Before Power Cost</p>
                <p class="hover a18">After Power Cost</p>
                <p class="hover a19 sidetitle">Efficiency RVN / Watt</p>
                <p class="hover a20">MH/s per Watt</p>
                <p class="inputrow a21 hover">Hashrate</p>
                <p class="inputrow a22 hover">Power consumption</p>
            </div>
        </div>
        <div class="col-sm-9 scrollcontent">
            <table class="table cryptosheet">
                <tr class="inputrow hover b1">
                    <?php
                    // GPU Name
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        #echo '<td><div class="input_units"><input type="text" id="'.$gpuList[$counter]['id'].'name" name="'.$gpuList[$counter]['id'].'name" value="'.$gpuList[$counter]['name'].'"></div></td>';
                        echo '<td><div class="input_units"><input type="text" id="'.$gpuList[$counter]['id'].'name" name="name[]" value="'.$gpuList[$counter]['name'].'"></div></td>';
                        echo '<td class="hidden"><div class="input_units"><input type="text" id="'.$gpuList[$counter]['id'].'id" name="id[]" value="'.$gpuList[$counter]['id'].'"></div></td>';
                    }
                    ?>
                </tr>
                <tr class="inputrow b2 hover">
                    <?php
                    // GPU Price
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo '<td><div class="input_units"><input type="number" step="0.01" id="'.$gpuList[$counter]['id'].'price" name="price[]"  value="'.$gpuList[$counter]['price'].'"><label id="'.$gpuList[$counter]['id'].'priceTrend" for="'.$gpuList[$counter]['id'].'price" class="units priceUnit">CHF</label></div></td>';
                    }
                    ?>
                </tr>
                <tr class="hover b3">
                    <!-- Profit per Day per GPU -->
                    <td colspan=<?php echo sizeof($gpuList); ?> class="sidetitle">&nbsp;</td>
                </tr>
                <tr class="hover b4">
                    <?php
                    // Before Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>CHF ".$gpuList[$counter]['revenueCHFPerDay']." ($".$gpuList[$counter]['revenuePerDay'].")</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b5">
                    <?php
                    // After Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>CHF ".$gpuList[$counter]['profitCHFPerDay']." ($".$gpuList[$counter]['profitPerDay'].")</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b6">
                    <?php
                    // Power Cost per Day per GPU
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>CHF ".$gpuList[$counter]['powerCostPerDay']."</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b7">
                    <!-- Return % per Day -->
                    <td colspan=<?php echo sizeof($gpuList); ?> class="sidetitle">&nbsp;</td>
                </tr>
                <tr class="hover b8">
                    <?php
                    // Before Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".round($gpuList[$counter]['revenueCHFPerDay']/$gpuList[$counter]['price']*100,2)."%</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b9">
                    <?php
                    // After Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".round($gpuList[$counter]['profitCHFPerDay']/$gpuList[$counter]['price']*100,2)."%</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b10">
                    <!-- Return % per Month -->
                    <td colspan=<?php echo sizeof($gpuList); ?> class="sidetitle">&nbsp;</td>
                </tr>
                <tr class="hover b11">
                    <?php
                    // Before Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".round($gpuList[$counter]['revenueCHFPerDay']*30/$gpuList[$counter]['price']*100,2)."%</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b12">
                    <?php
                    // After Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".round($gpuList[$counter]['profitCHFPerDay']*30/$gpuList[$counter]['price']*100,2)."%</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b13">
                    <!-- Return % per Year -->
                    <td colspan=<?php echo sizeof($gpuList); ?> class="sidetitle">&nbsp;</td>
                </tr>
                <tr class="hover b14">
                    <?php
                    // Before Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".round($gpuList[$counter]['revenueCHFPerDay']*365/$gpuList[$counter]['price']*100,2)."%</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b15">
                    <?php
                    // After Power Cost

                    // COLORS
                    $yearRevenues = [];
                    $yearRev = "yearRev";
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        ${$yearRev.$gpuList[$counter]['id']} = round($gpuList[$counter]['profitCHFPerDay']*365/$gpuList[$counter]['price']*100,2);
                        array_push($yearRevenues, ${$yearRev.$gpuList[$counter]['id']});
                    }

                    // Values needed for colorGradient function
                    $revYearMax = max($yearRevenues);
                    $revYearMin = min($yearRevenues);
                    $revYearDiff = $revYearMax - $revYearMin;

                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        # check if value is less that 0, then color = red
                        if (${$yearRev.$gpuList[$counter]['id']} <= 0) {
                            $colorGradientValue = '#ea9999';
                        }
                        else {
                            $colorGradientValue = colorGradient((${$yearRev.$gpuList[$counter]['id']}-$revYearMin)/$revYearDiff);
                        }
                        echo "<td style='background-color:".$colorGradientValue."'>".${$yearRev.$gpuList[$counter]['id']}."%</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b16">
                    <!-- Days to Break-Even -->
                    <td colspan=<?php echo sizeof($gpuList); ?> class="sidetitle">&nbsp;</td>
                </tr>
                <tr class="hover b17">
                    <?php
                    // Before Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".ceil($gpuList[$counter]['price']/$gpuList[$counter]['revenueCHFPerDay'])."</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b18">
                    <?php
                    // After Power Cost
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td>".ceil($gpuList[$counter]['price']/$gpuList[$counter]['profitCHFPerDay'])."</td>";
                    }
                    ?>
                </tr>
                <tr class="hover b19">
                    <!-- Efficiency rvn / Watt -->
                    <td colspan=<?php echo sizeof($gpuList); ?> class="sidetitle">&nbsp;</td>
                </tr>
                <tr class="hover b20">
                    <?php
                    // MH/s per Watt

                    // COLORS
                    $effMHwatt = [];
                    $effMhw = "effMhw";
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        ${$effMhw.$gpuList[$counter]['id']} = round($gpuList[$counter]['mhs']/$gpuList[$counter]['watt'],3);
                        array_push($effMHwatt, ${$effMhw.$gpuList[$counter]['id']});
                    }

                    // Values needed for colorGradient function
                    $mhwYearMax = max($effMHwatt);
                    $mhwYearMin = min($effMHwatt);
                    $mhwYearDiff = $mhwYearMax - $mhwYearMin;

                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo "<td style='background-color:".colorGradient((${$effMhw.$gpuList[$counter]['id']}-$mhwYearMin)/$mhwYearDiff)."'>".${$effMhw.$gpuList[$counter]['id']}."</td>";
                    }
                    ?>
                </tr>
                <tr class="inputrow b21 hover">
                    <?php
                    // Hashrate
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo '<td><div class="input_units"><input type="number" step="0.1" id="'.$gpuList[$counter]['id'].'mhs" name="mhs[]"  value="'.$gpuList[$counter]['mhs'].'"><label for="'.$gpuList[$counter]['id'].'mhs" class="units">MH/s</label></div></td>';
                    }
                    ?>
                </tr>
                <tr class="inputrow b22 hover">
                    <?php
                    // Power consumption
                    for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
                        echo '<td><div class="input_units"><input type="number" step="0.1" id="'.$gpuList[$counter]['id'].'watt" name="watt[]"  value="'.$gpuList[$counter]['watt'].'"><label for="'.$gpuList[$counter]['id'].'watt" class="units">W</label></div></td>';
                    }
                    ?>
                </tr>
            </table>
        </div>
    </div>
    <div class="clearer"></div>
    <div class="content">
        <p style="margin-bottom:10px;"><strong>Calculation Info:</strong></p>
    <figure><pre>
RVN Network Hashrate:   <?php echo $row['networkHashRate']/1000000000000; ?> TH/s 
Mining Block Time:      <?php echo $row['blockTime']; ?>s 
RVN Price:              $<?php echo $row['rvnUsd']; ?> 
Mining Difficulty:      <?php echo $row['difficulty']; ?> 
Block Reward:           <?php echo $row['rewardBlock']; ?> rvn</pre></figure>
    </div>
    <div class="content hidden_values">
        <p style="margin-bottom:10px;"><strong>To Do:</strong></p>
    <figure><pre>
- Optimize display of GPU price trend line
- Add close X button to the top right of GPU price trend display
- Add option to delete name from GPU name dictionary
- Check if it is worth more to payout RVN directly to CHF, instead of RVN->USD->CHF</pre></figure>
    </div>
</form>
</div>
<div id="addGpuWindow" class="addGpu content">
    <form action="/" method="post">
        <div class="col-sm-2"></div>
        <h2 class="col-sm-8">Add new GPU</h2>
        <button type="button" class="close col-sm-2" aria-label="Close" id="closeNewGpuWindow">
            <span aria-hidden="true">&times;</span>
        </button>
        <div class="clearer"></div>
        <p>GPU Name* <div class="input_units"><input type="text" id="newGpuName" name="newGpuName" required></div></p>
        <p>GPU Price* <div class="input_units"><input type="number" step="0.01" id="newGpuPrice" name="newGpuPrice" required><label for="newGpuPrice" class="units">CHF</label></div></p>
        <p>Hashrate* <div class="input_units"><input type="number" step="0.1" id="newGpuHashrate" name="newGpuHashrate" required><label for="newGpuHashrate" class="units">MH/s</label></div></p>
        <p>Power consumption* <div class="input_units"><input type="number" step="0.1" id="newGpuWatt" name="newGpuWatt" required><label for="newGpuWatt" class="units">W</label></div></p>
        <input type="hidden" id="newGpuId" name="newGpuId" value="<?php echo round(microtime(true) * 1000); ?>">
        <input type="hidden" value="<?php echo $rand; ?>" name="randcheck" />
        <input type="submit" class="button yellow" value="Add New GPU" style="width:100%">
    </form>
</div>
<div id="removeGpuWindow" class="addGpu content">
    <form action="/" method="post">
    <div class="col-sm-2"></div>
        <h2 class="col-sm-8">Remove GPUs</h2>
        <button type="button" class="close col-sm-2" aria-label="Close" id="closeRemoveGpuWindow">
            <span aria-hidden="true">&times;</span>
        </button>
        <div class="clearer"></div>

        <?php
        // List GPUs as checkboxes
        for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
            echo '<div class="form-check">';
                echo '<input class="form-check-input" type="checkbox" id="'.$gpuList[$counter]['id'].'delete" name="delete[]" value="'.$gpuList[$counter]['id'].'">';
                echo '<label class="form-check-label" for="'.$gpuList[$counter]['id'].'delete">'.$gpuList[$counter]['name'].'</label>';
            echo '</div>';
        }
        ?>

        <div class="clearer"></div>
        <input type="hidden" value="<?php echo $rand; ?>" name="randcheck" />
        <input type="submit" class="button brown" value="Remove GPUs" style="width:100%">
    </form>
</div>
<?php
// Pop-Up iframes of GPU price timelines
for ($counter = 0; $counter < sizeof($gpuList); $counter++) {
    echo '<div id="'.$gpuList[$counter]['id'].'priceTrendPage" class="gpuPriceTrend hidePriceDisplay">';
    echo '<iframe src="https://crypto.robinhood-seo.com/priceTrend.php?name='.$gpuList[$counter]['name'].'" title="'.$gpuList[$counter]['name'].' Price Trend" style="width:600px;height:350px;"></iframe>';
    echo '</div>';
}
?>
</body>
</html>