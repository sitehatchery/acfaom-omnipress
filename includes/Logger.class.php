<?php

class Logger
{

	private $file;

	public function __construct($filename)
	{
		$this->file = $filename;

		/* Create email attachments directory if not exists */
		if (!file_exists(F_LOGS)) {
			mkdir(F_LOGS, 0777, true);
		}
	}

	public function putLog($insert)
	{
		file_put_contents($this->file, $insert . "\n", FILE_APPEND);
	}

	public function getLog()
	{
		$content = @file_get_contents($this->file);
		return $content;
	}


}