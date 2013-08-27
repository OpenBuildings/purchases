<?php

/**
 * Testcase_Functest definition
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
abstract class Testcase_Purchases extends PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
		Database::instance(Kohana::TESTING)->begin();
		Jam_Association_Creator::current(1);
	}

	public function tearDown()
	{
		Database::instance(Kohana::TESTING)->rollback();	
		parent::tearDown();
	}
}