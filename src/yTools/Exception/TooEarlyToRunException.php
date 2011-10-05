<?php

namespace yTools\Exception;

class TooEarlyToRunException extends Exception {
    protected $message = 'Too early to run the function.';
}