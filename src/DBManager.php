<?php

namespace Football;


use mysqli;

class DBManager
{
	protected static $instance = null;

	public static function getInstance(): mysqli
	{
		if (!self::$instance) {
			$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

			$server = $url["host"];
			$username = $url["user"];
			$password = $url["pass"];
			$db = substr($url["path"], 1);

			self::$instance = new mysqli($server, $username, $password, $db);
		}

		return self::$instance;
	}

}
