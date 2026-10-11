<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;

/**
 * @property \TinyAuth\Controller\Component\AuthUserComponent $AuthUser
 * @property \TinyAuth\Controller\Component\AuthComponent $Auth
 */
class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return mixed
	 */
	public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);
		$callback = Configure::read('Comments.testHostBeforeFilter');
		if (is_callable($callback)) {
			return $callback($event);
		}

		return null;
	}

}
