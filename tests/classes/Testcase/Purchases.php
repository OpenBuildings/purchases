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
		Database::instance()->begin();
		Jam_Association_Creator::current(1);

		$this->env = new EB\Environment(array(
			'static' => new EB\Environment_Group_Static(),
			'config' => new EB\Environment_Group_Config(),
			'server' => new EB\Environment_Group_Server(),
		));
	}

	public function tearDown()
	{
		Database::instance()->rollback();

		$this->env->restore();

		parent::tearDown();
	}

	public function ids(array $items)
	{
		return array_values(array_map(function($item){ return $item->id(); }, $items));
	}
}
