<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Comments\Controller\Admin\CommentsController Test Case
 *
 * @uses \Comments\Controller\Admin\CommentsController
 */
class CommentsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Comments.Comments',
		'plugin.Comments.Users',
	];

	/**
	 * Test edit method (POST)
	 *
	 * @uses \Comments\Controller\Admin\CommentsController::edit()
	 * @return void
	 */
	public function testEditPost(): void {
		$this->enableRetainFlashMessages();

		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$data = [
			'content' => 'Updated comment content',
		];

		$this->post(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'edit', $comment->id], $data);

		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('The comment has been saved.');

		$updatedComment = $this->fetchTable('Comments.Comments')->get($comment->id);
		$this->assertSame('Updated comment content', $updatedComment->content);
	}

	/**
	 * Test delete method
	 *
	 * @uses \Comments\Controller\Admin\CommentsController::delete()
	 * @return void
	 */
	public function testDelete(): void {
		$this->enableRetainFlashMessages();

		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();
		$commentId = $comment->id;

		$this->post(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $commentId]);

		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('The comment has been deleted.');

		$exists = $this->fetchTable('Comments.Comments')->exists(['id' => $commentId]);
		$this->assertFalse($exists);
	}

	/**
	 * Test delete method with invalid id
	 *
	 * @uses \Comments\Controller\Admin\CommentsController::delete()
	 * @return void
	 */
	public function testDeleteNotFound(): void {
		$this->disableErrorHandlerMiddleware();

		$this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', 99999]);
	}

	/**
	 * Test delete method only allows POST/DELETE
	 *
	 * @uses \Comments\Controller\Admin\CommentsController::delete()
	 * @return void
	 */
	public function testDeleteGetNotAllowed(): void {
		$this->disableErrorHandlerMiddleware();

		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);
	}

}
