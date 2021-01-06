<?php

namespace CubeTools\CubeCommonBundle\Project;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Class to read version from this projects git repository.
 */
class ProjectVersionGit implements CacheWarmerInterface
{
    private $kernelRoot;
    private $cacheDir;
    private $data = null;

    /**
     * Create service.
     *
     * @param string $kernelRoot kernel root directory, git repo is in its parent directory
     * @param string $cacheDir   directory to store cached data
     */
    public function __construct($kernelRoot, $cacheDir)
    {
        $this->kernelRoot = $kernelRoot;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return string version of the project, read from git
     */
    public function getVersionString()
    {
        return $this->getGitData()['tag'];
    }

    /**
     * @return string closest tag of the project, read from git
     */
    public function getClosestTagString()
    {
        return $this->getGitData()['closestTag'];
    }

    /**
     * @return string id of the project version, git hash
     */
    public function getGitHash()
    {
        return $this->getGitData()['hash'];
    }

    /**
     * @return string url to the exact project version
     */
    public function getVersionUrl()
    {
        $repo = $this->getGitRepoUrl();
        if ($repo) {
            return $repo.'/commit/'.$this->getGitHash();
        }

        return '';
    }

    /**
     * @return string url to the project
     */
    public function getGitRepoUrl()
    {
        return $this->getGitData()['url'];
    }

    public function warmUp($cacheDir)
    {
        // from CacheWarmerInterface

        $this->getGitData(true);
    }

    public function isOptional()
    {
        // from CacheWarmerInterface

        return true; // warmup is optional
    }

    protected function getGitData($doCheck = null)
    {
        if (null !== $this->data) {
            return $this->data;
        }

        $cacheFile = $this->cacheDir.'/cube-common_ProjectVersionGit.json';
        if (null === $doCheck) {
            $doCheck = 0 === rand(0, 15);
        }
        $cache = new ConfigCache($cacheFile, $doCheck);
        if ($cache->isFresh()) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (false !== $data) {
                return $data;
            }
        }

        $gitDir = $this->kernelRoot.'/../.git/';
        $headFile = $gitDir.'HEAD';
        if (is_readable($headFile)) {
            $refFile = file_get_contents($headFile, false, null, 0, 512);
        } else {
            $refFile = false;
        }
        if (false === $refFile) {
            // reading failed
            $resources = array(new FileExistenceResource($headFile));
            $data = array('hash' => '', 'url' => '', 'tag' => '');
        } elseif ('ref: ' === substr($refFile, 0, 5)) {
            // reference
            $refFile = $gitDir.rtrim(substr($refFile, 5));
            if (file_exists($refFile)) {
                $resources = array(new FileResource($headFile), new FileResource($refFile));
            } else {// reference is in packed-refs
                $resources = array(
                    new FileResource($headFile),
                    new FileExistenceResource($refFile),
                    new FileResource($gitDir.'packed-refs'),
                );
            }
            $data = $this->queryGitData();
        } else {
            // hash or unknown
            $resources = array(new FileResource($headFile));
            $data = $this->queryGitData();
        }
        if (is_file($gitDir.'config')) {
            $resources[] = new FileResource($gitDir.'config');
        }
        $cache->write(json_encode($data), $resources);
        $this->data = $data;

        return $data;
    }

    protected function queryGitData()
    {
        $R = 'https://';
        $data = array();
        $data['hash'] = exec('git rev-parse HEAD');
        $url = exec('git config --get remote.origin.url');
        if (false !== $atPos = strpos($url, '@')) { // user@host:path/to
            $dPos = strpos($url, ':');
            if (false !== $dPos && $dPos > $atPos) {
                $url[$dPos] = '/';
                $url = $R.substr($url, $atPos + 1);
            } elseif ('/' === $url[$dPos + 1] && '/' === $url[$dPos + 2]) { // proto://
                $url = substr_replace($url, '', $dPos + 3, $atPos - $dPos - 2);
            }
        }
        $data['url'] = strtr($url, array('.git' => '', 'ssh://' => $R, 'git://' => $R, 'ftp://' => $R, 'ftps://' => $R, 'rsync://' => $R));
        $data['tag'] = exec('git describe --tags --always');
        $data['closestTag'] = exec('git describe --tags --always --abbrev=0');

        return $data;
    }
}
