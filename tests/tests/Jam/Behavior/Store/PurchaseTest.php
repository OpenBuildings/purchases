<?php

/**
 * @group jam
 * @group jam.behavior
 * @group jam.behavior.store_purchase
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Jam_Behavior_Store_PurchaseTest extends Testcase_Purchases {

	public function data_extract_types()
	{
		return array(
			array(array('test', 'test12'), array('test', 'test12')),
			array(array('test3' => 'test12'), array()),
			array(array('test', 'test2', 'test1' => 'test'), array('test', 'test2')),
		);
	}

	/**
	 * @covers Jam_Behavior_Store_Purchase::extract_types
	 * @dataProvider data_extract_types
	 */
	public function test_extract_types($array, $expected)
	{
		$assoc = Jam_Behavior_Store_Purchase::extract_types($array, $expected);
		$this->assertEquals($expected, $assoc);
	}
}