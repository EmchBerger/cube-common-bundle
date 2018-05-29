<?php

namespace CubeTools\CubeCommonBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class for getting column properties from filter form (possibility to add custom validation in extended class)
 */
class ColumnsExtractor
{
    /**
     * @var int number of columns for custom fields (to know how many empty columns add for records, where custom fields are not present)
     */
    protected $customFieldsNumberOfColumns = 0;

    /**
     * Method can be overwritten in order to make custom validation for columns.
     *
     * @param \Symfony\Component\Form\Form $formElement
     *
     * @return bool true if column should be displayed
     */
    public function validateColumn($formElement)
    {
        return true;
    }

    /**
     * Method can be overwritten in order to make custom label for columns.
     *
     * @param \Symfony\Component\Form\Form $formElement
     *
     * @return string label for column
     */
    public function getColumnLabel($formElement)
    {
        return $formElement->getConfig()->getOptions()['label'];
    }

    /**
     * Method can be overwritten in order to make custom name for columns.
     *
     * @param \Symfony\Component\Form\Form $formElement
     *
     * @return string name for column
     */
    public function getColumnName($formElement)
    {
        return $formElement->getConfig()->getName();
    }

    /**
     * Method can be overwritten in order to make custom style for columns.
     *
     * @param \Symfony\Component\Form\Form $formElement
     *
     * @return string style for column
     */
    public function getColumnStyle($formElement)
    {
        $formElementOptions = $formElement->getConfig()->getOptions();

        return ($formElementOptions['attr']['style'] ?? '');
    }

    /**
     * Method for getting array with name of columns
     *
     * @param \Symfony\Component\Form\AbstractType $form form object, from which elements would be taken
     *
     * @return array string[]
     */
    public function getColumns($form)
    {
        $columns = array();

        foreach ($form->all() as $formElement) {
            $elementOptions = $formElement->getConfig()->getOptions();
            if (get_class($formElement->getConfig()->getType()->getInnerType()) != HiddenType::class &&
                !(isset($elementOptions['attr']['data-isindexcolumn']) && !$elementOptions['attr']['data-isindexcolumn']) &&
                $this->validateColumn($formElement)
            ) {
                $columns[] = $this->getColumnLabel($formElement);
            }
        }

        return $columns;
    }

    /**
     * Method adding custom fields labels to given array with column names.
     * Method counts number of custom fields (result available via getCustomFieldsNumberOfColumns method).
     *
     * @param \Symfony\Component\Form\AbstractType $form    form, from which custom fields are extracted
     * @param array                                $columns current array with column names (can be empty, then return array contains only custom fields)
     *
     * @return array array from input with custom fields attached
     */
    public function addCustomFieldsColumns($form, $columns = array())
    {
        $this->customFieldsNumberOfColumns = 0;

        foreach ($form as $formElement) {
            $elementOptions = $formElement->getConfig()->getOptions();
            if (isset($elementOptions['translation_domain']) && $elementOptions['translation_domain'] == 'custom_fields') {
                $columns[] = $this->getColumnLabel($formElement);
                $this->customFieldsNumberOfColumns++;
            }
        }

        return $columns;
    }

    /**
     * Getter for number of custom fields columns (to be called after addCustomFieldsColumns method).
     *
     * @return int number of custom fields columns
     */
    public function getCustomFieldsNumberOfColumns()
    {
        return $this->customFieldsNumberOfColumns;
    }
}
