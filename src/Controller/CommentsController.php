<?php

namespace Comments\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

/**
 * @property \Comments\Model\Table\CommentsTable $Comments
 */
class CommentsController extends AppController {

	protected ?string $modelClass = 'Comments.Comments';

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
		$data['foreign_key'] = $entity->id;
		$data['user_id'] = $this->userId();
		$data['content'] = $data['comment'] ?? null;

		$result = $this->Comments->add($data);
		if ($result !== true) {
			$this->Flash->error(__d('comments', 'Could not save comment, please try again.'));
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
	 * @return \Cake\Http\Response|null
	 */
	public function delete() {
		$this->request->allowMethod(['post', 'delete']);

		$id = $this->request->getData('id');
		$comment = $this->Comments->get($id);

		$this->Comments->delete($comment);

		return $this->redirect($this->referer(['action' => 'index']));
	}

}
