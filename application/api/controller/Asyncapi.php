<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/3 0003
 * Time: 16:06
 */

namespace app\api\controller;


use app\admin\model\AgoodsSn;
use app\common\controller\Api;
use think\Db;

class Asyncapi extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 异步接受请求
     * @param $dataarr array 解密后的数组数据
     *
     * */
    public function trade()
    {
        set_time_limit(0);
        ignore_user_abort(true);//设置与客户机断开是否会终止执行
        //file_put_contents('1.txt',$this->request->param('data'));
        $dataarr = $this->request->param();
        foreach ($dataarr as $k=>$v){
            $v = rtrim($v,'}');
            $v = trim($v,'"');
            $value = explode('":"',$v);
            $new[$value[0]] = $value[1];
        }
        //2, 插入刷卡记录表
        if($new['cardClass'] == '00' || $new['cardClass'] == '02'){
            $insert['type'] = '1';//信用卡
        }else{
            $insert['type'] = '2';//其他
        }
        $user = AgoodsSn::get(['sn'=>$new['snNo']]);
        $insert['snNo'] = $new['snNo'];
        $insert['time'] = strtotime($new['transDate'].$new['transTime']);
        $insert['money'] = $new['transAmt'];
        $insert['u_id'] = $user->u_id??0;//机具所属用户id
        $insert['date'] = date('Y-m',$insert['time']);
        $insert['transDate'] = date('Y-m-d H:i:s',$insert['time']);
        $insert['agentNo'] = $new['agentNo'];
        $insert['keyRsp'] = $new['keyRsp'];
        $insert['cardNo'] = $new['cardNo'];
        $insert['cardBankName'] = $new['cardBankName'];
        $insert['transType'] = $new['transType'];
        $insert['fee'] = $new['fee'];
        $insert['memName'] = $new['memName'];
        $insert['memNo'] = $new['memNo'];
        $insert['cardClass'] = $new['cardClass'];
        $ret = Db::name('sn_record')->insert($insert);
    }

    /*
     * 绑定终端
     * */
    public function bind()
    {
        set_time_limit(0);
        ignore_user_abort(true);//设置与客户机断开是否会终止执行
        $dataarr = $this->request->param();
        $new = [];
        foreach ($dataarr as $k=>$v){
            $v = rtrim($v,'}');
            $v = trim($v,'"');
            $value = explode('":"',$v);
            $new[$value[0]] = $value[1];
        }
        $new['time'] = time();
        $ret = Db::name('sn_bind')->insertGetId($new);
    }
}