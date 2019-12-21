<?php

namespace PHPBrew\Tests\Extension;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PHPBrew\Extension\ExtensionDownloader;
use PHPBrew\Extension\ExtensionFactory;
use PHPBrew\Extension\ExtensionManager;
use PHPBrew\Extension\Provider\PeclProvider;
use PHPBrew\Testing\CommandTestCase;

/**
 * NOTE: This depends on an existing installed php build. we need to ensure
 * that the installer test runs before this test.
 *
 * @large
 * @group extension
 */
class ExtensionInstallerTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();
        $versionName = $this->getPrimaryVersion();
        $this->runCommand("phpbrew use php-{$versionName}");
    }

    /**
     * @group noVCR
     */
    public function testPackageUrl()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped('Skipping since VCR cannot properly record this request');
        }

        $logger = new Logger();
        $logger->setQuiet();
        $peclProvider = new PeclProvider($logger, new OptionResult());
        $downloader = new ExtensionDownloader($logger, new OptionResult());
        $peclProvider->setPackageName('APCu');
        $extractPath = $downloader->download($peclProvider, 'latest');
        $this->assertFileExists($extractPath);
    }

    public function packageNameProvider()
    {
        return array(
            // xdebug requires at least php 5.4
            // array('xdebug'),
            array(version_compare(PHP_VERSION, '5.5', '=='),'APCu', 'stable', array()),
            // array(version_compare(PHP_VERSION, '5.5', '=='),'yaml', 'stable', array()),
        );
    }

    /**
     * @dataProvider packageNameProvider
     */
    public function testInstallPackages($build, $extensionName, $extensionVersion, $options)
    {
        if (!$build) {
            $this->markTestSkipped('skip extension build test');
            return;
        }
        $logger = new Logger();
        $logger->setDebug();
        $manager = new ExtensionManager($logger);
        $peclProvider = new PeclProvider();
        $downloader = new ExtensionDownloader($logger, new OptionResult());
        $peclProvider->setPackageName($extensionName);
        $downloader->download($peclProvider, $extensionVersion);
        $ext = ExtensionFactory::lookup($extensionName);
        $this->assertNotNull($ext);
        $manager->installExtension($ext, $options);
    }
}