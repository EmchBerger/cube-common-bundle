<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

trait LogsFunctionsTrait
{
    /**
     * Calculates the attribute values, limitable by a condition.
     *
     * @param array                        $changes
     * @param \DateTimeInterface|int|false $stopAt  Stop at the given index, the given time, or never if false
     *
     * @return mixed[] attribute values as [ 'attrName' => $attrValue, ...] with $attrValue as string or array
     */
    public static function calculateAttributesAt(array $changes, $stopAt)
    {
        $stopFn = self::getStopAtFunction($stopAt);

        $attributesAt = array();
        $i = 0;
        foreach (array_reverse($changes) as $change) {
            if ($stopFn($change, $i)) {
                break;
            }
            // create value by following all changes
            foreach ($change['changes'] as $attrName => $attrChange) {
                if (is_array($attrChange)) {
                    $prevValue = isset($attributesAt[$attrName]) ? $attributesAt[$attrName] : array();
                    if (isset($attrChange[static::KEY_ADD])) {
                        $attributesAt[$attrName] = array_merge($prevValue, $attrChange[static::KEY_ADD]);
                    }
                    if (isset($attrChange[static::KEY_REMOVE])) {
                        $prevValue = array_diff($prevValue, $attrChange[static::KEY_REMOVE]);
                    }
                    // ignore KEY_UNCHANGED since this is only set when add+remove was done in this change
                } else {
                    $attributesAt[$attrName] = $attrChange;
                }
            }
            ++$i;
        }

        return $attributesAt;
    }

    /**
     * Filters out the changes of one property, suitable for LogsInterface::getVersionsOfProperty().
     *
     * @param mixed[] $allVersionChanges data formatted as returned by LogsInterface::getAllVersionsDiff()
     * @param string  $columnName        name of column to get property for
     *
     * @return mixed[]
     */
    protected function filterLogForOneProperty($allVersionChanges, $columnName)
    {
        foreach ($allVersionChanges as $versionKey => $value) {
            $changes = $value['changes'];
            if (isset($changes[$columnName])) {
                $value['changes'] = $changes[$columnName];
                yield $versionKey => $value;
            }
        }
    }

    /**
     * Get stopAt function for calculateAttributesAt().
     *
     * @param mixed $stopAt {@see calculateAttributesAt()}
     *
     * @return \Closure
     *
     * @throws \InvalidArgumentException
     */
    private static function getStopAtFunction($stopAt)
    {
        if ($stopAt instanceof \DateTimeInterface) {
            $stopFn = function ($oneChange) use ($stopAt) {
                return $oneChange['savedAt'] > $stopAt;
            };
        } elseif (false === $stopAt) {
            $stopFn = function () {
                return false;
            };
        } elseif (is_int($stopAt)) {
            $stopFn = function ($oneChange, $loopIndex) use ($stopAt) {
                return $loopIndex > $stopAt;
            };
        } else {
            $type = is_object($stopAt) ? get_class($stopAt) : gettype($stopAt);
            throw new \InvalidArgumentException('$stopAt must be int, DateTime or false. (is '.$type.')');
        }

        return $stopFn;
    }
}
