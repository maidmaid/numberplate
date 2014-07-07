<?php
	
namespace NumberPlate;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
	
class Searcher
{
	/* @var $dispatcher EventDispatcher */
	private $dispatcher;
	
	/* @var $client Client */
	private $client;

	/* @var $jar CookieJar */
	private $jar;
	
	public function __construct()
	{
		$this->client = new Client();
		$this->jar = new CookieJar();
		$this->dispatcher = new EventDispatcher();
	}
	
	/**
	 * 
	 * @return EventDispatcher
	 */
	public function getDispatcher()
	{
		return $this->dispatcher;
	}
	
	public function search($numberPlate)
	{
		$options = array('cookies' => $this->jar);
		
		// Initialise les cookie
		if($this->jar->count() == 0)
		{
			$this->client->get('http://www.vs.ch/cari-online/rechDet', $options);
			$this->dispatcher->dispatch('cookie.initialize', new GenericEvent($this->jar->toArray()));
		}

		// Traite le captcha
		$captchaVal = '';
		while(strlen($captchaVal) != 6) // 6 Chars
		{
			// Download captcha
			$captcha = $this->client->get('http://www.vs.ch/cari-online/drawCaptcha', $options);
			file_put_contents('captcha.png', $captcha->getBody()->__toString());
			$this->dispatcher->dispatch('captcha.download');
			
			// Decode captcha
			$captchaVal = Captcha::decode('captcha.png');
			$this->dispatcher->dispatch('captcha.decode', new GenericEvent($captchaVal));
		}

		// Envoie la recherche
		$options['body'] = array(
			'pageContext' => 'login',
			'action' => 'query',
			'no' => $numberPlate,
			'cat' => '1',
			'sousCat' => '1',
			'captchaVal' => $captchaVal,
			'valider' => 'Continuer'
		);
		$response = $this->client->post('http://www.vs.ch/cari-online/rechDet', $options);
		$this->dispatcher->dispatch('search.send', new GenericEvent($response));

		// Crawler
		$html = $response->getBody()->__toString();
		$crawler = new Crawler($html);

		// Traitements des erreurs
		$e = count($e = $crawler->filter('#idDivError')) ? $e->text() : '';
		$error = utf8_decode(trim($e));
		if(empty($error))
		{
			$lines = $crawler->filter('table')->eq(5)->filter('tr');
			
			$filter = function($line) use(&$lines) {
				$r = count($r = $lines->eq($line)->filter('td')->eq(1)) ? $r->text() : '';
				return utf8_decode(trim($r));
			};
			
			$data['numberplate'] = $filter(0);
			$data['category'] = $filter(1);
			$data['subcategory'] = $filter(2);
			$data['name'] = $filter(3);
			$data['address'] = $filter(4);
			$data['complement'] = $filter(5);
			$data['locality'] = $filter(6);
		}
		else
		{
			$this->dispatcher->dispatch('error.return', new GenericEvent($error));
			
			if(strpos($error, 'Code incorrect') !== false)
			{
				$data = $this->search($numberPlate);
			}
		}
		
		return $data;
	}	
}