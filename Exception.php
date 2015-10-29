<?php

class Ko_Exception extends Exception
{
	protected $data;

	public function __construct($message = '', $code = 0, $previous = null, $data = null)
	{
		parent::__construct($message, $code, $previous);

		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}
}
