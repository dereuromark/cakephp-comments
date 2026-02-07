<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;

/**
 * Posts Model
 */
class PostsTable extends Table {

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('posts');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');
	}

}
