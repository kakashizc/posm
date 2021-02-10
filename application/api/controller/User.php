<?php

namespace app\api\controller;

use app\admin\model\Level;
use app\common\controller\Api;
use fast\Http;
use think\Db;
use think\Cache;
use think\Request;
use app\admin\model\Auser;
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
//        $msg = $this->request->request('msg');//短信验证码
//        $cache_msg = Cache::get($phone);
//        if ($msg != $cache_msg) {//如果验证码不正确,退出
//            $this->success('短信验证码错误或者超时', '','2');
//        }

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
        $up['indent_face_image'] = $this->request->param('front');
        $up['indent_back_image'] = $this->request->param('back');
        $up['status'] = '1';
        $res = Auser::where('id',$this->_uid)->update($up);
        if ($res){
            $this->success('申请成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
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
        $img = $qrcode->get_qrcode($uid,1);
        if (sizeof($img) >= 2){
            $local_path = $img['local_path'];
            Db::name('auser')->where('id',$uid)->setField('qrcode',$local_path);
            $ret['qrcode'] = $img['pname'];
            $ret['code'] = $usercode['code'];//我的邀请码
            $this->success('成功',$ret,'0');
        }else{
            $this->success('生成失败','','1');
        }
    }
}
