<?php

class Database
{

	public static function getConnection()
	{
		$dbtype = DBTYPE;
		$dbhost = DBHOST;
		$dbname = DBNAME;
		$dbuser = DBUSER;
		$dbpass = DBPASS;
		return new PDO("$dbtype:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	}

}