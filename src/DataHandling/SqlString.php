<?php

namespace CubeTools\CubeCommonBundle\DataHandling;

class SqlString
{
    /**
     * Convertes a (glob) string to a string for sql like.
     *
     * @param string $submitted like typed from the user
     *
     * @return string for sql like
     */
    public static function toLikeString($submitted)
    {
        $patter = $submitted
        if ('' === $pattern) {
            return $pattern; // without % at both sides
        } elseif ('%' === substr($pattern, 0, 1) || '%' === substr($pattern, -1)) {
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
        if ('%' === substr($forSql, 0, 1) && '%' === substr($forSql, -1)) {
            $forUser = substr($forSql, 1, -1);
        } else {
            $forUser = $forSql;
        }

        return $forUser;
    }
}
