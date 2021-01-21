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
     * 我的下级人员列表(app中的 通讯录)
     * */
    public function sons()
    {
        $uid = $this->_uid;
        $users = Auser::all(function ($list) use ($uid){
            $list->field('id,mobile,indent_name as name,avatar,ctime')->where('pid',$uid);
        })->each(function ($item){
            if ( substr($item['avatar'],0,3) != 'http' ){
                $item['avatar'] = IMG.$item['avatar'];
            }
            return $item;
        });
        if ($users){
            $this->success('成功',$users,'0');
        }else{
            $this->success('无下级人员','','1');
        }
    }

    /*
     * 某个直属下级的财务记录
     * */
    public function sons_detail()
    {

    }

    /*
     * 待装机(下级购买了 pos机,待上级划拨)
     * */
    public function wait_set()
    {
        $uid = $this->_uid;
        $son_ids = Auser::where('pid',$uid)->column('id');

        //查找我的下级购买了pos机 但是未划拨的 用户id  (订单状态不等于 5 的, 也就是未划拨的我的下级用户id)
        $order_sons = AOrder::where('u_id','IN',$son_ids)->where('status','NEQ','5')->column('u_id');

        $users = Auser::all(function ($list) use ($order_sons){
            $list->field('id,mobile,indent_name as name,avatar,ctime')->where('id','IN',$order_sons);
        })->each(function ($item){
            if ( substr($item['avatar'],0,3) != 'http' ){
                $item['avatar'] = IMG.$item['avatar'];
            }
            return $item;
        });
        if ($users){
            $this->success('成功',$users,'0');
        }else{
            $this->success('无','','1');
        }
    }

    /*
     * 待签约(下级人员购买了pos机, 但是有pos机还没有激活 , 把这些用户列出来)
     * */
    public function wati_sign()
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
        if ($datas){
            $this->success('成功',$datas,'0');
        }else{
            $this->success('无','','1');
        }
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

