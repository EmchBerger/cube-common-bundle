<?php

namespace CubeTools\CubeCommonBundle\Form\EventListener;

use CubeTools\CubeCommonBundle\Form\ColumnsExtractor;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AnyNoneFilterListener
{
    /**
     * Name of column, where subsequent elements are column names, for which any or none records can be selected (in form stored as json string)
     */
    const KEY_ANY_NONE_COLUMNS = 'anyNoneColumns';

    /**
     * Name of column with information about fields, where any or none records can be selected (in form stored as json string)
     * Example structure:
     * self::KEY_ANY_NONE_COLUMS => array(
     *     self::KEY_ANY_NONE_NOT_DEFINED => array('column1'),
     *     self::KEY_ANY_COLUMNS => array(),
     *     self::KEY_NONE_COLUMNS => array('column2')
     * );
     * )
     */
    const KEY_ANY_NONE_SELECTED_COLUMNS = 'anyNoneSelectedColumns';

    /**
     * Subkey for filter defined in KEY_ANY_NONE_SELECTED_COLUMNS specifying columns, for which any or none filter is not set (although it can be)
     */
    const KEY_ANY_NONE_NOT_DEFINED = 'not_defined';

    /**
     * Subkey for filter defined in KEY_ANY_NONE_SELECTED_COLUMNS specifying columns, where all records where any value is set are taken into account (used also as select value)
     */
    const KEY_ANY_COLUMNS = 'any';

    /**
     * Subkey for filter defined in KEY_ANY_NONE_SELECTED_COLUMNS specifying columns, where all records where no value is set are taken into account (used also as select value)
     */
    const KEY_NONE_COLUMNS = 'none';

    /**
     * @var \CubeTools\CubeCommonBundle\Form\ColumnsExtractor
     */
    protected $columnsExtractor;

    /**
     * Elements (listing entities) containing AnyNoneSelection
     *
     * @var string[]
     */
    private $entityElementsWithAnyNone = array();

    public function __construct(ColumnsExtractor $columnsExtractor)
    {
        $this->columnsExtractor = $columnsExtractor;
    }

    /**
     * Adds data for AnyNoneFilter to the form (events, ...).
     *
     * @param \CubeTools\CubeCommonBundle\Form\EventListener\FormBuilderInterface $builder
     */
    public function addToBuilder(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'addAnyNoneColumns'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'processAnyNoneColumns'));
    }

    /**
     * Method to be called as PRE_SET_DATA listener for form.
     *
     * Add fields to the form to submit data to frontend.
     *
     * @param \Symfony\Component\Form\FormEvent $event
     */
    public function addAnyNoneColumns(FormEvent $event)
    {
        $this->entityElementsWithAnyNone = $this->columnsExtractor->getEntitiesColumnsByName($event->getForm());
        // field transfers data to frontend (javascript) to append selecions
        $event->getForm()->add(self::KEY_ANY_NONE_COLUMNS, HiddenType::class, array(
            'data' => json_encode($this->entityElementsWithAnyNone),
        ));
        // field transfers current selections to frontend (javascript) and to ..\Filter\FilterQueryCondition
        $event->getForm()->add(self::KEY_ANY_NONE_SELECTED_COLUMNS, HiddenType::class);
    }

    /**
     * Method to be called as PRE_SUBMIT listener for form.
     *
     * Method analyses form input and process fields, where any or none record option can be set.
     * At the end, method modify form input (avoid inproper values when matching with entities).
     *
     * @param \Symfony\Component\Form\FormEvent $event
     */
    public function processAnyNoneColumns(FormEvent $event)
    {
        $formData = $event->getData();

        if (empty($formData[self::KEY_ANY_NONE_SELECTED_COLUMNS])) { // only run once
            $anyNoneColumns = $this->entityElementsWithAnyNone;
            $newAnyNoneColumns = array(self::KEY_ANY_NONE_NOT_DEFINED => array(), self::KEY_ANY_COLUMNS => array(), self::KEY_NONE_COLUMNS => array());
            foreach ($anyNoneColumns as $columnName) {
                if (isset($formData[$columnName])) {
                    $isArray = is_array($formData[$columnName]);
                    $columnValue = ($isArray ? current($formData[$columnName]) : $formData[$columnName]);
                    if (in_array($columnValue, array(self::KEY_ANY_COLUMNS, self::KEY_NONE_COLUMNS))) {
                        $newAnyNoneColumns[$columnValue][] = $columnName;
                        $formData[$columnName] = ($isArray ? array() : '');
                    } else {
                        $newAnyNoneColumns[self::KEY_ANY_NONE_NOT_DEFINED][] = $columnName;
                    }
                } else {
                    $newAnyNoneColumns[self::KEY_ANY_NONE_NOT_DEFINED][] = $columnName;
                }
            }
            // saved for frontend and for applying filter
            $formData[self::KEY_ANY_NONE_SELECTED_COLUMNS] = json_encode($newAnyNoneColumns);
            $event->setData($formData);
        }
    }
}
