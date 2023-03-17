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
$url = "http://api.exchangeratesapi.io/v1/latest?access_key=181a3232bcb2643a021a1665f96f4b7f&symbols=USD,CHF";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_json = curl_exec($ch);
curl_close($ch);
$response=json_decode($response_json, true);

//var_dump($response);


// INSERT VALUES
$insert = "INSERT INTO `currency` (`eur`, `chf`, `usd`) VALUES
(1, ".number_format($response['rates']['USD'],6,'.','').", ".number_format($response['rates']['CHF'],6,'.','').")";
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


