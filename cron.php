<?php

require_once   __DIR__ . "/vendor/autoload.php";

date_default_timezone_set('Asia/Thailand');

$accessToken = getenv('ACCESS_TOKEN');
$adminLineID = getenv('ADMIN_LINE_ID');

$arrayHeader = array();
$arrayHeader[] = "Content-Type: application/json";
$arrayHeader[] = "Authorization: Bearer {$accessToken}";

pushMsg($arrayHeader, $adminLineID, "cron started: " . date('d/m/Y h:i:s a', time()));

function pushMsg($arrayHeader, $toID, $content){
    $strUrl = "https://api.line.me/v2/bot/message/push";

    $arrayPostData['to'] = $toID;
    $arrayPostData['messages'][0]['type'] = "text";
    $arrayPostData['messages'][0]['text'] = $content;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$strUrl);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($arrayPostData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close ($ch);
}