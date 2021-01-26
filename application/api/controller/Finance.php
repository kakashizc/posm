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
        $data = AgoodsSn::where(['u_id'=>$uid,'status'=>'0'])->column('sn');
        if (sizeof($data) > 0){
            $this->success('成功',$data,'0');
        }else{
            $this->success('无数据,请联系平台购买,划拨','','1');
        }
    }

    /*
     * 入库记录
     * 获取我的pos机 划拨记录
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
        $sn = $this->request->param('sn');
        $where = "FIND_IN_SET('$sn',no)";
        $data = Db::name('agoods_sn_record')
            ->where($where)
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
}

