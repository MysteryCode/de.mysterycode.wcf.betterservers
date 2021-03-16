<?php

namespace wcf\data\package\update\server;

/**
 * @property	PackageUpdateServer	$object
 * @method	PackageUpdateServer	getDecoratedObject()
 * @mixin	PackageUpdateServer
 */
class ToggleablePackageUpdateServerList extends PackageUpdateServerList {
	/**
	 * @inheritDoc
	 */
	public $objectClassName = ToggleablePackageUpdateServer::class;
	
	/**
	 * @inheritDoc
	 */
	public $decoratorClassName = DecoratedToggleablePackageUpdateServer::class;
}
