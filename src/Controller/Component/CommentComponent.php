<?php

/**
 * CommentsComponent
 *
 * Helps handle 'view' action of controller so it can list/add related comments.
 * In related controller action there is no need to fetch associated data for comments - this
 * component is fetching them separately (needed different result from model in dependency of
 * used displayType).
 *
 * Needs Router::connectNamed(array('comment', 'comment_view', 'comment_action)) in config/routes.php.
 *
 * It is also usable to define (in controller, to not fetch unnecessary data
 * in used Controller::paginate() method):
 * var $paginate = array('Comment' => array(
 *  'order' => array('Comment.created' => 'desc'),
 *  'recursive' => 0,
 *  'limit' => 10
 * ));
 *
 * Includes helpers TextWidget and CommentWidget for controller, uses method
 * AppController::blackHole().
 *
 * Most of component methods possible to override in controller
 * for it need to create method with prefix _comments
 * Ex. : _add -> _commentsAdd, _fetchData -> _commentsFetchData
 * Callbacks also need to prefix with '_comments' in controller.
 *
 * callbacks
 * afterAdd
 *
 * params
 *  comment
 *  comment_view_type
 *  comment_action
 */

namespace Comments\Controller\Component;

use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Event\EventInterface;
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
		'allowAnonymous' => false,
		'useEntity' => true,
		'viewVariable' => null,
	];

	/**
	 * Components
	 *
	 * @var array
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

		$parts = explode('\\', $model->getEntityClass());
		$entityName = Inflector::classify(Inflector::underscore(array_pop($parts)));
		$this->viewVariable = Inflector::variable($entityName);
		//$this->Controller->helpers = array_merge($this->Controller->helpers, ['Comments.CommentWidget', 'Time', 'Comments.Cleaner', 'Comments.Tree']);
		if (!$this->Controller->{$this->modelAlias}->behaviors()->has('Commentable')) {
			$config = [
				'userModelClass' => $this->getConfig('userModelClass'),
				'userId' => $this->userId(),
			];
			$this->Controller->{$this->modelAlias}->behaviors()->load('Comments.Commentable', $config);
		}

		/*
        $this->Auth = $this->Controller->Auth;
        if (!empty($this->Auth) && $this->Auth->user()) {
            $this->Controller->set('isAuthorized', ($this->Auth->user('id') != ''));
        }
        */

		/*
        if (in_array($this->Controller->action, $this->deleteActions)) {
            $this->Controller->{$this->modelAlias}->{$this->assocName}->softDelete(false);
        } elseif ($this->unbindAssoc) {
            foreach (['hasMany', 'hasOne'] as $assocType) {
                if (array_key_exists($this->assocName, $this->Controller->{$this->modelAlias}->{$assocType})) {
                    $this->Controller->{$this->modelAlias}->unbindModel([$assocType => [$this->assocName]], false);

                    break;
                }
            }
        }
        */

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
		$data = $this->Controller->getRequest()->getData();
		if (empty($data['comment'])) {
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

		if ($this->getConfig('useEntity')) {
			$modelId = $entity->get('id');
		} else {
			$modelId = $data['id'] ?? null;
		}

		$options = [
			'userId' => $this->userId(),
			'modelId' => $modelId,
			'model' => $this->modelAlias,
			'data' => $data,
			//'permalink' => $permalink,
		];

		// Parent comment id
		$commentId = $data['parent_id'] ?? null;
		/** @var \Comments\Model\Behavior\CommentableBehavior $table */
		$table = $this->Controller->{$this->modelAlias};
		$result = $table->commentAdd($commentId, $options);
		if ($result) {
			return $this->prgRedirect();
		}

		return $result;
	}

	/**
	 * @return int|null
	 */
	protected function userId() {
		$userId = $this->getConfig('userId') ?: null;
		if (!$userId && $this->Controller->components()->has('AuthUser')) {
			$userId = $this->Controller->AuthUser->user($this->getConfig('userIdField'));
		} elseif (!$userId && $this->Controller->components()->has('Auth')) {
			$userId = $this->Controller->Auth->user($this->getConfig('userIdField'));
		} elseif (!$userId) {
			$userId = $this->Controller->getRequest()->getSession()->read('Auth.User.' . $this->getConfig('userIdField'));
		}

		return $userId;
	}

	/**
	 * Determine used type of display (flat/threaded/tree)
	 *
	 * @return string Type of comment display
	 */
	public function callbackInitType() {
		$types = ['flat', 'threaded', 'tree'];
		$param = 'Comments.' . $this->modelAlias;
		//dd($this->Controller->viewBuilder()->getVars());
		/*
        if (!empty($this->Controller->passedArgs['comment_view_type'])) {
            $type = $this->Controller->passedArgs['comment_view_type'];
            if (in_array($type, $types)) {
                $this->Cookie->write($param, $type, true, '+2 weeks');

                return $type;
            }
        }
        */

		/*
		if ($this->Controller->getRequest()->getCookieCollection()->has($param)) {
			$type = $this->Controller->getRequest()->getCookieCollection()->get($param);
			if (in_array($type, $types)) {
				return $type;
			}

			$this->Controller->getRequest()->getCookieCollection()->remove('Comments');
		}
		*/

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
		$table = $this->Controller->{$this->modelAlias};
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
		$options = compact('displayType', 'id');
		if ($processActions) {
			//TODO
			//$this->_processActions($options);
		}

		try {
			$data = $this->_call('fetchData' . Inflector::camelize($displayType), [$options]);
		} catch (BadMethodCallException $exception) {
			$data = $this->_call('fetchData', [$options]);
		}

		$this->Controller->set($this->viewComments, $data);
	}

	/**
	 * Paginateable tree representation of the comment data.
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return array
	 */
	public function callbackFetchDataTree(array $options) {
		/*
		$settings = $this->_prepareModel($options);
		$settings += ['order' => ['Comment.lft' => 'asc']];
		$paginate = $settings;
		$paginate['limit'] = 10;

		$overloadPaginate = !empty($this->Controller->paginate['Comment']) ? $this->Controller->paginate['Comment'] : [];
		$this->Controller->Paginator->settings['Comment'] = array_merge($paginate, $overloadPaginate);
		$data = $this->Controller->Paginator->paginate($this->Controller->{$this->modelAlias}->Comments);
		$parents = [];
		if (isset($data[0]['Comment'])) {
			$rec = $data[0]['Comment'];
			$settings['conditions'][] = ['Comment.lft <' => $rec['lft']];
			$settings['conditions'][] = ['Comment.rght >' => $rec['rght']];
			$parents = $this->Controller->{$this->modelAlias}->Comments->find('all', ...$settings);
		}

		return array_merge($parents, $data);
		*/

		return [];
	}

	/**
	 * Flat representation of the comment data.
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\Datasource\Paging\PaginatedInterface
	 */
	public function callbackFetchDataFlat(array $options): PaginatedInterface {
		$paginate = []; //$this->_prepareModel($options);

		//$overloadPaginate = !empty($this->Controller->paginate['Comment']) ? $this->Controller->paginate['Comment'] : [];
		//$this->Controller->Paginator->settings['Comment'] = array_merge($paginate, $overloadPaginate);

		/** @var \Comments\Model\Table\CommentsTable $relation */
		$relation = $this->Controller->{$this->modelAlias}->Comments->getTarget();

		return $this->Controller->paginate($relation);
	}

	/**
	 * Threaded comment data, one-paginateable, the whole data is fetched.
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return array
	 */
	public function callbackFetchDataThreaded(array $options) {
		$Comment =&$this->Controller->{$this->modelAlias}->Comments;
		$settings = $this->_prepareModel($options);
		$settings['fields'] = [
			'Comment.author_email', 'Comment.author_name', 'Comment.author_url',
			'Comment.id', 'Comment.user_id', 'Comment.foreign_key', 'Comment.parent_id', 'Comment.approved',
			'Comment.title', 'Comment.body', 'Comment.slug', 'Comment.created',
			$this->Controller->{$this->modelAlias}->alias . '.' . $this->Controller->{$this->modelAlias}->primaryKey,
			$this->userModel . '.' . $Comment->{$this->userModel}->primaryKey,
			$this->userModel . '.' . $Comment->{$this->userModel}->displayField,
		];

		if ($Comment->{$this->userModel}->hasField('slug')) {
			$settings['fields'][] = $this->userModel . '.slug';
		}

		$settings += [
			'order' => [
				'Comment.parent_id' => 'asc',
				'Comment.created' => 'asc',
			],
		];

		return $Comment->find('threaded', ...$settings);
	}

	/**
	 * Default method, calls callback_fetchData
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return array
	 */
	public function callbackFetchData($options) {
		$this->callbackFetchDataFlat($options);

		return [];
	}

	/**
	 * Prepare model association to fetch data
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return array
	 */
	protected function _prepareModel($options) {
		$params = [
			//'isAdmin' => $this->Auth->user('is_admin') == true,
			'userModel' => $this->userModel,
			//'userData' => $this->Auth->user(),
		];

		return $this->Controller->{$this->modelAlias}->commentBeforeFind(array_merge($params, $options));
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

		$allowedParams = ['comment', 'comment_action', 'quote'];
		foreach ($allowedParams as $param) {
			/*
            if (isset($this->Controller->passedArgs[$param])) {
                $this->commentParams[$param] = $this->Controller->passedArgs[$param];
            }
            */
		}
	}

	/**
	 * Handle adding comments
	 *
	 * @param int $modelId
	 * @param int $commentId Parent comment id
	 * @param string $displayType
	 * @param array $data
	 *
	 * @return void
	 */
	public function callbackAdd($modelId, $commentId, $displayType, $data = []) {
		if ($this->Controller->getRequest()->getData('Comment')) {
			$data = $this->Controller->getRequest()->getData('Comment');
			$modelName = $this->Controller->{$this->modelAlias}->getRegistryAlias();
			$permalink = null;
			if (method_exists($this->Controller->{$this->modelAlias}, 'permalink')) {
				//$premalink = $this->Controller->{$this->modelAlias}->permalink($modelId);
			}
			$options = [
				'userId' => $this->userId(),
				'modelId' => $modelId,
				'modelName' => $modelName,
				'defaultTitle' => $this->Controller->defaultTitle ?? '',
				'data' => $data,
				'permalink' => $permalink,
			];
			$result = $this->Controller->{$this->modelAlias}->commentAdd($commentId, $options);

			if ($result !== null) {
				if ($result) {
					try {
						$options['commentId'] = $result;
						$this->_call('afterAdd', [$options]);
					} catch (BadMethodCallException $exception) {
					}
					$this->flash(__d('comments', 'The Comment has been saved.'));
					$this->prgRedirect(['#' => 'comment' . $result]);
					if (!empty($this->ajaxMode)) {
						$this->ajaxMode = null;
						$this->Controller->set('redirect', null);
						if (isset($this->Controller->passedArgs['comment'])) {
							unset($this->Controller->passedArgs['comment']);
						}
						$this->_call('view', [$this->commentParams['displayType'], false]);
					}
				} else {
					$this->flash(__d('comments', 'The Comment could not be saved. Please, try again.'));
				}
			}
		} else {
			if (!empty($this->Controller->passedArgs['quote'])) {
				if (!empty($this->Controller->passedArgs['comment'])) {
					$message = $this->_call('getFormattedComment', [$this->Controller->passedArgs['comment']]);
					if ($message) {
						//$this->Controller->getRequest()->getData('Comment.body') = $message;
					}
				}
			}
		}
	}

	/**
	 * Fetch and format a comment message.
	 *
	 * @param string $commentId
	 *
	 * @return string|null
	 */
	public function callbackgetFormattedComment($commentId) {
		$comment = $this->Controller->{$this->modelAlias}->Comments->find('first', ...[
			'fields' => ['Comment.body', 'Comment.title'],
			'conditions' => ['Comment.id' => $commentId],
		]);
		if ($comment) {
		} else {
			return null;
		}

		return "[quote]\n" . $comment['Comment']['body'] . "\n[end quote]";
	}

	/**
	 * Deletes comments
	 *
	 * @param string $modelId
	 * @param string $commentId
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function callbackDelete($modelId, $commentId) {
		if ($this->Controller->{$this->modelAlias}->commentDelete($commentId)) {
			$this->flash(__d('comments', 'The Comment has been deleted.'));
		} else {
			$this->flash(__d('comments', 'Error appear during comment deleting. Try later.'));
		}

		return $this->prgRedirect();
	}

	/**
	 * Flash message - for ajax queries, sets 'messageTxt' view variable,
	 * otherwise uses the Session component and values from CommentsComponent::$flash.
	 *
	 * @param string $message The message to set.
	 *
	 * @return void
	 */
	public function flash($message) {
		$isAjax = $this->Controller->params['isAjax'] ?? false;
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
		$isAjax = $this->Controller->getRequest()->getParam('isAjax') ?? false;

		$url = array_merge(
			array_diff_key($this->Controller->getRequest()->getParam('pass'), array_flip($this->_supportNamedParams)),
			$urlBase,
		);
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

	/**
	 * Non view action process method
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	protected function _processActions(array $options) {
		if (isset($this->Controller->passedArgs['comment'])) {
			if ($this->getConfig('allowAnonymous') || $this->userId()) {
				$id = $options['id'];
				$displayType = $options['displayType'];

				if (isset($this->Controller->passedArgs['comment_action'])) {
					$commentAction = $this->Controller->passedArgs['comment_action'];
					if (!in_array($commentAction, ['delete'])) {
						//return $this->Controller->blackHole("CommentsComponent: unsupported comment_Action '$commentAction'");
					}
					$this->_call(Inflector::variable($commentAction), [$id, $this->Controller->passedArgs['comment']]);
				} else {
					//Configure::write('Comment.action', 'add');
					$parent = empty($this->Controller->passedArgs['comment']) ? null : $this->Controller->passedArgs['comment'];
					$this->_call('add', [$id, $parent, $displayType]);
				}
			} else {
				//$this->Controller->Session->write('Auth.redirect', $this->Controller->request['url']);
				$this->Controller->redirect($this->Controller->Auth->getConfig('loginAction'));
			}
		}
	}

	/**
	 * Wrapping method to clean incoming html contents
	 *
	 * @deprecated 2.0.0 Use proper sanitization in your application layer
	 *
	 * @param string $text
	 * @param string $settings
	 *
	 * @return string
	 */
	public function cleanHtml($text, $settings = 'full') {
		deprecationWarning('CommentComponent::cleanHtml() is deprecated.');

		//$cleaner = & new CleanerHelper(new View($this->Controller));
		//return $cleaner->clean($text, $settings);
		return $text;
	}

}
