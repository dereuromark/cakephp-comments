<?php

namespace Comments\Utility;

use Cake\Core\Configure;
use Cake\ORM\Table;

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

		return $model ?? $table->getAlias();
	}

}
