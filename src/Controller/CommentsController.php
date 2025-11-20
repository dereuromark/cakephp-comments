<?php

namespace Comments\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use TinyAuth\Controller\Component\AuthUserComponent;

/**
 * @property \Comments\Model\Table\CommentsTable $Comments
 * @property \TinyAuth\Controller\Component\AuthUserComponent $AuthUser
 * @property \TinyAuth\Controller\Component\AuthComponent $Auth
 */
class CommentsController extends AppController {

	protected ?string $modelClass = 'Comments.Comments';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		if (class_exists(AuthUserComponent::class)) {
			$this->loadComponent('TinyAuth.AuthUser');
		}
	}

	/**
	 * @param string|null $alias
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function add($alias = null, $id = null) {
		$this->request->allowMethod(['post', 'put', 'patch']);
		$data = $this->request->getData();

		$model = Configure::read('Comments.controllerModels.' . $alias);
		if (!$model) {
			throw new NotFoundException('Invalid alias');
		}
		$table = $this->fetchTable($model);
		$entity = $table->get($id);

		$data['model'] = $model;
		$data['foreign_key'] = $entity->get('id');
		$data['user_id'] = $this->userId();
		$data['content'] = $data['comment'] ?? null;

		$result = $this->Comments->add($data);
		if ($result->isNew()) {
			$this->Flash->error(__d('comments', 'Could not save comment, please try again.'));
		} else {
			$this->Flash->success(__d('comments', 'The comment has been saved.'));
		}

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @return int|null
	 */
	protected function userId() {
		$userIdField = Configure::read('Comments.userIdField') ?: 'id';
		if ($this->components()->has('AuthUser')) {
			return $this->AuthUser->user($userIdField);
		}
		if ($this->components()->has('Auth')) {
			return $this->Auth->user('id');
		}

		return $this->getRequest()->getSession()->read('Auth.User.' . $userIdField);
	}

	/**
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);

		$id = $this->request->getData('id') ?: $id;
		$comment = $this->Comments->get($id);

		$this->Comments->delete($comment);

		return $this->redirect($this->referer(['action' => 'index']));
	}

}
