<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/9 0009
 * Time: 13:45
 */

namespace app\api\controller;


use app\admin\model\AgoodsSn;
use app\admin\model\Auser;
use app\admin\model\Feed;
use app\admin\model\Level;
use app\common\controller\Api;
use think\Config;
use think\Db;
use think\Exception;

class Time extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    /*
     * 定时任务执行:
     * 每天凌晨1点执行
     * 查找所有时间戳 < 当前时间戳的 sn_record_bak记录表中的数据, 根据数据进行返佣金 / 升级/ 返88 等相关操作
     * */
    public function index()
    {
        $all = Db::name('sn_record_bak')
            ->where('ftime','<',time())
            ->where('status','0')
            ->select();
        foreach ($all as $k => $v){
            $this->trade($v);
        }
    }

    /*
     * 异步接受请求
     * @param $arr array 解密后的数组数据
     *
     * */
    private function trade($arr)
    {
        /**
         * 90个工作日内, 刷够5000元, 返回给88元的机具采购费用(单笔或者多笔累计)
         * 手续费为万60的 才算入5000元累计内 支付方式 手续费为60的是: 刷卡 02 ,插卡 05
         * 非接 07 98 -> 手续费为38或60 ,条件是1000元以内38, 1000元以上是60
         * 手工 01  正扫/反扫 03 手续费都是38 不计算入内
         */
        $count = 0;// 0-不计算入5000元以内, 1-计入5000元内
        if ($arr['posEntry'] == '05' || $arr['posEntry'] == '02'){
            $count = 1;
        }else if ($arr['posEntry'] == '07' ||  $arr['posEntry'] == '98'){
            if( $arr['money'] >= 1000 ){
                $count = 1;
            }
        }
        $this->back($arr,$count);
    }

    /*
     * 返给佣金
     * @param $arr array 交易返回的数组
     * @param $count int 90个自然日内, 刷够5000元, 返回给88元的机具采购费用 1=金额可以算入 0=不可
     * */
    private function back($arr,$count)
    {
        //根据snNo 查询用户u_id->查询用户信息
        $uid = AgoodsSn::where(['sn'=>$arr['snNo']])->value('ac_id');
        $user = Auser::where('id',$uid)->find();
        //1,先根据自己的等级, 返给万几的佣金,同时累计业绩
        Db::startTrans();
        try{
            $this->feed_record($user,$arr['money'],2,$arr['snNo'],$count);
            //2,返给上级佣金,同时给上级加上业绩
            $this->feed_record($user,$arr['money'],1,$arr['snNo']);
            //3,更改记录为已返佣
            Db::name('sn_record_bak')->where('id',$arr['id'])->setField('status','1');
            unset($arr['id']);
            $arr['is_bak'] = 1;
            $arr['bak_ftime'] = $arr['ftime'];
            unset($arr['ftime']);
            $arr['bak_ctime'] = time();
            $arr['status'] = '1';
            $arr['ctime'] = time();
            Db::name('sn_record')->insertGetId($arr);
            Db::commit();
        }catch(Exception $exception){
            @file_put_contents('asyncapi_bak.txt',$exception->getMessage().'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
            Db::rollback();
        }
    }

    /*
     * 插入一条佣金记录
     * @param $user array 用户信息
     * @param $money float 刷卡金额
     * @param $type int 佣金类型:1=下级刷卡返佣,2=本人刷卡
     * @param $count int 90个自然日内, 刷够5000元, 返回给88元的机具采购费用 1=金额可以算入 0=不可
     * */
    private function feed_record($user,$money,$type,$snNo,$count=2)
    {
        $mylevel = Level::get(['id'=>$user['level_id']]);
        $broker = $mylevel->feed;//用户等级对应的分润比例 -> 元/万元
        $insert['u_id'] = $user['id'];
        if ($type == 2){
            $uid = $user['id'];
            //如果是别人刷卡,获取佣金为自己等级对应的分润
            $bro = ($broker*$money)/10000;
        }else{
            $uid = $user['pid'];
            //如果是下级刷卡, 我获取佣金, 那么对应佣金比例是 我的等级拥挤-下级等级佣金
            $parent = Auser::get($uid);
            $parent_level = Level::get($parent->level_id);
            $parent_feed = $parent_level->feed;

            $son = Auser::get($user['id']);
            $son_level = Level::get($son->level_id);
            $son_feed = $son_level->feed;
            if ($parent_feed > $son_feed){//如果上级＞下级佣金比例，那么进行分润相减
                $ex = $parent_feed - $son_feed;
                $bro = ($ex*$money)/10000;
            }
        }
        $insert['card_id'] = $uid;
        $insert['status'] = $type;
        $insert['ctime'] = time();
        $insert['date_d'] = date('Y-m-d',time());//年月日
        $insert['date_m'] = date('Y-m',time());//年月
        $insert['money'] = $bro;
        $insert['trade_money'] = $money;
        Feed::create($insert);
        if ($count == 1  && $type == 2){ //交易类型满足5000累计,并且是本人刷卡,那么就去累加5000字段
            $userinfo = Auser::get($uid);
            if ($userinfo->five < 5000){
                $userinfo->five = $userinfo->five+$money;
                $userinfo->save();
                //判断是否第一笔
                $agoods = AgoodsSn::get(['sn'=>$snNo]);
                if($agoods->status == '0'){//如果状态是未激活,说明是第一笔消费
                    $agoods->status = '1';//修改为伪激活
                    $agoods->save();
                }
                if ($userinfo->five >= 5000 ){
                    //返回给88机具钱,同时插入一条记录
                    $this->eight($uid);
                    //修改机具状态为真激活
                    $up = ['status'=>'2','actime'=>time()];
                    Db::name('agoods_sn')->where('sn',$snNo)->update($up);
                }
            }
        }
        if ($type == 2){
            //给自己增加总业绩金额
            $userinfo = Auser::get($user['id']);
            $userinfo->all_trade = $userinfo->all_trade+$money;
            $userinfo->save();

            //判断是否升级
            $this->isUp($user['id']);
        }else{
            //给上级增加总业绩
            $userinfo = Auser::get($user['pid']);
            $userinfo->all_trade = $userinfo->all_trade+$money;
            $userinfo->save();
            //判断是否升级
            $this->isUp($user['pid']);
        }
    }

    //插入一条返88的机具记录
    private function eight($uid)
    {
        $insert['card_id'] = $uid;
        $insert['u_id'] = $uid;
        $insert['status'] = 3;
        $insert['ctime'] = time();
        $insert['date_d'] = date('Y-m-d',time());//年月日
        $insert['date_m'] = date('Y-m',time());//年月
        $insert['money'] = 88.00;
        $insert['trade_money'] = 0.00;
        Feed::create($insert);
    }

    /*
     * 判断用户当前的业绩是否够升级
     *
     * */
    private function isUp($uid)
    {
        $userinfo = Auser::get($uid);
        $level = Level::get($userinfo->level_id);
        switch ($level->name){
            case 'V1'://v1升到V2需要邀请3个v1,并且都已经返满5000 也就是返了88元机具
                //查看是否有3个或以上下级返了88元
                $sons = Auser::where(['pid'=>$uid,'reback'=>'1'])->count();
                if ( $sons >= 3 ){
                    //可以升级
                    $v2 = Level::get(['name' => 'V2']);
                    $userinfo->level_id = $v2->id;
                    $userinfo->save();
                }
                break;
            case 'V2':
                $this->up('V3',$userinfo);
                break;
            case 'V3':
                $this->up('V4',$userinfo);
                break;
            case 'V4':
                $this->up('V5',$userinfo);
                break;
            case 'V5':
                $this->up('V6',$userinfo);
                break;
            case 'V6':
                $this->up('V7',$userinfo);
                break;
            case 'V7':
                $this->up('V8',$userinfo);
                break;
        }
    }

    /*
     * 升级
     * @param $name string 升级所需要的等级名字
     * @param $userinfo object 用户信息
     * */
    private function up($name,$userinfo)
    {
        //查询升级需要多少钱 ,因为库里面是万为单位的,所以乘以10000 是 元
        $need = Level::where(['name'=>$name])->value('money')*10000;
        if( $userinfo->all_trade >= $need ){//如果当前用户的总交易额 大于等于 升下一级所需要的晋级金额时,升级!
            $userinfo->level_id = Level::where(['name'=>$name])->value('id');
            $userinfo->save();
        }
    }
}