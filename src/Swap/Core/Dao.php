<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

abstract class Dao
{
    use Utiltrait;

    private $tabName;

    private $writeClient;
    private $readClient;
    /**
     * @var App
     */
    private $app;

    /**
     * @param $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }


    /**
     * @return string
     * @author LCF
     * @date
     * 设置链接
     */
    public function setConnect()
    {
        return 'db';
    }

    /**
     * @return false|string
     * @author LCF
     * @date
     * 表名处理
     */
    public function tabName()
    {
        if ($this->tabName) {
            return $this->tabName;
        }
        $name = str_replace('\\', '/', static::class);
        $name = basename($name);
        $newName = strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), '_'));
        $this->tabName = substr($newName, 0, strrpos($newName, '_dao'));
        return $this->tabName;
    }


    /**
     * @return string
     * @author LCF
     * @date
     * 默认主键字段
     */
    public function defaultId()
    {
        return 'id';
    }

    /**
     * @return array
     * @author LCF
     * @date
     * 表字段
     */
    public function fieldArr()
    {
        return ['*'];
    }

    /**
     * @param bool $flag
     * @return DbInterface
     * @author LCF
     * @date 2020/1/18 21:07
     */
    private function connect($flag = true)
    {
        $config = $this->app->config()[$this->setConnect()];
        $separate = isset($config['separate']) ? $config['separate'] : false;
        if (true === $separate) {
            $this->readClient = $this->app->dbInstance($config['read_db'], 'linker_read');
            $this->writeClient = $this->app->dbInstance($config);
        } else {
            $this->readClient = $this->writeClient = $this->app->dbInstance($config);
        }
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
        return $this->connect();
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
        return $this->connect(false);
    }

    /**
     * @return DbInterface
     * @author LCF
     * @date 2019/10/26 16:49
     * 读写分离尽量别用该方法
     */
    public function db()
    {
        return $this->writeClient()->db();
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

    public function insert($data)
    {
        return $this->writeClient()->insert($this->tabName(), $data);
    }

    public function updateId($id, $data)
    {
        return $this->writeClient()->updateId($this->tabName(), $this->defaultId(), $id, $data);
    }

    public function deleteId($id)
    {
        return $this->writeClient()->deleteId($this->tabName(), $this->defaultId(), $id);
    }

    public function update($data, $where)
    {
        return $this->writeClient()->update($this->tabName(), $data, $where);
    }

    public function delete($where)
    {
        return $this->writeClient()->delete($this->tabName(), $where);
    }

    public function selectId($id)
    {
        return $this->readClient()->selectId($this->tabName(), $this->defaultId(), $id, $this->fieldArr());
    }

    public function selectOne($where, $order = [])
    {
        return $this->readClient()->selectOne($this->tabName(), $where, $order, $this->fieldArr());
    }

    public function selectAll($order = [], $offset = 0, $fetchNum = 0)
    {
        return $this->readClient()->selectAll($this->tabName(), $order, $offset, $fetchNum, $this->fieldArr());
    }

    public function selects($where = [], $order = [], $offset = 0, $fetchNum = 0)
    {
        return $this->readClient()->selects($this->tabName(), $where, $order, $offset, $fetchNum, $this->fieldArr());
    }

    public function selectIn($field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0)
    {
        return $this->readClient()->selectIn($this->tabName(), $field, $inWhere, $where, $order, $offset, $fetchNum, $this->fieldArr());
    }

    public function query($sql, $param = [])
    {
        if (stripos(trim($sql), 'select') === 0) {
            return $this->readClient()->query($sql, $param);
        }
        return $this->writeClient()->query($sql, $param);
    }

    public function like($stringName, $content, $where = [], $isCount = false, $order = [], $offset = 0, $fetchNum = 0, $direction = 'in')
    {
        return $this->readClient()->like($this->tabName(), $stringName, $content, $where, $isCount, $order, $offset, $fetchNum, $direction, $this->fieldArr());
    }

    public function count($where = [], $columnName = '*', $distinct = false)
    {
        return $this->readClient()->count($this->tabName(), $where, $columnName, $distinct);
    }

    public function min($columnName, $where = [])
    {
        return $this->readClient()->min($this->tabName(), $columnName, $where);
    }

    public function max($columnName, $where = [])
    {
        return $this->readClient()->max($this->tabName(), $columnName, $where);
    }

    public function avg($columnName, $where = [])
    {
        return $this->readClient()->avg($this->tabName(), $columnName, $where);
    }

    public function sum($columnName, $where = [])
    {
        return $this->readClient()->sum($this->tabName(), $columnName, $where);
    }

    public function insertMultiple($multipleInsertData, $keys = [])
    {
        return $this->writeClient()->insertMultiple($this->tabName(), $multipleInsertData, $keys);
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
