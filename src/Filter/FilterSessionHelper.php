<?php

namespace CubeTools\CubeCommonBundle\Filter;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Used by FilterService.
 */
class FilterSessionHelper
{
    /**
     * Load filter from session.
     *
     * @param SessionInterface $session
     * @param string           $pageName
     *
     * @return array of string
     */
    public static function getFilterDataFromSession(SessionInterface $session, $pageName)
    {
        $filter = $session->get($pageName.'_filter');

        return $filter;
    }

    /**
     * Save filter to session.
     *
     * @param SessionInterface $session
     * @param string           $pageName
     * @param array            $filter
     */
    public static function setFilterDataToSession(SessionInterface $session, $pageName, array $filter, $onSuccessKeepFn)
    {
        $hashCtx = hash_init('md5');
        foreach ($filter as $n => $f) {
            if (is_subclass_of($f, FormInterface::class)) {
                $form = $f;
                $f = $form->getViewData();
                if (self::isProbablyTetranzEntityData($f) && self::isTetranzEntityForm($form)) {
                    if (self::isTetranzMultiEntityForm($form)) {
                        $f = self::tetranzEntityDataToNormal($f);
                    } else {
                        $f = self::tetranzSingleEntityDataToNormal($f);
                    }
                }
                $filter[$n] = $f;
            }
            hash_update($hashCtx, '!'); // hash depends on field position
            hash_update($hashCtx, is_array($f) ? implode(';', $f) : $f);
        }
        $hash = hash_final($hashCtx);
        if ($session->get($pageName.'_filter_Hash') !== $hash) {
            //filter has changed
            if ($onSuccessKeepFn) {
                $onSuccessKeepFn($session, array($pageName.'_filter', $pageName.'_filter_Hash'));
            }
            $session->remove($pageName.'_page');
            $session->set($pageName.'_filter', $filter);
            $session->set($pageName.'_filter_Hash', $hash);
        }
    }

    public static function saveFilterData(Request $request, FormInterface $form, array $data, $pageName, $onSuccessKeepFn)
    {
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($data as $key => $value) {
                if (self::isProbablyTetranzEntityData($value)) {
                    $elementForm = $form[$key];
                    if (!$elementForm->getViewData() && self::isTetranzEntityForm($elementForm)) {
                        $data[$key] = self::tetranzEntityDataToNormal($value);
                    }
                }
            }
            self::setFilterDataToSession($request->getSession(), $pageName, $data, $onSuccessKeepFn);

            return true;
        }

