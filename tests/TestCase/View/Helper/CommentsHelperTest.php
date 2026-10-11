<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Comments\View\Helper\CommentsHelper;

/**
 * Comments\View\Helper\CommentsHelper Test Case
 */
class CommentsHelperTest extends TestCase {

	/**
	 * @var \Comments\View\Helper\CommentsHelper
	 */
	protected CommentsHelper $helper;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes) {
			$routes->setRouteClass(DashedRoute::class);
			$routes->plugin('Comments', function (RouteBuilder $builder) {
				$builder->fallbacks();
			});
		});

		$view = new View();
		$this->helper = new CommentsHelper($view);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->helper);
		Configure::delete('Comments.controllerModels');
		Router::reload();

		parent::tearDown();
	}

	/**
	 * Test url method generates correct URL with alias
	 *
	 * @return void
	 */
	public function testUrl(): void {
		Configure::write('Comments.controllerModels.Posts', 'Posts');

		$result = $this->helper->url('Posts', 123);

		$this->assertStringContainsString('/comments/comments/add/Posts/123', $result);
	}

	/**
	 * Test url method with plugin model
	 *
	 * @return void
	 */
	public function testUrlWithPluginModel(): void {
		Configure::write('Comments.controllerModels.Articles', 'Blog.Articles');

		$result = $this->helper->url('Articles', 456);

		// Should use alias 'Articles' in URL, not 'Blog.Articles'
		$this->assertStringContainsString('/comments/comments/add/Articles/456', $result);
		$this->assertStringNotContainsString('Blog.Articles', $result);
	}

	/**
	 * Test url method throws exception for invalid alias
	 *
	 * @return void
	 */
	public function testUrlInvalidAlias(): void {
		$this->expectException(NotFoundException::class);

		$this->helper->url('NonExistent', 1);
	}

	public function testUrlClearsAdminPrefix(): void {
		Configure::write('Comments.controllerModels.Posts', 'Posts');
		$request = new ServerRequest(['params' => ['prefix' => 'Admin', 'plugin' => 'Comments', 'controller' => 'Comments']]);
		Router::setRequest($request);
		$helper = new CommentsHelper(new View($request));

		$result = $helper->url('Posts', 123);

		$this->assertStringContainsString('/comments/comments/add/Posts/123', $result);
		$this->assertStringNotContainsString('/admin/', $result);
	}

}
