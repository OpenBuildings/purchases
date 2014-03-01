<?php

/**
 * @group emp
 */
class EmpTest extends Testcase_Purchases {

	/**
	 * @covers Emp::is_threatmatrix_enabled
	 */
	public function test_is_threatmatrix_enabled()
	{
		$this->assertFalse(Emp::is_threatmatrix_enabled());

		$this->env->backup_and_set(array(
			'purchases.processor.emp.threatmatrix' => array('org_id' => '1')
		));

		$this->assertTrue(Emp::is_threatmatrix_enabled());
	}

	/**
	 * @covers Emp::threatmatrix
	 * @covers Emp::clear_threatmatrix
	 */
	public function test_threatmatrix()
	{
		$this->assertNull(Emp::threatmatrix());

		$this->env->backup_and_set(array(
			'purchases.processor.emp.threatmatrix' => array('org_id' => 'TESTORG', 'client_id' => 'TESTCLIENT')
		));

		$threatmatrix = Emp::threatmatrix();

		$this->assertInstanceOf('Openbuildings\Emp\Threatmatrix', $threatmatrix);
		$this->assertEquals('TESTORG', $threatmatrix->org_id());

		$this->assertSame($threatmatrix, Emp::threatmatrix());

		Emp::clear_threatmatrix();

		$this->assertInstanceOf('Openbuildings\Emp\Threatmatrix', Emp::threatmatrix());
		$this->assertNotSame($threatmatrix, Emp::threatmatrix());
	}

	/**
	 * @covers Emp::api
	 */
	public function test_api()
	{
		$this->env->backup_and_set(array(
			'Emp::$_api' => NULL,
			'purchases.processor.emp.threatmatrix' => array('org_id' => 'TESTORG', 'client_id' => 'TESTCLIENT'),
			'purchases.processor.emp.api' => array('gateway_url' => 'http://example.com', 'api_key' => 'TESTAPI', 'client_id' => 'TESTCLIENT', 'proxy' => 'PROXY')
		));

		$api = Emp::api();

		$this->assertInstanceOf('Openbuildings\Emp\Api', $api);
		$this->assertEquals('TESTAPI', $api->api_key());
		$this->assertEquals('http://example.com', $api->gateway_url());
		$this->assertEquals('TESTCLIENT', $api->client_id());
		$this->assertEquals('PROXY', $api->proxy());
		$this->assertEquals(Emp::threatmatrix(), $api->threatmatrix());
	}
}