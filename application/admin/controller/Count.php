<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2021/2/23
 * Time: 10:35
 */

namespace app\admin\controller;


use app\common\controller\Backend;
use think\Db;

class Count extends Backend
{
    public function index()
    {
        $stime = strtotime(input('stime'))?:1609430400;//开始时间 默认2021-1-1
        $etime = strtotime(input('etime'))?:2524579200;//结束时间 默认 2050-1-1
//        echo $stime.'------';echo $etime;
        $totals = Db::name('sn_record')->whereTime('ctime',[$stime,$etime])->sum('money');
        $records = Db::name('sn_record')->whereTime('ctime',[$stime,$etime])->count('id');
        $users = Db::name('auser')->whereTime('ctime',[$stime,$etime])->count();
        $this->assign('totals',$totals/10000);//刷卡总额(万元)
        $this->assign('users',$users);//用户总数
        $this->assign('records',$records);//刷卡记录总数
        return $this->view->fetch();
    }
}