<?php

namespace Football;

class Team
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var float */
    protected $numPlayers;

    protected $saveFile;

    protected $dbKey;

    protected $dbManager;

    function __construct(string $id, string $name, float $numPlayers = 0)
    {
        $this->id = $id;
        $this->name = $name;
        $this->numPlayers = $numPlayers;
        $this->dbManager = DBManager::getInstance();
        $this->dbKey = "team-{$id}";
    }

    /**
     * @param float $num
     * @return Team
     */
    public function addPlayer(float $num): Team
    {
        $this->numPlayers += $num;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroupLineID(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName():string
    {
        return $this->name;
    }

    public function getNumPlayers(): float
    {
        return $this->numPlayers;
    }

    public function load(): void
    {
        $data = $this->dbManager->get($this->dbKey);
        if (!$data) return;

        $data = json_decode($data, true);
        $this->numPlayers = $data['numPlayers'] ?? 0;
    }

    public function save(): void
    {
        $this->dbManager->set($this->dbKey, json_encode($this->toArray()));
    }

    /**
     * @param float $num
     * @return Team
     */
    public function setPlayer(float $num): Team
    {
        $this->numPlayers = $num;
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'numPlayers' => $this->numPlayers,
        ];
    }
}