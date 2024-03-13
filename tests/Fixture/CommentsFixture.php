<?php
declare(strict_types=1);

namespace Comments\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CommentsFixture
 */
class CommentsFixture extends TestFixture {

	/**
	 * Table name
	 *
	 * @var string
	 */
	public string $table = 'comments_comments';

	/**
	 * Init method
	 *
	 * @return void
	 */
	public function init(): void {
		$this->records = [
			[
				'foreign_key' => 1,
				'model' => 'Posts',
				'user_id' => null,
				'name' => 'Lorem ipsum dolor sit amet',
				'email' => 'foo@bar.de',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
				'is_private' => 1,
				'is_spam' => 1,
				'parent_id' => null,
				'created' => '2024-03-13 02:01:23',
				'modified' => '2024-03-13 02:01:23',
			],
		];
		parent::init();
	}

}
