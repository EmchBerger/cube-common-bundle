<?php

namespace CubeTools\CubeCommonBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Method for getting column names from filter form (possibility to add custom validation in extended class)
 */
class ColumnsExtractor
{
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
}
