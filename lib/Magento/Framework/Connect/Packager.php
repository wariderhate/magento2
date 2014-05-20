<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Framework\Connect;

use Magento\Framework\HTTP\IClient;

/**
 * Class to manipulate with packages
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Packager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     *
     * @var \Magento\Framework\Archive
     */
    protected $_archiver = null;

    /**
     * @var IClient
     */
    protected $_http = null;

    /**
     *
     * @return \Magento\Framework\Archive
     */
    public function getArchiver()
    {
        if (is_null($this->_archiver)) {
            $this->_archiver = new \Magento\Framework\Archive();
        }
        return $this->_archiver;
    }

    /**
     * @return IClient
     */
    public function getDownloader()
    {
        if (is_null($this->_http)) {
            $this->_http = \Magento\Framework\HTTP\Client::getInstance();
        }
        return $this->_http;
    }

    /**
     * @param string $ftpString
     * @return \Magento\Framework\Object[]
     */
    public function getRemoteConf($ftpString)
    {
        $ftpObj = new Ftp();
        $ftpObj->connect($ftpString);
        $cfgFile = "connect.cfg";
        $cacheFile = "cache.cfg";


        $wd = $ftpObj->getcwd();

        $remoteConfigExists = $ftpObj->fileExists($cfgFile);
        $tempConfigFile = uniqid($cfgFile . "_temp");
        if (!$remoteConfigExists) {
            $remoteCfg = new Config($tempConfigFile);
            $remoteCfg->store();
            $ftpObj->upload($cfgFile, $tempConfigFile);
        } else {
            $ftpObj->get($tempConfigFile, $cfgFile);
            $remoteCfg = new Config($tempConfigFile);
        }

        $ftpObj->chdir($wd);

        $remoteCacheExists = $ftpObj->fileExists($cacheFile);
        $tempCacheFile = uniqid($cacheFile . "_temp");

        if (!$remoteCacheExists) {
            $remoteCache = new Singleconfig($tempCacheFile);
            $remoteCache->clear();
            $ftpObj->upload($cacheFile, $tempCacheFile);
        } else {
            $ftpObj->get($tempCacheFile, $cacheFile);
            $remoteCache = new Singleconfig($tempCacheFile);
        }
        $ftpObj->chdir($wd);
        return array($remoteCache, $remoteCfg, $ftpObj);
    }

    /**
     * @param string $ftpString
     * @return \Magento\Framework\Object[]
     */
    public function getRemoteCache($ftpString)
    {

        $ftpObj = new Ftp();
        $ftpObj->connect($ftpString);
        $remoteConfigExists = $ftpObj->fileExists("cache.cfg");
        if (!$remoteConfigExists) {
            $configFile = uniqid("temp_cachecfg_");
            $remoteCfg = new Singleconfig($configFile);
            $remoteCfg->clear();
            $ftpObj->upload("cache.cfg", $configFile);
        } else {
            $configFile = uniqid("temp_cachecfg_");
            $ftpObj->get($configFile, "cache.cfg");
            $remoteCfg = new Singleconfig($configFile);
        }
        return array($remoteCfg, $ftpObj);
    }

    /**
     * @param string $ftpString
     * @return \Magento\Framework\Object[]
     */
    public function getRemoteConfig($ftpString)
    {
        $ftpObj = new Ftp();
        $ftpObj->connect($ftpString);
        $cfgFile = "connect.cfg";

        $wd = $ftpObj->getcwd();
        $remoteConfigExists = $ftpObj->fileExists($cfgFile);
        $tempConfigFile = uniqid($cfgFile . "_temp");
        if (!$remoteConfigExists) {
            $remoteCfg = new Config($tempConfigFile);
            $remoteCfg->store();
            $ftpObj->upload($cfgFile, $tempConfigFile);
        } else {
            $ftpObj->get($tempConfigFile, $cfgFile);
            $remoteCfg = new Config($tempConfigFile);
        }
        $ftpObj->chdir($wd);
        return array($remoteCfg, $ftpObj);
    }

    /**
     * @param Singleconfig $cache
     * @param Ftp $ftpObj
     * @return void
     */
    public function writeToRemoteCache($cache, $ftpObj)
    {
        $wd = $ftpObj->getcwd();
        $ftpObj->upload("cache.cfg", $cache->getFilename());
        @unlink($cache->getFilename());
        $ftpObj->chdir($wd);
    }

    /**
     * @param Singleconfig $cache
     * @param Ftp $ftpObj
     * @return void
     */
    public function writeToRemoteConfig($cache, $ftpObj)
    {
        $wd = $ftpObj->getcwd();
        $ftpObj->upload("connect.cfg", $cache->getFilename());
        @unlink($cache->getFilename());
        $ftpObj->chdir($wd);
    }

    /**
     *
     * @param string $chanName
     * @param string $package
     * @param Singleconfig $cacheObj
     * @param Config $configObj
     * @return void
     */
    public function processUninstallPackage($chanName, $package, $cacheObj, $configObj)
    {
        $package = $cacheObj->getPackageObject($chanName, $package);
        $contents = $package->getContents();

        $targetPath = rtrim($configObj->magento_root, "\\/");
        foreach ($contents as $file) {
            $fileName = basename($file);
            $filePath = dirname($file);
            $dest = $targetPath . '/' . $filePath . '/' . $fileName;
            if (@file_exists($dest)) {
                //var_dump($dest);
                @unlink($dest);
            }
        }
    }

    /**
     *
     * @param string $chanName
     * @param string $package
     * @param Singleconfig $cacheObj
     * @param Ftp $ftp
     * @return void
     */
    public function processUninstallPackageFtp($chanName, $package, $cacheObj, $ftp)
    {
        $ftpDir = $ftp->getcwd();
        $package = $cacheObj->getPackageObject($chanName, $package);
        $contents = $package->getContents();
        foreach ($contents as $file) {
            $res = $ftp->delete($file);
        }
        $ftp->chdir($ftpDir);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function convertFtpPath($str)
    {
        return str_replace("\\", "/", $str);
    }

    /**
     *
     * @param string $package
     * @param string $file
     * @param Config $configObj
     * @param Ftp $ftp
     * @return void
     */
    public function processInstallPackageFtp($package, $file, $configObj, $ftp)
    {
        $ftpDir = $ftp->getcwd();
        $contents = $package->getContents();
        $arc = $this->getArchiver();
        $target = dirname($file) . '/' . $package->getReleaseFilename();
        @mkdir($target, 0777, true);
        $mode = $configObj->global_dir_mode;
        $tar = $arc->unpack($file, $target);
        $modeFile = $configObj->global_file_mode;
        $modeDir = $configObj->global_dir_mode;
        foreach ($contents as $file) {
            $fileName = basename($file);
            $filePath = $this->convertFtpPath(dirname($file));
            $source = $tar . '/' . $file;
            if (file_exists($source) && is_file($source)) {
                $args = array(ltrim($file, "/"), $source);
                if ($modeDir) {
                    $args[] = $modeDir;
                }
                call_user_func_array(array($ftp, 'upload'), $args);
            }
        }
        $ftp->chdir($ftpDir);
        \Magento\Framework\System\Dirs::rm(array("-r", $target));
    }

    /**
     * Package installation to FS
     * @param \Magento\Framework\Connect\Package $package
     * @param string $file
     * @param Config $configObj
     * @return void
     * @throws \Exception
     */
    public function processInstallPackage($package, $file, $configObj)
    {
        $contents = $package->getContents();
        $arc = $this->getArchiver();
        $target = dirname($file) . '/' . $package->getReleaseFilename();
        @mkdir($target, 0777, true);
        $mode = $configObj->global_dir_mode;
        $tar = $arc->unpack($file, $target);
        $modeFile = $configObj->global_file_mode;
        $modeDir = $configObj->global_dir_mode;
        foreach ($contents as $file) {
            $fileName = basename($file);
            $filePath = dirname($file);
            $source = $tar . '/' . $file;
            $targetPath = rtrim($configObj->magento_root, "\\/");
            @mkdir($targetPath . '/' . $filePath, $modeDir, true);
            $dest = $targetPath . '/' . $filePath . '/' . $fileName;
            if (is_file($source)) {
                @copy($source, $dest);
                if ($modeFile) {
                    @chmod($dest, $modeFile);
                }
            } else {
                @mkdir($dest, $modeDir);
            }
        }
        \Magento\Framework\System\Dirs::rm(array("-r", $target));
    }

    /**
     * Get local modified files
     * @param string $chanName
     * @param string $package
     * @param Singleconfig $cacheObj
     * @param Config $configObj
     * @return string[]
     */
    public function getLocalModifiedFiles($chanName, $package, $cacheObj, $configObj)
    {
        $p = $cacheObj->getPackageObject($chanName, $package);
        $hashContents = $p->getHashContents();
        $listModified = array();
        foreach ($hashContents as $file => $hash) {
            if (md5_file($configObj->magento_root . '/' . $file) !== $hash) {
                $listModified[] = $file;
            }
        }
        return $listModified;
    }

    /**
     * Get remote modified files
     *
     * @param string $chanName
     * @param string $package
     * @param Singleconfig $cacheObj
     * @param Ftp $ftp
     * @return string[]
     */
    public function getRemoteModifiedFiles($chanName, $package, $cacheObj, $ftp)
    {
        $p = $cacheObj->getPackageObject($chanName, $package);
        $hashContents = $p->getHashContents();
        $listModified = array();
        foreach ($hashContents as $file => $hash) {
            $localFile = uniqid("temp_remote_");
            if (!$ftp->fileExists($file)) {
                continue;
            }
            $ftp->get($localFile, $file);
            if (file_exists($localFile) && md5_file($localFile) !== $hash) {
                $listModified[] = $file;
            }
            @unlink($localFile);
        }
        return $listModified;
    }

    /**
     * Get upgrades list
     *
     * @param string/array $channels
     * @param Singleconfig $cacheObject
     * @param Config $configObj
     * @param \Magento\Framework\Connect\Rest $restObj optional
     * @param bool $checkConflicts
     * @return array
     */
    public function getUpgradesList($channels, $cacheObject, $configObj, $restObj = null, $checkConflicts = false)
    {
        if (is_scalar($channels)) {
            $channels = array($channels);
        }

        if (!$restObj) {
            $restObj = new \Magento\Framework\Connect\Rest();
        }

        $updates = array();
        foreach ($channels as $chan) {

            if (!$cacheObject->isChannel($chan)) {
                continue;
            }
            $chanName = $cacheObject->chanName($chan);
            $localPackages = $cacheObject->getInstalledPackages($chanName);
            $localPackages = $localPackages[$chanName];

            if (!count($localPackages)) {
                continue;
            }

            $channel = $cacheObject->getChannel($chan);
            $uri = $channel[Singleconfig::K_URI];
            $restObj->setChannel($uri);
            $remotePackages = $restObj->getPackagesHashed();

            /**
             * Iterate packages of channel $chan
             */
            $state = $configObj->preferred_state ? $configObj->preferred_state : "devel";

            foreach ($localPackages as $localName => $localData) {
                if (!isset($remotePackages[$localName])) {
                    continue;
                }
                $package = $remotePackages[$localName];
                $neededToUpgrade = false;
                $remoteVersion = $localVersion = trim($localData[Singleconfig::K_VER]);
                foreach ($package as $version => $s) {

                    if ($cacheObject->compareStabilities($s, $state) < 0) {
                        continue;
                    }

                    if (version_compare($version, $localVersion, ">")) {
                        $neededToUpgrade = true;
                        $remoteVersion = $version;
                    }

                    if ($checkConflicts) {
                        $conflicts = $cacheObject->hasConflicts($chanName, $localName, $remoteVersion);
                        if (false !== $conflicts) {
                            $neededToUpgrade = false;
                        }
                    }
                }
                if (!$neededToUpgrade) {
                    continue;
                }
                if (!isset($updates[$chanName])) {
                    $updates[$chanName] = array();
                }
                $updates[$chanName][$localName] = array("from" => $localVersion, "to" => $remoteVersion);
            }
        }
        return $updates;
    }

    /**
     * Get uninstall list
     *
     * @param string $chanName
     * @param string $package
     * @param Singleconfig $cache
     * @param Config $config
     * @param bool $withDepsRecursive
     * @return array|void
     */
    public function getUninstallList($chanName, $package, $cache, $config, $withDepsRecursive = true)
    {
        static $level = 0;
        static $hash = array();

        $chanName = $cache->chanName($chanName);
        $keyOuter = $chanName . "/" . $package;
        $level++;

        try {
            $chanName = $cache->chanName($chanName);
            if (!$cache->hasPackage($chanName, $package)) {
                $level--;
                if ($level == 0) {
                    $hash = array();
                    return array('list' => array());
                }
                return;
            }
            $dependencies = $cache->getPackageDependencies($chanName, $package);
            $data = $cache->getPackage($chanName, $package);
            $version = $data['version'];
            $keyOuter = $chanName . "/" . $package;

            //print "Processing outer: {$keyOuter} \n";
            $hash[$keyOuter] = array(
                'name' => $package,
                'channel' => $chanName,
                'version' => $version,
                'packages' => $dependencies
            );

            if ($withDepsRecursive) {
                $flds = array('name', 'channel', 'min', 'max');
                $fldsCount = count($flds);
                foreach ($dependencies as $row) {
                    foreach ($flds as $key) {
                        $varName = "p" . ucfirst($key);
                        ${$varName} = $row[$key];
                    }
                    $method = __FUNCTION__;
                    $keyInner = $pChannel . "/" . $pName;
                    if (!isset($hash[$keyInner])) {
                        $this->{$method}($pChannel, $pName, $cache, $config, $withDepsRecursive, false);
                    }
                }
            }
        } catch (\Exception $e) {
        }

        $level--;
        if (0 === $level) {
            $out = $this->processDepsHash($hash);
            $hash = array();
            return array('list' => $out);
        }
    }

    /**
     * Get dependencies list/install order info
     *
     * @param string $chanName
     * @param string $package
     * @param Singleconfig $cache
     * @param Config $config
     * @param string|false $versionMax
     * @param string|false $versionMin
     * @param bool $withDepsRecursive
     * @param bool $forceRemote
     * @return array|void
     * @throws \Exception
     */
    public function getDependenciesList(
        $chanName,
        $package,
        $cache,
        $config,
        $versionMax = false,
        $versionMin = false,
        $withDepsRecursive = true,
        $forceRemote = false
    ) {
        static $level = 0;
        static $_depsHash = array();
        static $_deps = array();
        static $_failed = array();

        $level++;

        try {
            $chanName = $cache->chanName($chanName);

            $rest = new \Magento\Framework\Connect\Rest($config->protocol);
            $rest->setChannel($cache->chanUrl($chanName));
            $releases = $rest->getReleases($package);
            if (!$releases || !count($releases)) {
                throw new \Exception("No releases for: '{$package}', skipping");
            }
            $state = $config->preffered_state ? $confg->preffered_state : 'devel';
            $version = $cache->detectVersionFromRestArray($releases, $versionMin, $versionMax, $state);
            if (!$version) {
                throw new \Exception("Version for '{$package}' was not detected");
            }
            $packageInfo = $rest->getPackageReleaseInfo($package, $version);
            if (false === $packageInfo) {
                throw new \Exception("Package release '{$package}' not found on server");
            }
            unset($rest);
            $dependencies = $packageInfo->getDependencyPackages();
            $keyOuter = $chanName . "/" . $package;

            //print "Processing outer: {$keyOuter} \n";
            $_depsHash[$keyOuter] = array(
                'name' => $package,
                'channel' => $chanName,
                'downloaded_version' => $version,
                'min' => $versionMin,
                'max' => $versionMax,
                'packages' => $dependencies
            );

            if ($withDepsRecursive) {
                $flds = array('name', 'channel', 'min', 'max');
                $fldsCount = count($flds);
                foreach ($dependencies as $row) {
                    foreach ($flds as $key) {
                        $varName = "p" . ucfirst($key);
                        ${$varName} = $row[$key];
                    }
                    $method = __FUNCTION__;
                    $keyInner = $pChannel . "/" . $pName;
                    if (!isset($_depsHash[$keyInner])) {
                        $_deps[] = $row;
                        $this->{$method}(
                            $pChannel,
                            $pName,
                            $cache,
                            $config,
                            $pMax,
                            $pMin,
                            $withDepsRecursive,
                            $forceRemote,
                            false
                        );
                    } else {
                        $downloaded = $_depsHash[$keyInner]['downloaded_version'];
                        $hasMin = $_depsHash[$keyInner]['min'];
                        $hasMax = $_depsHash[$keyInner]['max'];
                        if ($pMin === $hasMin && $pMax === $hasMax) {
                            //var_dump("Equal requirements, skipping");
                            continue;
                        }

                        if ($cache->versionInRange($downloaded, $pMin, $pMax)) {
                            //var_dump("Downloaded package matches new range too");
                            continue;
                        }

                        $names = array("pMin", "pMax", "hasMin", "hasMax");
                        for ($i = 0,$c = count($names); $i < $c; $i++) {
                            if (!isset(${$names[$i]})) {
                                continue;
                            }
                            if (false !== ${$names[$i]}) {
                                continue;
                            }
                            ${$names[$i]} = $i % 2 == 0 ? "0" : "999999999";
                        }

                        if (!$cache->hasVersionRangeIntersect($pMin, $pMax, $hasMin, $hasMax)) {
                            $reason = "Detected {$pName} conflict of versions: {$hasMin}-{$hasMax} and {$pMin}-{$pMax}";
                            unset($_depsHash[$keyInner]);
                            $_failed[] = array(
                                'name' => $pName,
                                'channel' => $pChannel,
                                'max' => $pMax,
                                'min' => $pMin,
                                'reason' => $reason
                            );
                            continue;
                        }
                        $newMaxIsLess = version_compare($pMax, $hasMax, "<");
                        $newMinIsGreater = version_compare($pMin, $hasMin, ">");
                        $forceMax = $newMaxIsLess ? $pMax : $hasMax;
                        $forceMin = $newMinIsGreater ? $pMin : $hasMin;
                        //var_dump("Trying to process {$pName} : max {$forceMax} - min {$forceMin}");
                        $this->{$method}(
                            $pChannel,
                            $pName,
                            $cache,
                            $config,
                            $forceMax,
                            $forceMin,
                            $withDepsRecursive,
                            $forceRemote
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $_failed[] = array(
                'name' => $package,
                'channel' => $chanName,
                'max' => $versionMax,
                'min' => $versionMin,
                'reason' => $e->getMessage()
            );
        }

        $level--;
        if ($level == 0) {
            $out = $this->processDepsHash($_depsHash);
            $deps = $_deps;
            $failed = $_failed;
            $_depsHash = array();
            $_deps = array();
            $_failed = array();
            return array('deps' => $deps, 'result' => $out, 'failed' => $failed);
        }
    }

    /**
     * Process dependencies hash
     * Makes topological sorting and gives operation order list
     *
     * @param array &$depsHash
     * @param bool $sortReverse
     * @return array
     */
    protected function processDepsHash(&$depsHash, $sortReverse = true)
    {
        $nodes = array();
        $graph = new \Magento\Framework\Connect\Structures\Graph();

        foreach ($depsHash as $key => $data) {
            $packages = $data['packages'];
            $node = new \Magento\Framework\Connect\Structures\Node();
            $nodes[$key] =& $node;
            unset($data['packages']);
            $node->setData($data);
            $graph->addNode($node);
            unset($node);
        }

        if (count($nodes) > 1) {
            foreach ($depsHash as $key => $data) {
                $packages = $data['packages'];
                foreach ($packages as $pdata) {
                    $pName = $pdata['channel'] . "/" . $pdata['name'];
                    if (isset($nodes[$key], $nodes[$pName])) {
                        $nodes[$key]->connectTo($nodes[$pName]);
                    }
                }
            }
        }
        $result = $graph->topologicalSort();
        $sortReverse ? krsort($result) : ksort($result);
        $out = array();
        $total = 0;
        foreach ($result as $order => $nodes) {
            foreach ($nodes as $n) {
                $out[] = $n->getData();
            }
        }
        unset($graph, $nodes);
        return $out;
    }
}
