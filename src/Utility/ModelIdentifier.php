<?php

namespace Comments\Utility;

use Cake\Core\Configure;
use Cake\ORM\Table;
use InvalidArgumentException;

final class ModelIdentifier {

	/**
	 * @param \Cake\ORM\Table $table Commented table
	 *
	 * @return string
	 */
	public static function resolve(Table $table): string {
		$model = $table->hasBehavior('Commentable')
			? $table->getBehavior('Commentable')->getConfig('model')
			: Configure::read('Comments.model');

		$model ??= $table->getAlias();
		if (!is_string($model) || $model === '') {
			throw new InvalidArgumentException('The comment model identifier must be a non-empty string.');
		}

		return $model;
	}

}
