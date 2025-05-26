<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

use Swap\Core\DbInterface;

/**
 * Class MongoClass
 * @package Swap\Utils
 * @author LCF
 * @date
 * 只有基础查询  未完成
 */
class MongoClass implements DbInterface
{
    private ?\MongoDB\Driver\Manager $_connect = null;
    private mixed $_dbName = '';
    private array $op = [
        '>' => '$gt',
        '<' => '$lt',
        '>=' => '$gte',
        '<=' => '$lte',
    ];

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
        //实例化mongodb对象
//        $url = 'mongodb://user:pass@localhost:27017';
//        $this->_connect = new \MongoDB\Driver\Manager("mongodb://" . $config['username'] . ':' . $config['password'] . '@' . $config['hostname'] . ':' . $config['hostport']);

        $url = 'mongodb://' . $config['host'] . ':' . $config['port'];
        $opt = ['username' => $config['user'], 'password' => $config['password']];
        $this->_connect = new \MongoDB\Driver\Manager($url, $opt);
        $this->_dbName = $config['database'];
    }

    /**
     * @param $param
     * @return \MongoDB\Driver\Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     * @author LCF
     * @date 2020/4/25 14:56
     * mongo命令执行
     */
    public function command($param)
    {
        $cmd = new \MongoDB\Driver\Command($param);
        return $this->_connect->executeCommand($this->_dbName, $cmd);
    }

    public function count($table, $where = [], $columnName = '*', $distinct = false)
    {
        $arr = [
            'count' => $table,
            'query' => $this->_and($where)
        ];
        $cmd = new \MongoDB\Driver\Command($arr);
        $cursor = $this->_connect->executeCommand($this->_dbName, $cmd);
        return $cursor->toArray()[0]->n;
    }

    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $filter = $this->_and($where);
        $options = $this->_order($order);
        if ($fetchNum > 0 && $offset > 0) {
            $options['skip'] = ($offset - 1) * $fetchNum;
            $options['limit'] = $fetchNum;
        }
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->_connect->executeQuery($this->_dbName . '.' . $table, $query);
        return $this->_result($cursor);
    }

    public function selectIn($table, $field, $inWhere, $andWhere = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $filter = $this->_in($field, $inWhere, $andWhere);
        $options = $this->_order($order);
        if ($fetchNum > 0 && $offset > 0) {
            $options['skip'] = ($offset - 1) * $fetchNum;
            $options['limit'] = $fetchNum;
        }
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->_connect->executeQuery($this->_dbName . '.' . $table, $query);
        return $this->_result($cursor);
    }

    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        return $this->selects($table, [], $order, $offset, $fetchNum);
    }

    public function insert($table, $data)
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert($data);
        return $this->_connect->executeBulkWrite($this->_dbName . '.' . $table, $bulk)->getInsertedCount();
    }

    public function delete($table, $where)
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $filter = $this->_and($where);
        $bulk->delete($filter);
        return $this->_connect->executeBulkWrite($this->_dbName . '.' . $table, $bulk)->getDeletedCount();
    }

    public function update($table, $data, $where)
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update($this->_and($where), ['$set' => $data], ['multi' => true, 'upsert' => false]);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        return $this->_connect->executeBulkWrite($this->_dbName . '.' . $table, $bulk, $writeConcern)->getModifiedCount();
    }

    public function selectOne($table, $where, $order = [], $getInfo = ['*'])
    {
        $options = $this->_order($order);
        $options['limit'] = 1;
        $query = new \MongoDB\Driver\Query($this->_and($where), $options);
        $cursor = $this->_connect->executeQuery($this->_dbName . '.' . $table, $query);
        $where = $cursor->toArray();
        return isset($where[0]) ? get_object_vars($where[0]) : [];
    }

    public function close()
    {
        if ($this->_connect) {
            $this->_connect = null;
        }
        if (isset(self::$instances[self::$instancesKey])) {
            unset(self::$instances[self::$instancesKey]);
        }
    }


    public function db()
    {
        return $this->_connect;
    }

    /**
     * @inheritDoc
     */
    public function clientInfo()
    {
        // TODO: Implement clientInfo() method.
        return $this->thatErr();
    }

    /**
     * @inheritDoc
     */
    public function serverInfo()
    {
        // TODO: Implement serverInfo() method.
        return $this->thatErr();
    }

    /**
     * @inheritDoc
     */
    public function getLastSql($flag = true)
    {
        // TODO: Implement getLastSql() method.
        return $this->thatErr();
    }

    public function updateId($table, $idField, $id, $data)
    {
        // TODO: Implement updateId() method.
        return $this->thatErr();
    }

    public function deleteId($table, $idField, $id)
    {
        // TODO: Implement deleteId() method.
        return $this->thatErr();
    }

    public function selectId($table, $idField, $id, $getInfo = ['*'])
    {
        // TODO: Implement selectId() method.
        return $this->thatErr();
    }

    /**
     * @inheritDoc
     */
    public function query($sql, $param = [])
    {
        // TODO: Implement query() method.
        return $this->thatErr();
    }

    /**
     * @inheritDoc
     */
    public function like($table, $stringName, $content, $where = [], $isCount = false, $order = [], $offset = 0, $fetchNum = 0, $direction = 'in', $getInfo = ['*'])
    {
        // TODO: Implement like() method.
        return $this->thatErr();
    }

    /**
     * @inheritDoc
     */
    public function insertMultiple($table, $multiInsertData, $keys = [])
    {
        // TODO: Implement insertMultiple() method.
        return $this->thatErr();
    }

    public function min($table, $columnName, $where = [])
    {
        // TODO: Implement min() method.
        return $this->thatErr();
    }

    public function max($table, $columnName, $where = [])
    {
        // TODO: Implement max() method.
        return $this->thatErr();
    }

    public function avg($table, $columnName, $where = [])
    {
        // TODO: Implement avg() method.
        return $this->thatErr();
    }

    public function sum($table, $columnName, $where = [])
    {
        // TODO: Implement sum() method.
        return $this->thatErr();
    }

    public function beginTransaction()
    {
        // TODO: Implement beginTransaction() method.
        return $this->thatErr();
    }

    public function commitTransaction()
    {
        // TODO: Implement commitTransaction() method.
        return $this->thatErr();
    }

    public function rollbackTransaction()
    {
        // TODO: Implement rollbackTransaction() method.
        return $this->thatErr();
    }

    private function thatErr()
    {
        return trigger_error('mongo暂时不支持该方法', E_USER_ERROR);
    }

    private function _order($order)
    {
        //根据id字段排序 1是升序，-1是降序
        if (empty($order)) {
            return [
                'sort' => ['_id' => 1]
            ];
        }
        $sort = [];
        foreach ($order as $keys => $items) {
            $sort[$keys] = ('ASC' == strtoupper($items)) ? 1 : -1;
        }
        return [
            'sort' => $sort
        ];
    }

    private function _and($where)
    {
        if (empty($where)) {
            return [];
        }
        $filters = [];
        foreach ($where as $keys => $values) {
            if (!strpos($keys, '::')) {
                $filters[][$keys] = $values;
            } else {
                $op = explode('::', $keys);
                $filters[][$op[0]] = [$this->op[$op[1]] => $values];
            }
        }
        return ['$and' => $filters];
    }

    private function _in($field, $inWhere, $where)
    {
        $inFilters = [];
        foreach ($inWhere as $value) {
            $inFilters[] = $value;
        }
        $andFilters = [];
        if ($where) {
            foreach ($where as $keys => $values) {
                if (!strpos($keys, '::')) {
                    $andFilters[][$keys] = $values;
                } else {
                    $op = explode('::', $keys);
                    $andFilters[][$op[0]] = [$this->op[$op[1]] => $values];
                }
            }
        }
        if ($andFilters) {
            return [$field => ['$in' => $inFilters], '$and' => $andFilters];
        }
        return [$field => ['$in' => $inFilters]];
    }

    private function _or($orWhere, $where)
    {
        if (empty($where) || empty($orWhere)) {
            return [];
        }
        $orFilters = [];
        foreach ($orWhere as $key => $value) {
            if (!strpos($key, '::')) {
                $orFilters[][$key] = $value;
            } else {
                $op = explode('::', $key);
                $orFilters[][$op[0]] = [$this->op[$op[1]] => $value];
            }
        }
        $andFilters = [];
        if ($where) {
            foreach ($where as $keys => $values) {
                if (!strpos($keys, '::')) {
                    $andFilters[][$keys] = $values;
                } else {
                    $op = explode('::', $keys);
                    $andFilters[][$op[0]] = [$this->op[$op[1]] => $values];
                }
            }
        }
        return ['$and' => $andFilters, '$or' => $orFilters];
    }

    private function _result($cursor)
    {
        $result = [];
        foreach ($cursor as $document) {
            $result[] = get_object_vars($document);
        }
        return $result;
    }
}
