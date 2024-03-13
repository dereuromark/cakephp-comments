<?php

namespace Comments\Model\Entity;

use Cake\ORM\Entity;

/**
 * Comment Entity
 *
 * @property int $id
 * @property string|null $content
 * @property string $model
 * @property int $foreign_key
 * @property int|null $parent_id
 * @property int|null $user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\User|null $user
 * @property string|null $name
 * @property string|null $email
 * @property bool $is_private
 * @property bool $is_spam
 * @property \Comments\Model\Entity\Comment $parent_comment
 * @property array<\Comments\Model\Entity\Comment> $child_comments
 */
class Comment extends Entity {

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'*' => true,
		'id' => false,
	];

}
