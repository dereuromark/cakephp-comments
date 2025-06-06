<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Comments\CommentsPlugin;
use TestApp\Controller\AppController;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('PLUGIN_ROOT', dirname(__DIR__));
define('ROOT', PLUGIN_ROOT . DS . 'tests' . DS . 'test_app');
define('TMP', PLUGIN_ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', ROOT . DS . 'src' . DS);
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', PLUGIN_ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);

define('WWW_ROOT', PLUGIN_ROOT . DS . 'webroot' . DS);
define('TESTS', __DIR__ . DS);
define('CONFIG', TESTS . 'config' . DS);

ini_set('intl.default_locale', 'de-DE');

require PLUGIN_ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
require CAKE . 'functions.php';

Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS,
		],
	],
]);

Configure::write('debug', true);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_translations_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_translations_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cache::setConfig($cache);

class_alias(AppController::class, 'App\Controller\AppController');

Plugin::getCollection()->add(new CommentsPlugin());

Chronos::setTestNow(Chronos::now());

if (!getenv('DB_URL')) {
	putenv('DB_URL=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
	'className' => Connection::class,
	'url' => getenv('DB_URL') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => false,
	'cacheMetadata' => true,
]);

/*
(new \Migrations\TestSuite\Migrator())->runMany([
	['connection' => 'test', 'plugin' => 'Comments'],
]);
*/

if (env('FIXTURE_SCHEMA_METADATA')) {
	$loader = new SchemaLoader();
	$loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}

class_alias(\TestApp\Model\Entity\User::class, 'App\Model\Entity\User');
class_alias(\TestApp\Model\Table\UsersTable::class, 'App\Model\Table\UsersTable');