        return false;
    }

    /**
     *  Get filter data from request or session.
     *
     * @param Request       $request
     * @param FormInterface $form     filter form generated from this class
     * @param string|null   $pageName for storing filter and page no in session
     *
     * @return array with redirect (URL or null), filter (FilterQueryCondition) and page nr (int)
     */
    public static function getFilterData(Request $request, FormInterface $form, $pageName, $onSuccessKeepFn)
    {
        $session = $request->getSession();
        if ('1' == $request->query->get('filter_reset')) {
            self::setFilterDataToSession($session, $pageName, array(), null);

            return array('redirect' => static::getRedirectUrl($request, array('filter_reset')));
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // use all() because getViewData() returns the forms viewdata but not the elements
            self::setFilterDataToSession($session, $pageName, $form->all(), $onSuccessKeepFn);
            if ($request->getMethod() !== 'GET') {
                return array('redirect' => static::getRedirectUrl($request));
            }
            $filter = $form->getData();
        } else {
            $data = self::getFilterDataFromSession($session, $pageName);
            $filter = self::getUnsubmittedData($data, $form);
        }

        $fData = self::prepareFilterData($request, $pageName, $form->getConfig()->getOptions(), $onSuccessKeepFn);
        $fData['filter'] = new FilterQueryCondition($filter);

        return $fData;
    }

    public static function getClearedFilterData(Request $request, FormInterface $form, $pageName)
    {
        $session = $request->getSession();
        $noData = array();
        self::setFilterDataToSession($session, $pageName, $noData, null);
        $filter = self::getUnsubmittedData($noData, $form);

        $fData = self::prepareFilterData($request, $pageName, $form->getConfig()->getOptions(), null);
        $fData['filter'] = new FilterQueryCondition($filter);

        return $fData;
    }

    public static function readFilterData(FormInterface $form)
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new \LogicException('form to read is invalid');
        }
        $data = array();
        foreach ($form as $n => $f) {
            $data[$n] = $f->getViewData();
        }

        return $data;
    }

    private static function prepareFilterData(Request $request, $pageName, array $options, $onSuccessKeepFn)
    {
        $session = $request->getSession();

        $page = $request->query->get('page', false); // set to false if none set
        if (!$page) {
            $page = $session->get($pageName.'_page', 1); // if not set in session, set first page
        }
        $session->set($pageName.'_page', $page);

        $sortField = $request->query->get('sort', false);
        if (!$sortField) {
            if (false === $sortField) {
                $sort = $session->get($pageName.'_sort', false);
            } else {
                $sort = false;
            }
            if (false === $sort && !empty($options['defaultSort'])) {
                $dSort = $options['defaultSort'];
                $sortDirArr = array_intersect($dSort, array('asc', 'desc'));
                if ($sortDirArr) {
                    $dSort = array_values(array_diff($dSort, array('asc', 'desc')));
                    $sortDir = reset($sortDirArr); // first element independent of index no
                } else {
                    $sortDir = 'asc';
                }
                $sort = array('defaultSortFieldName' => $dSort, 'defaultSortDirection' => $sortDir);
            } elseif (false === $sort) {
                $sort = array();
            }
        } else {
            SortingHelper::validateSortField($sortField);
            $sortDir = $request->query->get('direction', 'asc');
            $sort = array('defaultSortFieldName' => $sortField, 'defaultSortDirection' => $sortDir);
            if ($onSuccessKeepFn && $session->get($pageName.'_sort') !== $sort) {
                $onSuccessKeepFn($session, array($pageName.'_sort'));
            }
            $session->set($pageName.'_sort', $sort);
        }

        return array('filter' => null, 'page' => $page, 'redirect' => null, 'options' => $sort);
    }

    private static function getUnsubmittedData($data, FormInterface $form)
    {
        if ($data && $form->isSubmitted()) {
            $formClass = get_class($form);
            $tmpForm = new $formClass($form->getConfig()); // to get the filter data without changing the form data
            $tmpForm->submit($data);
            $filter = $tmpForm->getData();
        } elseif ($data) {
            $form->submit($data);
            $filter = $form->getData();
        } elseif ($form->isEmpty()) {
            $filter = array();
        } elseif ($form->getData()) {
            $filter = $form->getData();
        } else { // data set directly on children
            $filter = array();
            foreach ($form as $name => $child) {
                if ($child->getConfig()->getMapped()) {
                    $filter[$name] = $child->getData();
                }
            }
        }

        return $filter;
    }

    private static function isProbablyTetranzEntityData($viewData)
    {
        return is_array($viewData) && $viewData;
    }

    private static function isTetranzEntityForm(FormInterface $form)
    {
        return $form->getConfig()->hasOption('transformer') && $form->getConfig()->hasOption('class');
    }

    private static function isTetranzMultiEntityForm(FormInterface $form)
    {
        return $form->getConfig()->getOption('multiple', false);
    }


    /**
     * Convert tetranz_select2_entity view data, which has the ids as keys in view format.
     *
     * @param string[] $viewData
     *
     * @return int[]
     */
    private static function tetranzEntityDataToNormal(array $viewData)
    {
        return array_keys($viewData);
    }

    /**
     * Convert tetranz_select2_entity view data, which has the ids as keys in view format.
     *
     * @param string[] $viewData
     *
     * @return string
     */
    private static function tetranzSingleEntityDataToNormal(array $viewData)
    {
        $keys = array_keys($viewData);
        if (count($keys)) {
            return (string)$keys[0];
        }
        return '';
    }

    private static function getRedirectUrl(Request $request, array $additionalKeys = array())
    {
        $noParam = count($request->query);
        if (0 === $noParam) {
            $redirectUri = $request->getRequestUri();
        } else {
            $query = clone $request->query;
            foreach (array('page', 'sort', 'direction') as $param) {
                $query->remove($param);
            }
            foreach ($additionalKeys as $param) {
                $query->remove($param);
            }
            $noQueryUri = $request->getBaseUrl().$request->getPathInfo();
            if (0 === count($query)) { // no query parameters
                $redirectUri = $noQueryUri;
            } elseif (count($query) === $noParam) { // same query parameters
                $redirectUri = $request->getRequestUri();
            } else {
                $redirectUri = Request::create($noQueryUri, 'GET', $query->all())->getRequestUri();
            }
        }

        return $redirectUri;
    }
}
