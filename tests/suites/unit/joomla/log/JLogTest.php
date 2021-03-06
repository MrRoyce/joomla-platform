<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Log
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

require_once JPATH_PLATFORM.'/joomla/log/log.php';
require_once JPATH_PLATFORM.'/joomla/log/entry.php';
require_once JPATH_PLATFORM.'/joomla/log/logexception.php';
require_once JPATH_PLATFORM.'/joomla/log/logger.php';
require_once __DIR__.'/stubs/log/inspector.php';

/**
 * Test class for JLog.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Log
 * @since       11.1
 */
class JLogTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Overrides the parent tearDown method.
	 *
	 * @return  void
	 *
	 * @see     PHPUnit_Framework_TestCase::tearDown()
	 * @since   11.1
	 */
	protected function tearDown()
	{
		// Clear out the log instance.
		$log = new JLogInspector;
		JLog::setInstance($log);

		parent::tearDown();
	}

	/**
	 * Test the JLog::addEntry method to make sure if we give it invalid scalar input it will return false
	 * just as in Joomla! CMS 1.5.  This method is deprecated and will be removed in 11.2.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testAddEntryWithInvalidScalarInput()
	{
		$log = new JLogInspector;

		$this->assertFalse($log->addEntry(123), 'Line: '.__LINE__);
		$this->assertFalse($log->addEntry('foobar'), 'Line: '.__LINE__);
		$this->assertFalse($log->addEntry(3.14), 'Line: '.__LINE__);
	}

	/**
	 * Test the JLog::addEntry method to make sure if we give it valid array input as in Joomla! CMS 1.5 then
	 * it will in fact add a log entry and transform it correctly.  This method is deprecated and will be
	 * removed in 11.2.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testAddEntryWithValidArrayInput()
	{
		$log = new JLogInspector;

		// Create an array similar to expected input array from Joomla! CMS 1.5
		$entry = array(
			'c-ip' => '127.0.0.1',
			'status' => 'deprecated',
			'level' => JLog::DEBUG,
			'comment' => 'Test Entry',
			'foo' => 'bar'
		);

		$log->addEntry($entry);

		// Verify all of the JLogEntry values.
		$this->assertEquals($log->queue[0]->category, 'deprecated', 'Line: '.__LINE__);
		$this->assertEquals($log->queue[0]->priority, JLog::DEBUG, 'Line: '.__LINE__);
		$this->assertEquals($log->queue[0]->message, 'Test Entry', 'Line: '.__LINE__);
		$this->assertEquals($log->queue[0]->foo, 'bar', 'Line: '.__LINE__);
		$this->assertEquals($log->queue[0]->clientIP, '127.0.0.1', 'Line: '.__LINE__);
	}

	/**
	 * Test the JLog::addEntry method to make sure if we give it a valid JLogEntry object as input it will
	 * correctly accept and add the entry.  This method is deprecated and will be removed in 11.2.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testAddEntryWithValidJLogEntry()
	{
		$log = new JLogInspector;

		$entry = new JLogEntry('TESTING', JLog::DEBUG);

		$log->addEntry($entry);

		$this->assertEquals($log->queue[0], $entry, 'Line: '.__LINE__);
	}

	/**
	 * Test the JLog::addLogEntry method to verify that if called directly it will route the entry to the
	 * appropriate loggers.  We use the echo logger here for easy testing using the PHP output buffer.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testAddLogEntry()
	{
		// First let's test a set of priorities.
		$log = new JLogInspector;
		JLog::setInstance($log);

		// Add a loggers to the JLog object.
		JLog::addLogger(array('logger' => 'echo'), JLog::ALL);

		$this->expectOutputString("DEBUG: TESTING [deprecated]\n");
		$log->addLogEntry(new JLogEntry('TESTING', JLog::DEBUG, 'DePrEcAtEd'));
	}

	/**
	 * Test that if JLog::addLogger is called and no JLog instance has been instantiated yet, that one will
	 * be instantiated automatically and the logger will work accordingly.  We use the echo logger here for
	 * easy testing using the PHP output buffer.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testAddLoggerAutoInstantiation()
	{
		JLog::setInstance(null);

		JLog::addLogger(array('logger' => 'echo'), JLog::ALL);

		$this->expectOutputString("WARNING: TESTING [deprecated]\n");
		JLog::add(new JLogEntry('TESTING', JLog::WARNING, 'DePrEcAtEd'));
	}

	/**
	 * Test that if JLog::addLogger is called and no JLog instance has been instantiated yet, that one will
	 * be instantiated automatically and the logger will work accordingly.  We use the echo logger here for
	 * easy testing using the PHP output buffer.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testAddLoggerAutoInstantiationInvalidLogger()
	{
		// We are expecting a LogException to be thrown since we are trying to add a bogus logger.
		$this->setExpectedException('LogException');

		JLog::setInstance(null);

		JLog::addLogger(array('logger' => 'foobar'), JLog::ALL);

		JLog::add(new JLogEntry('TESTING', JLog::WARNING, 'DePrEcAtEd'));
	}

	/**
	 * Test the JLog::findLoggers method to make sure given a category we are finding the correct loggers that
	 * have been added to JLog.  It is important to note that if a logger was added with no category, then it
	 * will be returned for all categories.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testFindLoggersByCategory()
	{
		// First let's test a set of priorities.
		$log = new JLogInspector;
		JLog::setInstance($log);

		// Add a loggers to the JLog object.
		JLog::addLogger(array('text_file' => 'deprecated.log'), JLog::ALL, 'deprecated'); // 767d00c8f22f5859a1fd73835ee47e4d
		JLog::addLogger(array('text_file' => 'com_foo.log'), JLog::ALL, 'com_foo'); // 09826310049345665887853e4688d89e
		JLog::addLogger(array('text_file' => 'none.log'), JLog::ALL); // 5099e81204381e68555c620cd8140421
		JLog::addLogger(array('text_file' => 'deprecated-com_foo.log'), JLog::ALL, array('deprecated', 'com_foo')); // 57604db2561c1c4492f5dfceed3d943c
		JLog::addLogger(array('text_file' => 'foobar-deprecated.log'), JLog::ALL, array('foobar', 'deprecated')); // 5fbf17c78bfcd300debc791e01066128
		JLog::addLogger(array('text_file' => 'transactions-paypal.log'), JLog::ALL, array('transactions', 'paypal')); // b5550c1aa36c1eaf77206565ec5f9021
		JLog::addLogger(array('text_file' => 'transactions.log'), JLog::ALL, array('transactions')); // 916ed48d2f635431a93aee60c56b0219
		//var_dump($log->lookup);

		$this->assertThat(
			$log->findLoggers(JLog::EMERGENCY, 'deprecated'),
			$this->equalTo(
				array(
					'767d00c8f22f5859a1fd73835ee47e4d',
					'5099e81204381e68555c620cd8140421',
					'57604db2561c1c4492f5dfceed3d943c',
					'5fbf17c78bfcd300debc791e01066128',
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::NOTICE, 'paypal'),
			$this->equalTo(
				array(
					'5099e81204381e68555c620cd8140421',
					'b5550c1aa36c1eaf77206565ec5f9021',
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::DEBUG, 'com_foo'),
			$this->equalTo(
				array(
					'09826310049345665887853e4688d89e',
					'5099e81204381e68555c620cd8140421',
					'57604db2561c1c4492f5dfceed3d943c'
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::WARNING, 'transactions'),
			$this->equalTo(
				array(
					'5099e81204381e68555c620cd8140421',
					'b5550c1aa36c1eaf77206565ec5f9021',
					'916ed48d2f635431a93aee60c56b0219',
				)),
			'Line: '.__LINE__.'.'
		);

	}

	/**
	 * Test the JLog::findLoggers method to make sure given a priority we are finding the correct loggers that
	 * have been added to JLog.  It is important to test not only straight values but also bitwise combinations
	 * and the catch all JLog::ALL as registered loggers.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testFindLoggersByPriority()
	{
		// First let's test a set of priorities.
		$log = new JLogInspector;
		JLog::setInstance($log);

		// Add a loggers to the JLog object.
		JLog::addLogger(array('text_file' => 'error.log'), JLog::ERROR); // 684e35a45ddd17c00024891e95c29046
		JLog::addLogger(array('text_file' => 'notice.log'), JLog::NOTICE); // 3ab1ff5941725c3ed01e6dd1ff623415
		JLog::addLogger(array('text_file' => 'warning.log'), JLog::WARNING); // e16e9516d55213efd9255d8c9c13020b
		JLog::addLogger(array('text_file' => 'error_warning.log'), JLog::ERROR | JLog::WARNING); // d941cfc07f7641537991eaecaa8ea553
		JLog::addLogger(array('text_file' => 'all.log'), JLog::ALL); // a2fae4fb61ef676032361e47068deb9a
		JLog::addLogger(array('text_file' => 'all_except_debug.log'), JLog::ALL & ~JLog::DEBUG); // aaa7a0e4a4720ef7aed99ded3b764303
		//var_dump($log->lookup);

		$this->assertThat(
			$log->findLoggers(JLog::EMERGENCY, null),
			$this->equalTo(
				array(
					'a2fae4fb61ef676032361e47068deb9a',
					'aaa7a0e4a4720ef7aed99ded3b764303',
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::NOTICE, null),
			$this->equalTo(
				array(
					'3ab1ff5941725c3ed01e6dd1ff623415',
					'a2fae4fb61ef676032361e47068deb9a',
					'aaa7a0e4a4720ef7aed99ded3b764303'
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::DEBUG, null),
			$this->equalTo(
				array(
					'a2fae4fb61ef676032361e47068deb9a'
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::WARNING, null),
			$this->equalTo(
				array(
					'e16e9516d55213efd9255d8c9c13020b',
					'd941cfc07f7641537991eaecaa8ea553',
					'a2fae4fb61ef676032361e47068deb9a',
					'aaa7a0e4a4720ef7aed99ded3b764303'
				)),
			'Line: '.__LINE__.'.'
		);

	}

	/**
	 * Test the JLog::findLoggers method to make sure given a priority and category we are finding the correct
	 * loggers that have been added to JLog.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testFindLoggersByPriorityAndCategory()
	{
		// First let's test a set of priorities.
		$log = new JLogInspector;
		JLog::setInstance($log);

		// Add a loggers to the JLog object.
		JLog::addLogger(array('text_file' => 'deprecated.log'), JLog::ALL, 'deprecated'); // 767d00c8f22f5859a1fd73835ee47e4d
		JLog::addLogger(array('text_file' => 'com_foo.log'), JLog::DEBUG, 'com_foo'); // 09826310049345665887853e4688d89e
		JLog::addLogger(array('text_file' => 'none.log'), JLog::ERROR | JLog::CRITICAL | JLog::EMERGENCY); // 5099e81204381e68555c620cd8140421
		JLog::addLogger(array('text_file' => 'deprecated-com_foo.log'), JLog::NOTICE | JLog::WARNING, array('deprecated', 'com_foo')); // 57604db2561c1c4492f5dfceed3d943c
		JLog::addLogger(array('text_file' => 'transactions-paypal.log'), JLog::INFO, array('transactions', 'paypal')); // b5550c1aa36c1eaf77206565ec5f9021
		JLog::addLogger(array('text_file' => 'transactions.log'), JLog::ERROR, array('transactions')); // 916ed48d2f635431a93aee60c56b0219
		//var_dump($log->lookup);

		$this->assertThat(
			$log->findLoggers(JLog::EMERGENCY, 'deprecated'),
			$this->equalTo(
				array(
					'767d00c8f22f5859a1fd73835ee47e4d',
					'5099e81204381e68555c620cd8140421',
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::NOTICE, 'paypal'),
			$this->equalTo(
				array(
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::DEBUG, 'com_foo'),
			$this->equalTo(
				array(
					'09826310049345665887853e4688d89e',
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::ERROR, 'transactions'),
			$this->equalTo(
				array(
					'5099e81204381e68555c620cd8140421',
					'916ed48d2f635431a93aee60c56b0219',
				)),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->findLoggers(JLog::INFO, 'transactions'),
			$this->equalTo(
				array(
					'b5550c1aa36c1eaf77206565ec5f9021',
				)),
			'Line: '.__LINE__.'.'
		);
	}

	/**
	 * Test the JLog::getInstance method to make sure we are getting a valid JLog instance from it.  This
	 * method is deprecated and will be removed in 11.2.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testGetInstance()
	{
		// Make sure we are working with a clean instance.
		JLog::setInstance(null);

		// Get an instance of the JLog class.
		$log = JLog::getInstance();

		// Verify that it is a JLog.
		$this->assertTrue(($log instanceof JLog), 'Line: '.__LINE__);
	}

	/**
	 * Test the JLog::setInstance method to make sure that if we set a logger instance JLog is actually going
	 * to use it.  We accomplish this by setting an instance of JLogInspector and then performing some
	 * operations using JLog::addLogger() to alter the state of the internal instance.  We then check that the
	 * JLogInspector instance we created (and set) has the same values we would expect for lookup and configuration
	 * so we can assert that the operations we performed using JLog::addLogger() were actually performed on our
	 * instance of JLogInspector that was set.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function testSetInstance()
	{
		$log = new JLogInspector;
		JLog::setInstance($log);

		// Add a logger to the JLog object.
		JLog::addLogger(array('logger' => 'w3c'));

		// Get the expected configurations array after adding the single logger.
		$expectedConfigurations = array(
			'55202c195e23298813df4292c827b241' => array('logger' => 'w3c')
		);

		// Get the expected lookup array after adding the single logger.
		$expectedLookup = array(
			'55202c195e23298813df4292c827b241' => (object) array('priorities' => JLog::ALL, 'categories' => array())
		);

		// Get the expected loggers array after adding the single logger (hasn't been instantiated yet so null).
		$expectedLoggers = null;

		$this->assertThat(
			$log->configurations,
			$this->equalTo($expectedConfigurations),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->lookup,
			$this->equalTo($expectedLookup),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->loggers,
			$this->equalTo($expectedLoggers),
			'Line: '.__LINE__.'.'
		);

		// Start over so we test that it actually sets the instance appropriately.
		$log = new JLogInspector;
		JLog::setInstance($log);

		// Add a logger to the JLog object.
		JLog::addLogger(array('logger' => 'database', 'db_type' => 'mysql', 'db_table' => '#__test_table'), JLog::ERROR);

		// Get the expected configurations array after adding the single logger.
		$expectedConfigurations = array(
			'b67483f5ba61450d173aae527fa4163f' => array('logger' => 'database', 'db_type' => 'mysql', 'db_table' => '#__test_table')
		);

		// Get the expected lookup array after adding the single logger.
		$expectedLookup = array(
			'b67483f5ba61450d173aae527fa4163f' => (object) array('priorities' => JLog::ERROR, 'categories' => array())
		);

		// Get the expected loggers array after adding the single logger (hasn't been instantiated yet so null).
		$expectedLoggers = null;

		$this->assertThat(
			$log->configurations,
			$this->equalTo($expectedConfigurations),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->lookup,
			$this->equalTo($expectedLookup),
			'Line: '.__LINE__.'.'
		);

		$this->assertThat(
			$log->loggers,
			$this->equalTo($expectedLoggers),
			'Line: '.__LINE__.'.'
		);
	}
}
