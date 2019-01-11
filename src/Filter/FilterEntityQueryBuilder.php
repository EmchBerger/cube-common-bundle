<?php

namespace CubeTools\CubeCommonBundle\Filter;

/**
 * Object to be set for FilterQueryCondition->setQuerybuilder to listen for sql query and make conditions only on provided entity.
 * The aim is to avoid sql queries while checking one entity against filters. Object emulates mostly used methods of QueryBuilder.
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
     * Phrase used in expression for checked value (have to be looked for before "IS NULL")
     */
    const EXPRESSION_ZERO_OR_NULL = ' = 0 OR ';

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
    protected $dbExpressions = array(self::EXPRESSION_EQUAL, self::EXPRESSION_IN, self::EXPRESSION_LIKE, self::EXPRESSION_NOT_NULL, self::EXPRESSION_ZERO_OR_NULL, self::EXPRESSION_NULL, self::EXPRESSION_NOT_ZERO, self::EXPRESSION_MORE_OR_EQUAL, self::EXPRESSION_DATE_RANGE_TO);

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
     * @var array root aliases
     */
    protected $rootAliases;

    /**
     * @var \Doctrine\ORM\Query\Expr expression builder, lazy initialisation
    */
    protected $expressionBuilder;

    /**
     * @var array optional provider of data instead of calling get method (helpful when multiple joins are used).
     *
     * Key of array is called getter.
     *
     * array('calledGetter' => array('getter1' => array('getter2', 'getter3'))) - getter1 return many elements; for each of them getter2 and getter3 returns respectively one element
     *
     * array('calledGetter' => array('getter1' => array('getter2' => array('getter3'))) - getter1 return many elements; getter2 also, whereas getter3 returns one element
     */
    protected $getterProvider = array();

    /**
     * @var array data collected by getter provider
     */
    protected $getterProviderData = array();

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
     * Method for adding getter provider.
     *
     * @param string $getterName          called getter
     * @param array  $getterConfiguration for example:
     *
     * array('calledGetter' => array('getter2', 'getter3')) - getter1 return many elements; for each of them getter2 and getter3 returns respectively one element
     *
     * array('calledGetter' => array('getter2' => array('getter3')) - getter1 return many elements; getter2 also, whereas getter3 returns one element
     *
     * NOT POSSIBLE to have manyToMany relationship after oneToOne
     *
     * @return $this
     */
    public function addGetterProvider($getterName, $getterConfiguration)
    {
        $this->getterProvider[$getterName] = $getterConfiguration;
        $this->getterProviderData[$getterName] = array();

        return $this;
    }

    /**
     * Method for obtaining values, which normally are returned by database.
     *
     * @param string $getterName name of getter called on entity (directly or through getterProvider)
     *
     * @return mixed data normally returned by database
     */
    public function getValueFromDb($getterName)
    {
        if (isset($this->getterProvider[$getterName])) {
            $this->setGetterProviderData($getterName, $this->getterProvider[$getterName], $this->analysedEntity);

            if (isset($this->getterProviderData[$getterName][0]) && stripos(substr(get_class($this->getterProviderData[$getterName][0]), 0, 5), 'Mock_') !== false) {
                // phpunit tests execution
                $valueFromDb = $this->getterProviderData[$getterName][0];
            } else {
                $valueFromDb = new \Doctrine\Common\Collections\ArrayCollection($this->getterProviderData[$getterName]);
            }
        } else {
            $valueFromDb = $this->analysedEntity->{$getterName}();
        }

        return $valueFromDb;
    }

    /**
     * Setter for emulating method getRootAliases.
     *
     * @param array|string $rootAliases array of aliases or one alias as string
     *
     * @return $this
     */
    public function setRootAliases($rootAliases)
    {
        if (is_array($rootAliases)) {
            $this->rootAliases = $rootAliases;
        } else {
            $this->rootAliases = array($rootAliases);
        }

        return $this;
    }

    /**
     * Method doing the same comparison as this done in sql.
     *
     * @param string $expression
     * @param mixed  $valueFromDb
     * @param mixed  $valueExpected by default null, due to the fact, that for some expression is not needed (for example checking if null)
     *
     * @return bool true if condition is passed
     */
    public function evaluateExpression($expression, $valueFromDb, $valueExpected = null)
    {
        $expressionResult = true;
        switch ($expression) {
            case self::EXPRESSION_EQUAL:
                $expressionResult = ($valueFromDb == $valueExpected);
                break;
            case self::EXPRESSION_LIKE:
                if ($valueExpected[0] === '%') {
                    $valueExpected = substr($valueExpected, 1);
                }
                if ($valueExpected[strlen($valueExpected) - 1] === '%') {
                    $valueExpected = substr($valueExpected, 0, -1);
                }

                $expressionResult = (stripos($valueFromDb, $valueExpected) !== false);
                break;
            case self::EXPRESSION_IN:
                $expressionResult = false;
                if (is_iterable($valueExpected)) {
                    foreach ($valueExpected as $elementExpected) {
                        if (is_object($valueFromDb) && method_exists($valueFromDb, 'contains')) {
                            // ArrayCollection handling
                            if ($valueFromDb->contains($elementExpected)) {
                                // condition fullfiled if at least one element is present
                                $expressionResult = true;
                                break;
                            }
                        } else if (is_object($valueFromDb) && method_exists($valueFromDb, 'getId')) {
                            // Single object by ManyToOne or OneToOne relationships
                            if ($valueFromDb->getId() === $elementExpected->getId()) {
                                    $expressionResult = true;
                                    break;
                            }
                        }
                    }
                } else {
                    if (is_object($valueFromDb) && method_exists($valueFromDb, 'contains')) {
                        // ArrayCollection handling when it is confronted against one element
                        if ($valueFromDb->contains($valueExpected)) {
                            // condition fullfiled if at least one element is present
                            $expressionResult = true;
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
                $expressionResult = ($valueFromDb >= $valueExpected);
                break;
            case self::EXPRESSION_ZERO_OR_NULL:
                $expressionResult = (intval($valueFromDb) === 0 || is_null($valueFromDb));
                break;
            case self::EXPRESSION_DATE_RANGE_TO:
                $valueExpected = clone $valueExpected;
                $parameterValue = $valueExpected->modify('+1 day');
                $expressionResult = ($valueFromDb < $parameterValue);
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
                if (stripos($columnNameArray[1], ' ') !== false) {
                    // remove not needed part of column name for some conditions (like 0 and null)
                    $columnNameArray[1] = explode(' ', $columnNameArray[1])[0];
                }
                if (isset($this->aliases[$columnNameArray[0]])) {
                    // if alias was registered by leftJoin method, then other getter is used
                    $getterName = 'get'.ucfirst($this->aliases[$columnNameArray[0]]);
                } else {
                    $getterName = 'get'.ucfirst($columnNameArray[1]);
                }
                if ($expression === self::EXPRESSION_DATE_RANGE_TO) {
                    preg_match(self::REGULAR_EXPRESSION_TILL_COMMA, $conditionArray[1], $matches);
                    $parameterValue = $matches[0];
                } else if (in_array($expression, array(self::EXPRESSION_NOT_NULL, self::EXPRESSION_NULL, self::EXPRESSION_NOT_ZERO, self::EXPRESSION_ZERO_OR_NULL))) {
                    $parameterValue = null;
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
                    $this->getValueFromDb($getterName),
                    $parameterValue
                )) {
                    $this->conditionsFulfilled = false;
                }

                break;
            }
        }
    }

    /**
     * Method reseting object for test purposes.
     *
     * @return $this
     */
    public function resetObject()
    {
        $this->conditions = array();
        $this->aliases = array();
        $this->parameters = array();
        $this->conditionsFulfilled = true;
        $this->getterProvider = array();
        $this->getterProviderData = array();

        return $this;
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

        return $this;
    }

    /**
     * Normally merge with array_keys($this->joinRootAliases), here only output of getRootAliases.
     *
     * @return array
     */
    public function getAllAliases()
    {
        return $this->getRootAliases();
    }

    public function getRootAliases()
    {
        return $this->rootAliases;
    }

    public function getRootEntities()
    {
        return array(\Doctrine\Common\Util\ClassUtils::getClass($this->analysedEntity));
    }

    public function expr()
    {
        if (!isset($this->expressionBuilder)) {
            $this->expressionBuilder = new \Doctrine\ORM\Query\Expr();
        }

        return $this->expressionBuilder;
    }

    /**
     * Method for setting data for getter provider (can be subject of recurrency).
     *
     * @param string $firstGetterName     name of first getter (where data are stored)
     * @param array  $getterConfiguration configuration of getter
     * @param object $currentEntity       entity, on which operations are made
     *
     * @return $this
     */
    protected function setGetterProviderData($firstGetterName, $getterConfiguration, $currentEntity)
    {
        reset($getterConfiguration);
        $methodIndex = key($getterConfiguration);

        if (is_array($getterConfiguration[$methodIndex])) {
            // many elements can be returned
            $methodName = $methodIndex;
            foreach ($currentEntity->{$methodName}() as $newEntity) {
                $this->setGetterProviderData($firstGetterName, $getterConfiguration[$methodName], $newEntity);
            }
        } else {
            reset($getterConfiguration);
            $getterConfigurationLength = count($getterConfiguration);
            $arrayIterator = 0;

            foreach ($getterConfiguration as $methodName) {
                if ($arrayIterator === ($getterConfigurationLength - 1)) {
                    $this->getterProviderData[$firstGetterName][] = $currentEntity->{$methodName}();
                } else {
                    $currentEntity = $currentEntity->{$methodName}();
                }

                $arrayIterator++;
            }
        }

        return $this;
    }
}
