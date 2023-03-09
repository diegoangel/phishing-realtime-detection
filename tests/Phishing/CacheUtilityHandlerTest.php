<?php

use \Mockery as M;
use SomeCompanyNamespace\Services\Phishing\Clients\CacheClient;

/**
* @runTestsInSeparateProcesses
* @preserveGlobalState disabled
*/
class CacheUtilityHandlerTest extends PHPUnit_Framework_TestCase
{
    private $handler;

    private $ttl;

	protected function setUp() 
    {
		$this->handler = new CacheClient;
        $this->ttl = 300;

        $this->createMockMemcache();
	}

    public function testGetKey()
    {
        $item = $this->handler->get('test');

        $this->assertEquals($item, ['testKey' => 'testValue']);
    }

    public function testAddKey()
    {
        $log = $this->handler->add('test', ['1' => '2']);

        $this->assertTrue($log);
    }

    public function testSetKey()
    {
        $log = $this->handler->set('test', ['1', '2']);

        $this->assertTrue($log);
    }

    public function testDeleteKey()
    {
        $item = $this->handler->delete('test');

        $this->assertTrue($item);
    }

    public function testCacheKey()
    {
        $log = $this->handler->cache('test', ['testKey' => 'testValue']);

        $this->assertTrue($log);
    }

    /**
	 * Set up a mock DatabaseServer and mock DB Adapter
     *
	 * @return void
	 */
	private function createMockMemcache()
	{
        M::mock('alias:MCache', [
			'get' => ['testKey' => 'testValue'],
            'add' => true,
            'set' => true,
            'delete' => true,
		]);
	}
}