<?php

namespace CubeTools\CubeCommonBundle\DataHandling;

class StringHelper
{
    public static function contains($needle, $haystack, $insensitive = false)
    {
        if ($insensitive) {
            $haystack = mb_strtolower($haystack, 'UTF-8');
            $needle = mb_strtolower($needle, 'UTF-8');
        }

        return false !== strpos($haystack, $needle);
    }

    public static function startsWith($haystack, $needle, $insensitive = false)
    {
        if ($insensitive) {
            $haystack = mb_strtolower($haystack, 'UTF-8');
            $needle = mb_strtolower($needle, 'UTF-8');
        }

        return '' === $needle || substr($haystack, 0, strlen($needle)) === $needle;
    }

    public static function endsWith($haystack, $needle, $insensitive = false)
    {
        if ($insensitive) {
            $haystack = mb_strtolower($haystack, 'UTF-8');
            $needle = mb_strtolower($needle, 'UTF-8');
        }

        return '' === $needle || substr($haystack, strlen($haystack) - strlen($needle)) === $needle;
    }

    /**
     * Removes surrounding from text if it exists.
     *
     * Known shortcoming: "<p>a</p><p>b</p>" will be converted to "a</p><p>b"
     *
     * @param string $text  to remove surrounding from
     * @param string $start to remove (like <p>)
     * @param string $end   to remove (like </p>)
     *
     * @return string
     */
    public static function removeSurroundingText($text, $start, $end)
    {
        if (static::startsWith($text, $start) && static::endsWith($text, $end)) {
            $text = substr($text, strlen($start), strlen($text) - strlen($start) - strlen($end));
        }

        return $text;
    }

    /**
     * Make a filename safe to use in any function. (Accents, spaces, special chars...)
     * The iconv function must be activated.
     *
     * @param string $fileName       The filename to sanitize (with or without extension)
     * @param string $defaultIfEmpty The default string returned for a non valid filename (only special chars or separators)
     * @param string $separator      The default separator
     * @param bool   $lowerCase      Tells if the string must converted to lower case
     *
     * @author COil <https://github.com/COil>
     *
     * @see    http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
     *
     * @return string
     */
    public static function sanitizeFilename($fileName, $defaultIfEmpty = 'default', $separator = '_', $lowerCase = false)
    {
        // replace all / with _
        $fileName = str_replace('/', '_', $fileName);

        // Gather file informations and store its extension
        $fileInfos = pathinfo($fileName);
        $fileExt   = array_key_exists('extension', $fileInfos) ? '.'.strtolower($fileInfos['extension']) : '';

        // Removes accents
        $fileName = @iconv('UTF-8', 'us-ascii//TRANSLIT', $fileInfos['filename']);

        // Removes all characters that are not separators, letters, numbers, dots or whitespaces
        $fileName = preg_replace('/[^ a-zA-Z'.preg_quote($separator).'\d\.\s]/', '', $lowerCase ? strtolower($fileName) : $fileName);

        // Replaces all successive separators into a single one
        $fileName = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $fileName);

        // Trim beginning and ending seperators
        $fileName = trim($fileName, $separator);

        // If empty use the default string
        if (empty($fileName)) {
            $fileName = $defaultIfEmpty;
        }

        return $fileName.$fileExt;
    }

    public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' kB';
        } elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        } elseif ($bytes === false) {
            $bytes = '--';
        } else {
            $bytes = $bytes.' bytes';
        }

        return $bytes;
    }

    /**
     * Removes quotes (" and ') from string.
     *
     * @param string $string
     *
     * @return string
     */
    public function stripQuotes($string)
    {
        return strtr($string, array('"' => '', "'" => ''));
    }

    /**
     * Returns the path including ending slash.
     */
    public static function sanitizeDirectorySlash($dir)
    {
        return rtrim($dir, '/').'/';
    }

    /**
     * Indicates with … when the text is likely truncated.
     *
     * The text is truncated on a space, if one is close to the end.
     * The string length is preserved at the lenght of bytes. (Spaces are appended when the length is too short.)
     *
     * @param string $text
     * @param int    $trucatedLength lenght (in bytes) the text may have been truncated to
     *
     * @return string modified text (if length is trucatedLength)
     */
    public static function indicateStrippedKeepSize($text, $trucatedLength)
    {
        if (strlen($text) === $trucatedLength) {
            $strLenPpp = 3; // strlen of …
            $tmpText = substr($text, 0, 1 - $strLenPpp); // is one byte too long, is stripped below
            $workBreakText = preg_replace('/[\p{L}\p{Nd}]*$/u', '', $tmpText);
            if ($tmpText !== $workBreakText && strlen($workBreakText) > max($trucatedLength - 25, 10)) { // length sufficent
                $tmpText = $workBreakText;
            } else {
                $tmpText = mb_substr($tmpText, 0, -1); // remove one entire character
            }
            $text = substr($tmpText.'…                         ', 0, $trucatedLength); // keep length
        }

        return $text;
    }
}
