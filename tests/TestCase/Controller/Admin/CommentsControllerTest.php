<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller\Admin;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Comments\Controller\Admin\CommentsController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesMethod;
use RuntimeException;

/**
 * Comments\Controller\Admin\CommentsController Test Case
 */
#[UsesClass(CommentsController::class)]
#[UsesMethod(CommentsController::class, 'delete')]
#[UsesMethod(CommentsController::class, 'edit')]
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
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// The plugin's beforeFilter requires `Comments.adminAccess` to be a Closure
		// returning true for permitted callers.
		Configure::write('Comments.adminAccess', function ($request): bool {
			return true;
		});
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Configure::delete('Comments.adminAccess');
		Configure::delete('Comments.testHostBeforeFilter');

		parent::tearDown();
	}

	public function testHostRedirectIsPreservedBeforeAdminGate(): void {
		Configure::delete('Comments.adminAccess');
		Configure::write('Comments.testHostBeforeFilter', fn () => (new Response())->withStatus(302)->withHeader('Location', '/login'));

		$this->get('/admin/comments');

		$this->assertRedirect('/login');
	}

	public function testHostEventResponseIsPreservedBeforeAdminGate(): void {
		Configure::delete('Comments.adminAccess');
		Configure::write('Comments.testHostBeforeFilter', function ($event): void {
			$event->setResult((new Response())->withStatus(302)->withHeader('Location', '/login'));
		});

		$this->get('/admin/comments');

		$this->assertRedirect('/login');
	}

	/**
	 * Test edit method (POST)
	 *
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
	 * @return void
	 */
	public function testDeleteNotFound(): void {
		$this->disableErrorHandlerMiddleware();

		$this->expectException(RecordNotFoundException::class);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', 99999]);
	}

	/**
	 * Test delete method only allows POST/DELETE
	 *
	 * @return void
	 */
	public function testDeleteGetNotAllowed(): void {
		$this->disableErrorHandlerMiddleware();

		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();

		$this->expectException(MethodNotAllowedException::class);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'delete', $comment->id]);
	}

	public function testIndexRendersWithoutToolsOrIconPlugins(): void {
		$this->get('/admin/comments');

		$this->assertResponseOk();
		$this->assertResponseContains('Lorem ipsum');
	}

	public function testViewRendersWithoutToolsOrIconPlugins(): void {
		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();
		$this->get(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'view', $comment->id]);

		$this->assertResponseOk();
		$this->assertResponseContains('Related Comments');
	}

	public function testEditUpdatesModerationWithoutReassigningRecord(): void {
		$comment = $this->fetchTable('Comments.Comments')->find()->firstOrFail();
		$this->post(['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments', 'action' => 'edit', $comment->id], [
			'is_private' => false,
			'is_spam' => false,
			'model' => 'Forged',
			'foreign_key' => 999,
		]);

		$this->assertRedirect();
		$updated = $this->fetchTable('Comments.Comments')->get($comment->id);
		$this->assertFalse($updated->is_private);
		$this->assertFalse($updated->is_spam);
		$this->assertSame($comment->model, $updated->model);
		$this->assertSame($comment->foreign_key, $updated->foreign_key);
	}

	public function testUnexpectedGateFailureDeniesAccess(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Comments.adminAccess', static function (): never {
			throw new RuntimeException('Private gate failure');
		});
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('Comments admin access denied.');

		$this->get('/admin/comments');
	}

	public function testExplicitHttpExceptionFromGateIsPreserved(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Comments.adminAccess', static function (): never {
			throw new NotFoundException('Hidden admin');
		});
		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Hidden admin');

		$this->get('/admin/comments');
	}

	#[DataProvider('hostResponseProvider')]
	public function testHostResponseStopsListenersAndMarksAuthorization(bool $returned, bool $withComponent): void {
		$authorization = new class {
			public bool $checked = false;

			public function skipAuthorization(): void {
				$this->checked = true;
			}
		};
		Configure::delete('Comments.adminAccess');
		Configure::write('Comments.testHostBeforeFilter', static function ($event) use ($returned): ?Response {
			$response = (new Response())->withStatus(302)->withHeader('Location', '/login');
			if ($returned) {
				return $response;
			}
			$event->setResult($response);

			return null;
		});
		$request = (new ServerRequest())->withAttribute('authorization', $authorization);
		$controller = new CommentsController($request);
		if ($withComponent) {
			$controller->components()->set('Authorization', new class ($controller->components(), ['authorization' => $authorization]) extends Component {
				public function skipAuthorization(): void {
					$this->getConfig('authorization')->skipAuthorization();
				}
			});
		}
		$manager = $controller->getEventManager();
		$manager->on('Controller.initialize', ['priority' => 100], static function (): never {
			throw new RuntimeException('A later listener must not run');
		});
		$event = new Event('Controller.initialize', $controller);
		$manager->dispatch($event);

		$this->assertTrue($event->isStopped());
		$this->assertInstanceOf(Response::class, $event->getResult());
		$this->assertSame('/login', $event->getResult()->getHeaderLine('Location'));
		$this->assertTrue($authorization->checked);
	}

	/**
	 * @return array<string, array<bool>>
	 */
	public static function hostResponseProvider(): array {
		return [
			'returned with service' => [true, false],
			'event result with service' => [false, false],
			'returned with component' => [true, true],
			'event result with component' => [false, true],
		];
	}

	public function testLoadedAuthorizationComponentWithoutRequestServicePreservesHostRedirect(): void {
		Configure::delete('Comments.adminAccess');
		Configure::write('Comments.testHostBeforeFilter', fn () => (new Response())->withStatus(302)->withHeader('Location', '/login'));
		$controller = new CommentsController(new ServerRequest());
		$controller->components()->set('Authorization', new class ($controller->components()) extends Component {
			public function skipAuthorization(): never {
				throw new RuntimeException('No Authorization middleware on this route');
			}
		});
		$event = new Event('Controller.initialize', $controller);

		$controller->getEventManager()->dispatch($event);

		$this->assertInstanceOf(Response::class, $event->getResult());
		$this->assertSame('/login', $event->getResult()->getHeaderLine('Location'));
	}

}
