<?php

use Football\Manager;

require_once   __DIR__ . "/vendor/autoload.php";

date_default_timezone_set('Asia/Bangkok');


$adminLineID = getenv('ADMIN_LINE_ID');

$currentDateTime = new DateTime();
$manager = Manager::getInstance();
$forThursday = null;

if ($manager->isCountStart()) {
	$forThursday = DateTime::createFromFormat('d-m-Y h:i:s', $manager->getForDate() . " 00:00:00");
	$interval = $forThursday->diff($currentDateTime);
	$diffHour = ($interval->days * 24) + $interval->h;
} else {
	$diffHour = 100;
}

switch($manager->getState()) {
    case Manager::STATE_SLEEP:
		$nextThursday = new DateTime();
		$nextThursday->modify('next thursday');

		$interval = $nextThursday->diff($currentDateTime);
		$diffHour = ($interval->days * 24) + $interval->h;

		if ($diffHour <= 59) {
			if ($manager->startCount()) {
				broadcast($manager->getTeams(), 'Thursday Football kub?');
			};
		}
        break;

    case Manager::STATE_INITIAL:
        if ($diffHour <= 35) {
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
		if ($diffHour <= 11) {
			if ($manager->getTotalPlayers() < 21) {
				$manager->setState(Manager::STATE_SECOND_CHECKED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "Anymore?");
			}else {
				$manager->setState(Manager::STATE_CONFIRMED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "คอนเฟิมมีเตะครับ");
			}
		}
		break;

    case Manager::STATE_SECOND_CHECKED:
		if ($diffHour <= 6) {
			if ($manager->getTotalPlayers() < 14) {
				$manager->setState(Manager::STATE_THIRD_CHECKED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "Anymore?");
			}else {
				$manager->setState(Manager::STATE_CONFIRMED)->save();
				broadcast($manager->getTeams(), $manager->getTeamsReport() . "คอนเฟิมมีเตะครับ");
			}
		}
		break;

    case Manager::STATE_THIRD_CHECKED:
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
		if ($forThursday && $currentDateTime > $forThursday && $diffHour >= 22) {
			$manager->endCount();
			pushMsg($adminLineID, "End count.");
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
