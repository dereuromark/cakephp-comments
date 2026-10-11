<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Entity;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Comments\Controller\CommentsController;
use Comments\Model\Entity\Comment;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesMethod;

/**
 * Comments\Controller\CommentsController Test Case
 */
#[UsesClass(CommentsController::class)]
#[UsesMethod(CommentsController::class, 'add')]
#[UsesMethod(CommentsController::class, 'delete')]
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
		Configure::delete('Comments.allowAnonymous');
		Configure::delete('Comments.adminAccess');
		Configure::delete('Comments.controllerModels');
		Configure::delete('Comments.testHostBeforeFilter');
		Configure::delete('Comments.userIdField');

		parent::tearDown();
	}

	/**
	 * Build a saved Comment for a test, bypassing the entity's tight
	 * `_accessible` allowlist that blocks `user_id` / `model` / `foreign_key`
	 * from mass assignment. Production code sets those server-side; tests
	 * mimic that by passing `accessibleFields` explicitly.
	 *
	 * @param array<string, mixed> $fields
     *
	 * @return \Comments\Model\Entity\Comment
	 */
	protected function makeComment(array $fields): Comment {
		$table = $this->fetchTable('Comments.Comments');

		return $table->saveOrFail($table->newEntity($fields, ['accessibleFields' => ['*' => true]]));
	}

	/**
	 * Test add method with 'comment' field name
	 *
	 * @return void
	 */
	public function testAdd(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.allowAnonymous', true);
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => 'This is a test comment',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect('/');
		$this->assertFlashMessage('The comment has been saved.');

		Configure::delete('Comments.controllerModels');
	}

	/**
	 * Test add method with 'content' field name (alternative)
	 *
	 * @return void
	 */
	public function testAddWithContentField(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.allowAnonymous', true);
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'content' => 'This is a test comment using content field',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect('/');

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'This is a test comment using content field'])
			->first();
		$this->assertNotNull($comment);

		Configure::delete('Comments.controllerModels');
	}

	/**
	 * Test add method with invalid alias throws NotFoundException
	 *
	 * @return void
	 */
	public function testAddInvalidAlias(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Comments.allowAnonymous', true);

		$data = [
			'comment' => 'Test',
		];

		$this->expectException(NotFoundException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Invalid', 1], $data);
	}

	/**
	 * Test add method with invalid model id throws RecordNotFoundException
	 *
	 * @return void
	 */
	public function testAddInvalidModelId(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.allowAnonymous', true);
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$data = [
			'comment' => 'Test',
		];

		$this->expectException(RecordNotFoundException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 99999], $data);
	}

	/**
	 * Test add method only allows POST/PUT/PATCH
	 *
	 * @return void
	 */
	public function testAddGetNotAllowed(): void {
		$this->disableErrorHandlerMiddleware();

		Configure::write('Comments.allowAnonymous', true);
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$this->expectException(MethodNotAllowedException::class);

		$this->get(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1]);
	}

	/**
	 * Test add method with empty content shows error
	 *
	 * @return void
	 */
	public function testAddEmptyContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.allowAnonymous', true);
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

		$this->assertRedirect('/');

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'Comment from logged in user'])
			->first();
		$this->assertNotNull($comment);
		$this->assertSame(1, $comment->user_id);
	}

	/**
	 * Test add method without user (anonymous)
	 *
	 * @return void
	 */
	public function testAddAnonymous(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$this->expectException(ForbiddenException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], [
			'comment' => 'Anonymous comment',
			'email' => '',
		]);
	}

	/**
	 * @return void
	 */
	public function testAddAnonymousAllowed(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.allowAnonymous', true);
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], [
			'comment' => 'Anonymous comment',
			'email' => '',
		]);

		$this->assertRedirect('/');

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'Anonymous comment'])
			->first();
		$this->assertNotNull($comment);
		$this->assertNull($comment->user_id);
	}

	/**
	 * Test add method with plugin model
	 *
	 * @return void
	 */
	public function testAddWithPluginModel(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.allowAnonymous', true);
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
	 * @return void
	 */
	public function testDelete(): void {
		$comment = $this->makeComment([
			'foreign_key' => 1,
			'model' => 'Posts',
			'user_id' => 1,
			'content' => 'Owned comment',
		]);
		$this->session(['Auth.User.id' => 1]);

		$this->delete(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);

		$this->assertRedirect('/');
	}

	/**
	 * Test delete method with POST and id in data
	 *
	 * @return void
	 */
	public function testDeleteWithDataId(): void {
		$comment = $this->makeComment([
			'foreign_key' => 1,
			'model' => 'Posts',
			'user_id' => 1,
			'content' => 'Owned comment via post',
		]);
		$this->session(['Auth.User.id' => 1]);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete'], ['id' => $comment->id]);

		$this->assertRedirect('/');

		$exists = $this->fetchTable('Comments.Comments')->exists(['id' => $comment->id]);
		$this->assertFalse($exists);
	}

	/**
	 * Test delete method with invalid id throws RecordNotFoundException
	 *
	 * @return void
	 */
	public function testDeleteInvalidId(): void {
		$this->disableErrorHandlerMiddleware();
		$this->session(['Auth.User.id' => 1]);

		$this->expectException(RecordNotFoundException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', 99999]);
	}

	/**
	 * Test delete method only allows POST/DELETE
	 *
	 * @return void
	 */
	public function testDeleteGetNotAllowed(): void {
		$this->disableErrorHandlerMiddleware();

		$comment = $this->makeComment([
			'foreign_key' => 1,
			'model' => 'Posts',
			'user_id' => 1,
			'content' => 'Owned comment for method test',
		]);
		$this->session(['Auth.User.id' => 1]);

		$this->expectException(MethodNotAllowedException::class);

		$this->get(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);
	}

	/**
	 * @return void
	 */
	public function testDeleteForbiddenForOtherUser(): void {
		$this->disableErrorHandlerMiddleware();

		$comment = $this->makeComment([
			'foreign_key' => 1,
			'model' => 'Posts',
			'user_id' => 1,
			'content' => 'Other user comment',
		]);
		$this->session(['Auth.User.id' => 999]);

		$this->expectException(ForbiddenException::class);

		$this->delete(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);
	}

	/**
	 * @return void
	 */
	public function testDeleteAllowedForAdminAccessGate(): void {
		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();
		Configure::write('Comments.adminAccess', static function (): bool {
			return true;
		});

		$this->delete(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);

		$this->assertRedirect('/');
		$this->assertFalse($this->fetchTable('Comments.Comments')->exists(['id' => $comment->id]));
	}

	/**
	 * Test add method with custom userIdField
	 *
	 * @return void
	 */
	public function testAddWithCustomUserIdField(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		Configure::write('Comments.controllerModels.Posts', 'Posts');
		Configure::write('Comments.userIdField', 'id');
		$this->session(['Auth.User.id' => 1]);

		$data = [
			'comment' => 'Comment with custom userIdField',
		];

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], $data);

		$this->assertRedirect('/');

		$comment = $this->fetchTable('Comments.Comments')->find()
			->where(['content' => 'Comment with custom userIdField'])
			->first();
		$this->assertNotNull($comment);
		$this->assertSame(1, $comment->user_id);

		Configure::delete('Comments.userIdField');
	}

	public function testAddUsesRequestIdentityBeforeLegacySession(): void {
		Configure::write('Comments.controllerModels.Posts', 'Posts');
		$this->session(['Auth.User.id' => 999]);
		Configure::write('Comments.testHostBeforeFilter', static function ($event): void {
			$controller = $event->getSubject();
			$controller->setRequest($controller->getRequest()->withAttribute('identity', new Entity(['id' => 1])));
		});

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], ['content' => 'Request identity']);

		$this->assertRedirect();
		$comment = $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Request identity'])->firstOrFail();
		$this->assertSame(1, $comment->user_id);
	}

	public function testAddUsesIdentifierForNonArrayIdentity(): void {
		Configure::write('Comments.controllerModels.Posts', 'Posts');
		Configure::write('Comments.testHostBeforeFilter', static function ($event): void {
			$identity = new class {
				public function getIdentifier(): int {
					return 1;
				}
			};
			$controller = $event->getSubject();
			$controller->setRequest($controller->getRequest()->withAttribute('identity', $identity));
		});

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], ['content' => 'Identifier identity']);

		$this->assertRedirect();
		$comment = $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Identifier identity'])->firstOrFail();
		$this->assertSame(1, $comment->user_id);
	}

	public function testAddStoresActualTableAliasForMappedUrlAlias(): void {
		Configure::write('Comments.controllerModels.Article', 'Posts');
		$this->session(['Auth.User.id' => 1]);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Article', 1], ['content' => 'Mapped URL alias']);

		$this->assertRedirect();
		$comment = $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Mapped URL alias'])->firstOrFail();
		$this->assertSame('Posts', $comment->model);
	}

	public function testOwnerDeleteAcceptsStringIdentityIdentifier(): void {
		$comment = $this->makeComment(['foreign_key' => 1, 'model' => 'Posts', 'user_id' => 1, 'content' => 'Owned']);
		Configure::write('Comments.testHostBeforeFilter', static function ($event): void {
			$controller = $event->getSubject();
			$controller->setRequest($controller->getRequest()->withAttribute('identity', new Entity(['id' => '1'])));
		});

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);

		$this->assertRedirect();
		$this->assertFalse($this->fetchTable('Comments.Comments')->exists(['id' => $comment->id]));
	}

	public function testDeleteParentWithRepliesPreservesBothAndShowsError(): void {
		$this->enableRetainFlashMessages();
		$parent = $this->makeComment(['foreign_key' => 1, 'model' => 'Posts', 'user_id' => 1, 'content' => 'Parent']);
		$reply = $this->makeComment(['foreign_key' => 1, 'model' => 'Posts', 'parent_id' => $parent->id, 'content' => 'Reply']);
		$this->session(['Auth.User.id' => 1]);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $parent->id]);

		$this->assertRedirect('/');
		$this->assertFlashMessage('Could not delete comment. Delete its replies first and try again.');
		$this->assertTrue($this->fetchTable('Comments.Comments')->exists(['id' => $parent->id]));
		$this->assertSame($parent->id, $this->fetchTable('Comments.Comments')->get($reply->id)->parent_id);
	}

	public function testSignedOutAuthComponentDoesNotUseStaleSession(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Comments.controllerModels.Posts', 'Posts');
		$this->session(['Auth.User.id' => 1]);
		Configure::write('Comments.testHostBeforeFilter', static function ($event): void {
			$controller = $event->getSubject();
			$controller->components()->set('AuthUser', new class ($controller->components()) extends Component {
				public function user(string $field): ?int {
					return null;
				}
			});
		});
		$this->expectException(ForbiddenException::class);

		$this->post(['plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'add', 'Posts', 1], ['content' => 'Stale session']);
	}

}
