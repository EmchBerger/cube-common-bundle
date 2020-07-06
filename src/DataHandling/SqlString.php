<?php

namespace CubeTools\CubeCommonBundle\DataHandling;

class SqlString
{
    /**
     * @var string[] from glob to sql pattern
     */
    private static $replacements = [
        '*' => '%',
        '?' => '_',
    ];

    /**
     * Convertes a (glob) string to a string for sql like.
     *
     * @param string $submitted like typed from the user
     *
     * @return string for sql like
     */
    public static function toLikeString($submitted)
    {
        $pattern = self::toLikePatterns($submitted);
        if ('' === $pattern) {
            return $pattern; // without % at both sides
        } elseif ($submitted !== $pattern) {
            return $pattern;
        } else {
            // pre- and append %
            return '%'.$pattern.'%';
        }
    }

    /**
     * Convertes a string for sql like to a (glob) string to show to the user.
     *
     * @param string $forSql
     *
     * @return string to show to the user
     */
    public static function fromLikeString($forSql)
    {
        if (static::hasSqlPatternOnlyAtEnds($forSql)) {
            // has % at start and end only
            $forUser = substr($forSql, 1, -1);
        } else {
            $forUser = $forSql;
        }

        return self::fromLikePatterns($forUser);
    }

    protected static function toLikePatterns($original)
    {
        return strtr($original, self::$replacements);
    }

    protected static function fromLikePatterns($submitted)
    {
        return strtr($submitted, array_flip(self::$replacements));
    }

    protected static function hasSqlPatternOnlyAtEnds($forSql)
    {
        return '%' === substr($forSql, 0, 1) && // % at start
            false === strpos($forSql, '_') && // no _
            false !== ($pPos = strpos($forSql, '%', 1)) && // has a 2nd %
            strlen($forSql) - 1 === $pPos // % is at end
        ;
    }
}
