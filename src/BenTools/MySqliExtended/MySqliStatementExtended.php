<?php

namespace BenTools\MySqliExtended;


use BenTools\MySqliExtended\Exception\MysqliException;

class MySqliStatementExtended extends \Mysqli_Stmt {

    public   $link;
    public   $queryString       =   '';
    public   $namedQueryString  =   '';
    public   $preview           =   '';
    public   $duration          =   0.00;
    public   $executed          =   false;
    public   $execCount         =   0;
    public   $totalDuration     =   0.00;
    public   $boundValues       =   array();
    public   $boundValuesTypes  =   array();
    public   $keywords          =   array();

    /**
     * @var \MySqli_Result
     */
    public   $result;

    /**
     * Public constructor, but should only be called from MySqli::prepare().
     * @param \mysqli $link
     * @param string $query
     * @param array $sqlValues
     * @param string $namedQueryString
     * @internal
     */
    public function __construct($link, $query, $sqlValues = array(), $namedQueryString = '') {
        parent::__construct($link, $query);
        $this->setLink($link);
        $this->setQueryString($query);
        $this->setNamedQueryString($namedQueryString ? $namedQueryString : $query);
        if ($sqlValues)
            $this->bindValues($sqlValues);
    }

    /**
     * Executes the statement with bounded params
     *
     * @param array $sqlValues : Optional Values to bind
     * @return \Mysqli_Result
     */
    public function sql($sqlValues = array()) {
        try {
            $exec = @$this->bindValues($sqlValues)->execute();
            $this->result = $exec->get_result(); // @link http://stackoverflow.com/questions/8321096/call-to-undefined-method-mysqli-stmtget-result
            return $this;
        }
        catch (\Mysqli_Sql_Exception $e) {
            if (strpos($e->getMessage(), "Commands out of sync; you can't run this command now") !== false) {
                set_error_handler([$this, 'errorHandler'], E_WARNING);
                try {
                    $this->result = null;
                    $this->result = $this->getLink()->query($this->preview());
                }
                catch (\Mysqli_Sql_Exception $e) {
                    restore_error_handler();
                    throw $e;
                }
                return $this;
            }
            throw $e;
        }
    }

    /**
     * SqlArray executes Query : returns the whole result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return array
     */
    public function sqlArray($sqlValues = array()) {
        $this->sql($sqlValues);
        return $this->result ? $this->result->fetch_all(MYSQLI_ASSOC) : array();
    }

    /**
     * SqlRow executes Query : returns the 1st row of your result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return array
     */
    public function sqlRow($sqlValues = array()) {
        $this->sql($sqlValues);
        return $this->result instanceof \mysqli_result && $this->result->num_rows > 0 ? $this->result->fetch_array(MYSQLI_ASSOC) : array();
    }

    /**
     * SqlValues executes Query : returns the 1st column of your result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return array
     */
    public function sqlColumn($sqlValues = array()) {
        $array = array();
        $this->sql($sqlValues);
        if ($this->result)
            while ($row = $this->result->fetch_array(MYSQLI_NUM))
                $array[] = $row[0];
        return $array;
    }

    /**
     * SqlValue executes Query : returns the 1st cell of your result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return string
     */
    public function sqlValue($sqlValues = array()) {
        $value = null;
        $this->sql($sqlValues);
        if ($this->result) {
            while ($row = $this->result->fetch_array(MYSQLI_NUM)) {
                $value = $row[0];
                break;
            }
        }
        return $value;
    }

    /**
     * Executes query, measures the total time
     * @param null $input_parameters
     * @return $this for fluent interface
     */
    public function execute($sqlValues = array()) {

        $this->result = null;

        $start                  =    microtime(true);

        if ($sqlValues)
            $this->bindValues($sqlValues);

        if (preg_match('#:([a-zA-Z0-9_]+)#', $this->queryString))
            $this->queryString = preg_replace('#:([a-zA-Z0-9_]+)#', '?', $this->queryString);

        if ($this->boundValues) {
            $tmp = array();
            foreach($this->boundValues as $key => $value)
                $tmp[$key] = &$this->boundValues[$key];

            array_unshift($tmp, join('', $this->boundValuesTypes));
            @call_user_func_array(array($this, 'bind_param'), $tmp);
        }

        $this->getLink()->setLatestStmt($this);

        @parent::execute();

        $end                    =    microtime(true);

        $this->duration         =    round($end - $start, 4);
        $this->totalDuration    +=   $this->duration;
        $this->executed         =    true;
        $this->execCount++;

        $this->preview          =   null;

        if ($this->error) {
            throw MysqliException::factory($this->error, $this->errno, null, $this);
        }

        return $this;
    }

    /**
     * Binds several values at once
     * @param array $sqlValues
     * @return $this
     */
    public function bindValues($sqlValues = array()) {

        $this->boundValues      =   array();
        $this->boundValuesTypes =   array();

        if (empty($sqlValues))
            return $this;

        if (!is_array($sqlValues))
            $sqlValues          =    array($sqlValues);

        $boundValuesTypes       =   array();
        $boundValues            =   $sqlValues;
        $namedPlaceHolders      =   false;

        foreach ($boundValues AS $key => $value) {
            if (!$namedPlaceHolders && !is_numeric($key))
                $namedPlaceHolders = true;
            $boundValuesTypes[$key] = static::getMysqliType($value);
        }

        if ($namedPlaceHolders) {
            preg_match_all('#:([a-zA-Z0-9_]+)#', $this->namedQueryString, $matches);
            $placeholders = $matches[1];

            foreach ($placeholders AS $placeholder) {
                if (array_key_exists($placeholder, $boundValues)) {
                    $this->boundValues[] = $boundValues[$placeholder];
                    $this->boundValuesTypes[] = $boundValuesTypes[$placeholder];
                }
            }
        }
        else {
            $this->boundValues = $boundValues;
            $this->boundValuesTypes = $boundValuesTypes;
        }

        return $this;
    }

