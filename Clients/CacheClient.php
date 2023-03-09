<?php

namespace SomeCompanyNamespace\Services\Phishing\Clients;

use MCache;
use SomeCompanyNamespace\Services\Phishing\Interfaces\CacheClientInterface;
use SomeCompanyNamespace\Services\Phishing\Traits\ConfigTrait;

class CacheClient implements CacheClientInterface
{
	use ConfigTrait;

	private $ttl;

	public function __construct()
	{
		$this->setTTL($this->getSecondsDelayBeforeSendingToScan());
	}

	/**
	 * TTL getter
	 *
	 * @return int                  How long to persist the value past the initial key creation
	 */
	public function getTTL()
	{
		return $this->ttl;
	}

	/**
	 * TTL setter
	 *
	 * @param int $ttl How long to persist the value past the initial key creation
	 * @return self                 This class
	 */
	public function setTTL(int $ttl): self
	{
		$this->ttl = $ttl;

		return $this;
	}

	/**
	 * Get key value to the server
	 *
	 * @param string                Key
	 * @return bool                 Returns true when succesfull. False on failure
	 */
	public function get($key)
	{
		return MCache::get($key);
	}

	/**
	 * Add key value to the server
	 *
	 * @param string                Key
	 * @param mixed                 Value
	 * @return bool                 Returns true when succesfull. False if the key already exists or failure
	 */
	public function add($key, $value): bool
	{
		return MCache::add($key, $value, $this->getTTL());
	}

	/**
	 * Set key value to the server. If doesn't exist it will created it.
	 *
	 * @param string                Key
	 * @param mixed                 Value
	 * @return bool                 Returns true when succesfull. False on failure
	 */
	public function set($key, $value): bool
	{
		return MCache::set($key, $value, $this->getTTL());
	}

	/**
	 * Delete key value to the server
	 *
	 * @param string                Key
	 * @return bool                 Returns true when succesfull. False on failure
	 */
	public function delete($key): bool
	{
		return MCache::delete($key);
	}

	/**
	 * Adds the key value to the server unless is already saved. In that case ignores the request to maintain original TTL
	 *
	 * @param
	 */
	public function cache($key, $value)
	{
		$result = $this->get($key);
		if ($result) {
			return true;
		}

		return $this->add($key, $value);
	}
}