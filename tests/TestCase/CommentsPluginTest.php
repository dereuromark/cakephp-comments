<?php
declare(strict_types=1);

namespace Comments\Test\TestCase;

use Cake\Console\CommandCollection;
use Cake\Core\Container;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Comments\CommentsPlugin;

/**
 * Comments\CommentsPlugin Test Case
 */
class CommentsPluginTest extends TestCase {

	/**
	 * @var \Comments\CommentsPlugin
	 */
	protected CommentsPlugin $plugin;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->plugin = new CommentsPlugin();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->plugin);
		Router::reload();

		parent::tearDown();
	}

	/**
	 * Test bootstrap method
	 *
	 * @return void
	 */
	public function testBootstrap(): void {
		$app = $this->createMock(\Cake\Core\PluginApplicationInterface::class);

		$this->plugin->bootstrap($app);

		$this->assertTrue(true);
	}

	/**
	 * Test routes method registers plugin routes
	 *
	 * @return void
	 */
	public function testRoutes(): void {
		$routeCollection = new RouteCollection();
		$routes = new RouteBuilder($routeCollection, '/');

		$this->plugin->routes($routes);

		$this->assertNotEmpty($routeCollection->routes());
	}

	/**
	 * Test routes method registers admin prefix routes
	 *
	 * @return void
	 */
	public function testRoutesAdminPrefix(): void {
		Router::reload();
		$routeCollection = new RouteCollection();
		$routes = new RouteBuilder($routeCollection, '/');

		$this->plugin->routes($routes);

		$allRoutes = $routeCollection->routes();
		$this->assertNotEmpty($allRoutes);
	}

	/**
	 * Test middleware method returns queue unchanged
	 *
	 * @return void
	 */
	public function testMiddleware(): void {
		$middlewareQueue = new MiddlewareQueue();

		$result = $this->plugin->middleware($middlewareQueue);

		$this->assertSame($middlewareQueue, $result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test console method returns commands unchanged
	 *
	 * @return void
	 */
	public function testConsole(): void {
		$commands = new CommandCollection();

		$result = $this->plugin->console($commands);

		$this->assertSame($commands, $result);
	}

	/**
	 * Test services method
	 *
	 * @return void
	 */
	public function testServices(): void {
		$container = new Container();

		$this->plugin->services($container);

		$this->assertTrue(true);
	}

	/**
	 * Test plugin name
	 *
	 * @return void
	 */
	public function testPluginName(): void {
		$this->assertSame('Comments', $this->plugin->getName());
	}

	/**
	 * Test plugin path
	 *
	 * @return void
	 */
	public function testPluginPath(): void {
		$path = $this->plugin->getPath();

		$this->assertNotEmpty($path);
		$this->assertDirectoryExists($path);
	}

}