    /**
     * @param $var
     * @return string
     */
    public static function getMysqliType($var) {
        if (is_float($var))
            return 'd';
        elseif (is_integer($var))
            return 'i';
        else
            return 's';
    }

    /**
     * MySqli_Stmt debug function
     *
     * @author Beno!t POLASZEK - Jun 2013
     */
    public function debug() {

        $this->preview      =   preg_replace("#\t+#", "\t", $this->queryString);

        if (preg_match('#:([a-zA-Z0-9_]+)#', $this->preview))
            $this->preview = preg_replace('#:([a-zA-Z0-9_]+)#', '?', $this->preview);

        $nbPlaceHolders     =   substr_count($this->preview, '?');
        $debuggedValues     =   array_map(array($this, 'debugValue'), array_keys($this->boundValues));

        if (count($debuggedValues) === $nbPlaceHolders)
            $this->preview  =    vsprintf(str_replace('?', '%s', $this->preview), $debuggedValues);
        else
            throw MysqliException::factory("Number of variables doesn't match number of parameters in prepared statement", 0, null, $this);

        return $this;
    }

    /**
     * @return string
     */
    public function preview() {
        $this->debug();
        return $this->preview;
    }

    /**
     * Add quotes or not for Debug() method
     */
    private function debugValue($key) {

        $value = $this->boundValues[$key];
        $type = $this->boundValuesTypes[$key];

        return in_array($type, array('d', 'i')) ? $value : (string) "'". addslashes($value) . "'";
    }

    /**
     * @return MySqliExtended
     */
    public function getLink() {
        return $this->link;
    }

    /**
     * @param MySqliExtended $link
     * @return $this - Provides Fluent Interface
     */
    public function setLink(\MySqli $link) {
        $this->link = $link;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueryString() {
        return $this->queryString;
    }

    /**
     * @param string $queryString
     * @return $this - Provides Fluent Interface
     */
    public function setQueryString($queryString) {
        $this->queryString = $queryString;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamedQueryString() {
        return $this->namedQueryString;
    }

    /**
     * @param string $namedQueryString
     * @return $this - Provides Fluent Interface
     */
    public function setNamedQueryString($namedQueryString) {
        $this->namedQueryString = $namedQueryString;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreview() {
        return $this->preview;
    }

    /**
     * @param string $preview
     * @return $this - Provides Fluent Interface
     */
    public function setPreview($preview) {
        $this->preview = $preview;
        return $this;
    }

    /**
     * @return float
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * @param float $duration
     * @return $this - Provides Fluent Interface
     */
    public function setDuration($duration) {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getExecuted() {
        return $this->executed;
    }

    /**
     * @param boolean $executed
     * @return $this - Provides Fluent Interface
     */
    public function setExecuted($executed) {
        $this->executed = $executed;
        return $this;
    }

    /**
     * @return int
     */
    public function getExecCount() {
        return $this->execCount;
    }

    /**
     * @param int $execCount
     * @return $this - Provides Fluent Interface
     */
    public function setExecCount($execCount) {
        $this->execCount = $execCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalDuration() {
        return $this->totalDuration;
    }

    /**
     * @param float $totalDuration
     * @return $this - Provides Fluent Interface
     */
    public function setTotalDuration($totalDuration) {
        $this->totalDuration = $totalDuration;
        return $this;
    }

    /**
     * @return array
     */
    public function getBoundValues() {
        return $this->boundValues;
    }

    /**
     * @param array $boundValues
     * @return $this - Provides Fluent Interface
     */
    public function setBoundValues($boundValues) {
        $this->boundValues = $boundValues;
        return $this;
    }

    /**
     * @return array
     */
    public function getBoundValuesTypes() {
        return $this->boundValuesTypes;
    }

    /**
     * @param array $boundValuesTypes
     * @return $this - Provides Fluent Interface
     */
    public function setBoundValuesTypes($boundValuesTypes) {
        $this->boundValuesTypes = $boundValuesTypes;
        return $this;
    }

    /**
     * @return int
     */
    public function getInsertId() {
        return $this->insert_id;
    }

    /**
     * @return int
     */
    public function getNumRows() {
        return $this->num_rows;
    }

    /**
     * @return int
     */
    public function getAffectedRows() {
        return $this->affected_rows;
    }

    /**
     * String context
     * @return string
     */
    public function __toString() {
        return $this->preview();
    }

    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @internal
     */
    public function errorHandler($errno, $errstr, $errfile, $errline) {
        throw MysqliException::factory($errstr, $errno, null, $this);
    }

    /**
     * Transforms an indexed array into placeholders
     * Example : array(0, 22, 99) ==> '?,?,?'
     * Usage : "WHERE VALUES IN (". MySqli_Stmt::PlaceHolders($MyArray) .")"
     *
     * @param array $array
     * @return string placeholder
     * @author Beno!t POLASZEK - Jun 2013
     */
    public static function PlaceHolders($array = array()) {
        return implode(',', array_fill(0, count($array), '?'));
    }
}