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