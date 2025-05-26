<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

class Db
{
    use Utiltrait;

    private $connectDb = 'db';

    private $writeClient;
    private $readClient;
    /**
     * @var App
     */
    private $app;

    private static $instance;

    /**
     * @param App $app
     * @return Db
     * @author LCF
     * @date
     */
    public static function instance(App $app)
    {
        if (empty(self::$instance)) {
            self::$instance = new self($app);
        }
        return self::$instance;
    }


    /**
     * @param $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $dbKey
     * @return $this
     * @author LCF
     * @date
     */
    public function connect($dbKey = 'db')
    {
        $config = $this->app->config()[$dbKey];
        $separate = isset($config['separate']) ? $config['separate'] : false;
        if (true === $separate) {
            $this->readClient = $this->app->dbInstance($config['read_db'], 'linker_read');
            $this->writeClient = $this->app->dbInstance($config);
        } else {
            $this->readClient = $this->writeClient = $this->app->dbInstance($config);
        }
        $this->connectDb = $dbKey;
        return $this;
    }

    /**
     * @param bool $flag
     * @return DbInterface
     * @author LCF
     * @date 2020/1/18 21:07
     */
    private function connectDb($flag = true)
    {
        $this->connect($this->connectDb);
        return $flag ? $this->readClient : $this->writeClient;
    }

    /**
     * @return DbInterface
     * @author LCF
     * @date 2020/1/18 21:06
     */
    private function readClient()
    {
        if ($this->readClient) {
            return $this->readClient;
        }
        return $this->connectDb();
    }

    /**
     * @return DbInterface
     * @author LCF
     * @date 2020/1/18 21:06
     */
    private function writeClient()
    {
        if ($this->writeClient) {
            return $this->writeClient;
        }
        return $this->connectDb(false);
    }

    /**
     * @param bool $isWrite
     * @return DbInterface
     * @author LCF
     * @date 2019/10/26 16:49
     */
    public function db($isWrite = false)
    {
        return $isWrite === true ? $this->writeClient()->db() : $this->readClient()->db();
    }

    public function clientInfo()
    {
        return $this->writeClient()->clientInfo();
    }

    public function serverInfo()
    {
        return $this->writeClient()->serverInfo();
    }

    public function getLastSql($flag = true)
    {
        $sql = $this->writeClient()->getLastSql($flag);
        if (empty($sql)) {
            $sql = $this->readClient()->getLastSql($flag);
        }
        return $sql;
    }

    public function insert($table, $data)
    {
        return $this->writeClient()->insert($table, $data);
    }

    public function updateId($table, $id, $data)
    {
        return $this->writeClient()->updateId($table, 'id', $id, $data);
    }

    public function deleteId($table, $id)
    {
        return $this->writeClient()->deleteId($table, 'id', $id);
    }

    public function update($table, $data, $where)
    {
        return $this->writeClient()->update($table, $data, $where);
    }

    public function delete($table, $where)
    {
        return $this->writeClient()->delete($table, $where);
    }

    public function selectId($table, $id, $field = ['*'])
    {
        return $this->readClient()->selectId($table, 'id', $id, $field);
    }

    public function selectOne($table, $where, $order = [], $field = ['*'])
    {
        return $this->readClient()->selectOne($table, $where, $order, $field);
    }

    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $field = ['*'])
    {
        return $this->readClient()->selectAll($table, $order, $offset, $fetchNum, $field);
    }

    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $field = ['*'])
    {
        return $this->readClient()->selects($table, $where, $order, $offset, $fetchNum, $field);
    }

    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0)
    {
        return $this->readClient()->selectIn($table, $field, $inWhere, $where, $order, $offset, $fetchNum, $field);
    }

    public function query($sql, $param = [])
    {
        if (stripos(trim($sql), 'select') === 0) {
            return $this->readClient()->query($sql, $param);
        }
        return $this->writeClient()->query($sql, $param);
    }

    public function like($table, $stringName, $content, $where = [], $isCount = false, $order = [], $offset = 0, $fetchNum = 0, $direction = 'in', $field = ['*'])
    {
        return $this->readClient()->like($table, $stringName, $content, $where, $isCount, $order, $offset, $fetchNum, $direction, $field);
    }

    public function count($table, $where = [], $columnName = '*', $distinct = false)
    {
        return $this->readClient()->count($table, $where, $columnName, $distinct);
    }

    public function min($table, $columnName, $where = [])
    {
        return $this->readClient()->min($table, $columnName, $where);
    }

    public function max($table, $columnName, $where = [])
    {
        return $this->readClient()->max($table, $columnName, $where);
    }

    public function avg($table, $columnName, $where = [])
    {
        return $this->readClient()->avg($table, $columnName, $where);
    }

    public function sum($table, $columnName, $where = [])
    {
        return $this->readClient()->sum($table, $columnName, $where);
    }

    public function insertMultiple($table, $multipleInsertData, $keys = [])
    {
        return $this->writeClient()->insertMultiple($table, $multipleInsertData, $keys);
    }

    public function beginTransaction()
    {
        return $this->writeClient()->beginTransaction();
    }

    public function commitTransaction()
    {
        return $this->writeClient()->commitTransaction();
    }

    public function rollbackTransaction()
    {
        return $this->writeClient()->rollbackTransaction();
    }

    public function close()
    {
        $this->writeClient()->close();
        $this->readClient()->close();
    }
}
