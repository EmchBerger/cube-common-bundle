<?php

namespace CubeTools\CubeCommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Help controller.
 *
 * @Route("_profiler/development")
 */
class DevelopmentSupportController extends Controller
{
    /**
     * Simplify reporting of a bug by prefilling the form.
     *
     * @Route("/reportbug", name="cube_common.reportbug")
     * @Method("GET")
     */
    public function reportBugAction(Request $request)
    {
        $projVer = $this->get('cube_common.project_version');
        $githubProjectUrl = $projVer->getGitRepoUrl();
        $version = $projVer->getVersionString();
        $verHash = $projVer->getGitHash();

        $reqInfo = $this->requestHandling($request);

        $msg = $this->generateBugMsg($version, $verHash, $reqInfo);
        $link = $this->generateBugLink($githubProjectUrl, $msg);
        $args = array(
            'redirect' => $reqInfo['redirectDelay'],
            'baseUrl' => $reqInfo['baseUrl'],
            'relatedUrl' => $reqInfo['relatedUrl'],
            'projectName' => basename($githubProjectUrl),
            'profilerToken' => $reqInfo['profilerToken'],
            'directLink' => $link,
            'msg' => $msg,
        );
        $response = $this->render('CubeToolsCubeCommonBundle:DevelopmentSupport:reportBug.html.twig', $args);
        if (null !== $reqInfo['redirectDelay']) {
            $response->headers->set('refresh', $reqInfo['redirectDelay'].'; url='.$link);
        }

        return $response;
    }

    /**
     * Simplify reporting of a bug by providing a link to prefill the form.
     */
    public function fullBugLinkAction(Request $request, $exception = null)
    {
        $projVer = $this->get('cube_common.project_version');
        $githubProjectUrl = $projVer->getGitRepoUrl();
        $version = $projVer->getVersionString();
        $verHash = $projVer->getGitHash();

        $reqInfo = $this->requestHandling($request);
        if ('/_fragment' === $request->getPathInfo()) {
            $request = $this->get('request_stack')->getMasterRequest();
        }
        $reqInfo['reallyRelated'] = $request->getHttpHost().$request->getRequestUri();

        $msg = '';
        $title = 'Error';
        if ($exception) {
            if ($exception instanceof FlattenException) {
                $errClass = $exception->getClass();
            } else {
                $errClass = get_class($exception);
            }
            $title .= ' '.substr($errClass, strrpos($errClass, '\\') + 1).': '.$exception->getMessage();
            // $exception.getFile().':'.$exception.getLine();
            // $exception->getTrace()
        }

        $msgTail = $this->generateBugMsg($version, $verHash, $reqInfo);
        $link = $this->generateBugLink($githubProjectUrl, $msg.$msgTail, null, $title);

        return new Response($link);
    }

    private function requestHandling(Request $request)
    {
        $relatedUrl = $request->query->get('relatedUrl', $this);
        $profilerToken = $request->query->get('profiler');
        $userAgent = $request->query->get('userAgent');
        if (!$userAgent) {
            $userAgent = $request->headers->get('user-agent');
        }
        // $module = guess module from prev url?

        $baseUrl = $request->getHttpHost().$request->getBaseUrl();
        if ($this === $relatedUrl) {
            $relatedUrl = $request->headers->get('referer');
        }
        $urlOffset = strpos($relatedUrl, $baseUrl);
        if (false !== $urlOffset) {
            $redirectDelay = 2;
            $reallyRelated = $relatedUrl;
        } else {
            $redirectDelay = null;
            $reallyRelated = null;
        }

        return array(
            'relatedUrl' => $relatedUrl,
            'profilerToken' => $profilerToken,
            'userAgent' => $userAgent,
            'baseUrl' => $baseUrl,
            'redirectDelay' => $redirectDelay,
            'reallyRelated' => $reallyRelated,
        );
    }

    private function generateBugMsg($version, $verHash, $reqInfo)
    {
        $relatedUrl = $reqInfo['reallyRelated'];
        $userAgent = $reqInfo['userAgent'];
        if (!$relatedUrl) {
            $relatedUrl = 'XXXurlXXX';
        }
        if (!$userAgent) {
            $userAgent = 'XXbrowserXX';
        }
        $msgBody = "\n\n<hr/>\n\nversion = ".$version.'  '.substr($verHash, 0, 8)."\nurl = ".$relatedUrl."\nbrowser = ".$userAgent;
        if ($reqInfo['profilerToken']) {
            $profilerUrl = $this->generateUrl(
                '_profiler',
                array('token' => $reqInfo['profilerToken']),
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $msgBody .= "\nprofiler: [".$reqInfo['profilerToken'].']('.$profilerUrl.')';
        }

        return $msgBody;
    }

    private function generateBugLink($githubProjectUrl, $msgBody, $module = null, $title = '')
    {
        if (null === $module) {
            $module = 'XXmoduleXX';
        }

        return $githubProjectUrl.'/issues/new?HINT= SIGN IN! &title='.urlencode('['.$module.'] ').urlencode($title).
                '&body='.urlencode($msgBody);
    }
}
