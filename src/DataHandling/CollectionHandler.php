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
    protected $listLinePrefix = self::LIST_LINE_PREFIX_DEFAULT;

    /**
     * @var string value of key for each row with information about columns stored (if not set, not taken into account while iteration)
     */
    protected $keyWithElements;

    /**
     * @var string value of key for each column of row with information about column value (if not set, not taken into account while iteration)
     */
    protected $keyWithElementValue;

    /**
     * @var object for processing raw elements (with html tags)
     */
    protected $rawElementProcessor;

    /**
     * @var string method name in raw element processor object which take raw value as input
     */
    protected $rawElementProcessorMethodName;

    /**
     * @var string name of flag, which tells, if element have to be processed
     */
    protected $rawElementFlag;

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
     * Method setting processor for raw elements.
     *
     * @param object $rawElementProcessor for processing raw elements (with html tags)
     *
     * @return $this
     */
    public function setRawElementProcessor($rawElementProcessor)
    {
        $this->rawElementProcessor = $rawElementProcessor;

        return $this;
    }

    /**
     * Method setting name of method in processor for raw elements.
     *
     * @param object $rawElementProcessorMethodName method name in raw element processor object which take raw value as input
     *
     * @return $this
     */
    public function setRawElementProcessorMethodName($rawElementProcessorMethodName)
    {
        $this->rawElementProcessorMethodName = $rawElementProcessorMethodName;

        return $this;
    }

    /**
     * Method setting name of raw elements flag.
     *
     * @param string $rawElementFlag name of flag, which tells, if element have to be processed
     *
     * @return $this
     */
    public function setRawElementFlag($rawElementFlag)
    {
        $this->rawElementFlag = $rawElementFlag;

        return $this;
    }

    /**
     * Method for processing column value (optionally uses raw element processor).
     *
     * @param string  $columnValue value of column
     * @param boolean $rawElement  flag telling, if element would be handled by rawElementProcessor (false by default)
     *
     * @return string|object
     */
    public function processValue($columnValue, $rawElement = false)
    {
        if ($rawElement) {
            $columnOutputValue = $this->rawElementProcessor->{$this->rawElementProcessorMethodName}((string) $columnValue);
        } else if (is_scalar($columnValue) || (is_object($columnValue) && method_exists($columnValue, '__toString'))) {
            $columnOutputValue = (string) $columnValue;
        } else {
            $columnOutputValue = $columnValue;
        }

        return $columnOutputValue;
    }

    /**
     * Method for extracting data from collections.
     *
     * @param mixed  $collection iterable object to be processed
     * @param string $method     method for getting string from each element
     * @param bool   $rawElement flag telling, if element would be handled by rawElementProcessor (false by default)
     *
     * @return string string with collection/field data
     */
    public function handleCollection($collection, $method = '__toString', $rawElement = false)
    {
        $outputArray = array();

        foreach ($collection as $element) {
            if (!is_object($element)) {
                $outputArray[] = sprintf('%s%s', $this->listLinePrefix, $this->processValue($element, $rawElement));
            } elseif (is_array($method)) {
                $oneElementArray = array();
                foreach ($method as $oneMethod) {
                    $oneElementArray[] = $element->{$oneMethod}();
                }
                $outputArray[] = $this->listLinePrefix.$this->processValue(implode(' ', $oneElementArray), $rawElement);
            } else {
                $outputArray[] = $this->listLinePrefix.$this->processValue($element->{$method}(), $rawElement);
            }
        }

        return implode(PHP_EOL, $outputArray);
    }

    /**
     * Method analyzing column value and extracting it's value.
     *
     * @param mixed $columnValue iterable element or convertible to string
     * @param bool  $rawElement  flag telling, if element would be handled by rawElementProcessor (false by default)
     *
     * @return string value of column to be displayed
     */
    public function getColumnValue($columnValue, $rawElement = false)
    {
        $columnOutputValue = '';

        if (is_iterable($columnValue)) {
            $columnOutputValue = $this->handleCollection($columnValue, '__toString', $rawElement);
        } else {
            $columnOutputValue = $this->processValue($columnValue, $rawElement);
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
                if (isset($this->keyWithElements)) {
                    foreach ($element[$this->keyWithElements] as $columnElement) {
                        if (isset($this->keyWithElementValue)) {
                            if (isset($columnElement[$this->rawElementFlag])) {
                                $outputRow[] = $this->getColumnValue($columnElement[$this->keyWithElementValue], $columnElement[$this->rawElementFlag]);
                            } else {
                                $outputRow[] = $this->getColumnValue($columnElement[$this->keyWithElementValue]);
                            }
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
