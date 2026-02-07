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
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Configure::delete('Comments.controllerModels');

		parent::tearDown();
	}

	/**
	 * Test add method with 'comment' field name
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAdd(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => 'This is a test comment',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('The comment has been saved.');

		Configure::delete('Comments.controllerModels');
	}

	/**
	 * Test add method with 'content' field name (alternative)
	 *
	 * @uses \Comments\Controller\CommentsController::add()
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
	 * Test add method with invalid alias throws NotFoundException
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddInvalidAlias(): void {
		$this->disableErrorHandlerMiddleware();

		$data = [
			'comment' => 'Test',
		];

		$this->expectException(\Cake\Http\Exception\NotFoundException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Invalid', 1], $data);
	}

	/**
	 * Test add method with invalid model id throws RecordNotFoundException
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddInvalidModelId(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => 'Test',
		];

		$this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 99999], $data);
	}

	/**
	 * Test add method only allows POST/PUT/PATCH
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddGetNotAllowed(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);

		$this->get(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1]);
	}

	/**
	 * Test add method with empty content shows error
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddEmptyContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => '',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect();
		$this->assertFlashMessage('Could not save comment, please try again.');
	}

	/**
	 * Test add method with session user
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddWithSessionUser(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.controllerModels.Posts', 'Posts');
		$this->session(['Auth.User.id' => 1]);

		$data = [
			'comment' => 'Comment from logged in user',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect(['action' => 'index']);

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'Comment from logged in user'])
			->first();
		$this->assertNotNull($comment);
		$this->assertSame(1, $comment->user_id);
	}

	/**
	 * Test add method without user (anonymous)
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddAnonymous(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => 'Anonymous comment',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect(['action' => 'index']);

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'Anonymous comment'])
			->first();
		$this->assertNotNull($comment);
		$this->assertNull($comment->user_id);
	}

	/**
	 * Test add method with plugin model
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 * @return void
	 */
	public function testAddWithPluginModel(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'content' => 'Comment on plugin model post',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect();
	}

	/**
	 * Test delete method
	 *
	 * @uses \Comments\Controller\CommentsController::delete()
	 * @return void
	 */
	public function testDelete(): void {
		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$this->delete(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);

		$this->assertRedirect(['action' => 'index']);
	}

	/**
	 * Test delete method with POST and id in data
	 *
	 * @uses \Comments\Controller\CommentsController::delete()
	 * @return void
	 */
	public function testDeleteWithDataId(): void {
		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete'], ['id' => $comment->id]);

		$this->assertRedirect(['action' => 'index']);

		$exists = $this->fetchTable('Comments.Comments')->exists(['id' => $comment->id]);
		$this->assertFalse($exists);
	}

	/**
	 * Test delete method with invalid id throws RecordNotFoundException
	 *
	 * @uses \Comments\Controller\CommentsController::delete()
	 * @return void
	 */
	public function testDeleteInvalidId(): void {
		$this->disableErrorHandlerMiddleware();

		$this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', 99999]);
	}

	/**
	 * Test delete method only allows POST/DELETE
	 *
	 * @uses \Comments\Controller\CommentsController::delete()
	 * @return void
	 */
	public function testDeleteGetNotAllowed(): void {
		$this->disableErrorHandlerMiddleware();

		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);

		$this->get(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);
	}

}
