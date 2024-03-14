<?php

namespace Comments\Model\Behavior;

use Cake\Core\Configure;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Comments\Model\Table\CommentsTable;

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
			$this->commentsTable()->addBehavior('CounterCache', [
				$this->_table->getAlias() => [$this->getConfig('fieldCounter')],
			]);
		}

		$this->commentsTable()->belongsTo($this->getConfig('modelClass'), [
			'className' => $this->getConfig('modelClass'),
			'foreignKey' => 'foreign_key',
		]);

		if ($this->_table->getSchema()->getColumn('parent_id')) {
			$this->commentsTable()->addBehavior('Tree');
		}

		if (!empty($config['userModel']) && is_array($config['userModel'])) {
			$this->commentsTable()->belongsTo($config['userModelAlias'], $config['userModel']);
		} else {
			$userConfig = [
				'className' => $this->getConfig('userModelClass'),
				'foreignKey' => 'user_id',
				//'counterCache' => true,
			];
			$this->commentsTable()->belongsTo($this->getConfig('userModelClass'), $userConfig);
		}
	}

	/**
	 * Handle adding comments
	 *
	 * @param int|null $commentId parent comment id, NULL for none
	 * @param array $options extra information and comment statistics
	 *
	 * @throws \Cake\Http\Exception\MethodNotAllowedException
	 *
	 * @return bool|null
	 */
	public function commentAdd(?int $commentId = null, array $options = []) {
		$options += ['defaultTitle' => '', 'model' => null, 'modelId' => null, 'userId' => null, 'data' => [], 'permalink' => ''];

		if (isset($options['permalink'])) {
			//$this->commentsTable()->permalink = $options['permalink'];
		}

		if ($commentId) {
			//$this->commentsTable()->id = $commentId;
			if (
				!$this->commentsTable()->find('all', [
					'conditions' => [
						'Comment.id' => $commentId,
						'Comment.approved' => true,
						'Comment.foreign_key' => $options['modelId'],
					],
				])->count()
			) {
				throw new MethodNotAllowedException(__d('comments', 'Unallowed comment id', true));
			}
		}

		$data = $options['data'];
		if ($data) {
			$data['Comment']['user_id'] = $options['userId'];
			$data['Comment']['model'] = $options['model'];
			if (!isset($data['Comment']['foreign_key'])) {
				$data['Comment']['foreign_key'] = $options['modelId'];
			}
			if (!isset($data['Comment']['parent_id'])) {
				$data['Comment']['parent_id'] = $commentId;
			}
			if (empty($data['Comment']['title'])) {
				$data['Comment']['title'] = $options['defaultTitle'];
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

			$comment = $this->commentsTable()->newEntity($data);

			if ($this->commentsTable()->Behaviors->enabled('Tree')) {
				if (isset($data['Comment']['foreign_key'])) {
					$fk = $data['Comment']['foreign_key'];
				} elseif (isset($data['foreign_key'])) {
					$fk = $data['foreign_key'];
				} else {
					$fk = null;
				}
				$this->commentsTable()->Behaviors->load('Tree', [
						'scope' => ['Comment.foreign_key' => $fk]]);
			}

			if ($this->commentsTable()->save($comment)) {
				$id = $comment->id;
				//$data['Comment']['id'] = $id;
				//$this->commentsTable()->data[$this->commentsTable()->alias]['id'] = $id;
				if (!isset($data['Comment']['approved']) || $data['Comment']['approved'] == true) {
					//$this->changeCommentCount($Model, $modelId);
				}

				//$event = new CakeEvent('Behavior.Commentable.afterCreateComment', $Model, $this->commentsTable()->data);
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
				return $q->find('threaded', $options);
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

	/**
	 * @return \Comments\Model\Table\CommentsTable&\Cake\ORM\Association\HasMany
	 */
	protected function commentsTable() {
		/** @var \Comments\Model\Table\CommentsTable&\Cake\ORM\Association\HasMany */
		return $this->_table->Comments;
	}

}
