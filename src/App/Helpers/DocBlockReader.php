<?php

namespace LaravelDev\App\Helpers;

class DocBlockReader
{
    /**
     * @param $docblock
     * @return array
     */
    public static function parse(string $docblock): array
    {
        $result = [];
        if (preg_match_all('/@(\w+)\s+([^\r\n]*)/m', $docblock, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $value = rtrim($matches[2][$i]);
                // 移除注释结束符 */ 或行首 *
                $value = preg_replace('/\s*\*\/$/', '', $value);
                $result[$key] = trim($value);
            }
        }
        return $result;
    }
}
