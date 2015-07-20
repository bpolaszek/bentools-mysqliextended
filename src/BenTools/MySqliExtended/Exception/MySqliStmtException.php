<?php

namespace BenTools\MySqliExtended\Exception;


use Exception;
use BenTools\MySqliExtended\MySqliStatementExtended;

class MySqliStmtException extends MysqliException {

    /**
     * @var MySqliStatementExtended
     */
    protected $stmt;

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Construct the exception. Note: The message is NOT binary safe.
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     */
    public function __construct($message = "", $code = 0, Exception $previous = null, MySqliStatementExtended $stmt) {
        parent::__construct($message, $code, $previous);
        $this->setStmt($stmt);
    }

    /**
     * @return MySqliStatementExtended
     */
    public function getStmt() {
        return $this->stmt;
    }

    /**
     * @param MySqliStatementExtended $stmt
     * @return $this - Provides Fluent Interface
     */
    public function setStmt($stmt) {
        $this->stmt = $stmt;
        return $this;
    }

}