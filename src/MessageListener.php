<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2019-08-26
 * Time: 15:03
 */

namespace Breeze\ProcessControl;


interface MessageListener
{

    public function onReceived($message, $type, $error);

}