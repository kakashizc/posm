<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/5 0005
 * Time: 11:19
 */

namespace app\api\controller;

use app\common\controller\Api;
use think\Cache;
use app\admin\model\Auser;
use think\Db;

class Bus extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 密码登录
     * */
    public function login()
    {
        $mobile = $this->request->param('mobile');//手机号
        $msg = $this->request->param('msg');//短信验证码
        $pass = $this->request->param('password');//密码
        if(!preg_match("/^1[345789]{1}\d{9}$/",$mobile) ){
            $this->success('手机号格式错误或缺少参数!','','1');
        }

         $cache_msg = Cache::get($mobile);
         if ($msg != $cache_msg) {//如果验证码不正确,退出
             $this->success('短信验证码错误或者超时', '','1');
         }
        //如果此手机号未登陆过, 那么就默认注册一个新号,给一个默认密码
        $user = Auser::get(['mobile'=>$mobile, 'password'=>md5($pass)]);
        //$user = Auser::get(['mobile'=>$mobile]);
        if( !$user ){//如果查询不到
            $this->success('手机号或密码错误','','1');
        }else{
            $this->check_white($mobile);
            $payload = array('iss'=>'admin','iat'=>time(),'exp'=>time()+72000000,'nbf'=>time(),'sub'=>'www.admin.com','uid'=>$user->id);
            $token = Jwt::getToken($payload);
            $return['token'] = $token;
            $return['mobile'] = $user->mobile;
            $return['code'] = $user->code;
            $this->success('成功',$return,'0');
        }
    }

    /*
     * 短信验证码登陆
     * */
    public function msg_login()
    {
        $mobile = $this->request->param('mobile');//手机号
        $msg = $this->request->param('msg');//短信验证码
        if(!preg_match("/^1[345789]{1}\d{9}$/",$mobile) ){
            $this->success('手机号格式错误或缺少参数!','','1');
        }

        $cache_msg = Cache::get($mobile);
        if ($msg != $cache_msg) {//如果验证码不正确,退出
            $this->success('短信验证码错误或者超时', '','1');
        }
        $user = Auser::get(['mobile'=>$mobile]);
        if( !$user ){//如果查询不到
            $this->success('手机号不存在','','1');
        }else{
            $this->check_white($mobile);
            $payload = array('iss'=>'admin','iat'=>time(),'exp'=>time()+72000000,'nbf'=>time(),'sub'=>'www.admin.com','uid'=>$user->id);
            $token = Jwt::getToken($payload);
            $return['token'] = $token;
            $return['mobile'] = $user->mobile;
            $return['code'] = $user->code;
            $this->success('成功',$return,'0');
        }
    }


    /*
     * 注册账号
     * */
    public function register()
    {
        $mobile = $this->request->param('mobile');//手机号
        $msg = $this->request->param('msg');//短信验证码
        $pass = $this->request->param('password');//密码
        $code = $this->request->param('code');//上级推荐码
        if( !preg_match("/^1[345789]{1}\d{9}$/",$mobile) ){
            $this->success('手机号格式错误!','','1');
        }
        if( !$code||!$msg||!$pass ){
            $this->success('缺少参数!','','1');
        }
        //查找当前手机号是否存在
        $re = Auser::get(['mobile'=>$mobile]);
        if ( $re ) $this->success('此手机号已存在','','1');
        //查找上级是否存在
        $ret = Auser::get(['code'=>$code]);
        if ( !$ret ) $this->success('上级不存在','','1');

        $cache_msg = Cache::get($mobile);
        if ($msg != $cache_msg) {//如果验证码不正确,退出
            $this->success('短信验证码错误或者超时', '','1');
        }
        $level_id = Db::name('level')->where('name','V1')->value('id');
        $data = array(
            'mobile' => $mobile,
            'password' => md5($pass),
            'ctime' => time(),
            'pid' => $ret->id,
            'level_id' => $level_id
        );
        $user = Auser::create($data);
        $payload = array('iss'=>'admin','iat'=>time(),'exp'=>time()+72000000,'nbf'=>time(),'sub'=>'www.admin.com','uid'=>$user->id);
        $token = Jwt::getToken($payload);
        $return['token'] = $token;
        $return['code'] = $this->getCode($user->id);
        $this->success('注册成功',$return,'0');
    }


    /*
     * 检测用户是否被拉黑, 如果拉黑不能继续操作了
     * */
    protected function check_white($mobile)
    {

        if(!preg_match("/^1[345789]{1}\d{9}$/",$mobile)){
            //如果是手机号格式,用手机号检测,如果不是 用用户id检测
            $where = array('id'=>$mobile);
        }else{
            $where = ['mobile'=>$mobile];
        }
        //检测该手机账号是否存在,并且是否设定为异常账号
        $user = Auser::get($where);
        if ($user){
            if ($user->status == '3'){
                $this->success('账号异常','','9');
            }
        }else{
            $this->success('账号不存在','','1');
        }
    }
    /*
    * 生成不重复的推荐码
    * */
    private function getCode($uid)
    {
        $user = Auser::get($uid);
        if (!$user->code){
            //生成code
            $code =  mt_rand(10000,999999);
            //查看生成的推荐码是否已存在
            $is = Auser::where(['code'=>$code])->find();
            if ($is){
                $this->getCode($uid);
            }else{
                //如果没人用这个推荐码, 那么更新为当前用户的推荐码
                $user->code = $code;
                $user->save();
                return  $code;
            }
        }else{
            return $user->code;
        }
    }

}