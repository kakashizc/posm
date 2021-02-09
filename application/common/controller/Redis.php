<?php

namespace app\common\controller;

//redis单利类
class Redis
{
    private static $instance;
    private $redis;
    //公有方法，用于获取实例
    public static function getInstance(){
        //判断实例有无创建，没有的话创建实例并返回，有的话直接返回
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    //构造方法私有化，防止外部创建实例
    private function __construct(){
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1','6379');
        if(config('redis.password') != ''){
            $this->redis->auth(config('redis.password'));
        }
    }
    //克隆方法私有化，防止复制实例
    private function __clone(){

    }
    //外部调用redis连接 用这个就行了
    public function getRedisConn ()
    {
        return $this->redis;
    }
    /**
     * 需要在单例切换的时候做清理工作
     */
    public function __destruct ()
    {
        $this->redis->close();
        $this->redis = NULL;
    }

}