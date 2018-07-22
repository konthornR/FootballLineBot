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

    function __construct(string $id, string $name, float $numPlayers = 0)
    {
        $this->id = $id;
        $this->name = $name;
        $this->numPlayers = $numPlayers;
        $this->saveFile = __DIR__ . "/../data/team-{$id}.json";
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
        if (!file_exists($this->saveFile)) return;

        $data = json_decode(file_get_contents($this->saveFile), true);
        $this->numPlayers = $data['numPlayers'] ?? 0;
    }

    public function save(): void
    {
        $fp = fopen($this->saveFile, 'w');
        fwrite($fp, json_encode($this->toArray()));
        fclose($fp);
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