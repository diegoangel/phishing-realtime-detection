<?php

namespace SomeCompanyNamespace\Services\Phishing\Requests;

use Iterator;
use Countable;

abstract class Request implements Iterator, Countable
{
	protected $requests = [];
	private $position;

	public function __construct()
	{
		$this->position = 1;
	}

	/**
	 * @inheritDoc
	 */
	public function current()
	{
		return $this->requests[$this->position];
	}

	/**
	 * @inheritDoc
	 */
	public function next()
	{
		++$this->position;
	}

	/**
	 * @inheritDoc
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * @inheritDoc
	 */
	public function valid()
	{
		return isset($this->requests[$this->position]);
	}

	/**
	 * @inheritDoc
	 */
	public function rewind()
	{
		$this->position = 0;
	}

	public function count()
	{
		return count($this->requests);
	}
}