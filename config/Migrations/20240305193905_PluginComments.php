<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class PluginComments extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('comments_comments')
			->addColumn('foreign_key', 'integer', [
				'default' => null,
				'null' => false,
				'signed' => false,
			])
			->addColumn('model', 'string', [
				'default' => null,
				'limit' => 80,
				'null' => false,
			])
			->addColumn('user_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => false,
			])
			->addColumn('parent_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => false,
			])
			->addColumn('name', 'string', [
				'default' => null,
				'limit' => 40,
				'null' => true,
			])
			->addColumn('email', 'string', [
				'default' => null,
				'limit' => 80,
				'null' => true,
			])
			->addColumn('title', 'string', [
				'default' => null,
				'limit' => 80,
				'null' => true,
			])
			->addColumn('content', 'text', [
				'default' => null,
				'limit' => 16777215,
				'null' => false,
			])
			->addColumn('is_private', 'boolean', [
				'default' => false,
				'null' => false,
			])
			->addColumn('is_spam', 'boolean', [
				'default' => false,
				'null' => false,
			])
			->addColumn('created', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->addColumn('modified', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->addIndex(
				[
					'user_id',
				],
				[
					'name' => 'comments-parent_id',
				],
			)
			->addIndex(
				[
					'model',
					'foreign_key',
				],
				[
					'name' => 'comments-foreign_key',
				],
			)
			->create();
	}

}
