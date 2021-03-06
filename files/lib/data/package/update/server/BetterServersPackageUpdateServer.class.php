<?php

namespace wcf\data\package\update\server;

use wcf\data\DatabaseObject;
use wcf\system\cache\builder\PackageUpdateCacheBuilder;
use wcf\system\io\RemoteFile;
use wcf\system\Regex;
use wcf\system\registry\RegistryHandler;
use wcf\system\WCF;
use wcf\util\FileUtil;
use wcf\util\Url;

class PackageUpdateServer extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'packageUpdateServerID';
	
	/**
	 * API meta data
	 *
	 * @var array
	 */
	protected $metaData = [];
	
	/**
	 * @inheritDoc
	 */
	protected function handleData($data) {
		if (!empty($data['metaData'])) {
			$metaData = @\unserialize($data['metaData']);
			if (\is_array($metaData)) {
				$this->metaData = $metaData;
			}
			
			unset($data['metaData']);
		}
		
		parent::handleData($data);
		
		if (!PACKAGE_SERVER_BETTERSERVERS || PACKAGE_SERVER_WOLTLAB_AUTO_VERSIONING) {
			$prefix = ENABLE_ENTERPRISE_MODE ? 'cloud/' : '';
			$officialPath = \wcf\getMinorVersion();
			if (self::isUpgradeOverrideEnabled()) {
				$officialPath = WCF::AVAILABLE_UPGRADE_VERSION;
			}
			
			if ($this->isWoltLabUpdateServer()) {
				$this->data['serverURL'] = "http://update.woltlab.com/{$prefix}{$officialPath}/";
			}
			if ($this->isWoltLabStoreServer()) {
				$this->data['serverURL'] = "http://store.woltlab.com/{$prefix}{$officialPath}/";
			}
			if (!PACKAGE_SERVER_BETTERSERVERS && ($this->isWoltLabUpdateServer() || $this->isWoltLabStoreServer())) {
				$this->data['isDisabled'] = 0;
			}
		}
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
		$woltlabUpdateServerCreate = $woltlabStoreServerCreate = !PACKAGE_SERVER_BETTERSERVERS || PACKAGE_SERVER_WOLTLAB_AUTO_CREATE;
		$results = [];
		foreach ($list as $packageServer) {
			$isWoltLab = $packageServer->isWoltLabStoreServer() || $packageServer->isWoltLabUpdateServer();
			
			if (PACKAGE_SERVER_BETTERSERVERS && $isWoltLab && ($packageServer->isDisabled || PACKAGE_SERVER_WOLTLAB_SKIP)) {
				if ($packageServer->isWoltLabUpdateServer()) {
					$woltlabUpdateServerCreate = false;
				}
				else {
					$woltlabStoreServerCreate = false;
				}
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
		
		$officialPath = \wcf\getMinorVersion();
		if (self::isUpgradeOverrideEnabled()) {
			$officialPath = WCF::AVAILABLE_UPGRADE_VERSION;
		}
		if (!$woltlabUpdateServer && $woltlabUpdateServerCreate) {
			$packageServer = PackageUpdateServerEditor::create(['serverURL' => "http://update.woltlab.com/{$officialPath}/",]);
			$results[$packageServer->packageUpdateServerID] = $packageServer;
		}
		if (!$woltlabStoreServer && $woltlabStoreServerCreate) {
			$packageServer = PackageUpdateServerEditor::create(['serverURL' => "http://store.woltlab.com/{$officialPath}/",]);
			$results[$packageServer->packageUpdateServerID] = $packageServer;
		}
		
		if (ENABLE_ENTERPRISE_MODE) {
			return \array_filter($results, static function(self $server) {
				return $server->isWoltLabStoreServer() || $server->isTrustedServer();
			});
		}
		
		return $results;
	}
	
	/**
	 * Returns true if the given server url is valid.
	 *
	 * @param    string    $serverURL
	 * @return  bool
	 */
	public static function isValidServerURL($serverURL) {
		$parsedURL = Url::parse($serverURL);
		
		return \in_array($parsedURL['scheme'], ['http', 'https']) && $parsedURL['host'] !== '';
	}
	
	/**
	 * Returns stored auth data of this update server.
	 *
	 * @return  string[]
	 */
	public function getAuthData() {
		if (ENABLE_ENTERPRISE_MODE && \defined('ENTERPRISE_MODE_AUTH_DATA')) {
			$host = Url::parse($this->serverURL)['host'];
			if (!empty(ENTERPRISE_MODE_AUTH_DATA[$host])) {
				return ENTERPRISE_MODE_AUTH_DATA[$host];
			}
		}
		
		$authData = [];
		// database data
		if ($this->loginUsername != '' && $this->loginPassword != '') {
			$authData = ['username' => $this->loginUsername, 'password' => $this->loginPassword,];
		}
		
		// session data
		$packageUpdateAuthData = WCF::getSession()->getVar('packageUpdateAuthData');
		if ($packageUpdateAuthData !== null) {
			$packageUpdateAuthData = @\unserialize($packageUpdateAuthData);
			if ($packageUpdateAuthData !== null && isset($packageUpdateAuthData[$this->packageUpdateServerID])) {
				$authData = $packageUpdateAuthData[$this->packageUpdateServerID];
			}
		}
		
		return $authData;
	}
	
	/**
	 * Stores auth data for a package update server.
	 *
	 * @param    int       $packageUpdateServerID
	 * @param    string    $username
	 * @param    string    $password
	 * @param    bool      $saveCredentials
	 */
	public static function storeAuthData($packageUpdateServerID, $username, $password, $saveCredentials = false) {
		$packageUpdateAuthData = @\unserialize(WCF::getSession()->getVar('packageUpdateAuthData'));
		if ($packageUpdateAuthData === null || !\is_array($packageUpdateAuthData)) {
			$packageUpdateAuthData = [];
		}
		
		$packageUpdateAuthData[$packageUpdateServerID] = ['username' => $username, 'password' => $password,];
		
		WCF::getSession()->register('packageUpdateAuthData', \serialize($packageUpdateAuthData));
		
		if ($saveCredentials) {
			$serverAction = new PackageUpdateServerAction([$packageUpdateServerID], 'update', ['data' => ['loginUsername' => $username, 'loginPassword' => $password,],]);
			$serverAction->executeAction();
		}
	}
	
	/**
	 * Returns true if update server requires license data instead of username/password.
	 *
	 * @return  int
	 */
	final public function requiresLicense() {
		return Regex::compile('^https?://update.woltlab.com/')->match($this->serverURL);
	}
	
	/**
	 * Returns the highlighted server URL.
	 *
	 * @return  string
	 */
	public function getHighlightedURL() {
		$host = Url::parse($this->serverURL)['host'];
		
		return \str_replace($host, '<strong>' . $host . '</strong>', $this->serverURL);
	}
	
	/**
	 * Returns the list endpoint for package servers.
	 *
	 * @param    bool    $forceHTTP
	 * @return  string
	 */
	public function getListURL($forceHTTP = false) {
		if ($this->apiVersion == '2.0') {
			return $this->serverURL;
		}
		
		$serverURL = FileUtil::addTrailingSlash($this->serverURL) . 'list/' . WCF::getLanguage()->getFixedLanguageCode() . '.xml';
		
		$metaData = $this->getMetaData();
		if ($forceHTTP || !RemoteFile::supportsSSL() || !$metaData['ssl']) {
			return \preg_replace('~^https://~', 'http://', $serverURL);
		}
		
		return \preg_replace('~^http://~', 'https://', $serverURL);
	}
	
	/**
	 * Returns the download endpoint for package servers.
	 *
	 * @return  string
	 */
	public function getDownloadURL() {
		if ($this->apiVersion == '2.0') {
			return $this->serverURL;
		}
		
		$metaData = $this->getMetaData();
		if (!RemoteFile::supportsSSL() || !$metaData['ssl']) {
			return \preg_replace('~^https://~', 'http://', $this->serverURL);
		}
		
		return \preg_replace('~^http://~', 'https://', $this->serverURL);
	}
	
	/**
	 * Returns API meta data.
	 *
	 * @return  array
	 */
	public function getMetaData() {
		return $this->metaData;
	}
	
	/**
	 * Returns true if a request to this server would make use of a secure connection.
	 *
	 * @return  bool
	 */
	public function attemptSecureConnection() {
		if ($this->apiVersion == '2.0') {
			return false;
		}
		
		$metaData = $this->getMetaData();
		if (RemoteFile::supportsSSL() && $metaData['ssl']) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns whether the current user may delete this update server.
	 *
	 * @since       5.3
	 * @return      bool
	 */
	final public function canDelete() {
		return PACKAGE_SERVER_BETTERSERVERS || (!$this->isWoltLabUpdateServer() && !$this->isWoltLabStoreServer());
	}
	
	/**
	 * Returns whether the current user may disable this update server.
	 *
	 * @since       5.3
	 * @return      bool
	 */
	final public function canDisable() {
		return PACKAGE_SERVER_BETTERSERVERS || (!$this->isWoltLabUpdateServer() && !$this->isWoltLabStoreServer());
	}
	
	/**
	 * Returns true if the host is `update.woltlab.com`.
	 *
	 * @return      bool
	 */
	final public function isWoltLabUpdateServer() {
		return Url::parse($this->serverURL)['host'] === 'update.woltlab.com';
	}
	
	/**
	 * Returns true if the host is `store.woltlab.com`.
	 *
	 * @return      bool
	 */
	final public function isWoltLabStoreServer() {
		return Url::parse($this->serverURL)['host'] === 'store.woltlab.com';
	}
	
	/**
	 * Returns true if this server is trusted and is therefore allowed to distribute
	 * official updates for packages whose identifier starts with "com.woltlab.".
	 *
	 * Internal mirrors in enterprise environments are supported through the optional
	 * PHP constant `UPDATE_SERVER_TRUSTED_MIRROR`, adding it to the `config.inc.php`
	 * of the Core is considered to be a safe practice.
	 *
	 * Example:
	 *   define('UPDATE_SERVER_TRUSTED_MIRROR', 'mirror.example.com');
	 *
	 * @return      bool
	 */
	final public function isTrustedServer() {
		$host = Url::parse($this->serverURL)['host'];
		
		// the official server is always considered to be trusted
		if ($host === 'update.woltlab.com') {
			return true;
		}
		
		// custom override to allow testing and mirrors in enterprise environments
		if (\defined('UPDATE_SERVER_TRUSTED_MIRROR') && !empty(UPDATE_SERVER_TRUSTED_MIRROR) && $host === UPDATE_SERVER_TRUSTED_MIRROR) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns whether the official update servers will point to WCF::AVAILABLE_UPGRADE_VERSION.
	 *
	 * @since 5.3
	 * @return bool
	 */
	final public static function isUpgradeOverrideEnabled() {
		if (WCF::AVAILABLE_UPGRADE_VERSION === null) {
			return false;
		}
		
		$override = RegistryHandler::getInstance()->get('com.woltlab.wcf', self::class . "\0upgradeOverride");
		
		if (!$override) {
			return false;
		}
		
		if ($override < TIME_NOW - 86400) {
			RegistryHandler::getInstance()->delete('com.woltlab.wcf', self::class . "\0upgradeOverride");
			
			// Clear package list cache to actually stop the upgrade from happening.
			self::resetAll();
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Resets all update servers into their original state and purges
	 * the package cache.
	 */
	public static function resetAll() {
		// purge package cache
		WCF::getDB()->prepareStatement("DELETE FROM wcf" . WCF_N . "_package_update")->execute();
		
		PackageUpdateCacheBuilder::getInstance()->reset();
		
		// reset servers into their original state
		$sql = "UPDATE  wcf" . WCF_N . "_package_update_server
                SET     lastUpdateTime = ?,
                        status = ?,
                        errorMessage = ?,
                        apiVersion = ?,
                        metaData = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([0, 'online', '', '2.0', null,]);
	}
}
