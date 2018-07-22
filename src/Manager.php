<?php

namespace Football;

use DateTime;

class Manager
{
    protected $startCount = false;

    protected $forDate = '';

    const SAVE_FILE = __DIR__ . '/../data/manager.json';

    /** @var Team[] */
    protected $teams = [];

    protected $config;

    protected static $instance = null;

    function __construct()
    {
        $this->config = require_once __DIR__ . "/../config.php";
        $this->load();
        $this->initializeTeam();
    }

    public static function getInstance() : Manager
    {
        if (!self::$instance) {
            self::$instance = new Manager();
        }

        return self::$instance;
    }

    protected function load(): void
    {
        if (!file_exists(self::SAVE_FILE)) return;

        $data = json_decode(file_get_contents(self::SAVE_FILE), true);
        $this->startCount = $data['startCount'] ?? false;
        $this->forDate = $data['forDate'] ?? '';
    }

    protected function initializeTeam(): void
    {
        foreach($this->config['teams'] as $team) {
            $this->teams[$team['groupID']] = new Team($team['groupID'], $team['name']);
            $this->teams[$team['groupID']]->load();
        }
    }

    /**
     * @param string $message
     * @param string $groupID
     */
    public function process(string $message, string $groupID): void
    {
        $message = trim($message);

        $team = $this->teams[$groupID] ?? null;
        if(!$team) return;

        if($this->startCount && !empty($message)) {
            // Save count player.

            if (is_numeric($message) && floatval($message) <= 3) {
                $team->addPlayer(floatval($message))->save();
            } else if($message[0] == '=') {
                $num = substr($message, 1);
                if (is_numeric($num) && floatval($num) <= 14) {
                    $team->setPlayer($num)->save();
                }
            }
        }
    }

    protected function save(): void
    {
        $fp = fopen(self::SAVE_FILE, 'w');
        fwrite($fp, json_encode([
            'startCount' => $this->startCount,
            'forDate' => $this->forDate,
        ]));
        fclose($fp);
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return $this->teams;
    }

    /**
     * @return string
     */
    public function getTeamsReport(): string
    {
        $content = "Thru " . $this->forDate . "\n";
        foreach($this->teams as $team) {
            $content .= $team->getName() . " " . round($team->getNumPlayers(), 2) . "\n";
        }

        return $content;
    }

    /**
     * @return bool
     */
    public function startCount(): bool
    {
        if ($this->startCount) {
            return false;
        }

        $this->startCount = true;

        // Get next thursday.
        $date = new DateTime();
        $date->modify('next thursday');
        $this->forDate = $date->format('d-m-Y');

        // Initialize teams, reset players to 0;
        foreach($this->teams as $team) {
            $team->setPlayer(0)->save();
        }

        $this->save();
        return true;
    }

    /**
     * @return bool
     */
    public function endCount(): bool
    {
        if (!$this->startCount) {
            return false;
        }

        $this->startCount = false;
        $this->save();
        return true;
    }

}
