<?php

namespace wcf\data\package\update\server;

/**
 * @property	ToggleablePackageUpdateServer	$object
 * @method	ToggleablePackageUpdateServer	getDecoratedObject()
 * @mixin	ToggleablePackageUpdateServer
 */
class ToggleablePackageUpdateServerEditor extends PackageUpdateServerEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = ToggleablePackageUpdateServer::class;
}
