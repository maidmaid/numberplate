<?php
	
namespace NumberPlate;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
	
class Searcher
{
	/* @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcher */
	private $dispatcher;
	
	/* @var $client Client */
	private $client;

	/* @var $jar CookieJar */
	private $jar;
	
	public function __construct()
	{
		$this->client = new Client();
		$this->jar = new CookieJar();
		$this->dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
	}
	
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
			$this->dispatcher->dispatch('cookie.initialize');
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
			$this->dispatcher->dispatch('captcha.decode');
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
		$this->dispatcher->dispatch('search.send');

		// Crawler
		$html = $response->getBody()->__toString();
		$crawler = new Crawler($html);

		// Traitements des erreurs
		$error = '';
		try
		{
			$error = trim($crawler->filter('#idDivError')->text());
			$this->dispatcher->dispatch('error.return');
		}
		catch(\InvalidArgumentException $e)
		{

		}

		// Extrait les données
		$name = '';
		if(empty($error))
		{
			$name = trim($crawler->filter('table')->eq(5)->filter('tr')->eq(3)->filter('td')->eq(1)->text());
		}
		else
		{
			if(strpos($error, 'Code incorrect') !== false)
			{
				$name = $this->search($numberPlate);
			}
		}
		
		return $name;
	}	
}