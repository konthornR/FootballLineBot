<?php

use Football\Manager;

require_once   __DIR__ . "/vendor/autoload.php";

$manager = Manager::getInstance();
$config = $manager->getConfig();

$accessToken = $config['accessToken'];

$content = file_get_contents('php://input');
$arrayJson = json_decode($content, true);

$arrayHeader = array();
$arrayHeader[] = "Content-Type: application/json";
$arrayHeader[] = "Authorization: Bearer {$accessToken}";

register_shutdown_function( 'shutdownFunction', [
    'arrayHeader' => $arrayHeader,
    'arrayJson' => $arrayJson,
    'config' => $config,
]);

$message = $arrayJson['events'][0]['message']['text'];
$fromGroupID = $arrayJson['events'][0]['source']['groupId'];

try {
    $teams = $manager->getTeams();
    $fromTeamName = isset($teams[$fromGroupID]) ? $teams[$fromGroupID]->getName() : '';

    if(strpos($message, '-bc') !== false) {
        // Broadcast msg
        $content = trim(substr($message, 3)) . "\n >Msg from {$fromTeamName}";
        foreach($teams as $team) {
            if ($team->getGroupLineID() != $fromGroupID) {
                pushMsg($arrayHeader, $team->getGroupLineID(), $content);
            }
        }
    } else if(strpos($message, '-r') !== false) {
        // Show report
        $content = $manager->getTeamsReport();
        replyMsg($arrayHeader, $arrayJson, $content);
    } else if(strpos($message, '-h') !== false) {
        // Show help msg.
    } else if(strpos($message, '-s') !== false) {
        $content = 'Thursday football kub?';
        if($manager->startCount()) {
            foreach($teams as $team) {
                pushMsg($arrayHeader, $team->getGroupLineID(), $content);
            }
        }
    }else if(strpos($message, '-e') !== false) {
        $manager->endCount();
    }else if(strpos($message, '-id') !== false) {
        replyMsg($arrayHeader, $arrayJson, $fromGroupID);
    }else {
        $manager->process($message, $fromGroupID);
    }
} catch(Exception $e) {
    pushMsg($arrayHeader, $config['adminLineID'], $e->__toString());
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
        pushMsg($var['arrayHeader'], $var['config']['adminLineID'], $error['message']);
    }
}








