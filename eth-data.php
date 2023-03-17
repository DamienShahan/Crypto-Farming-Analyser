<?php

// DB
setlocale(LC_ALL, "de_DE.utf8");

$sql = new mysqli('localhost', 'robinhoo_crypto', 'xfGnyXCEPXxQTrGdAgW8f4U');
// check connection
if ($sql->connect_errno) {
    printf("Connect failed: %s\n", $sql->connect_error);
    exit();
}

$sql->select_db("robinhoo_crypto");
$sql->query("set names 'UTF8'"); 


// GET ETH VALUES
$url = "https://api.minerstat.com/v2/coins?list=ETH";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_json = curl_exec($ch);
curl_close($ch);
$response=json_decode($response_json, true);


// INSERT VALUES
$insert = "INSERT INTO `eth` (`networkHashRate`, `blockTime`, `ethUsd`, `difficulty`, `rewardBlock`) VALUES
(".$response[0]['network_hashrate'].", 13, ".number_format($response[0]['price'],10,'.','').", ".number_format($response[0]['difficulty'],12,'.','').", '".number_format($response[0]['reward_block'],12,'.','')."')";

//(902200258586666, 13, 3757.8774304114, 12394909805176760, 2.0797720063545)";

//var_dump($insert);
$inserten = $sql->query($insert);
?>


<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>insert</title>
</head>

<body>
    <p>inserted</p>
    <var><?php var_dump($insert); ?></var>
</body>
</html>


