<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/21 0021
 * Time: 17:00
 */

namespace app\api\controller;


use app\admin\model\Level;
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
use app\admin\model\Feed;
use think\Config;
/*
 * 测试类
 * */
class Teet extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function aaa()
    {
        $userinfo = Auser::get(3);
        $sons = Auser::where(['pid'=>3,'reback'=>'1'])->count();
        if ( $sons >= 3 ){
            //可以升级
            $v2 = Level::get(['name' => 'V2']);
            $userinfo->level_id = $v2->id;
            $userinfo->save();
        }

    }


    //计算出包含假期 周末等等的下一个工作日
    public function abc($date = '2021-02-09'){
        //下一天时间
        $after =  date("Y-m-d",(strtotime($date) + 3600*24));
        $holidays = Config::get('holiday');
        foreach ($holidays as $k=>$v){
            $start_day = $v[0];//某个假期开始日期
            $end_day = $v[1];//某个假期结束日期

            //是不是节假日的前一天
            if ( $after == $start_day ){
                //开始时间加上节假日的天数的下一天就是下一个工作日
                return date("Y-m-d",(strtotime($start_day) + 3600*24*$v[2]));
            }elseif ($this->in($date,$start_day,$end_day)){//是不是在节假日当中,如果在节假日当中,就获取最后一天+1就是下一个工作日
                return date("Y-m-d",(strtotime($end_day) + 3600*24));
            }
        }
        //如果不是以上两种情况 , 正常判断周末就行
        $last = date('Y-m-d', strtotime($date . ' +1 Weekday'));
        //判断一下后面周日是否是节假日开始那天
        foreach ($holidays as $k=>$v){
            $start_day = $v[0];//某个假期开始日期
            $end_day = $v[1];//某个假期结束日期
            if ($this->in($last,$start_day,$end_day)){//是不是在节假日当中,如果在节假日当中,就获取最后一天+1就是下一个工作日
                return date("Y-m-d",(strtotime($end_day) + 3600*24));
            }
        }
        return strtotime($last);
        //如果想把调休的那天也算成工作日, 那么定义一个调休数组,将这里的返回值放入那个数组中循环判断一下
        //下一天是否是调休日,如果下一天是的话 就返回下一天就行,这里就不做判断了.
    }

    public function in($date,$start,$end){
        //判断某个日期,是否在两个日期当中
        $date = strtotime($date);

        $start = strtotime($start);
        $end = strtotime($end);
        if($start <= $date && $date <= $end) {
            return 1;//在其中
        }else{
            return 0;//不再其中
        }
    }
    /*
     * 今日收益
     * */
    public function today()
    {
        $uid = 3;
        $date = $this->request->param('date')??'1';//根据日期查询 1-当天 7-近一周 30-本月 100-全部
        $page = $this->request->param('page')??1;
        $num = $this->request->param('num')??5;
        switch ($date){
            case '1':
                $stime = $this->get_stime(1); //今天开始时间戳
                break;
            case '7':
                $stime = $this->get_stime(7); //7天前的0点
                break;
            case '30':
                $stime = $this->get_stime(30); //7天前的0点
                break;
            case '100':
                $stime = '1609430400'; //2021-01-01 00:00:00
                break;
        }
        $etime = time();//今天结束时间戳
        //1,先查询总收益
        $data['today_all'] = Feed::where('u_id',$uid)->whereTime('ctime',[$stime,$etime])->sum('money');
        //2,查询每一条收益
        $data['record'] = Feed::where(['u_id'=>$uid])->field('id,money,status,ctime')
            ->whereTime('ctime',[$stime,$etime])
            ->page($page,$num)
            ->select();
        if ( sizeof($data['record']) == 0){
            $this->success('无数据','','1');
        }

        foreach ($data['record'] as  $k=>$v){
            if ($v['status'] == 1){//收益类型,1=下级刷卡返现,2=本人刷卡收益
                $data['fanxian'][] = $v;
            }else{
                $data['shuaka'][] = $v;
            }
        }
        unset($data['record']);
        $this->success('成功',$data,'0');
    }
    /**
     * get_some_day  获取n天前0点的时间戳
     * @param int $some n天
     * @param null $day 当前时间
     * @return int|null
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    private function get_stime($some = 1, $day = null){
        $time = $day ? $day : time();
        $some_day = $time - 3600 * 24 * $some;
        $btime = date('Y-m-d' . ' 00:00:00', $some_day);
        $some_day = strtotime($btime);
        return $some_day;
    }
    public function inrec()
    {
        $uid = 12;
        $data = Db::name('agoods_sn_record')
            ->alias('r')
            ->join('auser u','u.id = r.op_id')
            ->where('r.u_id',$uid)
            ->field("u.indent_name as name , r.no , FROM_UNIXTIME(r.time,'%Y-%m-%d %H:%i:%s') as ctime")
            ->select()->each(function ($item) {
                $arr = explode(',',$item['no']);
                foreach ($arr as $k=>$v){
                    $item[$k]['sn'] = $v;
                    $item[$k]['time'] = $item['ctime'];
                    $item[$k]['name'] = $item['name'];
                }
                unset($item['name']);
                unset($item['no']);
                unset($item['ctime']);
                return $item;
            });
        $arr = [];
        foreach ($data as $k=>$v){
            $arr = array_merge($arr,$v);
        }
        if (sizeof($data) > 0){

            $this->success('成功',($arr),'0');
        }else{
            $this->success('无数据,请联系平台购买,划拨','','1');
        }
    }
    public function inrec_one()
    {
        $uid = 12;
        $sn = $this->request->param('sn');
        $where = "FIND_IN_SET('$sn',no)";
        $data = Db::name('agoods_sn_record')
            ->where($where)
            ->where('u_id',$uid)
            ->find();
        if ($data){
            $parent = Auser::get($data['op_id']);
            $ret['name'] = $parent->indent_name;
            $ret['sn'] = $sn;
            $ret['time'] = date('Y-m-d H:i:s',$data['time']);
            $this->success('成功',$ret,'0');
        }else{
            $this->success('无此sn号',[],'1');
        }

    }

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
        $uid = 3;
        $users = Auser::all(function ($list) use ($uid){
            $list->field('id,mobile,indent_name as name,avatar,ctime,nickName')->where('pid',$uid);
        })->each(function ($item){
            $item['money'] = Feed::where('date_m',date('Y-m',time()))->sum('money');
            $item['sons'] = Auser::where( ['pid'=>$item['id']] )->count('id');
            if ( substr($item['avatar'],0,4) != 'http' ){
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
    * 机具查询
    * 查询我名下所有的pos机器, 包括 未激活 已激活的
    * */
    public function findpos()
    {
        $uid = 3;
        $status = $this->request->param('status');
        $arr = [0,1,2];
        if ($status == 1){
            $arr = [1,2];
        }elseif ($status == 0){
            $arr = [0];
        }
        $data = AgoodsSn::all(function ($list) use ($uid,$arr){
            $list->where('u_id',$uid)->whereIN('status',$arr)->field('sn,status');
        });
        if (sizeof($data) > 0){
            $this->success('成功',$data,'0');
        }else{
            $this->success('无数据,请联系平台购买,划拨','','1');
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