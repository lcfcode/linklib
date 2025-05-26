<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

use Swap\Core\DbInterface;

/**
 * Class MssqlClass
 * @package Swap\Utils
 * 原本设计保留单例，现在也满足单例情况，需要自行改写
 */
class MssqlClass implements DbInterface
{
    /**
     * @var false|null|resource
     */
    private mixed $_connect = null;
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
        $config['charset'] = str_replace('utf8', 'utf-8', $config['charset']);
        $connectionOptions = array("Database" => $config['database'], "Uid" => $config['user'], "PWD" => $config['password'], 'CharacterSet' => $config['charset']);
        $this->_connect = sqlsrv_connect($config['host'] . ',' . $config['port'], $connectionOptions);
        if (false == $this->_connect) {
            throw new \Exception('db connection fail');
        }
        //todo
//        sqlsrv_configure("WarningsReturnAsErrors", 0);
//        sqlsrv_configure("WarningsReturnAsErrors", 1);
    }

    public function db()
    {
        return $this->_connect;
    }

    public function clientInfo()
    {
        return sqlsrv_client_info($this->_connect);
    }

    public function serverInfo()
    {
        return sqlsrv_server_info($this->_connect);
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
                $param = $parameter[$i];
                $explodeArr[$i] = $explodeArr[$i] . "N'{$param}'";
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
//        $sql .= ' (' . $this->_keys . ') values (' . $this->_values . ');SELECT SCOPE_IDENTITY() AS id from '.$table;
//        $stmt = sqlsrv_query($this->_connect, $sql, $this->_bindValue);

        $column = $this->selfQuery($sql, $this->_bindValue, true);
        $this->clear();
        return $column;
    }

    public function updateId($table, $idField, $id, $data)
    {
        $sql = 'update ' . $table . ' set ';
        $this->clear();
        $this->uBand($data);
        $sql .= ' ' . $this->_keys . ' where ' . $idField . '=? ';
        array_push($this->_bindValue, $id);
        $column = $this->selfQuery($sql, $this->_bindValue, true);
        $this->clear();
        return $column;
    }

    public function deleteId($table, $idField, $id)
    {
        $this->clear();
        $sql = 'delete from ' . $table . ' where ' . $idField . '=?';
        $column = $this->selfQuery($sql, [$id], true);
        $this->clear();
        return $column;
    }

    public function update($table, $data, $where)
    {
        $sql = 'update ' . $table . ' set ';
        $this->clear();
        $this->uBand($data);
        $sql .= ' ' . $this->_keys . ' where ';
        $this->_and($where);
        $sql .= ' ' . $this->_wheres;
        $column = $this->selfQuery($sql, $this->_bindValue, true);
        $this->clear();
        return $column;
    }

    public function delete($table, $where)
    {
        $sql = 'delete from ' . $table;
        $this->clear();
        $this->_and($where);
        $sql .= ' where ' . $this->_wheres;
        $column = $this->selfQuery($sql, $this->_bindValue, true);
        $this->clear();
        return $column;
    }

    public function selectId($table, $idField, $id, $getInfo = ['*'])
    {
        $this->clear();
        $sql = 'select top 1 ' . implode(',', $getInfo) . ' from ' . $table . ' where ' . $idField . '=? ';
        $returnData = $this->selfQuery($sql, [$id]);
        $this->clear();
        if ($returnData) {
            return $returnData[0];
        }
        return [];
    }

    public function selectOne($table, $where, $order = [], $getInfo = ['*'])
    {
        $field = implode(',', $getInfo);
        if ($order) {
            $thatOrder = $this->_order($order);
        } else {
            $thatOrder = 'rand()';
        }
        $this->clear();
        $this->_and($where);
        $innerSql = 'select ' . $field . ', row_number() over(order by ' . $thatOrder . ' ) as row_num from ' . $table . ' where ' . $this->_wheres;
        $sql = 'select top 1 ' . $field . ' from( ' . $innerSql . ' ) as linker ';
        $returnData = $this->selfQuery($sql, $this->_bindValue);
        $this->clear();
        if ($returnData) {
            return $returnData[0];
        }
        return [];
    }

    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $field = implode(',', $getInfo);
        $this->clear();
        if ($order) {
            $thatOrder = $this->_order($order);
        } else {
            $thatOrder = 'rand()';
        }
        $innerSql = 'select ' . $field . ' , row_number() over (order by ' . $thatOrder . ' ) as row_num from ' . $table;
        $sql = 'select ' . $field . ' from( ' . $innerSql . ' ) as linker ';
        if ($fetchNum > 0 && $offset > 0) {
            $pageNow = ($offset - 1) * $fetchNum + 1;
            $size = $pageNow + $fetchNum - 1;
            $sql .= ' where row_num between ' . $pageNow . ' and ' . $size;
        }
        $returnData = $this->selfQuery($sql, $this->_bindValue);
        $this->clear();

        return $returnData;
    }

    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $field = implode(',', $getInfo);

        if ($order) {
            $thatOrder = $this->_order($order);
        } else {
            $thatOrder = 'rand()';
        }
        $innerSql = 'select ' . $field . ' , row_number() over (order by ' . $thatOrder . ' ) as row_num from ' . $table;
        if ($where) {
            $this->clear();
            $this->_and($where);
            $innerSql .= ' where ' . $this->_wheres;
        }

        $sql = 'select ' . $field . ' from( ' . $innerSql . ' ) as linker ';
        if ($fetchNum > 0 && $offset > 0) {
            $pageNow = ($offset - 1) * $fetchNum + 1;
            $size = $pageNow + $fetchNum - 1;
            $sql .= ' where row_num between ' . $pageNow . ' and ' . $size;
        }

        $returnData = $this->selfQuery($sql, $this->_bindValue);
        $this->clear();
        return $returnData;
    }

    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {

        $fields = implode(',', $getInfo);

        if ($order) {
            $thatOrder = $this->_order($order);
        } else {
            $thatOrder = 'rand()';
        }
        $innerSql = 'select ' . $fields . ' , row_number() over (order by ' . $thatOrder . ' ) as row_num from ' . $table;
        $inStr = '';
        foreach ($inWhere as $value) {
            $inStr .= ",'{$value}'";
        }
        $inStr = trim($inStr, ',');
        $innerSql .= ' where ' . $field . ' in (' . $inStr . ') ';
        if ($where) {
            $this->clear();
            $this->_and($where);
            $innerSql .= ' and ' . $this->_wheres;
        }
        $sql = 'select ' . $fields . ' from( ' . $innerSql . ' ) as linker ';
        if ($fetchNum > 0 && $offset > 0) {
            $pageNow = ($offset - 1) * $fetchNum + 1;
            $size = $pageNow + $fetchNum - 1;
            $sql .= ' where row_num between ' . $pageNow . ' and ' . $size;
        }

        $returnData = $this->selfQuery($sql, $this->_bindValue);
        $this->clear();
        return $returnData;
    }

    public function query($sql, $param = [])
    {
        $parameter = [];
        if (!empty($param)) {
            foreach ($param as $key => $value) {
                $parameter[] = $param[$key];
            }
        }
        if (stripos(trim($sql), 'select') === 0) {
            $returnData = $this->selfQuery($sql, $parameter);
        } else {
            $returnData = $this->selfQuery($sql, $parameter, true);
        }
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
        if ($order) {
            $thatOrder = $this->_order($order);
        } else {
            $thatOrder = 'rand()';
        }

        $field = $isCount === false ? implode(',', $getInfo) : ' count(*) as count ';
        if ('in' == $direction) {
            $likeInfo = " like N'%{$content}%' ";
        } else if ('left' == $direction) {
            $likeInfo = " like N'{$content}%' ";
        } else {
            $likeInfo = " like N'%{$content}' ";
        }
        $innerSql = 'select ' . implode(',', $getInfo) . ' , row_number() over (order by ' . $thatOrder . ' ) as row_num from ' . $table . ' where ' . $stringName . $likeInfo;

        if ($where) {
            $this->clear();
            $this->_and($where);
            $innerSql .= ' and ' . $this->_wheres;
        }
        $sql = 'select ' . $field . ' from( ' . $innerSql . ' ) as linker ';
        if ($fetchNum > 0 && $offset > 0) {
            $pageNow = ($offset - 1) * $fetchNum + 1;
            $size = $pageNow + $fetchNum - 1;
            $sql .= ' where row_num between ' . $pageNow . ' and ' . $size;
//            $sql .= ' where row_num between ' . $offset . ' and ' . $fetchNum;
        }
        $returnData = $this->selfQuery($sql, $this->_bindValue);
        $this->clear();
        return $isCount === false ? $returnData : $returnData[0]['count'];
    }

    public function insertMultiple($table, $multiInsertData, $keys = [])
    {
        $sql = 'insert into ' . $table;
        $keyArr = [];
        $parameter = [];
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
                $parameter[] =& $data[$key];
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

        $returnData = $this->selfQuery($sql, $parameter, true);
        $this->clear();
        return $returnData;
    }

    public function count($table, $where = [], $columnName = '*', $distinct = false)
    {
        if ($distinct) {
            $sql = "select count( distinct " . $columnName . ") as count from " . $table;
        } else {
            $sql = "select count(" . $columnName . ") as count from " . $table;
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

        $returnData = $this->selfQuery($sql, $this->_bindValue);
        $this->clear();
        return $returnData;
    }

    public function beginTransaction()
    {
        $result = sqlsrv_begin_transaction($this->_connect);
        if ($result === false) {
            trigger_error('sqlsrv_begin_transaction Exception; message : ' . json_encode(sqlsrv_errors(), JSON_UNESCAPED_UNICODE), E_USER_ERROR);
        }
        return $result;
    }

    public function commitTransaction()
    {
        return sqlsrv_commit($this->_connect);
    }

    public function rollbackTransaction()
    {
        return sqlsrv_rollback($this->_connect);
    }

    public function close()
    {
        if ($this->_connect) {
            sqlsrv_close($this->_connect);
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
        }
        $this->_keys = implode(',', $keyArr);
        $this->_values = implode(',', $tmpArr);
        $this->_bindValue = $valueArr;
        return true;
    }

    private function uBand($data)
    {
        $keyArr = [];
        $valueArr = [];
        foreach ($data as $key => $value) {
            $keyArr[] = $key . '=? ';
            $valueArr[] =& $data[$key];
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
        }
        $this->_wheres = substr($strTmp, 4);
        if (!empty($this->_bindValue)) {
            $this->_bindValue = array_merge($this->_bindValue, $whereValueArr);
        } else {
            $this->_bindValue = $whereValueArr;
        }
        return true;
    }


    private function _order($order)
    {
        $orderArr = [];
        foreach ($order as $orderKey => $rowOrder) {
            $orderArr[] = $orderKey . ' ' . $rowOrder;
        }
        return implode(',', $orderArr);
    }

    private function sqlHtml($sql)
    {
        return '<b>' . $sql . '</b>';
    }

    /**
     * @param string $sql
     * @param array $parameter
     * @param bool $flag
     * @return array|int
     * @author LCF
     * @date 2019/9/21 20:29
     */
    private function selfQuery($sql, $parameter, $flag = false)
    {
        $this->_sql = $sql;
        if (empty($parameter)) {
            $stmt = sqlsrv_prepare($this->_connect, $sql);
        } else {
            $this->_sqlParameter = $parameter;
            $stmt = sqlsrv_prepare($this->_connect, $sql, $parameter);
        }
        if (!$stmt) {
            trigger_error('MssqlClass::selfQuery exception SQL:[ ' . $this->sqlHtml($sql) . ' ]; message : ' . json_encode(sqlsrv_errors(), JSON_UNESCAPED_UNICODE), E_USER_ERROR);
        }
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            trigger_error('MssqlClass::selfQuery exception SQL:[ ' . $this->sqlHtml($sql) . ' ]; message : ' . json_encode(sqlsrv_errors(), JSON_UNESCAPED_UNICODE), E_USER_ERROR);
        }
        if ($flag === true) {
            $returnData = sqlsrv_rows_affected($stmt);
        } else {
            $returnData = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $returnData[] = $row;
            }
        }
        sqlsrv_free_stmt($stmt);
        return $returnData;
    }

    public function __destruct()
    {
        if ($this->_connect) {
            sqlsrv_close($this->_connect);
        }
    }
}
