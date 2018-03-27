<?php

namespace CubeTools\CubeCommonBundle\DataHandling;

/**
 * Class for handling various types of data (with or without collections) and converting it to one standard.
 */
class CollectionHandler
{
    /**
     * default beginning of item line (bullet)
     */
    const LIST_LINE_PREFIX_DEFAULT = '• ';

    /**
     * @var string beginning of item line
     */
    protected $listLinePrefix = self::LINE_PREFIX_DEFAULT;

    /**
     * @var string value of key for each row with information about columns stored (if not set, not taken into account while iteration)
     */
    protected $keyWithElements;

    /**
     * @var string value of key for each column of row with information about column value (if not set, not taken into account while iteration)
     */
    protected $keyWithElementValue;

    /**
     * Method for setting custom beginning of item line.
     *
     * @param string $listLinePrefix beginning of item line
     *
     * @return $this
     */
    public function setLinePrefix($listLinePrefix)
    {
        $this->listLinePrefix = $listLinePrefix;

        return $this;
    }

    /**
     * Method setting value of keyWithElements (by default not set).
     *
     * @param string $keyWithElements value of key for each row with information about columns stored
     *
     * @return $this
     */
    public function setKeyWithElements($keyWithElements)
    {
        $this->keyWithElements = $keyWithElements;

        return $this;
    }

    /**
     * Method setting value of keyWithElementValue (by default not set).
     *
     * @param string $keyWithElementValue value of key for each column of row with information about column value
     *
     * @return $this
     */
    public function setKeyWithElementValue($keyWithElementValue)
    {
        $this->keyWithElementValue = $keyWithElementValue;

        return $this;
    }

    /**
     * Method for extracting data from collections.
     *
     * @param mixed  $collection iterable object to be processed
     * @param string $method     method for getting string from each element
     *
     * @return string string with collection/field data
     */
    public function handleCollection($collection, $method = '__toString')
    {
        $outputArray = array();

        foreach ($collection as $element) {
            if (!is_object($element)) {
                $outputArray[] = sprintf('%s%s', $this->listLinePrefix, strval($element));
            } elseif (is_array($method)) {
                $oneElementArray = array();
                foreach ($method as $oneMethod) {
                    $oneElementArray[] = $element->{$oneMethod}();
                }
                $outputArray[] = $this->listLinePrefix.implode(' ', $oneElementArray);
            } else {
                $outputArray[] = $this->listLinePrefix.$element->{$method}();
            }
        }

        return implode(PHP_EOL, $outputArray);
    }

    /**
     * Method analyzing column value and extracting it's value.
     *
     * @param mixed $columnValue iterable element or convertible to string
     *
     * @return string value of column to be displayed
     */
    public function getColumnValue($columnValue)
    {
        $columnOutputValue = '';

        if (is_iterable($columnValue)) {
            $columnOutputValue = $this->handleCollection($columnValue);
        } else {
            $columnOutputValue = (string) $columnValue;
        }

        return $columnOutputValue;
    }

    /**
     * Method handling different types of data (grouped into rows) and converting it to one standard.
     * Behavior of method depends on values provided to $this->setKeyWithElements and
     * $this->setKeyWithElementValue methods.
     *
     * @param array $inputData data to be handled
     *
     * @return array array[][] (first level is row, second - column values for this row)
     */
    public function iterateData($inputData)
    {
        $outputArray = array();

        foreach ($inputData as $element) {
            $outputRow = array();

            if (is_array($element)) {
                if (isset($this->keyWithElements) && isset($this->keyWithElements)) {
                    foreach ($element[$this->keyWithElements] as $columnElement) {
                        if (isset($this->keyWithElementValue)) {
                            $outputRow[] = $this->getColumnValue($columnElement[$this->keyWithElementValue]);
                        } else {
                            $outputRow[] = $this->getColumnValue($columnElement);
                        }
                    }
                }
            } else {
                $outputRow[] = $this->getColumnValue($element);
            }
            $outputArray[] = $outputRow;
        }

        return $outputArray;
    }
}
