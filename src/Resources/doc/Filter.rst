Using the filtering infrastructure
==================================


The custom ``FilterFormType`` extends ``AbstractFilterForm``.

Filtering (in a controller action or similar) is done like this:

.. code-block:: php
    // $ccFiltering is injected class CubeTools\CubeCommonBundle\Filter\FilterService

    $filterForm = $this->createForm(YourFilterFormType::class, null, $options); // $options is optional, details see below
    $fData = $ccFiltering->getFilterData($filterForm);
    if ($fData->getRedirect()) {
        return $this->redirect($fData->getRedirect());
    }
    $filter = $fData->getFilter();

    $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
        ->select('xxx')->from('AppBundle:...', 'xxx')
        ....
    ;

    $filter->setQuerybuilder($qb);
    $filter->andWhereEqual('xxy', 'number');
    /* table: xxy, filter and dbField: number
     * ... AND WHERE xxy.number = :number
     */
    $filter->andWhereLike('xxy', 'someText');
    /* from a AppBundle\Form\Type\TextLikefilterType
     * ... AND WHERE xxy.someText LIKE :someText // someText gets the % automatically by TextLikeFilterType
     */
    $filter->andWhereIn('xzy', 'selection', 'ids');
    /* table: xyz, filter: selection, dbField: ids
     * ... AND WHERE xyz.ids IN (:selection)
     */
    $filter->andWhereDaterange('yxz', 'date');
    /* from a AppBundle\Form\Type\DateRangeType
     * ... AND WHERE yxz.date > :date  /  ... AND WHERE yxz.date < :date  /  ...
     */
    $filter->andWhereCheckedValue('xzy', 'checkbox');
    /* from a SelectType with 2 options or from a CheckboxType
     * ... AND WHERE xzy.checkbox <> 0  /  ... AND WHERE xzy.checkbox = 0 OR xzy.checkbox IS NULL
     */
    $filter->andWhereIsSetIsNotSet('xxx', 'relatedThings');
    /* for select2 boxes with values YourFilterType::WHERE_IS_SET and YourFilterType::WHERE_IS_NOT_SET
     * ... AND WHERE yxx.relatedThings IS NULL  /  ... AND WHERE yxx.relatedThings IS NULL
     */

    if ($filter->isActive('complexField')) {
        $qb->andWhere(':paramName > 3 AND :paramName NOT ...')->setParameter('paramName', $filter['complexField']);
    }
    /* for a query not handled by FilterQueryCondition */
    ....

    $pagination = $fData->paginate(
        $qb,
        15, // limit per page, default is 10
        $paginationOptions // options for paginator, optional
    );

    ...

Variants
--------

Of course filters can also be used without features like query builders or ``$fData->paginate(``

  .. code-block:: php

    $filterForm = $this->createForm(YourFilterFormType::class, null, $options); // $options is optional, details see below
    $fData = $ccFiltering->getFilterData($filterform);

    ...

    if ($fData->hasSortField()) {
        $sortField = $fData->getSortField('tbl.dflt');
        $sortDir = $fData->getSortDir();
        $query = ...
    }

    $query = ...
    $query->setParameters($filter->getAsParameters());

    $pagination = $fData->paginate(
        $query
        // default limits per page
    );


Options for ``createForm``
--------------------------

There are some special options for AbstractFilterType:

- ``'defaultSort' => array('cde.date', 'deg.title', 'desc')`` //default sorting order and direction (main sorting by cde.date)

  The options 'defaultSortFieldName' and 'defaultSortDirectionon can still be used on pagination() instead.

Special field types for filters
-------------------------------

currently none in this bundle.
