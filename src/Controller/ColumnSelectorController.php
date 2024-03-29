<?php

namespace CubeTools\CubeCommonBundle\Controller;

use CubeTools\CubeCommonBundle\UserSettings\UserSettingsStorage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use CubeTools\CubeCommonBundle\DataHandling\DataConversion;
use Symfony\Component\Routing\Annotation\Route;

class ColumnSelectorController extends Controller
{
    private static $colsButtons = array();
    private $ccuSettings;

    public function setUserSettingsStorage(UserSettingsStorage $ccuSettings = null)
    {
        $this->ccuSettings = $ccuSettings;
    }

    /**
     * Renders the snippet to include for a ColumnSelector button.
     */
    public function nearButtonAction($path, $btnId = '')
    {
        if (isset(self::$colsButtons[$btnId])) {
            ++self::$colsButtons[$btnId];
            $reply = $this->render('CubeToolsCubeCommonBundle:ColumnSelector:nearButtonDuplicateId.inc.html.twig', array(
                'btnId' => $btnId,
            ));
        } else {
            self::$colsButtons[$btnId] = 1;
            $reply = new Response('');
        }

        return $reply;
    }

    public function getTablesSettingsAction($path = '')
    {
        $settings = array();
        $errors = array();
        foreach (self::$colsButtons as $btnId => $nr) {
            $saveId = $this->getId($path, $btnId);
            $settings[$btnId] = array('settings' => $this->getColsSettings($saveId));
            if ($nr > 1) {
                $errors[$btnId] = $nr;
            }
        }
        if ($errors) {
            $settings['ERRORS'] = array('msg' => 'id used for several tables:', 'tables' => $errors);
        }

        return new JsonResponse($settings);
    }

    /**
     * Saves column settings.
     *
     * @Route("/colssettings", name="cubecommon.colsselector_send", methods={"PUT"})
     */
    public function saveSettingsAction(Request $request)
    {
        $request->setRequestFormat('json');
        $id = $request->request->get('id');
        $fullPath = $request->request->get('fullPath');
        if (!$fullPath) {
            throw new BadRequestHttpException('request fullPath is not set');
        }
        $settings = $request->request->get('settings');

        $path = substr($fullPath, strlen($request->getBaseUrl()));
        $saveId = $this->getId($path, $id);
        $this->saveColsSettings($saveId, $settings);
        $savedSettings = $this->getColsSettings($saveId);

        return new JsonResponse(array('settings' => $savedSettings, 'id' => $id));
    }

    protected function getColsSettings($saveId)
    {
        if ($this->ccuSettings) {
            return $this->ccuSettings->getUserSetting('column', $saveId);
        } else {
            $msg = 'ERROR: missing service '.UserSettingsStorage::class;
            if (function_exists('dump')) {
                $log = 'dump';
                $log($msg);
            }

            return array($msg);
        }
    }

    protected function saveColsSettings($saveId, array $settings)
    {
        DataConversion::dataTextToDataInArray($settings);

        // error is handled by ajax caller
        return $this->ccuSettings->setUserSetting('column', $saveId, $settings);
    }

    /**
     * Create the saveId for the given ids.
     *
     * When the id starts with 'X:', it is taken directly as id without the path
     *
     * @param string $path relative page path
     * @param string $id   id of table (button) on the page
     *
     * @return string
     */
    protected function getId($path, $id)
    {
        if ($id && 'X' === $id[0] && ':' === $id[1]) {
            return substr($id, 2);
        }

        return $path.'~'.$id;
    }
}
