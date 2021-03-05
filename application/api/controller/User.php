<?php

namespace app\api\controller;

use app\admin\model\Level;
use app\common\controller\Api;
use fast\Http;
use think\Db;
use think\Cache;
use think\Request;
use app\admin\model\Auser;
use app\admin\model\AgoodsSn;
/**
 * 会员接口
 */
class User extends Api
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
     * 本月新增客户 和 我的机器
     *
     * */
    public function newm()
    {
        $uid = $this->_uid;
        $timestamp = strtotime( date('Y-m',time()) );
        $start_time = date( 'Y-m-1 00:00:00', $timestamp );
        $mdays = date( 't', $timestamp );
        $end_time = date( 'Y-m-' . $mdays . ' 23:59:59', $timestamp );
        $stime = strtotime($start_time);
        $etime = strtotime($end_time);
        $data['mon'] = Db::name('auser')->whereTime('ctime',[$stime,$etime])->where('pid',$uid)->count();
        //机具总数
        $data['num'] = Db::name('agoods_sn')->where('u_id',$uid)->count();
        //已激活机具(伪激活) 设计图中的已激活机具
        $data['wei'] = Db::name('agoods_sn')->where('u_id',$uid)->where('status','1')->count();
        //已激活机具(真激活) 设计图中的达标总数
        $data['zhen'] = Db::name('agoods_sn')->where('u_id',$uid)->where('status','2')->count();
        //商户总数 和 累计代理商 先用这个 ， 我的下级总数
        $data['sons'] = Db::name('auser')->where('pid',$uid)->count();
        //累计交易笔数
        $data['count'] = Db::name('sn_record')->where('u_id',$uid)->count();
        //累计交易总额
        $data['all'] = Db::name('sn_record')->where('u_id',$uid)->sum('money');
        $this->success('成功',$data,'0');
    }
    /*
     * 我的信息
     * */
    public function myinfo()
    {
        $uid = $this->_uid;
        $data = Auser::get(function ($list) use ($uid){
            $list->where('id',$uid);
        });
        if ($data){
            if ( substr($data['avatar'],0,4) != 'http' ){
                $data['avatar'] = IMG.$data['avatar'];
            }
            $data['vip'] = Level::where( ['id'=>$data['level_id']] )->value('name');
            //查找当前用户的日交易额, 和 月交易额
            $data['day_trade'] = Auser::trade($uid,1)??0;
            $data['month_trade'] = Auser::trade($uid,2)??0;
            //查找我的上级
            $data['parent'] = Db::name('auser')->where('id',$data['pid'])->field('mobile,indent_name')->find();
            $this->success('获取成功',$data,'0');
        }else{
            $this->success('无数据','','1');
        }
    }

    /*
     * 绑定/修改 手机号
     * */
    public function bindm()
    {
        $phone = $this->request->param('mobile');
        if ( !$phone || !preg_match("/^1[345789]{1}\d{9}$/",$phone) ) {
            $this->success('缺少参数或手机号格式错误','','1');
        }

        //验证手机短信验证码,暂时不用
        $msg = $this->request->request('msg')??'';//短信验证码
        $cache_msg = Cache::get($phone);
        if ($msg != $cache_msg) {//如果验证码不正确,退出
            $this->success('短信验证码错误或者超时', '','2');
        }

        $user = Auser::get($this->_uid);
        if (!$user){
            $this->success('账号不存在','','1');
        }
        $user->mobile = $phone;
        $res = $user->save();
        if ($res){
            $this->success('成功','','0');
        }else{
            $this->success('失败','','1');
        }
    }

    /*
     * 更换头像
     * */
    public function chead()
    {
        $imgstr = $this->request->param('img');
        $up = Auser::where('id',$this->_uid)->update(['avatar'=>$imgstr]);
        if ($up){
            $this->success('成功',['img'=>IMG.$imgstr],'0');
        }else{
            $this->success('修改失败','','1');
        }
    }
    /*
     * 更换昵称
     * */
    public function chname()
    {
        $imgstr = $this->request->param('name');
        $up = Auser::where('id',$this->_uid)->update(['nickName'=>$imgstr]);
        if ($up){
            $this->success('修改成功','','0');
        }else{
            $this->success('修改失败','','1');
        }
    }

    /*
     * 刷手,实名认证
     * */
    public function indent()
    {
        $up = [];
        $up['indent_name'] = $this->request->param('indent_name');
        $up['indent_no'] = $this->request->param('indent_no');
        $re = $this->chek($up['indent_no'],$up['indent_name']);
        if ($re == false){
            $this->success('身份证信息错误','','1');
        }
        $up['indent_face_image'] = $this->request->param('front');
        $up['indent_back_image'] = $this->request->param('back');
        $up['status'] = '2';
        $res = Auser::where('id',$this->_uid)->update($up);
        if ($res){
            $this->success('申请成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }
    
    private function chek($no,$name)
    {
        $host = "https://verify2.market.alicloudapi.com";
        $path = "/getapilist/verify_id_name";
        $method = "POST";
        $appcode = "754f7ed93737469c82d3c05207e3d781";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "id_number=$no&name=$name";
        $url = $host . $path . "?" . $querys;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $ret =(curl_exec($curl));
        $str = strrchr($ret, '{');
        $arr = (json_decode($str,1));
        if($arr['status'] == 'OK'){
            return true;
        }else{
            return false;
        }
    }
    
    /*
     * 查看实名认证状态
     * */
    public function indent_status()
    {
        $res = Auser::get(function ($query){
            $query->where('id',$this->_uid)->field('status');
        });
        if (!$res){
            $res['status'] = '4';
        }
        $this->success('成功',$res,'0');
    }

    /*
     * 修改地址
     * */
    public function editAddress()
    {
        $up = [];
        $up['recive_name'] = $this->request->param('recive_name');
        $up['recive_mobile'] = $this->request->param('recive_mobile');
        $up['recive_city'] = $this->request->param('recive_city');
        $up['recive_address'] = $this->request->param('recive_address');
        $res = Auser::where('id',$this->_uid)->update($up);
        if ($res){
            $this->success('修改成功','','0');
        }else{
            $this->success('修改失败或用户不存在','','1');
        }
    }
    /*
     * 我的地址
     * */
    public function myadd()
    {
        $data = Auser::where('id',$this->_uid)->field('recive_name,recive_mobile,recive_city,recive_address')->find();
        $this->success('成功',$data,'0');
    }

    /*
     * 生成我的二维码
     * */
    public function mycode()
    {
        $qrcode = new Qrcode();
        $uid =  $this->_token['uid'];
        $usercode = Db::name('auser')->find($uid);
        $ret = array();
        if ($usercode['qrcode'] != ''){
            $ret= array('qrcode'=>IMG.$usercode['qrcode']);
            $this->success('成功',$ret,'0');
        }
        $img = $qrcode->get_qrcode($usercode['code'],1);
        if ( sizeof($img) >= 2 ){
            $local_path = $img['local_path'];
            Db::name('auser')->where('id',$uid)->setField('qrcode',$local_path);
            $ret['qrcode'] = $img['pname'];
            $ret['indent_name'] = $img['indent_name']??'';
            $ret['code'] = $usercode['code'];//我的邀请码
            $this->success('成功',$ret,'0');
        }else{
            $this->success('生成失败','','1');
        }
    }
    
    /*
     * 我前几周的业绩
     * */
    public function weeks()
    {
        $uid =  $this->_token['uid'];
        for ($i=0;$i<4;$i++){
            list($x,$y) = $this->get_se($i,$uid);
            $arr['x'][] = $x;
            //$arr['y'][] = mt_rand(10,10000);
            $arr['y'][] = $y;
        }
        $this->success('成功',$arr,'0');
    }
    /*
     * 获取某一周的开始和结束时间
     * @param int $num 往前第几周
     * */
    private function get_se($num,$uid)
    {
        if ($num == 0){
            //获取本周的
            //本周一
            $a = date('Y-m-d',(time()-((date('w',time())==0?7:date('w',time()))-1)*24*3600)); //w为星期几的数字形式,这里0为周日 //w为星期几的数字形式,这里0为周日
            $week_start = strtotime($a);
            $sdate = date('m-d',$week_start);
            $week_end = strtotime($a) + (3600*24*7) -1;
            $edate = date('m-d',$week_end);
        }else{
            $str = '-'.$num;
            //上周日
            $a = date('Y-m-d', strtotime("$str sunday", time())); //上一个有效周日,同样适用于其它星期
            //上周一
            $one = strtotime($a) - 3600*24*6;
            $week_start = $one;
            $sdate = date('m-d',$week_start);
            $week_end = strtotime($a) + 3600*24 -1;
            $edate = date('m-d',$week_end);
        }
        //$money = Db::name('sn_record')->where('u_id',$uid)->whereTime('time',[$week_start,$week_end])->sum('money');
        $son_ids = Auser::where('pid',$uid)->column('id');
        //$money = Db::name('feed')->where('u_id','IN',$son_ids)->whereTime('ctime',[$week_start,$week_end])->sum('money');
        //我的佣金
        $my = Db::name('feed')->where('u_id','IN',$uid)->whereTime('ctime',[$week_start,$week_end])->sum('money');
        $arr[] = $sdate.'/'.$edate;
        $arr[] = $my;
        return $arr;
    }
     /*
     * 短信修改密码
     * */
    public function rep()
    {
        $phone = $this->request->param('mobile');
        $pass = $this->request->param('password');
        if ( !$pass || !$phone || !preg_match("/^1[345789]{1}\d{9}$/",$phone) ) {
            $this->success('缺少参数或手机号格式错误','','1');
        }

        //验证手机短信验证码,暂时不用
        $msg = $this->request->request('msg');//短信验证码
        $cache_msg = Cache::get($phone);
        if ($msg != $cache_msg) {//如果验证码不正确,退出
            $this->success('短信验证码错误或者超时', '','1');
        }

        $user = Auser::get($this->_uid);
        if (!$user){
            $this->success('账号不存在','','1');
        }
        $user->mobile = $phone;
        $user->password = md5($pass);
        $res = $user->save();
        if ($res){
            $this->success('成功','','0');
        }else{
            $this->success('失败','','1');
        }
    }
    /*
     * 获取某个下级 所有的机具(未激活的,已激活的不能撤回), 然后进行 撤回机具
     * */
    public function son_machines()
    {
        $uid =  $this->request->param('uid');
        //某个下级所有的 机具
        $sons_sn = AgoodsSn::where('ac_id','IN',$uid)
                ->where('status','0')
                ->column('sn');
        if ( sizeof($sons_sn) > 0 ){
            $this->success('成功',$sons_sn,'0');
        }else{
            $this->success('此下级无机具','','1');
        }
    }

    /*
     * 撤回机具
     * */
    public function back_machine()
    {
        $uid = $this->request->param('uid');
        $sn_str = $this->request->param('sn_arr');//选中机具sn的数组
        $sn_arr = explode(',',$sn_str);
        $fail = array();
        Db::startTrans();
        foreach ($sn_arr as $k=>$v){
            $sn = AgoodsSn::get(['sn'=>$v,'ac_id'=>$uid]);
            $sn->ac_id = '';
            $ret = $sn->save();
            if (!$ret){
                $fail[] = $v;
            }
        }
        if (sizeof($fail) == 0 ){
            Db::commit();
            $this->success('撤回机具成功','','0');
        }else{
            Db::rollback();
            $str = implode(',',$fail);
            $this->success('撤回机具失败:'.$str,'','1');
        }

    }
    
    /*
     * 我的下级中 还没有返回50元奖励的列表
     *
     * */
    public function sons()
    {
        $uid = $this->_uid;
        $users = Auser::all(function ($list) use ($uid){
            $list->field('id,mobile,indent_name as name,avatar,ctime,nickName,level_id')->where('pid',$uid)->where('mprice','0');
        })->each(function ($item){
            if ($item['name'] == null) $item['name'] = $item['nickName'];
            if ( substr($item['avatar'],0,4) != 'http' &&  $item['avatar'] != ''){
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
     * 查询某个下级 前5个月的完成任务情况
     * */
    public function lastfive()
    {
        $sons_id = $this->request->param('id');//下级id
        //1,获取机具下级开通的时间
        $goods = AgoodsSn::get(['ac_id'=>$sons_id]);
        if(!$goods){
            $this->success('暂无机具','','1');
        }
        $cmonth = date('Y-m',$goods->ctime);//下级机具开通的月份
        $one = $this->aa($cmonth);//下一个月
        $two = $this->aa($one);//第二个月
        $three = $this->aa($two);//第三个月
        $four = $this->aa($three);//第四个月
        $five = $this->aa($four);//第五个月
        //2,查询下级开通时间往后5个月每个月的业绩情况
        $data[0]['money'] = Db::name('sn_record')->where('u_id',$sons_id)->where('date',$one)->sum('money')??0;
        $data[0]['month'] = $one;
        $data[1]['money'] = Db::name('sn_record')->where('u_id',$sons_id)->where('date',$two)->sum('money')??0;
        $data[1]['month'] = $two;
        $data[2]['money'] = Db::name('sn_record')->where('u_id',$sons_id)->where('date',$three)->sum('money')??0;
        $data[2]['month'] = $three;
        $data[3]['money'] = Db::name('sn_record')->where('u_id',$sons_id)->where('date',$four)->sum('money')??0;
        $data[3]['month'] = $four;
        $data[4]['money'] = Db::name('sn_record')->where('u_id',$sons_id)->where('date',$five)->sum('money')??0;
        $data[4]['month'] = $five;
        $this->success('成功',$data,'0');
    }

    //获取当前月份的下一个月
    public function aa($date){
        $timestamp=strtotime($date);
        $arr=getdate($timestamp);
        if($arr['mon'] == 12){
            $year=$arr['year'] +1;
            $month=$arr['mon'] -11;
            $firstday=$year.'-0'.$month.'-01';
            $lastday=date('Y-m',strtotime("$firstday +1 month -1 day"));
        }else{
            $firstday=date('Y-m',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)+1).'-01'));
            $lastday=date('Y-m',strtotime("$firstday +1 month -1 day"));
        }
        return ($firstday);
    }
}

