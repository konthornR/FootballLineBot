<?php

namespace Football;


use mysqli;

class DBManager
{
	protected static $instance = null;

	public static function getInstance(): DBManager
	{
		if (!self::$instance) {
			self::$instance = new DBManager();
		}

		return self::$instance;
	}

	protected $db = null;
	protected $data = [];

	function __construct()
    {
        $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

        $server = $url["host"];
        $username = $url["user"];
        $password = $url["pass"];
        $db = substr($url["path"], 1);

        $this->db = new mysqli($server, $username, $password, $db);
        if($this->db->connect_errno) {
            throw new \Exception("DB connection error: on establish connection");
        };

        $this->load();
    }

    protected function load(): void
    {
        $sql = "SELECT config.key, config.value FROM config";

        if (!$result = $this->db->query($sql)) {
            throw new \Exception("DB connection error: on query");
        }

        while ($row = $result->fetch_assoc()) {
            $this->data[$row['key']] = $row['value'];
        }
    }

    public function get(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, string $value): void
    {
        $sql = "INSERT INTO config (config.key, config.value) 
                VALUES ('{$key}', '{$value}')
                ON DUPLICATE KEY UPDATE
                config.value='{$value}'";

        if (!$result = $this->db->query($sql)) {
            throw new \Exception("DB connection error: on insert/update {$key}:{$value}");
        }

        $this->data[$key] = $value;
    }

}
