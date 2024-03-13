<?php

namespace Comments\Model\Behavior;

use Cake\Core\Configure;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

class CommentableBehavior extends Behavior {

	/**
	 * Default settings
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'modelClass' => null, // Auto-detect
		'commentClass' => 'Comments.Comments',
		'nameField' => 'name',
		'emailField' => 'email', // Set to false to only use logged-in commenting
		'userModelAlias' => 'Users',
		'userModelClass' => 'Users', // Set to false to only use guest commenting
		'userModel' => null,
		'countComments' => false,
		'implementedFinders' => [
			'comments' => 'findComments',
			//'threaded' => 'findThreaded',
		],
		'fieldCounter' => 'comments_count', //TODO
		'titleField' => null, // Auto-detect "title" //TODO
		'spamField' => null, // Auto-detect "is_spam" //TODO
		'hiddenField' => null, // Auto-detect "is_hidden" //TODO
		'approval' => false, // Set to true if you want to allow users to approve comments (uses hiddenField then) //TODO
		'threaded' => null, // Auto-detect "parent_id" //TODO
	];

	/**
	 * Constructor
	 *
	 * Merges config with the default and store in the config property
	 *
	 * @param \Cake\ORM\Table $table The table this behavior is attached to.
	 * @param array<string, mixed> $config The config for this behavior.
	 */
	public function __construct(Table $table, array $config = []) {
		$config += (array)Configure::read('Comments');

		parent::__construct($table, $config);
	}

	/**
	 * Setup
	 *
	 * @param array $config default config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		if (!$this->getConfig('modelClass')) {
			$this->setConfig('modelClass', $this->_table->getAlias());
		}

		$this->_table->hasMany('Comments', [
			'className' => $this->getConfig('commentClass'),
			'foreignKey' => 'foreign_key',
			'order' => 'Comments.created ASC',
			'conditions' => ['Comments.model' => "{$this->_table->getAlias()}"],
			'dependent' => true,
		]);

		if ($this->getConfig('countComments')) {
			$this->_table->Comments->addBehavior('CounterCache', [
				$this->_table->getAlias() => [$this->getConfig('fieldCounter')],
			]);
		}

		$this->_table->Comments->belongsTo($this->getConfig('modelClass'), [
			'className' => $this->getConfig('modelClass'),
			'foreignKey' => 'foreign_key',
		]);

		if ($this->_table->getSchema()->getColumn('parent_id')) {
			$this->_table->Comments->addBehavior('Tree');
		}

		if (!empty($config['userModel']) && is_array($config['userModel'])) {
			$this->_table->Comments->belongsTo($config['userModelAlias'], $config['userModel']);
		} else {
			$userConfig = [
				'className' => $this->getConfig('userModelClass'),
				'foreignKey' => 'user_id',
				//'counterCache' => true,
			];
			$this->_table->Comments->belongsTo($this->getConfig('userModelClass'), $userConfig);
		}
	}

	/**
	 * Handle adding comments
	 *
	 * @param mixed $commentId parent comment id, 0 for none
	 * @param array $options extra information and comment statistics
	 *
	 * @throws \Cake\Http\Exception\MethodNotAllowedException
	 *
	 * @return bool|null
	 */
	public function commentAdd($commentId = null, array $options = []) {
		$options += ['defaultTitle' => '', 'modelId' => null, 'userId' => null, 'data' => [], 'permalink' => ''];

		if (isset($options['permalink'])) {
			$this->_table->Comments->permalink = $options['permalink'];
		}

		$this->_table->Comments->recursive = -1;
		if ($commentId) {
			$this->_table->Comments->id = $commentId;
			if (
				!$this->_table->Comments->find('count', [
				'conditions' => [
				'Comment.id' => $commentId,
				'Comment.approved' => true,
				'Comment.foreign_key' => $modelId]])
			) {
				throw new MethodNotAllowedException(__d('comments', 'Unallowed comment id', true));
			}
		}

		if ($data) {
			$data['Comment']['user_id'] = $userId;
			$data['Comment']['model'] = $modelName;
			if (!isset($data['Comment']['foreign_key'])) {
				$data['Comment']['foreign_key'] = $modelId;
			}
			if (!isset($data['Comment']['parent_id'])) {
				$data['Comment']['parent_id'] = $commentId;
			}
			if (empty($data['Comment']['title'])) {
				$data['Comment']['title'] = $defaultTitle;
			}

			if (!empty($data['Other'])) {
				foreach ($data['Other'] as $spam) {
					if ($spam) {
						return false;
					}
				}
			}

			//$event = new CakeEvent('Behavior.Commentable.beforeCreateComment', $Model, $data);
			//CakeEventManager::instance()->dispatch($event);
			/*
            if ($event->isStopped() && !$event->result) {
                return false;
            }
            if ($event->result) {
                $data = $event->result;
            }
            */

			$entity = $this->_table->Comments->newEntity($data);

			if ($this->_table->Comments->Behaviors->enabled('Tree')) {
				if (isset($data['Comment']['foreign_key'])) {
					$fk = $data['Comment']['foreign_key'];
				} elseif (isset($data['foreign_key'])) {
					$fk = $data['foreign_key'];
				} else {
					$fk = null;
				}
				$this->_table->Comments->Behaviors->load('Tree', [
						'scope' => ['Comment.foreign_key' => $fk]]);
			}

			if ($this->_table->Comments->save()) {
				$id = $this->_table->Comments->id;
				$data['Comment']['id'] = $id;
				$this->_table->Comments->data[$this->_table->Comments->alias]['id'] = $id;
				if (!isset($data['Comment']['approved']) || $data['Comment']['approved'] == true) {
					//$this->changeCommentCount($Model, $modelId);
				}

				//$event = new CakeEvent('Behavior.Commentable.afterCreateComment', $Model, $this->_table->Comments->data);
				//CakeEventManager::instance()->dispatch($event);
				//if ($event->isStopped() && !$event->result) {
				//    return false;
				//}

				return $id;
			}

			return false;
		}

		return null;
	}

	/**
	 * Create the finder comments
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findThreaded(SelectQuery $query, array $options = []): SelectQuery {
		return $query->contain([
			'Comments' => function (Query $q) use ($options) {
				return $q->find('threaded');
			},
		]);
	}

	/**
	 * Create the finder comments
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findComments(SelectQuery $query, array $options = []): SelectQuery {
		return $query->contain([
			'Comments' => function (Query $q) use ($options) {
				$q->contain('Users');
				$q->where(['foreign_key' => $options['id']]);
				if ($this->getConfig('hiddenField')) {
					$q->where([$this->getConfig('hiddenField') => false]);
				}

				return $q;
			},
		]);
	}

}
