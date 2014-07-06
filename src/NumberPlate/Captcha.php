<?php 

namespace NumberPlate;

use TesseractOCR;

class Captcha
{	
	static public function decode($filename = 'http://www.vs.ch/cari-online/drawCaptcha')
	{
		$path = getenv('PATH');
		putenv("Path=$path;\"C:\\Program Files (x86)\\Tesseract-OCR\"");
		
		$time = microtime(true);
		$prefix = __DIR__ . '/../../cache/' . $time . '_';
		$filenameEdit = $prefix . 'edit.jpg';

		// Captcha
		$captcha = imagecreatefromjpeg($filename);
		list($width, $height) = getimagesize($filename);

		// Crée une nouvelle image contrastée
		$new = imagecreate($width, $height);
		imagecolorallocate($new, 255, 255, 255);
		$black = imagecolorallocate($new, 0, 0, 0);
		for($x = 0; $x < $width; $x++)
		{
			for($y = 0; $y < $height; $y++)
			{
				$rgb = imagecolorat($captcha, $x, $y);
				$colors = imagecolorsforindex($captcha, $rgb);

				if($colors['red'] == 255 && $colors['green'] == 255 && $colors['blue'] == 255)
				{
					imagesetpixel($new, $x, $y, $black);
				}
			}
		}
		imagejpeg($new, $filenameEdit, 100);

		// Analase OCR
		$ocr = new TesseractOCR($filenameEdit);
		$ocr->setTempDir(__DIR__ . '/../../cache/ocr');
		$ocr->setWhitelist(range('0', '9'), range('a', 'z'), range('A', 'Z'));
		$decoded = $ocr->recognize();
		$decoded = str_replace(' ', '', $decoded);

		return $decoded;
	}
}