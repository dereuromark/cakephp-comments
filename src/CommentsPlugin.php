<?php

declare(strict_types=1);

namespace Comments;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for Comments
 */
class CommentsPlugin extends BasePlugin {

	/**
	 * Load all the plugin configuration and bootstrap logic.
	 *
	 * The host application is provided as an argument. This allows you to load
	 * additional plugin dependencies, or attach events.
	 *
	 * @param \Cake\Core\PluginApplicationInterface $app The host application
	 *
	 * @return void
	 */
	public function bootstrap(PluginApplicationInterface $app): void {
	}

	/**
	 * Add routes for the plugin.
	 *
	 * If your plugin has many routes and you would like to isolate them into a separate file,
	 * you can create `$plugin/config/routes.php` and delete this method.
	 *
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 *
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin(
			'Comments',
			['path' => '/comments'],
			function (RouteBuilder $builder) {
				$builder->fallbacks();
			},
		);

		$routes->prefix('Admin', function (RouteBuilder $builder) {
			$builder->plugin(
				'Comments',
				['path' => '/comments'],
				function (RouteBuilder $builder) {
					$builder->fallbacks();
				},
			);
		});

		parent::routes($routes);
	}

	/**
	 * Add middleware for the plugin.
	 *
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
	 *
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		return $middlewareQueue;
	}

	/**
	 * Add commands for the plugin.
	 *
	 * @param \Cake\Console\CommandCollection $commands The command collection to update.
	 *
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		return $commands;
	}

	/**
	 * Register application container services.
	 *
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 *
	 * @param \Cake\Core\ContainerInterface $container The Container to update.
	 *
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
		// Add your services here
	}

}
