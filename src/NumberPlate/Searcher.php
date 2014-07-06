<?php
	
namespace NumberPlate;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
	
class Searcher
{
	public static function search($numberPlate)
	{
		// Client HTTP
		$client = new Client();
		$jar = new CookieJar();
		$options = array('cookies' => $jar);

		$client->get('http://www.vs.ch/cari-online/rechDet', $options);

		$captchaVal = '';

		while(strlen($captchaVal) != 6)
		{
			$captcha = $client->get('http://www.vs.ch/cari-online/drawCaptcha', $options);
			file_put_contents('captcha.png', $captcha->getBody()->__toString());
			$captchaVal = Captcha::decode('captcha.png');
		}

		$options['body'] = array(
			'pageContext' => 'login',
			'action' => 'query',
			'no' => $numberPlate,
			'cat' => '1',
			'sousCat' => '1',
			'captchaVal' => $captchaVal,
			'valider' => 'Continuer'
		);
		$response = $client->post('http://www.vs.ch/cari-online/rechDet', $options);

		$html = $response->getBody()->__toString();
		$crawler = new Crawler($html);

		$error = '';

		try
		{
			$error = trim($crawler->filter('#idDivError')->text());
		}
		catch(\InvalidArgumentException $e)
		{

		}

		$name = '';
		if(empty($error))
		{
			$name = trim($crawler->filter('table')->eq(5)->filter('tr')->eq(3)->filter('td')->eq(1)->text());
		}
		else
		{
			if(strpos($error, 'Code incorrect') !== false)
			{
				$name = static::search($numberPlate);
			}
		}
		
		return $name;
	}	
}