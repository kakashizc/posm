<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/21 0021
 * Time: 17:00
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\admin\model\AgoodsSn;
use app\admin\model\Notice;
use app\admin\model\NoticeGg;
use app\admin\model\Agoods;
use fast\Http;
use think\Db;
use think\Request;
use app\admin\model\Auser;
use app\admin\model\Order as AOrder;

/*
 * 测试类
 * */
class Teet extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 测试:用户获取全部订单
     * */
    public function orders()
    {
        $status = $this->request->param('status')??5;//订单状态:0=未付款,1=待发货,2=已发货,3=已收货,4=已失效,5=全部订单
        if ($status != 5){
            $where = ['order.status'=>$status];
        }else{
            $where = '';
        }

        $datas = collection(AOrder::with(['agoods'])
            ->where('u_id',1)
            ->where($where)
            ->select())
            ->toArray();

        if ( !$datas ){
            $this->success('无数据','','1');
        }
        foreach ($datas as &$v){
            $v['agoods']['image'] = IMG. $v['agoods']['image'];
        }
        $this->success('成功',$datas,'0');
    }
    /*
    * 测试 我的通讯录(直属下级)
    * */
    public function sons()
    {
        $uid = 0;
        $users = Auser::all(function ($list) use ($uid){
            $list->field('id,mobile,indent_name as name,avatar,ctime,nickName')->where('pid',$uid);
        })->each(function ($item){
            if ( substr($item['avatar'],0,3) != 'http' ){
                $item['avatar'] = IMG.$item['avatar'];
            }
            return $item;
        });
        if (sizeof($users) > 0){
            $this->success('成功',$users,'0');
        }else{
            $this->success('无下级人员','','1');
        }
    }
    /*
     * 待装机(下级购买了 pos机,待上级划拨)
     * */
    public function wait_set()
    {
        $uid = 0;
        $son_ids = Auser::where('pid',$uid)->column('id');

        //查找我的下级购买了pos机 但是未划拨的 用户id  (订单状态不等于 5 的, 也就是未划拨的我的下级用户id)
        $order_sons = AOrder::where('u_id','IN',$son_ids)
            ->where('status','IN',[1,2,3,4])
            ->column('u_id');

        $users = Auser::all(function ($list) use ($order_sons){
            $list->field('id,mobile,indent_name as name,avatar,ctime')->where('id','IN',$order_sons);
        })->each(function ($item){
            if ( substr($item['avatar'],0,3) != 'http' ){
                $item['avatar'] = IMG.$item['avatar'];
            }
            return $item;
        });
        $this->success($users);
    }

    /*
     * 我的下级人员列表
     * */
    public function son()
    {
        $uid = 0;
        $users = Auser::all(function ($list) use ($uid){
            $list->field('id,mobile,indent_name as name,avatar,ctime')->where('pid',$uid);
        })->each(function ($item){
            if ( substr($item['avatar'],0,3) != 'http' ){
                $item['avatar'] = IMG.$item['avatar'];
            }
            return $item;
        });
        $this->success('',$users);
    }

    /*
     * 获取机具
     *
     * */
    public function goodList()
    {
        $list = Agoods::all(function ($query){
            $query->where('status','1')->field("id,name,price,factory,type,concat('$this->img',image) as image");
        });
        if ($list){
            $this->success('成功',$list,'0');
        }else{
            $this->success('无机具','','1');
        }
    }

}