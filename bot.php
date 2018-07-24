<?php

use Football\Manager;

require_once   __DIR__ . "/vendor/autoload.php";

$manager = Manager::getInstance();

$accessToken = getenv('ACCESS_TOKEN');
$adminLineID = getenv('ADMIN_LINE_ID');

$content = file_get_contents('php://input');
$arrayJson = json_decode($content, true);

$arrayHeader = array();
$arrayHeader[] = "Content-Type: application/json";
$arrayHeader[] = "Authorization: Bearer {$accessToken}";

register_shutdown_function( 'shutdownFunction', [
    'arrayHeader' => $arrayHeader,
    'arrayJson' => $arrayJson,
    'adminLineID' => $adminLineID,
]);

$message = $arrayJson['events'][0]['message']['text'] ?? null;
$fromGroupID = $arrayJson['events'][0]['source']['groupId'] ?? null;

if (empty($message) || empty($fromGroupID)) exit;

try {
    $teams = $manager->getTeams();
    if (!isset($teams[$fromGroupID])) exit;

    $fromTeamName = isset($teams[$fromGroupID]) ? $teams[$fromGroupID]->getName() : '';

    if(substr($message, 0, 3) == '-bc') {
        // Broadcast msg
        $content = "Broadcast: \n" . trim(substr($message, 3)) . "\n >Msg from {$fromTeamName}";
        /** @var \Football\Team $team */
		foreach($teams as $team) {
			pushMsg($arrayHeader, $team->getGroupLineID(), $content);
        }
    } else if(substr($message, 0, 2) == '-r') {
        // Show report
        $content = $manager->getTeamsReport();
        replyMsg($arrayHeader, $arrayJson, $content);
    } else if(substr($message, 0, 2) == '-h') {
		replyMsg($arrayHeader, $arrayJson,
			"1 = มา \n 0 = ไม่มา \n -1 = กดมาแล้วเปลี่ยนใจ \n ------ \n commands: \n -r = show report \n -bc msg = broadcast msg to other groups"
		);
    }else if(strpos($message, '-id') !== false) {
        replyMsg($arrayHeader, $arrayJson, $fromGroupID);
    }else {
        $manager->process($message, $fromGroupID);
    }
} catch(Exception $e) {
    pushMsg($arrayHeader, $adminLineID, $e->__toString());
}

function replyMsg($arrayHeader, $arrayJson, $content){
    $strUrl = "https://api.line.me/v2/bot/message/reply";
    $arrayPostData['replyToken'] = $arrayJson['events'][0]['replyToken'];
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

function shutDownFunction($var) {
    $error = error_get_last();
    if ($error) {
        pushMsg($var['arrayHeader'], $var['adminLineID'], $error['message']);
    }
}








