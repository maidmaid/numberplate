<?php 

namespace NumberPlate;

use TesseractOCR;

class Captcha
{	
	static public function decode($filename = 'http://www.vs.ch/cari-online/drawCaptcha')
	{
		$time = microtime(true);
		$filenameOriginal = __DIR__ . '/../../cache/' . $time . '_o.jpg';
		$filenameEdit = __DIR__ . '/../../cache/' . $time . '_e.jpg';	

		// clean clache
		static::cleanCache();
		
		// Captcha
		$captcha = imagecreatefromjpeg($filename);
		imagejpeg($captcha, $filenameOriginal, 100);
		list($width, $height) = getimagesize($filenameOriginal);

		// Crée une nouvelle image contrastée
		$edit = imagecreate($width, $height);
		imagecolorallocate($edit, 255, 255, 255);
		$black = imagecolorallocate($edit, 0, 0, 0);
		for($x = 0; $x < $width; $x++)
		{
			for($y = 0; $y < $height; $y++)
			{
				$rgb = imagecolorat($captcha, $x, $y);
				$colors = imagecolorsforindex($captcha, $rgb);

				if($colors['red'] == 255 && $colors['green'] == 255 && $colors['blue'] == 255)
				{
					imagesetpixel($edit, $x, $y, $black);
				}
			}
		}
		imagejpeg($edit, $filenameEdit, 100);

		// Analase OCR
		$ocr = new TesseractOCR($filenameEdit);
		$ocr->setTempDir(__DIR__ . '/../../cache/ocr');
		$ocr->setWhitelist(range('0', '9'), range('a', 'z'), range('A', 'Z'));
		$decoded = $ocr->recognize();
		$decoded = str_replace(' ', '', $decoded);

		return $decoded;
	}
	
	static public function cleanCache()
	{
		$cache = __DIR__ . '/../../cache/';
		$exclude = array('.', '..', 'ocr', 'index.html');
		
		// clean cache/
		$files = scandir($cache, SCANDIR_SORT_DESCENDING);
		foreach($files as $i => $file)
		{
			if ($i < 20 || in_array($file, $exclude)) {
				continue;
			}
			
			unlink($cache . $file);
		}
		
		// clean cache/ocr/
		$files = scandir($cache . 'ocr', SCANDIR_SORT_DESCENDING);
		foreach($files as $file)
		{
			if(!in_array($file, $exclude))
			{
				unlink($cache . 'ocr/' . $file);
			}
		}
	}
}