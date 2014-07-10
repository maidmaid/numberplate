<?php
	
namespace NumberPlate;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

abstract class AbstractSearcherSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array(
            'cookie.initialize' => array('onCookieInitialize', 0),
            'captcha.download' => array('onCaptchaDownload', 0),
            'captcha.decode' => array('onCaptchaDecode', 0),
            'search.send' => array('onSearchSend', 0),
            'error.return' => array('onErrorReturn', 0)
        );
	}
	
	public function onCookieInitialize(GenericEvent $e)
	{

	}
	
	public function onCaptchaDownload(Event $e)
	{

	}
	
	public function onCaptchaDecode(GenericEvent $e)
	{

	}
	
	public function onSearchSend(GenericEvent $e)
	{

	}
	
	public function onErrorReturn(GenericEvent $e)
	{

	}
}