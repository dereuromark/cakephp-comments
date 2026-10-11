<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Comments\Controller\Component\CommentComponent;
use TestApp\Controller\AppController;

/**
 * Comments\Controller\Component\CommentComponent Test Case
 */
class CommentComponentTest extends TestCase {

	/**
	 * @var \Comments\Controller\Component\CommentComponent
	 */
	protected CommentComponent $Component;

	/**
	 * @var \TestApp\Controller\AppController
	 */
	protected AppController $Controller;

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
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes) {
			$routes->connect('/{controller}/{action}/*');
			$routes->plugin('Comments', ['path' => '/comments'], function (RouteBuilder $builder) {
				$builder->fallbacks();
			});
		});

		$request = new ServerRequest([
			'url' => '/posts/view/1',
			'params' => [
				'controller' => 'Posts',
				'action' => 'view',
				'pass' => [1],
			],
		]);

		$this->Controller = new AppController($request);
		$this->Controller->setName('Posts');

		$registry = new ComponentRegistry($this->Controller);
		$this->Component = new CommentComponent($registry);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Component, $this->Controller);
		Configure::delete('Comments');
		Router::reload();

		parent::tearDown();
	}

	/**
	 * Test initialize method
	 *
	 * @return void
	 */
	public function testInitialize(): void {
		$this->Component->initialize([]);

		$this->assertSame('Users', $this->Component->getConfig('userModel'));
		$this->assertSame('Users', $this->Component->getConfig('userModelClass'));
		$this->assertSame('id', $this->Component->getConfig('userIdField'));
	}

	/**
	 * Test initialize with config from Configure
	 *
	 * @return void
	 */
	public function testInitializeWithConfigure(): void {
		Configure::write('Comments.userModelClass', 'Members');
		Configure::write('Comments.allowAnonymous', true);

		$request = new ServerRequest(['url' => '/posts/view/1']);
		$controller = new AppController($request);
		$registry = new ComponentRegistry($controller);
		$component = new CommentComponent($registry);
		$component->initialize([]);

		$this->assertSame('Members', $component->getConfig('userModelClass'));
		$this->assertTrue($component->getConfig('allowAnonymous'));
	}

	/**
	 * Test initialize with plugin model class
	 *
	 * @return void
	 */
	public function testInitializeWithPluginModelClass(): void {
		$this->Component->initialize(['userModelClass' => 'MyPlugin.Users']);

		$this->assertSame('Users', $this->Component->getConfig('userModel'));
	}

	/**
	 * Test default config values
	 *
	 * @return void
	 */
	public function testDefaultConfig(): void {
		$this->Component->initialize([]);

		$this->assertSame('beforeRender', $this->Component->getConfig('on'));
		$this->assertFalse($this->Component->getConfig('allowAnonymous'));
		$this->assertTrue($this->Component->getConfig('useEntity'));
		$this->assertNull($this->Component->getConfig('viewVariable'));
	}

	public function testFlatCommentsAreScopedToViewedRecord(): void {
		$table = $this->fetchTable('Comments.Comments');
		$table->add(['model' => 'Posts', 'foreign_key' => 2, 'content' => 'Other post']);
		$table->add(['model' => 'Articles', 'foreign_key' => 1, 'content' => 'Other model']);
		$this->Component->startup(new Event('Controller.startup', $this->Controller));
		$comments = $this->Component->callbackFetchDataFlat(['id' => 1]);

		$this->assertCount(1, $comments);
		foreach ($comments as $comment) {
			$this->assertSame('Posts', $comment->model);
			$this->assertSame(1, $comment->foreign_key);
		}
	}

	public function testAnonymousCommentIsDeniedByDefault(): void {
		$this->Controller->setRequest($this->Controller->getRequest()->withMethod('POST')->withParsedBody(['comment' => 'Anonymous example']));
		$this->Component->startup(new Event('Controller.startup', $this->Controller));
		$this->Controller->set('post', $this->fetchTable('Posts')->get(1));

		$this->expectException(ForbiddenException::class);
		$this->Component->beforeRender(new Event('Controller.beforeRender', $this->Controller));
	}

	public function testAnonymousCommentCanBeEnabled(): void {
		$this->Controller->setRequest($this->Controller->getRequest()->withMethod('POST')->withParsedBody(['content' => 'Anonymous example']));
		$this->Component->setConfig('allowAnonymous', true);
		$this->Component->startup(new Event('Controller.startup', $this->Controller));
		$this->Controller->set('post', $this->fetchTable('Posts')->get(1));
		$this->Component->beforeRender(new Event('Controller.beforeRender', $this->Controller));

		$comment = $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Anonymous example'])->firstOrFail();
		$this->assertNull($comment->user_id);
		$this->assertSame('Posts', $comment->model);
	}

	/**
	 * Test startup with actions filter - action not in list
	 *
	 * @return void
	 */
	public function testStartupWithActionsFilterNotMatching(): void {
		$this->Component->initialize(['actions' => ['index']]);

		$event = new Event('Controller.startup', $this->Controller);
		$this->Component->startup($event);

		$this->assertNull($event->getResult());
	}

	/**
	 * Test callbackInitType returns flat by default
	 *
	 * @return void
	 */
	public function testCallbackInitType(): void {
		$this->Component->initialize([]);

		$result = $this->Component->callbackInitType();

		$this->assertSame('flat', $result);
	}

	/**
	 * Test callbackPrepareParams
	 *
	 * @return void
	 */
	public function testCallbackPrepareParams(): void {
		$this->Component->initialize([]);

		$this->Component->callbackPrepareParams();

		$this->assertTrue(true);
	}

	/**
	 * Test permalink generates URL
	 *
	 * @return void
	 */
	public function testPermalink(): void {
		$request = new ServerRequest([
			'url' => '/posts/view/1',
			'params' => [
				'controller' => 'Posts',
				'action' => 'view',
				'pass' => [1],
			],
		]);

		$controller = new AppController($request);
		$controller->setName('Posts');
		$registry = new ComponentRegistry($controller);
		$component = new CommentComponent($registry);
		$component->initialize([]);

		$result = $component->permalink();

		$this->assertIsString($result);
		$this->assertStringContainsString('posts', strtolower($result));
	}

	/**
	 * Test flash sets flash message
	 *
	 * @return void
	 */
	public function testFlash(): void {
		$this->Component->initialize([]);

		$this->Component->flash('Test message');

		$this->assertTrue(true);
	}

	/**
	 * Test flash with ajax mode sets view variable
	 *
	 * @return void
	 */
	public function testFlashAjaxMode(): void {
		$request = new ServerRequest([
			'url' => '/posts/view/1',
			'params' => [
				'controller' => 'Posts',
				'action' => 'view',
				'isAjax' => true,
			],
		]);

		$controller = new AppController($request);
		$controller->setName('Posts');
		$registry = new ComponentRegistry($controller);
		$component = new CommentComponent($registry);
		$component->initialize([]);

		$component->flash('Ajax message');

		$this->assertTrue(true);
	}

	/**
	 * Test prgRedirect for ajax sets view variables
	 *
	 * @return void
	 */
	public function testPrgRedirectAjax(): void {
		$request = new ServerRequest([
			'url' => '/posts/view/1',
			'params' => [
				'controller' => 'Posts',
				'action' => 'view',
				'pass' => [1],
				'isAjax' => true,
			],
		]);

		$controller = new AppController($request);
		$controller->setName('Posts');
		$registry = new ComponentRegistry($controller);
		$component = new CommentComponent($registry);
		$component->initialize([]);

		$result = $component->prgRedirect();

		$this->assertNull($result);
	}

	/**
	 * Test config with custom options
	 *
	 * @return void
	 */
	public function testConfigCustomOptions(): void {
		$this->Component->initialize([
			'on' => 'startup',
			'allowAnonymous' => true,
			'useEntity' => false,
			'viewVariable' => 'article',
		]);

		$this->assertSame('startup', $this->Component->getConfig('on'));
		$this->assertTrue($this->Component->getConfig('allowAnonymous'));
		$this->assertFalse($this->Component->getConfig('useEntity'));
		$this->assertSame('article', $this->Component->getConfig('viewVariable'));
	}

	/**
	 * Test beforeRender with actions filter not matching
	 *
	 * @return void
	 */
	public function testBeforeRenderWithActionsFilterNotMatching(): void {
		$this->Component->initialize(['actions' => ['index']]);

		$event = new Event('Controller.beforeRender', $this->Controller);
		$this->Component->beforeRender($event);

		$this->assertNull($event->getResult());
	}

	public function testContentZeroWithCustomViewVariableAndIdentity(): void {
		$this->Component->setConfig('viewVariable', 'article');
		$request = $this->Controller->getRequest()->withMethod('POST')
			->withParsedBody(['content' => '0'])
			->withAttribute('identity', new Entity(['id' => 1]));
		$this->Controller->setRequest($request);
		$this->Controller->set('article', $this->fetchTable('Posts')->get(1));
		$this->Component->startup(new Event('Controller.startup', $this->Controller));
		$event = new Event('Controller.beforeRender', $this->Controller);

		$this->Component->beforeRender($event);

		$comment = $this->fetchTable('Comments.Comments')->find()->where(['content' => '0'])->firstOrFail();
		$this->assertSame(1, $comment->user_id);
		$this->assertSame(1, $comment->foreign_key);
		$this->assertInstanceOf(Response::class, $event->getResult());
		$this->assertSame('/Posts/view/1', $event->getResult()->getHeaderLine('Location'));
	}

	public function testGetBodyDoesNotCreateComment(): void {
		$this->Component->setConfig('allowAnonymous', true);
		$this->Controller->setRequest($this->Controller->getRequest()->withParsedBody(['content' => 'Read-only request']));
		$this->Controller->set('post', $this->fetchTable('Posts')->get(1));
		$comments = $this->fetchTable('Comments.Comments');
		$count = $comments->find()->count();
		$this->Component->startup(new Event('Controller.startup', $this->Controller));

		$this->Component->beforeRender(new Event('Controller.beforeRender', $this->Controller));

		$this->assertSame($count, $comments->find()->count());
	}

}
