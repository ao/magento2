<?php
/**
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */

namespace Magento\Framework\View\Asset;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Asset;
use Magento\Tools\View\Deployer;

/**
 * BundleService model
 */
class BundleService
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $bundles = [];

    /**
     * @var \Magento\Framework\View\Asset\BundleFactory
     */
    protected $bundleFactory;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /** @var LocalInterface */
    protected $asset;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param BundleFactory $bundleFactory
     * @param Bundle\ConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\View\Asset\BundleFactory $bundleFactory,
        \Magento\Framework\View\Asset\Bundle\ConfigInterface $config
    ) {
        $this->filesystem = $filesystem;
        $this->bundleFactory = $bundleFactory;
        $this->config = $config;
    }

    /**
     * @param LocalInterface $asset
     * @return $this
     */
    protected function setAsset(LocalInterface $asset)
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @return LocalInterface
     * @throws \InvalidArgumentException
     */
    protected function getAsset()
    {
        return $this->asset;
    }

    /**
     * @return ContextInterface
     */
    protected function getContext()
    {
        return $this->asset->getContext();
    }

    /**
     * Check if asset in exclude list
     *
     * @return bool
     */
    protected function isExcluded()
    {
        $asset = $this->getAsset();
        $area = $this->getContext()->getAreaCode();
        //if (in_array($asset->getFilePath(), $this->config->getExcludedFiles($area))) {
        //    return true;
        //}
        //
        //// check if file in excluded directory
        //$assetDirectory  = dirname($asset->getFilePath());
        //foreach ($this->config->getExcludedDir($area) as $dir) {
        //    if (strpos($assetDirectory, $dir) !== false) {
        //        return true;
        //    }
        //}

        return false;
    }

    /**
     * Collect bundle
     *
     * @param LocalInterface $asset
     * @return bool
     */
    public function collect(LocalInterface $asset)
    {
        $this->setAsset($asset);
        if (!$this->isValidAsset()) {
             return false;
        }

        /** @var \Magento\Framework\View\Asset\Bundle $bundle */
        $bundle = $this->getBundle();
        $bundle->addAsset($asset);

        return true;
    }

    /**
     * @return bool
     */
    protected function isValidAsset()
    {
        if (Bundle::isValidType($this->getAsset()->getContentType()) && !$this->isExcluded()) {
            return true;
        }
        return false;
    }

    /**
     * Return bundle
     *
     * @return \Magento\Framework\View\Asset\Bundle|bool
     */
    protected function getBundle()
    {
        $bundlePath = $this->getBundlePath();
        return (isset($this->bundles[$bundlePath])) ? $this->bundles[$bundlePath] : $this->createBundle();
    }

    /**
     * Create bundle
     *
     * @return \Magento\Framework\View\Asset\Bundle
     */
    protected function createBundle()
    {
        $bundlePath = $this->getBundlePath();
        $bundle = $this->bundleFactory->create();
        $bundle->setPath($bundlePath);
        $bundle->setType($this->getAsset()->getContentType());
        $this->bundles[$bundlePath] = $bundle;
        return $bundle;
    }

    /**
     * Build bundle path
     *
     * @return string
     */
    protected function getBundlePath()
    {
        $path = $this->getContext()->getPath();
        if ($this->getAsset()->getModule() != '') {
            $bundleName = '/bundle';
            if ($this->getAsset()->getContentType() == 'html') {
                $bundleName = '/bundle-html';
            }
        } else {
            $bundleName = '/lib-bundle';
        }
        $path .= $bundleName;
        return $path;
    }

    /**
     * Save bundle to js file
     *
     * @return bool
     */
    public function save()
    {
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);

        foreach ($this->bundles as $bundle) {
            /** @var \Magento\Framework\View\Asset\Bundle $bundle */
            foreach ($bundle->getContent() as $index => $part) {
                $dir->writeFile($bundle->getPath() . "$index.js", $part);
            }
        }

        return true;
    }
}
