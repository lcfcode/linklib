<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

use Swap\Core\DbInterface;

class MysqliClass implements DbInterface
{
    private ?\mysqli $_connect = null;
    private string $_keys = '';
    private string $_values = '';
    private string $_bindType = '';
    private string $_wheres = '';
    private string $_orWheres = '';
    private array $_bindValue = [];
    private string $_sql = '';
    private string|array $_sqlParameter;

    private static array $instances = [];
    private static string $instancesKey = '';

    /**
     * @param $config
     * @return DbInterface
     * @author LCF
     * @date
     */
    public static function getInstance($config)
    {
        self::$instancesKey = $config['host'] . ':' . $config['port'] . ':' . $config['user'] . ':' . $config['database'];
        if (isset(self::$instances[self::$instancesKey])) {
            return self::$instances[self::$instancesKey];
        }
        self::$instances[self::$instancesKey] = new self($config);
        return self::$instances[self::$instancesKey];
    }

    public function __construct($config)
    {
        $conn = new \mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
        $conn->set_charset($config['charset']);
        $this->_connect = $conn;
    }

    public function db()
    {
        return $this->_connect;
    }

    public function clientInfo()
    {
        if (PHP_VERSION > 8.0) {
            trigger_error('php版本大于8.0，该方法已起用', E_USER_ERROR);
        }
        return $this->_connect->get_client_info();
    }

    public function serverInfo()
    {
        return $this->_connect->server_info;
    }

    public function getLastSql($flag = true)
    {
        if (empty($this->_sqlParameter)) {
            return $this->_sql;
        }
        if (is_array($this->_sqlParameter)) {
            $parameter = $this->_sqlParameter;
            $explodeArr = explode('?', $this->_sql);
            $index = substr_count($this->_sql, '?');

            for ($i = 0; $i < $index; $i++) {
                $param = $parameter[$i + 1];
                $explodeArr[$i] = $explodeArr[$i] . "'{$param}'";
            }
            $sql = implode('', $explodeArr);
        } else {
            $sql = str_replace('?', "'{$this->_sqlParameter}'", $this->_sql);
            $parameter = [$this->_sqlParameter];
        }
        if ($flag === true) {
            return $sql;
        }
        return ['sql' => $this->_sql, 'parameter' => $parameter];
    }

