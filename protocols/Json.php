<?php
/**
 *
 * @author jhx
 * @date 2021/10/20 9:37
 */

namespace Protocols;


class Json
{
    public static function input($buffer)
    {
        // 获得换行字符"\n"位置
        $pos = strpos($buffer, "\n");
        // 没有换行符，无法得知包长，返回0继续等待数据
        if ($pos === false) {
            return 0;
        }
        // 有换行符，返回当前包长（包含换行符）
        return $pos + 1;
    }

    public static function encode($buffer)
    {
        // json序列化，并加上换行符作为请求结束的标记
        return json_encode($buffer) . "\n";
    }

    public static function decode($buffer)
    {
        // 去掉换行，还原成数组
        return json_decode(trim($buffer), true);
    }
}