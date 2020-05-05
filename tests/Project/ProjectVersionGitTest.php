<?php

namespace Tests\CubeTools\CubeCommonBundle\Project;

use CubeTools\CubeCommonBundle\Project\ProjectVersionGit;

class ProjectVersionGitTest extends \PHPUnit\Framework\TestCase
{
    private static $tmpDir;

    public static function setUpBeforeClass()
    {
        $tmpDir = sys_get_temp_dir();
        for ($i = 0; $i < 64; ++$i) {
            $tmpCache = $tmpDir.'/ccbPv'.rand();
            if (mkdir($tmpCache, 0700)) {
                static::$tmpDir = $tmpCache;

                return;
            }
        }

        throw new \Exception('temp cache dir could not be created');
    }

    /**
     * @dataProvider cacheChecking
     */
    public function testInexistingDir()
    {
        $pv = new ProjectVersionGit(__DIR__.'/non_existing_dir/app', static::$tmpDir.'/ne');
        $v = $pv->getVersionString();
        $this->assertSame('', $v);
        $u = $pv->getVersionUrl();
        $this->assertSame('', $u);
    }

    /**
     * @dataProvider cacheChecking
     */
    public function testRealVersion()
    {
        $rootDir = __DIR__.'/..'; // %kernel.root_dir% normally
        $pv = new ProjectVersionGit($rootDir, static::$tmpDir.'/rv');
        $v = $pv->getVersionString();
        $this->assertNotEquals('', $v);
        $h = $pv->getGitHash();
        $this->assertRegexp('/^[a-z0-9]*$/', $h);
        $r = $pv->getGitRepoUrl();
        $u = $pv->getVersionUrl();
        if ('' === $r) {
            $this->markTestSkipped('origin url is not set');
        }
        $this->assertRegexp('|^http.*'.$h.'|', $u);
    }

    /**
     * dataprovider to run tests 2x, once with querried data, once form cache.
     */
    public static function cacheChecking()
    {
        return array('load' => array(), 'cached' => array());
    }

    public static function tearDownAfterClass()
    {
        if (!getenv('TESTS_NO_CLEANUP')) {
            exec("rm -r '".static::$tmpDir."'");
        } else {
            echo ' ** cleanup skipped, delete manually '.static::$tmpDir;
        }
    }
}
