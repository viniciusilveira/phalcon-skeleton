<?php

namespace App\Plugins;

use Phalcon\Mvc\Dispatcher,
	Phalcon\Events\Event,
	Phalcon\Mvc\Dispatcher\Exception as DispatchException;

/**
 * @package App
 * @authors Nathan Daly, <justlikephp@gmail.com>
 * @version 1.0
 * @created 2015-01-04
 */
class ExceptionsPlugin
{
	public function beforeException(Event $event, Dispatcher $dispatcher, $exception)
	{
		//Handle 404 exceptions
		if ($exception instanceof DispatchException) {
			$dispatcher->forward(['controller' => 'error', 'action' => 'show404']);
			return false;
		}

		//Handle other exceptions
		$dispatcher->forward(['controller' => 'error', 'action' => 'show503']);
		return false;
	}
}