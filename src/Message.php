<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2019-08-26
 * Time: 13:42
 */

namespace Breeze\ProcessControl;


class Message
{


    protected $body;

    public function setMessage($string)
    {
        $this->body = $string;
    }

    public function getMessage()
    {
        return $this->body;
    }

}