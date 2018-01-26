<?php

namespace CubeTools\CubeCommonBundle\Filter;

use Knp\Component\Pager\PaginatorInterface;

/**
 * FilterData contains filer values, sorting and pagination functionality.
 */
class FilterData
{
    /**
     * @var FilterQueryCondition
     */
    private $filter;

    /**
     * @var int
     */
    private $page;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string|null
     */
    private $redirect;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * Create filter data.
     *
     * @param array              $fData     filter data
     * @param PaginatorInterface $paginator paginator (optional)
     */
    public function __construct(array $fData, PaginatorInterface $paginator = null)
    {
        $this->redirect = $fData['redirect'];
        if (!$this->redirect) {
            $this->filter = $fData['filter'];
            $this->page = $fData['page'];
            $this->options = $fData['options'];
            $this->paginator = $paginator;
        }
    }

    /**
     * Get filter content.
     *
     * @return FilterQueryCondition
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Get options for paginator.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get page to display.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get url to redirect to, null if no redirect.
     *
     * @return string|null
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Checks if the sort field is defined.
     *
     * @return bool
     */
    public function hasSortField()
    {
        $options = $this->getOptions();

        return !empty($options['defaultSortFieldName']);
    }

    /**
     * Gets the sort field.
     *
     * @param string $default default sort field
     *
     * @return string sort field
     */
    public function getSortField($default)
    {
        $options = $this->getOptions();

        if (empty($options['defaultSortFieldName'])) {
            $field = $default;
        } elseif (is_array($options['defaultSortFieldName'])) {
            $field = $options['defaultSortFieldName'][0];
        } else {
            $field = $options['defaultSortFieldName'];
        }
        SortingHelper::validateSortField($field);

        return $field;
    }

    /**
     * Gets the sort direction.
     *
     * @param string $default
     *
     * @return string ASC or DESC
     */
    public function getSortDir($default = 'asc')
    {
        $options = $this->getOptions();

        $dir = isset($options['defaultSortDirection']) ? $options['defaultSortDirection'] : $default;

        return SortingHelper::getValidSortDir($dir);
    }

    /**
     * Merges the paginator options with the one from this object.
     *
     * @param array $options paginator options
     *
     * @return array merged options
     */
    public function mergeOptions(array $options)
    {
        if (isset($options['defaultSortFieldName']) || isset($options['defaultSortDirection'])) {
            $sortFieldNames = array('defaultSortFieldName' => null, 'defaultSortDirection' => null);
            $defaultSortOptions = array_intersect_key($options, $sortFieldNames);
            $options = array_diff_key($options, $sortFieldNames);
        } else {
            $defaultSortOptions = array();
        }

        // no sql injection prevention because only used in paginator
        return array_merge($defaultSortOptions, $this->getOptions(), $options); // highest priority rightmost
    }

    /**
     * Paginates anything into Pagination object. @see PaginatorInterface::paginate .
     *
     * The page and sorting are set automatically.
     *
     * @param mixed $query   anything what needs to be paginated
     * @param int   $limit   number of items per page, defaults to 10
     * @param array $options less used options, @see PaginatorInterface::paginate
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     *
     * @throws \LogicException
     */
    public function paginate($query, $limit = null, array $options = array())
    {
        if (null === $this->paginator) {
            throw new \LogicException('paginator has not been set in this class');
        }
        if (null === $limit) {
            $limit = 10;
        }

        return $this->paginator->paginate($query, $this->getPage(), $limit, $this->mergeOptions($options));
    }
}
