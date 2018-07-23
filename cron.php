<?php

use Football\Manager;

require_once   __DIR__ . "/vendor/autoload.php";

date_default_timezone_set('Asia/Bangkok');


$adminLineID = getenv('ADMIN_LINE_ID');

$currentDateTime = new DateTime();

$manager = Manager::getInstance();

if ($manager->isCountStart()) {
	$forThursday = DateTime::createFromFormat('d-m-Y', $manager->getForDate());
	$interval = $nextThursday->diff($currentDateTime);
	$diffHour = ($interval->days * 24) + $interval->h;

	pushMsg($adminLineID, "for Thursday: " . $forThursday->format('d-m-Y'));
	pushMsg($adminLineID, "current datetime: " . $currentDateTime->format('d-m-Y'));
} else {
	$diffHour = 100;
}


pushMsg($adminLineID, "cron started: ");
pushMsg($adminLineID, "cron started: ");

switch($manager->getState()) {
    case Manager::STATE_SLEEP:
		$nextThursday = new DateTime();
		$nextThursday->modify('next thursday');

		$interval = $nextThursday->diff($currentDateTime);
		$diffHour = ($interval->days * 24) + $interval->h;

		pushMsg($adminLineID, "Next Thursday: " . $nextThursday->format('d-m-Y'));
		pushMsg($adminLineID, "current datetime: " . $currentDateTime->format('d-m-Y'));

		if ($diffHour <= 36) {
			if ($manager->startCount()) {
				broadcast($manager->getTeams(), 'Thursday Football kub?');
			};
		}
        break;
	case Manager::STATE_INITIAL:
		if ($diffHour <= 12) {
			if ($manager->getTotalPlayers() < 21) {
				$manager->setState(Manager::STATE_FIRST_CHECKED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "Anymore?");
			}else {
				$manager->setState(Manager::STATE_CONFIRMED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "คอนเฟิมมีเตะครับ");
			}
		}
		break;

    case Manager::STATE_FIRST_CHECKED:
		if ($diffHour <= 6) {
			if ($manager->getTotalPlayers() < 14) {
				$manager->setState(Manager::STATE_SECOND_CHECKED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "Anymore?");
			}else {
				$manager->setState(Manager::STATE_CONFIRMED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "คอนเฟิมมีเตะครับ");
			}
		}
		break;

    case Manager::STATE_SECOND_CHECKED:
		if ($diffHour <= 4) {
			if ($manager->getTotalPlayers() < 14) {
				$manager->setState(Manager::STATE_FINAL_CHECKED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "Final call...");
			}else {
				$manager->setState(Manager::STATE_CONFIRMED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "คอนเฟิมมีเตะครับ");
			}
		}
        break;

	case Manager::STATE_FINAL_CHECKED:
		if ($diffHour <= 3) {
			$manager->setState(Manager::STATE_CONFIRMED)->save();
			if ($manager->getTotalPlayers() < 14) {
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "ยกเลิกนะครับ");
			}else {
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "คอนเฟิมมีเตะครับ");
			}
		}
		break;

	case Manager::STATE_CONFIRMED:
		if ($diffHour <= -18) {
			$manager->endCount();
		}
		break;
}

/**
 * @param \Football\Team[] $teams
 * @param $content
 */
function broadcast(array $teams, $content) {
	foreach ($teams	as $team) {
		pushMsg($team->getGroupLineID(), $content);
	}
}

function pushMsg($toID, $content){
	$accessToken = getenv('ACCESS_TOKEN');

	$arrayHeader = array();
	$arrayHeader[] = "Content-Type: application/json";
	$arrayHeader[] = "Authorization: Bearer {$accessToken}";

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
