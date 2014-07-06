<?php
	
namespace NumberPlate;

require_once __DIR__.'/../../vendor/autoload.php';
	
class SearcherTest extends \PHPUnit_Framework_TestCase
{
	public function testSearch()
	{
		$name = Searcher::search('77729');
		$this->assertNotEmpty($name);
	}
}