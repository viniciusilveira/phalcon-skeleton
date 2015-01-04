<?php

namespace App\Controllers\Base;

use Phalcon\Mvc\Controller as PhalconController;

/**
 * Base Controller to set up the defaults for the controllers
 *
 * @package App
 * @authors Nathan Daly, <justlikephp@gmail.com>
 * @version 1.0
 * @created 2015-01-04
 *
 * @property \Phalcon\Assets\Manager $assets
 * @property \Phalcon\Mvc\View $view
 * @property \App\Library\I18n $i18n
 * @property \Phalcon\Tag $tag
 * @property \Phalcon\Flash\Session $flash
 * @property \Phalcon\Session\AdapterInterface $session
 */
class Controller extends PhalconController
{
	/**
	 * Initialize the base controller
	 *
	 * @return void
	 */
	public function initialize()
	{
		//Add some CSS resources
		$this->assets
			->addCss('css/bootstrap.min.css')
			->addCss('//fonts.googleapis.com/css?family=Open+Sans:400,600,300', false)
			->addCss('//fonts.googleapis.com/css?family=Ubuntu:regular,bold&subset=Latin', false)
			->addCss('//weloveiconfonts.com/api/?family=fontawesome|iconicfill', false);

		//and some javascript resources
		$this->assets
			->addJs('//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js', false)
			->addJs('js/bootstrap.min.js')
			->addJs('js/jquery.plugin.min.js');
	}
}
