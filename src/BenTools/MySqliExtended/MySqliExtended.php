<?php

namespace BenTools\MySqliExtended;

mysqli_report(MYSQLI_REPORT_ERROR);

class MySqliExtended extends \Mysqli {

    /**
     * @param string $query
     * @param array $sqlValues
     * @return bool|MySqliStatementExtended
     */
    public function prepare($query, $sqlValues = array()) {
        if (preg_match('#:([a-zA-Z0-9_]+)#', $query))
            $prepared = preg_replace('#:([a-zA-Z0-9_]+)#', '?', $query);
        else
            $prepared = $query;

        return parent::prepare($prepared) ? new MySqliStatementExtended($this, $prepared, $sqlValues, $query) : false;
    }

    /**
     * Executes the statement with bounded params
     *
     * @param array $sqlValues : Optional Values to bind
     * @return \Mysqli_Result|MySqliStatementExtended
     */
    public function sql($sqlString, $sqlValues = array()) {
        if ($sqlString instanceof MySqliStatementExtended)
            return $sqlString->sql($sqlValues);
        else
            return $this->prepare($sqlString)->sql($sqlValues);
    }

    /**
     * SqlArray executes Query : returns the whole result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return array
     */
    public function sqlArray($sqlString, $sqlValues = array()) {
        if ($sqlString instanceof MySqliStatementExtended)
            return $sqlString->sqlArray($sqlValues);
        else
            return $this->prepare($sqlString)->sqlArray($sqlValues);
    }

    /**
     * SqlRow executes Query : returns the 1st row of your result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return array
     */
    public function sqlRow($sqlString, $sqlValues = array()) {
        if ($sqlString instanceof MySqliStatementExtended)
            return $sqlString->sqlRow($sqlValues);
        else
            return $this->prepare($sqlString)->sqlRow($sqlValues);
    }

    /**
     * SqlValues executes Query : returns the 1st column of your result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return array
     */
    public function sqlColumn($sqlString, $sqlValues = array()) {
        if ($sqlString instanceof MySqliStatementExtended)
            return $sqlString->sqlColumn($sqlValues);
        else
            return $this->prepare($sqlString)->sqlColumn($sqlValues);
    }

    /**
     * SqlValue executes Query : returns the 1st cell of your result set
     *
     * @param array $sqlValues : Optional Values to bind
     * @return string
     */
    public function sqlValue($sqlString, $sqlValues = array()) {
        if ($sqlString instanceof MySqliStatementExtended)
            return $sqlString->sqlValue($sqlValues);
        else
            return $this->prepare($sqlString)->sqlValue($sqlValues);

    }

    /**
     * The __invoke method is called when a script tries to call an object as a function.
     *
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.invoke
     */
    public function __invoke($query, $sqlValues = array()) {
        return $this->prepare($query, $sqlValues);
    }


}