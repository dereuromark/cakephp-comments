<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Post Entity
 *
 * @property int $id
 * @property string $title
 * @property string|null $content
 */
class Post extends Entity {

	/**
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'*' => true,
		'id' => false,
	];

}
