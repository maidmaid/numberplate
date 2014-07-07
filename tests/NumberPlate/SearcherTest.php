<?php

namespace NumberPlate;

use PHPUnit_Framework_TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;
	
require_once __DIR__.'/../../vendor/autoload.php';

class SearcherTest extends PHPUnit_Framework_TestCase
{
	public function testSearch()
	{
		$searcher = new Searcher();
		$data = $searcher->search('77729');
		$this->assertNotEmpty($data);
	}
	
	public function testEvents()
	{
		$events = array(
			'cookie.initialize' => false,
			'captcha.download' => false,
			'captcha.decode' => false,
			'search.send' => false,
			'error.return' => false
		);
		
		$searcher = new Searcher();
		
		$searcher->getDispatcher()->addListener('cookie.initialize', function(GenericEvent $e) use(&$events) {
			$events['cookie.initialize'] = true;
		});
		$searcher->getDispatcher()->addListener('captcha.download', function(Event $e) use(&$events) {
			$events['captcha.download'] = true;
		});
		$searcher->getDispatcher()->addListener('captcha.decode', function(GenericEvent $e) use(&$events) {
			$events['captcha.decode'] = true;
		});
		$searcher->getDispatcher()->addListener('search.send', function(GenericEvent $e) use(&$events) {
			$events['search.send'] = true;
		});
		$searcher->getDispatcher()->addListener('error.return', function(GenericEvent $e) use(&$events) {
			$events['error.return'] = true;
		});
		
		$searcher->search('77729');
		
		$this->assertTrue($events['cookie.initialize']);
		$this->assertTrue($events['captcha.download']);
		$this->assertTrue($events['captcha.decode']);
		$this->assertTrue($events['search.send']);
	}
}