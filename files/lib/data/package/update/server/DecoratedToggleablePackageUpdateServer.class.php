<?php

namespace wcf\data\package\update\server;

use wcf\data\DatabaseObjectDecorator;

/**
 * @property	ToggleablePackageUpdateServer	$object
 * @method	ToggleablePackageUpdateServer	getDecoratedObject()
 * @mixin	ToggleablePackageUpdateServer
 */
class DecoratedToggleablePackageUpdateServer extends DatabaseObjectDecorator {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = ToggleablePackageUpdateServer::class;
	
	/**
	 * @inheritDoc
	 * @see	PackageUpdateServer::canDisable()
	 */
	final public function canDisable() {
		return true;
	}
	
	/**
	 * @inheritDoc
	 * @see	PackageUpdateServer::canDelete()
	 */
	final public function canDelete() {
		return true;
	}
	
	/**
	 * @inheritDoc
	 * @see	PackageUpdateServer::getActiveUpdateServers()
	 */
	final public static function getActiveUpdateServers(array $packageUpdateServerIDs = []) {
		if (!empty($packageUpdateServerIDs)) {
			throw new \InvalidArgumentException("Filtering package update servers by ID is no longer supported.");
		}
		
		$list = new PackageUpdateServerList();
		$list->readObjects();
		
		$woltlabUpdateServer = null;
		$woltlabStoreServer = null;
		$results = [];
		foreach ($list as $packageServer) {
			$isWoltLab = $packageServer->isWoltLabStoreServer() || $packageServer->isWoltLabUpdateServer();
			
			if (PACKAGE_SERVER_BETTERSERVERS && $isWoltLab && ($packageServer->isDisabled || PACKAGE_SERVER_WOLTLAB_SKIP)) {
				continue;
			}
			
			if ($packageServer->isWoltLabUpdateServer()) {
				$woltlabUpdateServer = $packageServer;
			}
			else if ($packageServer->isWoltLabStoreServer()) {
				$woltlabStoreServer = $packageServer;
			}
			else if ($packageServer->isDisabled) {
				continue;
			}
			
			$results[$packageServer->packageUpdateServerID] = $packageServer;
		}
		
		if (!$woltlabUpdateServer && (!PACKAGE_SERVER_BETTERSERVERS || PACKAGE_SERVER_WOLTLAB_AUTO_CREATE)) {
			$packageServer = PackageUpdateServerEditor::create(['serverURL' => 'http://update.woltlab.com/' . \wcf\getMinorVersion() . '/',]);
			$results[$packageServer->packageUpdateServerID] = $packageServer;
		}
		if (!$woltlabStoreServer && (!PACKAGE_SERVER_BETTERSERVERS || PACKAGE_SERVER_WOLTLAB_AUTO_CREATE)) {
			$packageServer = PackageUpdateServerEditor::create(['serverURL' => 'http://store.woltlab.com/' . \wcf\getMinorVersion() . '/',]);
			$results[$packageServer->packageUpdateServerID] = $packageServer;
		}
		
		if (ENABLE_ENTERPRISE_MODE) {
			return \array_filter($results, static function(self $server) {
				return $server->isWoltLabStoreServer() || $server->isTrustedServer();
			});
		}
		
		return $results;
	}
}
