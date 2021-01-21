<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/21 0021
 * Time: 11:35
 */

namespace app\api\controller;


use app\admin\model\Auser;
use app\common\controller\Api;
use think\Db;
/*
 * 财务相关记录数据
 * */
class Finance extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    private $_uid;
    public function _initialize()
    {
        parent::_initialize();
        $jwt = $this->request->header('Authorization');
        if($jwt){
            $this->check_token($jwt);
            $this->_uid = $this->_token['uid'];
        }else{
            $this->success('缺少token','','401');
        }
    }

    /*
     * 待签约(下级人员购买了pos机, 但是有pos机还没有激活 , 把这些用户列出来)
     * */
    public function sons()
    {
        $uid = $this->_uid;
        $datas = Db::name('auser')
            ->alias('u')
            ->join('agoods_sn s','s.ac_id = u.id')
            ->where('u.pid',$uid)
            ->where('s.status','=','0')
            ->field("u.id,u.mobile,u.indent_name as name,FROM_UNIXTIME(u.ctime,'%Y-%m-%d %H:%i:%s') as ctime,u.avatar")
            ->group('u.id')
            ->select()->each(function($item){
                if ( substr($item['avatar'],0,3) != 'http' ){
                    $item['avatar'] = IMG.$item['avatar'];
                }
                return $item;
            });
        $this->success('',$datas);
    }

    /*
     * 某个直属下级的财务记录
     * */
    public function sons_detail()
    {

    }

    /*
     * 待装机(下级购买器具,待划拨)
     * */
    public function wait_set()
    {

    }

    /*
     * 待签约(我的下级 还没有购买机具)
     * */
    public function wati_sign()
    {

    }

    /*
     * 给下级划拨器具
     * */
    public function give()
    {

    }

    /*
     * 入库记录
     * */
    public function inrec()
    {

    }

    /*
     * 机具查询
     * */
    public function findpos()
    {

    }
}

