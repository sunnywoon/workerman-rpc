<?php
/**
 *
 * @author jhx
 * @date 2021/10/20 11:25
 */

namespace Test\service;


class TestService
{
    public function test($string)
    {
        return "hello {$string}";
    }
}