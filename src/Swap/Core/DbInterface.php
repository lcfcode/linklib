<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;
/**
 * Class MysqliClass
 * @package Swap\Utils
 * 原本设计保留单例，现在也满足单例情况，需要自行改写
 */
interface DbInterface
{
    /**
     * @return mixed
     * @author LCF
     * @date
     * 返回原生链接
     */
    public function db();

    /**
     * @return string
     * @author LCF
     * @date 2019/9/24 10:06
     * 客户端信息
     */
    public function clientInfo();

    /**
     * @return string
     * @author LCF
     * @date 2019/9/24 10:06
     * 服务端信息
     */
    public function serverInfo();

    /**
     * @param bool $flag
     * @return array|mixed|string
     * @author LCF
     * @date 2019/8/17 18:37
     * 返回最后一条执行的sql语句
     */
    public function getLastSql($flag = true);

    /**
     * @param $table
     * @param $data
     * @return mixed
     * @author LCF
     * @date
     * 数据插入
     */
    public function insert($table, $data);

    /**
     * @param $table
     * @param $idField
     * @param $id
     * @param $data
     * @return mixed
     * @author LCF
     * @date
     * 根据id更新
     */
    public function updateId($table, $idField, $id, $data);

    /**
     * @param $table
     * @param $idField
     * @param $id
     * @return mixed
     * @author LCF
     * @date
     * 根据id删除
     */
    public function deleteId($table, $idField, $id);

    /**
     * @param $table
     * @param $data
     * @param $where
     * @return mixed
     * @author LCF
     * @date
     * 更新操作
     */
    public function update($table, $data, $where);

    /**
     * @param $table
     * @param $where
     * @return mixed
     * @author LCF
     * @date
     * 删除
     */
    public function delete($table, $where);

    /**
     * @param $table
     * @param $idField
     * @param $id
     * @param array $getInfo
     * @return mixed
     * @author LCF
     * @date
     * 根据id查询
     */
    public function selectId($table, $idField, $id, $getInfo = ['*']);

    /**
     * @param $table
     * @param $where
     * @param array $order
     * @param array $getInfo
     * @return array|mixed
     * @author LCF
     * @date 2019/8/17 21:32
     * 查询单条数据，一般用于登录类型的
     */
    public function selectOne($table, $where, $order = [], $getInfo = ['*']);

    /**
     * @param $table
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return array
     * @author LCF
     * @date 2019/8/17 21:33
     * 查询所有数据
     */
    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

    /**
     * @param $table
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return array
     * @author LCF
     * @date 2019/8/17 21:33
     * selectAll 方法 添加条件
     */
    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

    /**
     * @param $table
     * @param $field
     * @param $inWhere array 给子的需要数组
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return mixed
     * @author LCF
     * @date
     * wherein
     */
    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

    /**
     * @param $sql
     * @param array $param
     * @return array|bool|int
     * @author LCF
     * @date 2019/8/17 21:35
     * 执行sql语句,此处参数绑定注意参数顺序
     */
    public function query($sql, $param = []);

    /**
     * @param $table
     * @param $stringName
     * @param $content
     * @param array $where
     * @param bool $isCount
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param string $direction
     * @param array $getInfo
     * @return array
     * @author LCF
     * @date 2019/8/17 21:36
     * like语句
     */
    public function like($table, $stringName, $content, $where = [], $isCount = false, $order = [], $offset = 0, $fetchNum = 0, $direction = 'in', $getInfo = ['*']);

    /**
     * @param $table
     * @param $multiInsertData
     * @param array $keys
     * @return bool|int
     * @author LCF
     * @date 2019/8/17 21:36
     * 多条语句执行插入方法
     */
    public function insertMultiple($table, $multiInsertData, $keys = []);

    /**
     * @param $table
     * @param array $where
     * @param string $columnName
     * @param bool $distinct
     * @return mixed
     * @author LCF
     * @date
     * 统计
     */
    public function count($table, $where = [], $columnName = '*', $distinct = false);

    /**
     * @param $table
     * @param $columnName
     * @param array $where
     * @return mixed
     * @author LCF
     * @date
     * 最小值
     */
    public function min($table, $columnName, $where = []);

    /**
     * @param $table
     * @param $columnName
     * @param array $where
     * @return mixed
     * @author LCF
     * @date
     * 最大值
     */
    public function max($table, $columnName, $where = []);

    /**
     * @param $table
     * @param $columnName
     * @param array $where
     * @return mixed
     * @author LCF
     * @date
     * 平均值
     */
    public function avg($table, $columnName, $where = []);

    /**
     * @param $table
     * @param $columnName
     * @param array $where
     * @return mixed
     * @author LCF
     * @date
     */
    public function sum($table, $columnName, $where = []);

    /**
     * @return mixed
     * @author LCF
     * @date
     * 开启事务
     */
    public function beginTransaction();

    /**
     * @return mixed
     * @author LCF
     * @date
     * 提交事务
     */
    public function commitTransaction();

    /**
     * @return mixed
     * @author LCF
     * @date
     * 回滚事务
     */
    public function rollbackTransaction();

    /**
     * @return mixed
     * @author LCF
     * @date
     * 关闭链接
     */
    public function close();
}