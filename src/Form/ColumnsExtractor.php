<?php

namespace CubeTools\CubeCommonBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

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
     * @param FormInterface|FormConfigInterface $formElement
     *
     * @return string label for column
     */
    public function getColumnLabel($formElement)
    {
        return static::getConfigOf($formElement)->getOptions()['label'];
    }

    /**
     * Method can be overwritten in order to make custom name for columns.
     *
     * @param FormInterface|FormConfigInterface $formElement
     *
     * @return string name for column
     */
    public function getColumnName($formElement)
    {
        return static::getConfigOf($formElement)->getName();
    }

    /**
     * Method can be overwritten in order to make custom style for columns.
     *
     * @param FormInterface|FormConfigInterface $formElement
     *
     * @return string style for column
     */
    public function getColumnStyle($formElement)
    {
        $formElementOptions = static::getConfigOf($formElement)->getOptions();

        return ($formElementOptions['attr']['style'] ?? '');
    }

    /**
     * Method for getting array with label of columns.
     *
     * @param FormInterface|FormBuilderInterface $form form object, from which elements would be taken
     *
     * @return array string[] labels for columns
     */
    public function getColumns($form)
    {
        $columns = array();

        foreach ($form->all() as $formElement) {
            $elementOptions = static::getConfigOf($formElement)->getOptions();
            if (get_class(static::getConfigOf($formElement)->getType()->getInnerType()) != HiddenType::class &&
                !(isset($elementOptions['attr']['data-isindexcolumn']) && !$elementOptions['attr']['data-isindexcolumn']) &&
                $this->validateColumn($formElement)
            ) {
                $columns[] = $this->getColumnLabel($formElement);
            }
        }

        return $columns;
    }

    /**
     * Method for getting array with name of columns, for which entities can be selected.
     *
     * @param FormInterface|FormBuilderInterface $form form object, from which elements would be taken
     *
     * @return array string[] names for columns
     */
    public function getEntitiesColumnsByName($form)
    {
        $columns = array();

        foreach ($form->all() as $formElement) {
            $elementOptions = static::getConfigOf($formElement)->getOptions();
            if (in_array(
                get_class(static::getConfigOf($formElement)->getType()->getInnerType()),
                array('Symfony\Bridge\Doctrine\Form\Type\EntityType', 'Tetranz\Select2EntityBundle\Form\Type\Select2EntityType')
                ) &&
                !(isset($elementOptions['attr']['data-isindexcolumn']) && !$elementOptions['attr']['data-isindexcolumn'])
            ) {
                $columns[] = $this->getColumnName($formElement);
            }
        }

        return $columns;
    }

    /**
     * Method adding custom fields labels to given array with column names.
     * Method counts number of custom fields (result available via getCustomFieldsNumberOfColumns method).
     *
     * @param FormInterface|FormBuilderInterface $form    form, from which custom fields are extracted
     * @param string[]                           $columns current array with column names (can be empty, then return array contains only custom fields)
     *
     * @return array array from input with custom fields attached
     */
    public function addCustomFieldsColumns($form, $columns = array())
    {
        $this->customFieldsNumberOfColumns = 0;

        foreach ($form as $formElement) {
            $elementOptions = static::getConfigOf($formElement)->getOptions();
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

    /**
     * Gets the config for form, formbuilder and similar classes.
     *
     * @param FormInterface|FormConfigInterface $form
     *
     * @return FormConfigInterface
     */
    public static function getConfigOf($form)
    {
        if (!$form instanceof FormConfigInterface) {
            $form = $form->getConfig();
        }

        return $form;
    }
}
