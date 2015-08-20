<?php

namespace BenTools\MySqliExtended\Exception;


use Exception;
use BenTools\MySqliExtended\MySqliStatementExtended;

class MysqliException extends \Mysqli_Sql_Exception {

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Construct the exception. Note: The message is NOT binary safe.
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     */
    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param MySqliStatementExtended|null $stmt
     * @return BoundVariablesException|MysqliException|MySqliStmtException
     */
    public static function factory($message = "", $code = 0, Exception $previous = null, MySqliStatementExtended $stmt = null) {
        if (is_null($stmt))
            return new self($message, $code, $previous);

        elseif (strpos("Number of variables doesn't match number of parameters in prepared statement", $message) !== false)
            return new BoundVariablesException($message, $code, $previous, $stmt);

        elseif (strpos("No data supplied for parameters in prepared statement", $message) !== false)
            return new BoundVariablesException($message, $code, $previous, $stmt);

        elseif (strpos("Missing placeholder", $message) !== false)
            return new BoundVariablesException($message, $code, $previous, $stmt);

        else
            return new MySqliStmtException($message, $code, $previous, $stmt);
    }

}