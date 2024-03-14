<?php

namespace Comments\View\Helper;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\View\Helper;

/**
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CommentsHelper extends Helper {

	/**
	 * @var array
	 */
	protected array $helpers = [
		'Url',
	];

	/**
	 * @param string $alias
	 * @param string|int $id
	 *
	 * @throws \Cake\Http\Exception\NotFoundException
	 *
	 * @return string
	 */
	public function url(string $alias, int|string $id): string {
		$model = Configure::read('Comments.controllerModels.' . $alias);
		if (!$model) {
			throw new NotFoundException('Invalid alias');
		}

		return $this->Url->build(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', $model, $id]);
	}

}