    public function insert($table, $data)
    {
        $sql = 'insert into ' . $table;
        $this->clear();
        $this->iBand($data);
        $sql .= ' (' . $this->_keys . ') values (' . $this->_values . ')';
        $args[] = $this->_bindType;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            trigger_error('MysqliClass::insert exception , message:' . $stmt->error . ' errno:' . $stmt->errno, E_USER_ERROR);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function updateId($table, $idField, $id, $data)
    {
        $sql = 'update ' . $table . ' set ';
        $this->clear();
        $this->uBand($data);
        $sql .= ' ' . $this->_keys . ' where ' . $idField . '=? ';
        $this->_bindType .= $this->_determineType($id);
        $args[] = $this->_bindType;
        array_push($this->_bindValue, $id);
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            trigger_error('MysqliClass::updateId exception , message:' . $stmt->error . ' errno:' . $stmt->errno, E_USER_ERROR);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function deleteId($table, $idField, $id)
    {
        $sql = 'delete from ' . $table . ' where ' . $idField . '=?';
        $bindType = $this->_determineType($id);
        $stmt = $this->_prepare($sql, $id);
        $stmt->bind_param($bindType, $id);
        $stmt->execute();
        if ($stmt->errno) {
            trigger_error('MysqliClass::deleteId exception , message:' . $stmt->error . ' errno:' . $stmt->errno, E_USER_ERROR);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function update($table, $data, $where)
    {
        $sql = 'update ' . $table . ' set ';
        $this->clear();
        $this->uBand($data);
        $sql .= ' ' . $this->_keys . ' where ';
        $this->_and($where);
        $args[] = $this->_bindType;
        $sql .= ' ' . $this->_wheres;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            trigger_error('MysqliClass::update exception , message:' . $stmt->error . ' errno:' . $stmt->errno, E_USER_ERROR);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function delete($table, $where)
    {
        $sql = 'delete from ' . $table;
        $this->clear();
        $this->_and($where);
        $sql .= ' where ' . $this->_wheres;
        $args[] = $this->_bindType;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            trigger_error('MysqliClass::delete exception , message:' . $stmt->error . ' errno:' . $stmt->errno, E_USER_ERROR);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function selectId($table, $idField, $id, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table . ' where ' . $idField . '=? limit 1';
        $bindType = $this->_determineType($id);
        $stmt = $this->_prepare($sql, $id);
        $stmt->bind_param($bindType, $id);
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        if ($returnData) {
            return $returnData[0];
        }
        return [];
    }

    public function selectOne($table, $where, $order = [], $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        $this->clear();
        $this->_and($where);
        $sql .= ' where ' . $this->_wheres;
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        $sql .= ' limit 1';
        $args[] = $this->_bindType;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($returnData) {
            return $returnData[0];
        }
        return [];
    }

    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        $stmt = $this->_prepare($sql);
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        return $returnData;
    }

    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' where ' . $this->_wheres;
        }
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        $inStr = '';
        foreach ($inWhere as $value) {
            $inStr .= ",'{$value}'";
        }
        $inStr = trim($inStr, ',');
        $sql .= ' where ' . $field . ' in (' . $inStr . ') ';
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' and ' . $this->_wheres;
        }
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    public function query($sql, $param = [])
    {
        if (!empty($param)) {
            $paramTmp = [];
            $bindType = '';
            foreach ($param as $key => $value) {
                $bindType .= $this->_determineType($param[$key]);
                $paramTmp[] = $param[$key];
            }
            $parameter = array_merge([$bindType], $paramTmp);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        } else {
            $stmt = $this->_prepare($sql);
        }
        $stmt->execute();
        if (stripos(trim($sql), 'select') === 0) {
            $returnData = $this->_dynamicBindResults($stmt);
        } else {
            $res = $stmt->affected_rows;
            if ($res > 0) {
                $returnData = $res;
            } else {
                $returnData = false;
            }
        }
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    public function like($table, $stringName, $content, $where = [], $isCount = false, $order = [], $offset = 0, $fetchNum = 0, $direction = 'in', $getInfo = ['*'])
    {
        $content = addslashes($content);
        if (stristr($content, '_')) {
            $content = str_replace('_', "\\_", $content);
        }
        if (stristr($content, '%')) {
            $content = str_replace('%', '', $content);
        }
        $content = $this->_connect->real_escape_string($content);
        $field = $isCount === false ? implode(',', $getInfo) : ' count(*) as count ';
        if ('in' == $direction) {
            $likeInfo = " like '%" . $content . "%' ";
        } else if ('left' == $direction) {
            $likeInfo = " like '" . $content . "%' ";
        } else {
            $likeInfo = " like '%" . $content . "' ";
        }
        $sql = 'select ' . $field . ' from ' . $table . ' where ' . $stringName . ' ' . $likeInfo;
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' and ' . $this->_wheres;
        }
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $isCount === false ? $returnData : $returnData[0]['count'];
    }

    public function insertMultiple($table, $multiInsertData, $keys = [])
    {
        $sql = 'insert into ' . $table;
        $keyArr = [];
        $valueArr = [];
        $bindType = '';
        $sqlTemp = '';
        $index = 0;
        foreach ($multiInsertData as $data) {
            if (empty($data)) {
                continue;
            }
            $tmpArr = [];
            foreach ($data as $key => $value) {
                if ($index == 0) {
                    $keyArr[] = $key;
                }
                $tmpArr[] = '?';
                $valueArr[] =& $data[$key];
                $bindType .= $this->_determineType($value);
            }
            $values = implode(',', $tmpArr);
            $sqlTemp .= '(' . $values . '),';
            $index++;
        }
        $sqlTemp = rtrim($sqlTemp, ',');
        if (empty($keys)) {
            $keys = implode(',', $keyArr);
        }
        $sql .= ' (' . $keys . ') values ' . $sqlTemp;
        $bindValue = $valueArr;
        $args[] = $bindType;
        $parameter = array_merge($args, $bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function count($table, $where = [], $columnName = '*', $distinct = false)
    {
        if ($distinct) {
            $sql = 'select count( distinct ' . $columnName . ') as count from ' . $table;
        } else {
            $sql = 'select count(' . $columnName . ') as count from ' . $table;
        }
        $returnData = $this->_group($sql, $where);
        return $returnData[0]['count'];
    }

    public function min($table, $columnName, $where = [])
    {
        $sql = "select min(" . $columnName . ") as min from " . $table;
        $returnData = $this->_group($sql, $where);
        return $returnData[0]['min'];
    }

    public function max($table, $columnName, $where = [])
    {
        $sql = "select max(" . $columnName . ") as max from " . $table;
        $returnData = $this->_group($sql, $where);
        return $returnData[0]['max'];
    }

    public function avg($table, $columnName, $where = [])
    {
        $sql = "select avg(" . $columnName . ") as avg from " . $table;
        $returnData = $this->_group($sql, $where);
        return $returnData[0]['avg'];
    }

    public function sum($table, $columnName, $where = [])
    {
        $sql = "select sum(" . $columnName . ") as sum from " . $table;
        $returnData = $this->_group($sql, $where);
        return $returnData[0]['sum'];
    }

    private function _group($sql, $where = [])
    {
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' where ' . $this->_wheres;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    public function beginTransaction()
    {
        return $this->_connect->autocommit(false);
    }

    public function commitTransaction()
    {
        return $this->_connect->commit();
    }

    public function rollbackTransaction()
    {
        return $this->_connect->rollback();
    }

    public function close()
    {
        if ($this->_connect) {
            $this->_connect->close();
            $this->_connect = null;
        }
        if (isset(self::$instances[self::$instancesKey])) {
            unset(self::$instances[self::$instancesKey]);
        }
    }

    private function clear()
    {
        $this->_keys = '';
        $this->_values = '';
        $this->_bindType = '';
        $this->_wheres = '';
        $this->_orWheres = '';
        $this->_bindValue = [];
    }

    private function iBand($data)
    {
        $keyArr = [];
        $tmpArr = [];
        $valueArr = [];
        foreach ($data as $key => $value) {
            $keyArr[] = $key;
            $tmpArr[] = '?';
            $valueArr[] =& $data[$key];
            $this->_bindType .= $this->_determineType($value);
        }
        $this->_keys = implode(',', $keyArr);
        $this->_values = implode(',', $tmpArr);
        $this->_bindValue = $valueArr;
        return true;
    }

    private function _determineType($dataType)
    {
        switch (gettype($dataType)) {
            case 'NULL':
            case 'string':
                return 's';
                break;
            case 'boolean':
            case 'integer':
                return 'i';
                break;
            case 'blob':
                return 'b';
                break;
            case 'double':
                return 'd';
                break;
        }
        return trigger_error('MysqliClass::_determineType exception , message : data type exception!', E_USER_ERROR);
    }

    /**
     * @param $sql
     * @param null $parameter
     * @return \mysqli_stmt
     * @author LCF
     * @date 2019/9/24 9:57
     */
    private function _prepare($sql, $parameter = null)
    {
        $this->_sql = $sql;
        if (!empty($parameter)) {
            $this->_sqlParameter = $parameter;
        }
        $stmt = $this->_connect->prepare($sql);
        if (!$stmt) {
            $msg = $this->_connect->error . " --SQL: " . $this->sqlHtml($this->getLastSql());
            trigger_error('MysqliClass::_prepare exception , message : ' . $msg, E_USER_ERROR);
        }
        return $stmt;
    }

    private function refValues($data)
    {
        $refs = [];
        foreach ($data as $key => $value) {
            $refs[] =& $data[$key];
        }
        return $refs;
    }

    private function uBand($data)
    {
        $keyArr = [];
        $valueArr = [];
        foreach ($data as $key => $value) {
            $keyArr[] = $key . '=? ';
            $valueArr[] =& $data[$key];
            $this->_bindType .= $this->_determineType($value);
        }
        $this->_keys = implode(',', $keyArr);
        $this->_bindValue = $valueArr;
        return true;
    }

    private function _and($where)
    {
        $whereValueArr = [];
        $strTmp = '';
        foreach ($where as $keys => $values) {
            if (!strpos($keys, '::')) {
                $strTmp .= ' and ' . $keys . '=? ';
            } else {
                $strTmp .= ' and ' . str_replace('::', ' ', $keys) . ' ? ';
            }
            $whereValueArr[] =& $where[$keys];
            $this->_bindType .= $this->_determineType($values);
        }
        $this->_wheres = substr($strTmp, 4);
        if (!empty($this->_bindValue)) {
            $this->_bindValue = array_merge($this->_bindValue, $whereValueArr);
        } else {
            $this->_bindValue = $whereValueArr;
        }
        return true;
    }

    private function _dynamicBindResults($stmt)
    {
        $result = $stmt->get_result();
        $results = [];
        while ($resultRow = $result->fetch_assoc()) {
            $results[] = $resultRow;
        }
        return $results;
    }

    private function _order($order)
    {
        $orderArr = [];
        foreach ($order as $orderKey => $rowOrder) {
            $orderArr[] = $orderKey . ' ' . $rowOrder;
        }
        return implode(',', $orderArr);
    }

    private function _dynamicBindResults2($stmt)
    {
        $parameters = [];
        $results = [];
        $meta = $stmt->result_metadata();
        while ($field = $meta->fetch_field()) {
            $parameters[] = &$row[$field->name];
        }
        call_user_func_array([$stmt, 'bind_result'], $parameters);
        while ($stmt->fetch()) {
            $x = [];
            foreach ($row as $key => $val) {
                $x[$key] = $val;
            }
            $results[] = $x;
        }
        return $results;
    }

    private function sqlHtml($sql)
    {
        return '<b>' . $sql . '</b>';
    }

    public function __destruct()
    {
        if ($this->_connect) {
            $this->_connect->close();
        }
    }
}
