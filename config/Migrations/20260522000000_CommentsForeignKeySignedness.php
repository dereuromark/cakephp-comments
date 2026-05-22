<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\BaseMigration;

class CommentsForeignKeySignedness extends BaseMigration {

	/**
	 * The `foreign_key` (polymorphic host record), `user_id` and `parent_id`
	 * columns reference primary keys, so they must use the same signedness as
	 * the application's primary keys, governed by the
	 * `Migrations.unsigned_primary_keys` flag. The original migration hardcoded
	 * them as unsigned, which mismatches signed-primary-key apps.
	 * Signedness only takes effect on MySQL; SQLite/Postgres ignore it.
	 *
	 * @return void
	 */
	public function up(): void {
		$signed = !(bool)Configure::read('Migrations.unsigned_primary_keys');

		$this->table('comments_comments')
			->changeColumn('foreign_key', 'integer', [
				'default' => null,
				'null' => false,
				'signed' => $signed,
			])
			->changeColumn('user_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => $signed,
			])
			->changeColumn('parent_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => $signed,
			])
			->update();
	}

	/**
	 * @return void
	 */
	public function down(): void {
		$this->table('comments_comments')
			->changeColumn('foreign_key', 'integer', [
				'default' => null,
				'null' => false,
				'signed' => false,
			])
			->changeColumn('user_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => false,
			])
			->changeColumn('parent_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => false,
			])
			->update();
	}

}
