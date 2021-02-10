<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/10 0010
 * Time: 11:00
 */

namespace app\api\controller;


use app\admin\model\AgoodsSn;
use app\common\controller\Api;
use think\Db;
use think\Exception;

class Mprice extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 定时任务->完成活动奖励
     * 营销活动奖励->自终端激活次月起,5个月内每个月累计交易量满3万元，第六个月奖励用户 50元
     * */
    public function mp()
    {
        $stime = strtotime(date('Y-m-01', strtotime('-6 month')));//当前月 回推 6个月之前的1号0点
        $etime = strtotime(date('Y-m-t', strtotime('-6 month'))) + (3600 * 24) -1;//当前月 回推 6个月之前的最后一天23:59:59
        //1,查询没有获得活动奖励的用户列表
        $users = Db::name('auser')
            ->alias('u')
            ->join('agoods_sn g','u.id = g.ac_id')
            ->whereBetween('g.actime',[$stime,$etime])//激活时间为当前月回推6个月的时间
            ->where('u.mprice','0')
            ->field('u.id as uid,g.id as gid,g.sn')
            ->select();
        //2,计算是否满足条件, 满足则给与奖励 并记录
        if ( sizeof($users) > 0 ){
            $get = [];
            foreach ($users as $K=>$v){
                //依次计算每个用户是否满足了连续6个月交易满3万元,满足则放入数组中
                $res = $this->mtime($v['sn'],$etime+2);
                if ($res){
                    $get[] = $v['uid'];
                }
            }

            if (sizeof($get) > 0){
                Db::startTrans();
                try {
                    //所有get数组里面的uid用户,余额增加50,并且添加一条佣金记录
                    foreach ($get as $k=>$v){
                        Db::name('auser')->where('id',$v)->setInc('money',50);
                        $data['u_id'] = $v;
                        $data['money'] = 50.00;
                        $data['status'] = '4';
                        $data['ctime'] = time();
                        $data['date_m'] = date('Y-m',time());
                        $data['date_d'] = date('Y-m-d',time());
                        Db::name('feed')->insertGetId($data);
                    }
                    Db::commit();
                }catch(Exception $exception){
                    @file_put_contents('mprice.txt',$exception->getMessage().'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
                    Db::rollback();
                }
            }
        }
    }

    /*
     * 判断是否自激活的下一个月开始连续5个月,每个月的交易额达到3万元如果满足那么给予奖励50元
     * @param $sn string sn编号
     * @param $etime string 激活后,下一个月的第一秒时间戳
     * */
    private function mtime($sn,$etime)
    {
        $month_time = 3600*24*32;//一个月到下一个月的时间戳 按照32天计算,这样计算出来的下个月时间戳,肯定是下一个月中的时间戳
        for($i=0;$i<5;$i++){
            $one = $this->tt($etime+$i*$month_time);
            $month1_start = $one['stime'];//月的开始时间
            $month1_end = $one['etime'];//月的结束时间
            //根据sn号,和 时间 查找当月的业绩,如果有一个不符合就返回false
            $month_achievement = Db::name('sn_record')
                ->where('sn',$sn)
                ->where('type','1')
                ->whereBetween('ctime',[$month1_start,$month1_end])
                ->sum('money');
            if ($month_achievement < 30000){
                return false;
            }
        }
        return 1;
    }

    private function tt($time)
    {
         $date = date('Y-m-d',$time);
         $start_date =  mktime(00, 00, 00, date('m', strtotime($date)), 01);
         $end_date =  mktime(23, 59, 59, date('m', strtotime($date))+1, 00);
         return [
             'stime' => $start_date,
             'etime' => $end_date,
         ];

    }
}