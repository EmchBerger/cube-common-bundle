<?php

namespace CubeTools\CubeCommonBundle\Filter;

/**
 * Object to be set for FilterQueryCondition->setQuerybuilder to listen for sql query and make conditions only on provided entity.
 * The aim is to avoid sql queries while checking one entity against filters.
 */
class FilterEntityQueryBuilder
{
    /**
     * Phrase used in equal db expressions
     */
    const EXPRESSION_EQUAL = ' = :';

    /**
     * Phrase used in like db expressions
     */
    const EXPRESSION_LIKE = ' LIKE :';

    /**
     * Phrase used in 'in' db expressions
     */
    const EXPRESSION_IN = ' IN (:';

    /**
     * Phrase used in not null expressions
     */
    const EXPRESSION_NOT_NULL = ' IS NOT NULL';

    /**
     * Phrase used in expression looking for null value
     */
    const EXPRESSION_NULL = ' IS NULL';

    /**
     * Phrase used in expression where element is not zero
     */
    const EXPRESSION_NOT_ZERO = ' <> 0';

    /**
     * Phrase used in expression for getting value with minimal value
     */
    const EXPRESSION_MORE_OR_EQUAL = ' >= :';

    /**
     * Phrase used in expression for checked value
     */
    const EXPRESSION_ZERO_OR_NULL = ' = 0 OR ';

    /**
     * Phrase used in expression for date range (to)
     */
    const EXPRESSION_DATE_RANGE_TO = ' < DATE_ADD(:';

    /**
     * Perl regular expression to get string till first comma.
     */
    const REGULAR_EXPRESSION_TILL_COMMA = '/[^,]*/';

    /**
     * @var array list of db expressions used to process sql query
     */
    protected $dbExpressions = array(self::EXPRESSION_EQUAL, self::EXPRESSION_IN, self::EXPRESSION_LIKE, self::EXPRESSION_NOT_NULL, self::EXPRESSION_NULL, self::EXPRESSION_NOT_ZERO, self::EXPRESSION_MORE_OR_EQUAL, self::EXPRESSION_ZERO_OR_NULL, self::EXPRESSION_DATE_RANGE_TO);

    /**
     * @var object analysed object
     */
    protected $analysedEntity;

    /**
     * @var array string[]
     */
    protected $conditions = array();

    /**
     * @var bool flag set to true, when all conditions for entity are met
     */
    protected $conditionsFulfilled = true;

    /**
     * @var array key is parameter name, value - parameter value
     */
    protected $parameters = array();

    /**
     * @var array key is alias name - value is entity variable for which it stays for
     */
    protected $aliases = array();

    /**
     * Method setting entity, for each filtering would be made.
     *
     * @param object $entity
     *
     * @return $this
     */
    public function setAnalysedEntity($entity)
    {
        $this->analysedEntity = $entity;

        return $this;
    }

    public function setParameter($parameterName, $parameterValue, $type = null)
    {
        $this->parameters[$parameterName] = $parameterValue;
    }

    /**
     * Method doing the same comparison as this done in sql.
     *
     * @param string $expression
     * @param mixed  $valueFromDb
     * @param mixed  $valueExpected
     *
     * @return bool true if condition is passed
     */
    public function evaluateExpression($expression, $valueFromDb, $valueExpected)
    {
        $expressionResult = true;
        switch ($expression) {
            case self::EXPRESSION_EQUAL:
                $expressionResult = ($valueFromDb == $valueExpected);
                break;
            case self::EXPRESSION_LIKE:
                // TODO: check if % is in at the beginning and at the end
                $parameterValue = substr($valueExpected, 1, -1); // remove % at the beginning and at the end
                $expressionResult = (stripos($valueFromDb, $parameterValue) !== false);
                break;
            case self::EXPRESSION_IN:
                $expressionResult = false;
                foreach ($valueExpected as $elementExpected) {
                    if (is_object($valueFromDb) && method_exists($valueFromDb, 'contains')) {
                        // ArrayCollection handling
                        if ($valueFromDb->contains($elementExpected)) {
                            // condition fullfiled if at least one element is present
                            $expressionResult = true;
                            break;
                        }
                    }
                }
                break;
            case self::EXPRESSION_NOT_NULL:
                $expressionResult = !is_null($valueFromDb);
                break;
            case self::EXPRESSION_NULL:
                $expressionResult = is_null($valueFromDb);
                break;
            case self::EXPRESSION_NOT_ZERO:
                $expressionResult = (intval($valueFromDb) <> 0);
                break;
            case self::EXPRESSION_MORE_OR_EQUAL:
                // mostly DateTime object comparisons:
                $expressionResult = ($valueExpected >= $valueFromDb);
                break;
            case self::EXPRESSION_ZERO_OR_NULL:
                $expressionResult = (intval($valueFromDb) === 0 || is_null($valueFromDb));
                break;
            case self::EXPRESSION_DATE_RANGE_TO:
                $parameterValue = $valueExpected->modify('+1 day');
                $expressionResult = ($parameterValue < $valueFromDb);
                break;
        }

        return $expressionResult;
    }

    /**
     * Method executing single condition.
     *
     * @param string $condition sql condition before binding
     */
    public function executeCondition($condition)
    {
        foreach ($this->dbExpressions as $expression) {
            if (stripos($condition, $expression) !== false) {
                $conditionArray = explode($expression, $condition);
                // dividing something like 's.status':
                $columnNameArray = explode('.', $conditionArray[0]);
                if (isset($this->aliases[$columnNameArray[0]])) {
                    // if alias was registered by leftJoin method, then other getter is used
                    $getterName = 'get'.ucfirst($this->aliases[$columnNameArray[0]]);
                } else {
                    $getterName = 'get'.ucfirst($columnNameArray[1]);
                }
                if ($expression === self::EXPRESSION_DATE_RANGE_TO) {
                    preg_match(self::REGULAR_EXPRESSION_TILL_COMMA, $conditionArray[1], $matches);
                    $parameterValue = $matches[0];
                } else {
                    // remove of possible bracket at the end of parameter:
                    $parameterValue = $this->parameters[str_replace(')', '', $conditionArray[1])];
                }
                if (!method_exists($this->analysedEntity, $getterName)) {
                    // getter not exists - try to make plural version
                    $getterName .= 's';
                }
                if (!$this->evaluateExpression(
                    $expression,
                    $this->analysedEntity->{$getterName}(),
                    $parameterValue
                ))
                {
                    $this->conditionsFulfilled = false;
                }

                break;
            }
        }
    }

    /**
     * Should return query, but here used only as a way to for get 'getResult'.
     *
     * @return $this
     */
    public function getQuery()
    {
        foreach ($this->conditions as $condition) {
            $this->executeCondition($condition);
        }

        return $this;
    }

    /**
     * @return array analysed entity or empty array when condition is not filled
     */
    public function getResult()
    {
        if ($this->conditionsFulfilled) {
            $outputResult = array($this->analysedEntity);
        } else {
            $outputResult = array();
        }

        return $outputResult;
    }

    public function andWhere()
    {
        $args  = func_get_args();
        $expression = $args[0];
        $this->conditions[] = $expression;

        return $this;
    }

    public function andWhereIn()
    {
        $args  = func_get_args();
        $expression = $args[0];
        $this->conditions[] = $expression;

        return $this;
    }

    public function leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
    {
        $joinArray = explode('.', $join);
        $this->aliases[$alias] = $joinArray[1];
    }
}
