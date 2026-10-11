<?php

namespace Comments\Controller\Component;

use ArrayAccess;
use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use RuntimeException;

/**
 * @property \Cake\Controller\Component\FlashComponent $Flash
 *
 * @method \App\Controller\AppController getController()
 */
class CommentComponent extends Component {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'on' => 'beforeRender',
		'userModelClass' => 'Users',
		'userIdField' => 'id',
		'sessionKey' => null, // Defaults to 'Auth.User', use 'Auth' for CakeDC/Users
		'allowAnonymous' => false,
		'useEntity' => true,
		'viewVariable' => null,
	];

	/**
	 * Components
	 *
	 * @var array<int|string, array<string, mixed>|string>
	 */
	protected array $components = [
		'Flash',
	];

	/**
	 * Controller
	 *
	 * @var \App\Controller\AppController
	 */
	protected $Controller;

	/**
	 * Name of 'commentable' model
	 *
	 * Customizable in beforeFilter(), or default controller's model name is used
	 *
	 * @var string|null Model name
	 */
	protected $modelAlias;

	/**
	 * Name of association for comments
	 *
	 * Customizable in beforeFilter()
	 *
	 * @var string Association name
	 */
	protected $assocName = 'Comments';

	/**
	 * Name of user model associated to comment
	 *
	 * Customizable in beforeFilter()
	 *
	 * @var string Name of the user model
	 */
	protected $userModel = 'Users';

	/**
	 * Flag if this component should permanently unbind association to Comment model in order to not
	 * query model for not necessary data in Controller::view() action
	 *
	 * Customizable in beforeFilter()
	 *
	 * @var bool
	 */
	protected $unbindAssoc = false;

	/**
	 * Parameters passed to view
	 *
	 * @var array
	 */
	protected array $commentParams = [];

	/**
	 * Name of view variable which contains model data for view() action
	 *
	 * Needed just for PK value available in it
	 *
	 * Customizable in beforeFilter(), or default Inflector::variable($this->modelAlias)
	 *
	 * @var string|null
	 */
	protected $viewVariable;

	/**
	 * Name of view variable for comments data
	 *
	 * Customizable in beforeFilter()
	 *
	 * @var string
	 */
	protected $viewComments = 'commentsData';

	/**
	 * Settings to use when CommentsComponent needs to do a flash message with SessionComponent::setFlash().
	 * Available keys are:
	 *
	 * - `element` - The element to use, defaults to 'default'.
	 * - `key` - The key to use, defaults to 'flash'
	 * - `params` - The array of additional params to use, defaults to array()
	 *
	 * @var array
	 */
	protected array $flash = [
		'element' => 'default',
		'key' => 'flash',
		'params' => [],
	];

	/**
	 * Named params used internally by the component
	 *
	 * @var array
	 */
	protected array $_supportNamedParams = [
		'comment',
		'comment_action',
		'comment_view_type',
		'quote',
	];

	/**
	 * Initialize Callback
	 *
	 * @param array $config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->Controller = $this->getController();

		$config += (array)Configure::read('Comments');
		$this->setConfig($config);

		if (!$this->getConfig('userModel')) {
			[, $alias] = pluginSplit($this->getConfig('userModelClass'));
			$this->setConfig('userModel', $alias);
		}
	}

	/**
	 * Callback
	 *
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function startup(EventInterface $event): void {
		$actions = $this->getConfig('actions');
		if ($actions) {
			$action = $this->Controller->getRequest()->getParam('action') ?: '';
			if (!in_array($action, $actions, true)) {
				return;
			}
		}

		$model = $this->Controller->fetchTable();
		$this->modelAlias = $model->getAlias();

		$parts = explode('\\', (string)$model->getEntityClass());
		$entityName = Inflector::classify(Inflector::underscore(array_pop($parts)));
		$this->viewVariable = $this->getConfig('viewVariable') ?? Inflector::variable($entityName);
		if (!$model->hasBehavior('Commentable')) {
			$config = [
				'userModelClass' => $this->getConfig('userModelClass'),
				'userId' => $this->userId(),
			];
			$model->addBehavior('Comments.Commentable', $config);
		}

		if (!$this->Controller->getRequest()->is(['post', 'put', 'patch'])) {
			return;
		}

		if ($this->getConfig('on') !== 'startup') {
			return;
		}

		$result = $this->process();
		if ($result) {
			$event->setResult($result);
		}
	}

	/**
	 * Callback
	 *
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		$actions = $this->getConfig('actions');
		if ($actions) {
			$action = $this->Controller->getRequest()->getParam('action') ?: '';
			if (!in_array($action, $actions, true)) {
				return;
			}
		}

		if ($this->getConfig('on') === 'beforeRender') {
			$result = $this->process();
			if ($result) {
				$event->setResult($result);

				return;
			}
		}

		$type = $this->_call('initType');
		$this->commentParams = array_merge($this->commentParams, ['displayType' => $type]);
		$this->_call('view', [$type]);
		$this->_call('prepareParams');
		$this->Controller->set('commentParams', $this->commentParams);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	protected function process() {
		if (!$this->Controller->getRequest()->is(['post', 'put', 'patch'])) {
			return null;
		}

		$data = $this->Controller->getRequest()->getData();
		if (!array_key_exists('comment', $data) && !array_key_exists('content', $data)) {
			return null;
		}

		return $this->addComment($data);
	}

	/**
	 * //FIXME
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	protected function addComment(array $data) {
		/** @var \Cake\Datasource\EntityInterface|null $entity */
		$entity = $this->Controller->viewBuilder()->getVar((string)$this->viewVariable);
		if (!$entity) {
			throw new RuntimeException('Entity missing for commenting');
		}

		$userId = $this->userId();
		if ($userId === null && !$this->getConfig('allowAnonymous')) {
			throw new ForbiddenException(__d('comments', 'Anonymous comments are disabled.'));
		}

		$modelId = $this->getConfig('useEntity') ? $entity->get('id') : ($data['id'] ?? null);

		$options = [
			'userId' => $userId,
			'modelId' => $modelId,
			'model' => $this->modelAlias,
			'data' => $data,
			//'permalink' => $permalink,
		];

		// Parent comment id
		$commentId = $data['parent_id'] ?? null;
		if ($commentId === '') {
			$commentId = null;
		} elseif ($commentId !== null) {
			if (!is_int($commentId) && !is_string($commentId)) {
				throw new BadRequestException(__d('comments', 'Invalid parent id.'));
			}
			$commentId = filter_var($commentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			if ($commentId === false) {
				throw new BadRequestException(__d('comments', 'Invalid parent id.'));
			}
		}
		/** @var \Cake\ORM\Table $table */
		$table = $this->Controller->fetchTable();
		/** @var \Comments\Model\Behavior\CommentableBehavior $behavior */
		$behavior = $table->getBehavior('Commentable');
		$result = $behavior->commentAdd($commentId, $options);
		if ($result) {
			return $this->prgRedirect();
		}

		$this->Flash->error(__d('comments', 'Could not save comment, please try again.'));

		return $result;
	}

	/**
	 * @return int|null
	 */
	protected function userId() {
		$userId = $this->getConfig('userId') ?: null;
		$identity = $this->Controller->getRequest()->getAttribute('identity');
		if ($userId === null && $identity instanceof ArrayAccess && isset($identity[$this->getConfig('userIdField')])) {
			return $identity[$this->getConfig('userIdField')];
		}
		if ($userId === null && is_object($identity) && method_exists($identity, 'getIdentifier')) {
			return $identity->getIdentifier();
		}
		if (!$userId && $this->Controller->components()->has('AuthUser')) {
			$authUser = $this->Controller->components()->get('AuthUser');
			if (method_exists($authUser, 'user')) {
				$userId = $authUser->user($this->getConfig('userIdField'));
			}
		} elseif (!$userId && $this->Controller->components()->has('Auth')) {
			$auth = $this->Controller->components()->get('Auth');
			if (method_exists($auth, 'user')) {
				$userId = $auth->user($this->getConfig('userIdField'));
			}
		} elseif (!$userId) {
			$sessionKey = $this->getConfig('sessionKey') ?? Configure::read('Comments.sessionKey') ?? 'Auth.User';
			$userId = $this->Controller->getRequest()->getSession()->read($sessionKey . '.' . $this->getConfig('userIdField'));
		}

		return $userId;
	}

	/**
	 * Determine used type of display (flat/threaded/tree)
	 *
	 * @return string Type of comment display
	 */
	public function callbackInitType() {
		return 'flat';
	}

	/**
	 * Handles controllers actions like list/add related comments
	 *
	 * @param string $displayType
	 * @param bool $processActions
	 *
	 * @throws \RuntimeException
	 *
	 * @return void
	 */
	public function callbackView(string $displayType, bool $processActions = true) {
		/** @var \Cake\ORM\Table $table */
		$table = $this->Controller->fetchTable();
		if (
			!$table->hasAssociation($this->assocName)
		) {
			throw new RuntimeException('CommentsComponent: model ' . $this->modelAlias . ' or association ' . $this->assocName . ' doesn\'t exist');
		}

		assert($this->viewVariable !== null);
		/** @var \Cake\Datasource\EntityInterface|null $entity */
		$entity = $this->Controller->viewBuilder()->getVar($this->viewVariable);

		if (!$entity || !$entity->get('id')) {
			/** @var string $key */
			$key = $table->getPrimaryKey();

			throw new RuntimeException('CommentsComponent: missing view variable ' . $this->viewVariable . ' or value for primary key ' . $key . ' of model ' . $this->modelAlias);
		}

		$id = $entity->get('id');
		$options = ['displayType' => $displayType, 'id' => $id];

		// Historical fetchData{Type} routing supported `flat`, `threaded`,
		// and `tree`, but the latter two were Cake 2-shaped wrappers that
		// referenced columns the schema never carried. Only `flat` is
		// supported now; any other display type fails loudly via _call().
		$data = $this->_call('fetchData' . Inflector::camelize($displayType), [$options]);

		$this->Controller->set($this->viewComments, $data);
	}

	/**
	 * Flat representation of the comment data.
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\Datasource\Paging\PaginatedInterface
	 */
	public function callbackFetchDataFlat(array $options): PaginatedInterface {
		/** @var \Comments\Model\Table\CommentsTable $relation */
		$relation = $this->Controller->fetchTable()->getAssociation('Comments')->getTarget();

		return $this->Controller->paginate($relation->find()->where([
			$relation->aliasField('model') => $this->modelAlias,
			$relation->aliasField('foreign_key') => $options['id'],
		]));
	}

	/**
	 * Prepare passed parameters.
	 *
	 * @return void
	 */
	public function callbackPrepareParams() {
		$this->commentParams = [
			'viewComments' => $this->viewComments,
			'modelName' => $this->modelAlias,
			'userModel' => $this->userModel,
		] + $this->commentParams;
	}

	/**
	 * For AJAX requests, set the messageTxt view variable.
	 * Otherwise use the controller's Flash component.
	 *
	 * @param string $message The message to set.
	 *
	 * @return void
	 */
	public function flash($message) {
		$isAjax = $this->Controller->getRequest()->getParam('isAjax') ?? $this->Controller->getRequest()->is('ajax');
		if ($isAjax) {
			$this->Controller->set('messageTxt', $message);
		} else {
			$options = [];
			// $this->flash['element'], $this->flash['params'], $this->flash['key']
			$this->Controller->Flash->set($message, $options);
		}
	}

	/**
	 * Redirect
	 * Redirects the user to the wanted action by persisting passed args excepted
	 * the ones used internally by the component
	 *
	 * @param array $urlBase
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function prgRedirect(array $urlBase = []) {
		$isAjax = $this->Controller->getRequest()->getParam('isAjax') ?? $this->Controller->getRequest()->is('ajax');

		$request = $this->Controller->getRequest();
		$url = array_replace([
			'plugin' => $request->getParam('plugin'),
			'prefix' => $request->getParam('prefix'),
			'controller' => $request->getParam('controller'),
			'action' => $request->getParam('action'),
		] + (array)$request->getParam('pass'), $urlBase);

		if (!$isAjax) {
			return $this->Controller->redirect($url);
		}

		$this->Controller->set('redirect', $url);
		//$this->ajaxMode = true;
		$this->Controller->set('ajaxMode', true);
	}

	/**
	 * Generate permalink to page
	 *
	 * @return string URL to the comment
	 */
	public function permalink() {
		$params = [];
		foreach (['prefix', 'controller', 'action', 'plugin'] as $name) {
			if ($this->Controller->getRequest()->getParam($name)) {
				$params[$name] = $this->Controller->getRequest()->getParam($name);
			}
		}

		if ($this->Controller->getRequest()->getParam('pass')) {
			$params = array_merge($params, $this->Controller->getRequest()->getParam('pass'));
		}

		if ($this->Controller->getRequest()->getParam('named')) {
			foreach ($this->Controller->getRequest()->getParam('named') as $k => $v) {
				if (!in_array($k, $this->_supportNamedParams)) {
					$params[$k] = $v;
				}
			}
		}

		return Router::url($params, true);
	}

	/**
	 * Call action from component or overridden action from controller.
	 *
	 * @param string $method
	 * @param array $args
	 *
	 * @throws \BadMethodCallException
	 *
	 * @return mixed
	 */
	protected function _call($method, $args = []) {
		$methodName = 'callbackComments' . Inflector::camelize(Inflector::underscore($method));
		$localMethodName = 'callback' . $method;
		if (method_exists($this->Controller, $methodName)) {
			/** @var callable $callable */
			$callable = [$this->Controller, $methodName];

			return call_user_func_array($callable, $args);
		}
		if (method_exists($this, $localMethodName)) {
			/** @var callable $callable */
			$callable = [$this, $localMethodName];

			return call_user_func_array($callable, $args);
		}

		throw new BadMethodCallException();
	}

}
