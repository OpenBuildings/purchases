<?php

use Openbuildings\EnvironmentBackup as EB;

/**
 * Testcase_Functest definition
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
abstract class Testcase_Purchases extends PHPUnit_Framework_TestCase {

	public $environment;
	
	public function setUp()
	{
		parent::setUp();
		Database::instance(Kohana::TESTING)->begin();
		Jam_Association_Creator::current(1);

		$this->env = new EB\Environment(array(
			'static' => new EB\Environment_Group_Static(),
			'config' => new EB\Environment_Group_Config(),
		));
	}

	public function tearDown()
	{
		Database::instance(Kohana::TESTING)->rollback();	
		$this->env->restore();

		parent::tearDown();
	}
}