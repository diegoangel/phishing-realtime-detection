<?php

namespace SomeCompanyNamespace\Services\Phishing\Requests;

class ScanUrlRequest extends Request
{
	const PRIORITY_LOW = 300;
	const PRIORITY_MEDIUM = 150;
	const PRIORITY_HIGH = 1;

	protected $requests = [];
	private $customerId;
	private $url;
	private $surveyId;
	private $phishingClientResponse;
	private $attempt = 0;
	private $state;
	private $phishingScanId;
	private $priority = self::PRIORITY_LOW;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Compose a nested Request objects structure.
	 * This object can be treated as a single object or a group of objects.
	 * A reminiscence of the Composite design pattern it can be seen.
	 *
	 * @param Request $scanUrlRequest
	 */
	public function add(Request $scanUrlRequest): void
	{
		array_push($this->requests, $scanUrlRequest);
	}

	/**
	 * @param int $key
	 */
	public function remove($key): void
	{
		unset($this->requests[$key]);
	}

	/**
	 * @return int
	 */
	public function getCustomerId(): int
	{
		return $this->customerId;
	}

	/**
	 * @param int $customerId
	 */
	public function setCustomerId(int $customerId): void
	{
		$this->customerId = $customerId;
	}

	/**
	 * @return int|null
	 */
	public function getSurveyId(): ?int
	{
		return $this->surveyId;
	}

	/**
	 * @param int|null $surveyId
	 */
	public function setSurveyId(?int $surveyId): void
	{
		$this->surveyId = $surveyId;
	}

	/**
	 * @return string|null
	 */
	public function getUrl(): ?string
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl(string $url): void
	{
		$this->url = $url;
	}

	/**
	 * @return mixed
	 */
	public function getPhishingClientResponse()
	{
		return $this->phishingClientResponse;
	}

	/**
	 * @param object $phishingClientResponse
	 */
	public function setPhishingClientResponse(object $phishingClientResponse): void
	{
		$this->phishingClientResponse = $phishingClientResponse;
	}

	/**
	 * @return int
	 */
	public function getAttempts(): int
	{
		return $this->attempt;
	}

	/**
	 * @param int $attempt
	 */
	public function setAttempts(int $attempt): void
	{
		$this->attempt = $attempt;
	}

	/**
	 * @return string|null
	 */
	public function getState(): ?string
	{
		return $this->state;
	}

	/**
	 * @param mixed $state
	 */
	public function setState(string $state): void
	{
		$this->state = $state;
	}

	/**
	 * @return mixed
	 */
	public function getPhishingScanId()
	{
		return $this->phishingScanId;
	}

	/**
	 * @param string $phishingScanId
	 */
	public function setPhishingScanId(?string $phishingScanId): void
	{
		$this->phishingScanId = $phishingScanId;
	}

	/**
	 * @return bool
	 */
	public function isComposite()
	{
		return count($this->requests) == 0 ? false : true;
	}

	/**
	 * @return mixed
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param mixed $priority
	 */
	public function setPriority($priority): void
	{
		$this->priority = $priority;
	}
}
