<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2019-08-26
 * Time: 13:37
 */
namespace Breeze\ProcessControl;

interface Runable
{


    public function run($max, $current);

}