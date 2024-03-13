<?php

namespace Comments\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Comments\Model\Entity\Comment;

/**
 * Comments Model
 *
 * @property \Comments\Model\Table\CommentsTable&\Cake\ORM\Association\BelongsTo $ParentComments
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Comments\Model\Table\CommentsTable&\Cake\ORM\Association\HasMany $ChildComments
 * @method \Comments\Model\Entity\Comment newEmptyEntity()
 * @method \Comments\Model\Entity\Comment newEntity(array $data, array $options = [])
 * @method array<\Comments\Model\Entity\Comment> newEntities(array $data, array $options = [])
 * @method \Comments\Model\Entity\Comment get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Comments\Model\Entity\Comment findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Comments\Model\Entity\Comment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Comments\Model\Entity\Comment> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Comments\Model\Entity\Comment|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Comments\Model\Entity\Comment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Comments\Model\Entity\Comment>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Comments\Model\Entity\Comment> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Comments\Model\Entity\Comment>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Comments\Model\Entity\Comment> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CommentsTable extends Table {

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->setTable('comments_comments');
		$this->setDisplayField('content');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');

		$this->belongsTo('ParentComments', [
			'className' => 'Comments.Comments',
			'foreignKey' => 'parent_id',
		]);

		$this->hasMany('ChildComments', [
			'className' => 'Comments.Comments',
			'foreignKey' => 'parent_id',
		]);
		$this->belongsTo('Users');
	}

	/**
	 * Validations rules
	 *
	 * @param \Cake\Validation\Validator $validator validator
	 *
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator->notEmptyString('content');
		$validator->requirePresence('content', 'create');
		$validator->notEmptyString('model');
		$validator->requirePresence('model', 'create');
		$validator->notEmptyString('foreign_key');
		$validator->requirePresence('foreign_key', 'create');

		return $validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 *
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['parent_id'], 'ParentComments'));
		$rules->add($rules->existsIn(['user_id'], 'Users'));

		return $rules;
	}

	/**
	 * @param array $data
	 *
	 * @return \Comments\Model\Entity\Comment|true
	 */
	public function add(array $data): Comment|true {
		$comment = $this->newEntity($data);
		if ($comment->hasErrors()) {
			return $comment;
		}

		$this->saveOrFail($comment);

		return true;
	}

}
