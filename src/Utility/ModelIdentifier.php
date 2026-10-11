<?php

namespace Comments\Utility;

use Cake\ORM\Table;

final class ModelIdentifier {

	/**
	 * Include the older registry identifier only for this commented table.
	 *
	 * @param \Cake\ORM\Table $table Commented table
     *
	 * @return list<string>
	 */
	public static function aliases(Table $table): array {
		return array_values(array_unique([$table->getAlias(), $table->getRegistryAlias()]));
	}

}
