<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Comments\Controller\CommentsController Test Case
 *
 * @uses \Comments\Controller\CommentsController
 */
class CommentsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Fixtures
	 *
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Comments.Comments',
		'plugin.Comments.Posts',
		'plugin.Comments.Users',
	];

	/**
	 * Test add method with 'comment' field name
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 *
	 * @return void
	 */
	public function testAdd(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => 'This is a test comment',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect(['action' => 'index']);

		Configure::delete('Comments.controllerModels');
	}

	/**
	 * Test add method with 'content' field name (alternative)
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 *
	 * @return void
	 */
	public function testAddWithContentField(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'content' => 'This is a test comment using content field',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect(['action' => 'index']);

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'This is a test comment using content field'])
			->first();
		$this->assertNotNull($comment);

		Configure::delete('Comments.controllerModels');
	}

	/**
	 * Test delete method
	 *
	 * @uses \Comments\Controller\CommentsController::delete()
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$this->delete(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);

		$this->assertRedirect(['action' => 'index']);
	}

}
