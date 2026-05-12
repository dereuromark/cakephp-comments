<?php

namespace Comments\Model\Entity;

use Cake\ORM\Entity;

/**
 * Comment Entity
 *
 * @property int $id
 * @property string $content
 * @property string $model
 * @property int $foreign_key
 * @property int|null $parent_id
 * @property int|null $user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\User|null $user
 * @property string|null $name
 * @property string|null $email
 * @property string|null $title
 * @property bool $is_private
 * @property bool $is_spam
 * @property \Comments\Model\Entity\Comment|null $parent_comment
 * @property array<\Comments\Model\Entity\Comment> $child_comments
 */
class Comment extends Entity {

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Only public-form fields are accessible. Identity / relational columns
	 * (`user_id`, `model`, `foreign_key`, `parent_id`) and moderation flags
	 * (`is_private`, `is_spam`) must be set by trusted server-side code,
	 * never by request data, otherwise an attacker can cross-post a comment
	 * onto an arbitrary record or pre-approve their own spam.
	 *
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'name' => true,
		'email' => true,
		'title' => true,
		'content' => true,
	];

}
