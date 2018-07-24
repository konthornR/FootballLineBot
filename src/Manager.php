<?php

namespace Football;

use DateTime;

class Manager
{
    protected $startCount = false;

    protected $forDate = '';

    const DB_KEY = "manager";

    /** @var Team[] */
    protected $teams = [];

    protected $config;

    protected $dbManager;

    protected static $instance = null;

    protected $state = self::STATE_SLEEP;

    const STATE_INITIAL = 'initial';
    const STATE_FIRST_CHECKED = 'first_checked';
    const STATE_SECOND_CHECKED = 'second_checked';
    const STATE_FINAL_CHECKED = 'final_checked';
    const STATE_CONFIRMED = 'confirmed';
    const STATE_SLEEP = 'sleep';


    function __construct()
    {
        $this->config = require_once __DIR__ . "/../config.php";
        $this->dbManager = DBManager::getInstance();
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

    	$data = $this->dbManager->get(self::DB_KEY);
    	if (!$data) return;
        $data = json_decode($data, true);
        $this->startCount = $data['startCount'] ?? false;
        $this->forDate = $data['forDate'] ?? '';
        $this->state = $data['state'] ?? self::STATE_SLEEP;
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

    public function save(): void
    {
        $this->dbManager->set(self::DB_KEY, json_encode([
            'startCount' => $this->startCount,
            'forDate' => $this->forDate,
            'state' => $this->state
        ]));

    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
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
    public function isCountStart(): bool
	{
		return $this->startCount;
	}

	/**
	 * @return string
	 */
	public function getForDate(): string
	{
		return $this->forDate;
	}

	/**
	 * @return int
	 */
	public function getTotalPlayers(): int
	{
		$result = 0;
		foreach ($this->teams as $team) {
			$result += $team->getNumPlayers();
		}
		return $result;
	}

	/**
	 * @param string $state
	 * @return Manager
	 */
	public function setState(string $state): Manager
	{
		$this->state = $state;
		return $this;
	}

    /**
     * @return bool
     */
    public function startCount(): bool
    {
        if ($this->startCount) {
            return false;
        }

        $this->state = Manager::STATE_INITIAL;
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
        $this->state = self::STATE_SLEEP;
        $this->save();
        return true;
    }

}
