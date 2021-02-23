<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/21 0021
 * Time: 11:35
 */

namespace app\api\controller;


use app\admin\model\Auser;
use app\admin\model\Level;
use app\common\controller\Api;
use think\Db;
use app\admin\model\AgoodsSn;
use app\admin\model\Notice;
use app\admin\model\NoticeGg;
use app\admin\model\Agoods;
use fast\Http;
use think\Request;
use app\admin\model\Order as AOrder;
use think\Exception;
use app\admin\model\Feed;

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
            $list->field('id,mobile,indent_name as name,avatar,ctime,nickName,level_id')->where('pid',$uid);
        })->each(function ($item){
            $item['money'] = Feed::where('date_m',date('Y-m',time()))->sum('money');
            $item['sons'] = Auser::where( ['pid'=>$item['id']] )->count('id');
            $item['vip'] = Level::where( ['id'=>$item['level_id']] )->value('name');
            if ($item['name'] == null) $item['name'] = $item['nickName'];
            if ( substr($item['avatar'],0,4) != 'http' ){
                $item['avatar'] = IMG.$item['avatar'];
            }
            if ( $item['ctime'] > strtotime(date("Y-m-d"),time()) ){
                $item['new'] = '1';
            }else{
                $item['new'] = '0';
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
     * 查询我的下级人员信息
     * */
    public function select_son()
    {
        $uid = $this->_uid;
        $keywords = $this->request->param('words');
        if ( preg_match("/^1[345789]{1}\d{9}$/",$keywords) ) {
            //根据手机号查询
            $where = ['mobile'=>$keywords];
        }else{
            $where = ['indent_name'=>$keywords];
        }
        $users = Auser::get(function ($list) use ($uid,$where){
            $list->field('id,mobile,indent_name as name,avatar,ctime,nickName,level_id')
                ->where($where)
                ->where('pid',$uid);
        });
        if (!$users) $this->success('无此人','','1');
        $users['money'] = Feed::where('date_m',date('Y-m',time()))->sum('money');
        $users['sons'] = Auser::where( ['pid'=>$users->id] )->count('id');
        $users['vip'] = Level::where( ['id'=>$users->level_id] )->value('name');
        if ( substr($users->avatar,0,4) != 'http' ){
            $users->avatar = IMG.$users->avatar;
        }
        if ($users){
            $data[0] = $users;
            $this->success('成功',$data,'0');
        }else{
            $this->success('无下级人员','','1');
        }
    }

    /*
     * 余额明细
     * */
    public function feed_detail()
    {
        $uid = $this->_uid;
        $month = $this->request->param('month');
        $page = $this->request->param('page')??1;
        $num = $this->request->param('num')??5;
        // 用户总余额, 总返佣收益 , 总刷卡收益   余额明细单
        $yue = Auser::get($uid);
        if (!$yue)$this->success('无此用户','','1');
        //查询预计收益 1, 88元待返机具款 2, 活动营销奖励 连续5个月每个月交易额满3万元,第六个月给50元
        $eight = $yue->reback==0?88:0;
        $fifty = $yue->mprice==0?50:0;
        $data['pre'] = $eight + $fifty;//预计收益
        $data['money'] = $yue->money;
        $data['back'] = Feed::where('u_id',$uid)->where('status','1')->sum('money');
        $data['card'] = Feed::where('u_id',$uid)->where('status','2')->sum('money');
        $data['record'] = Feed::with(['sons'=>function($list)use($uid,$month,$page,$num){
            if(!$month){
                $where = [];
            }else{
                $where = ['date_m'=>$month];
            }
            $list->where(['u_id'=>$uid])->where($where);
        }])->page($page,$num)->select()->each(function ($item){
            if ( substr($item['sons']['avatar'],0,4) != 'http' ){
                $item['sons']['avatar'] = IMG.$item['sons']['avatar'];
            }
            //下级信息
            return $item;
        });
        $this->success('成功',$data,'0');
    }
    /*
     * 今日收益
     * */
    public function today()
    {
        $uid = $this->_uid;
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
                $stime = $this->get_stime(30); //30天前的0点
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

    /*
     * 待装机(下级购买了 pos机,待上级划拨)
     * */
    public function wait_set()
    {
        $users = self::son_ungive($this->_uid);
        if ($users){
            $this->success('成功',$users,'0');
        }else{
            $this->success('无','','1');
        }
    }
    /*
     * 待装机,根据手机号/名字 单个搜索(下级购买了 pos机,待上级划拨)
     * */
    public function wait_set_sel()
    {
        $select = $this->request->param('select');
        $users = self::son_ungive($this->_uid,$select);
        if ($users){
            $this->success('成功',$users,'0');
        }else{
            $this->success('无','','1');
        }
    }

    /*
     * 待签约(下级人员购买了pos机, 也划拨了, 但是有pos机还没有进行第一次刷卡未激活 , 把这些用户列出来)
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
                if ( substr($item['avatar'],0,4) != 'http' ){
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
     * 给下级划拨机具 type = 1 , 2
     * 1,区间划拨
     * 2,选择某些机具sn码 进行划拨
     * */
    public function give()
    {
        $hid = $this->request->param('hid');//被划拨人id
        $type = $this->request->param('type');
        Db::startTrans();
        $time = time();
        try{
            if ($type == 1){ //区间划拨
                $start = $this->request->param('start');
                $end = $this->request->param('end');
                if ($start == $end){
                    //选择了一个终端
                    $count = 0;
                }else{
                    $count = bcsub($end,$start);
                }
                //插入sn表,并绑定u_id
                    for ($i=0;$i<=$count;$i++){
                        if ($i == 0) {
                            $sn = $start;
                        }elseif ($i == $count){
                            $sn = $end;
                        }else{
                            $sn = bcadd( $start,"$i");
                        }
                        $up['u_id'] = $hid;
                        $up['ctime'] = $time;
                        $res = Db::name('agoods_sn')->where('sn',$sn)->update($up);
                    }
                    if ($res){
                        //插入一条划拨机具的记录
                        self::insert_rec(1);
                        Db::commit();
                        $this->success('划拨成功','','0');
                    }else{
                        Db::rollback();
                        $this->error("划拨失败",'','1');
                    }

            }else{//固定的sn号码划拨
                $sn_str = $this->request->param('sn_arr');//选中机具sn的数组
                $sn_arr = explode(',',$sn_str);
                //更新sn表,并绑定新的u_id
                $time = time();
                foreach ($sn_arr as $k=>$v){
                    $up['u_id'] = $hid;
                    $up['ctime'] = $time;
                    $res = Db::name('agoods_sn')->where('sn',$v)->update($up);
                }
                if ($res){
                    //插入一条划拨机具的记录
                    self::insert_rec(2);
                    Db::commit();
                    $this->success('划拨成功','','0');
                }else{
                    Db::rollback();
                    $this->error("划拨失败",'','1');
                }
            }
        }catch (Exception $exception){
            Db::rollback();
            $this->success('失败:'.$exception->getMessage(),'','1');
        }
    }

    /*
    * 给下级划拨之前,查找我已经拥有的sn码
    * */
    public function my_sn()
    {
        $uid = $this->_uid;
        $data = AgoodsSn::where(['u_id'=>$uid,'status'=>'0'])
            ->where('ac_id',null)
            ->column('sn');
        if (sizeof($data) > 0){
            $this->success('成功',$data,'0');
        }else{
            $this->success('无数据,请联系平台购买,划拨','','1');
        }
    }

    /*
     * 入库记录
     * 获取我的pos机 上级给我划拨机具的记录
     * */
    public function inrec()
    {
        $uid = $this->_uid;
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
    /*
     * 入库记录,单个sn号码查询
     * */
    public function inrec_one()
    {
        $uid = $this->_uid;
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
     * 机具查询
     * 查询我名下所有的pos机器, 包括 未激活 已激活的
     * */
    public function findpos()
    {
        $uid = $this->_uid;
        $status = $this->request->param('status');
        if ($status == null){
            $this->success('缺少参数','','1');
        }
        if ($status == 1){
            $arr = [1,2];
        }elseif ($status == 0){
            $arr = [0];
        }else{
            $arr = [0,1,2];
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
     * 我的下级  购买未划拨机具的人员列表
     * */
    private static function son_ungive($uid,$select='')
    {

        if ($select == ''){
            $son_ids = Auser::where('pid',$uid)->column('id');

            //查找我的下级购买了pos机 但是未划拨的 用户id  (订单状态不等于 5 的, 也就是未划拨的我的下级用户id)
            $order_sons = AOrder::where('u_id','IN',$son_ids)->where('status','IN',[1,2,3,4])->column('u_id');

            $users = Auser::all(function ($list) use ($order_sons){
                $list->field('id,mobile,indent_name as name,avatar,ctime')->where('id','IN',$order_sons);
            })->each(function ($item){
                if ( substr($item['avatar'],0,4) != 'http' ){
                    $item['avatar'] = IMG.$item['avatar'];
                }
                return $item;
            });
            if (sizeof($users) > 0){
                return $users;
            }else{
                return false;
            }
        }else{
            if ( preg_match("/^1[345789]{1}\d{9}$/",$select) ) {
                //根据手机号查询
                $where = ['mobile'=>$select];
            }else{
                $where = ['indent_name'=>$select];
            }
            $son_id = Auser::where($where)->value('id');
            if (!$son_id){
                return false;
            }
            //查找我的下级购买了pos机 但是未划拨的 用户id  (订单状态不等于 5 的, 也就是未划拨的我的下级用户id)
            $order_son = AOrder::where('u_id','EQ',$son_id)->where('status','IN',[1,2,3,4])->value('u_id');
            if (!$order_son){
                return false;
            }
            $users = Auser::all(function ($list) use ($order_son){
                $list->field('id,mobile,indent_name as name,avatar,ctime')->where('id','eq',$order_son);
            })->each(function ($item){
                if ( substr($item['avatar'],0,4) != 'http' ){
                    $item['avatar'] = IMG.$item['avatar'];
                }
                return $item;
            });
            return $users;
        }

    }


    /*
     * 插入一条机具记录
     * type = 1 区间划拨
     * type = 2 选中划拨
     * */
    private function insert_rec($type)
    {
        $hid = $this->request->param('hid');//被划拨人id
        if ($type == 1){//区间划拨
            $rec['op_id'] = $this->_uid;
            $rec['time'] = time();
            $rec['start'] = $this->request->param('start');
            $rec['end'] = $this->request->param('end');
            $rec['no'] = start_end_tostr($rec['start'] ,$rec['end']);
            $rec['u_id'] = $hid;
            Db::name('agoods_sn_record')->insert($rec);
        }else{//选中划拨
            $rec['op_id'] = $this->_uid;
            $rec['time'] = time();
            $rec['no'] = $this->request->param('sn_arr');
            $rec['u_id'] = $hid;
            Db::name('agoods_sn_record')->insert($rec);
        }
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

    /*
     * 用户提现
     * 每笔手续费3元
     * 每笔扣除税点 提现金额的9%
     * */
    public function tixian()
    {
        $uid = $this->_uid;
        $data = $this->request->param();
        $user = Auser::get($uid);
        if ($data['money']+3 > $user->money){
            $this->success('余额不足','','1');
        }
        if ($data['money'] < 50 ){
            $this->success('最低提现金额50元','','1');
        }
        Db::startTrans();
        try{
            //插入记录表
            $data['createtime'] = time();
            $dec = $data['money'] + 3.00;//实际扣除账号的金额
            $data['money'] = $data['money'] - $data['money'] * 0.09;//实际显示的提现金额  = 提现金额-9%的税点
            Db::name('tixian')->insertGetId($data);
            //减少用户余额
            $user->setDec('money',$dec);
            Db::commit();
            $this->success('申请成功,已扣除每笔提现手续费3元','','0');
        }catch(Exception $exception){
            Db::rollback();
            $this->success($exception->getMessage(),'','1');
        }
    }
}

