<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/2 0002
 * Time: 15:49
 */

namespace app\api\controller;

use app\admin\model\AgoodsSn;
use app\admin\model\Feed;
use app\common\controller\Api;
use fast\Async;
use think\Db;
use think\Exception;
use app\common\controller\Redis;
class Dposapi extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $_Dpos;
    private $_Redis;
    public function __construct()
    {
        parent::__construct();
        $this->_Dpos = new Dpos();
        $this->_Redis = Redis::getInstance()->getRedisConn();
    }
    public function ae()
    {
        $this->_Redis->lPush('index','ss');
        dump($this->_Redis->rPop('index'));
    }
    /*
     * 实时交易数据推送
     * */
    public function index()
    {
        $return['resultContent'] = '成功';
        $return['resultCode'] = '0000';
        try{
            $dataarr = $this->des();

            foreach ($dataarr as $k=>$v){
                $v = rtrim($v,'}');
                $v = ltrim($v,'{');
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
            $insert['ctime'] = time();//记录创建时间
            $insert['money'] = $new['transAmt'];
            $insert['u_id'] = $user->u_id??0;//机具所属用户id
            $insert['date'] = date('Y-m',$insert['time']);
            $insert['transDate'] = date('Y-m-d H:i:s',$insert['time']);
            $insert['agentNo'] = $new['agentNo'];
            $insert['agentId'] = $new['agentId'];
            $insert['keyRsp'] = $new['keyRsp'];
            $insert['cardNo'] = $new['cardNo'];
            $insert['cardBankName'] = $new['cardBankName'];
            $insert['transType'] = $new['transType'];
            $insert['fee'] = $new['fee'];
            $insert['memName'] = $new['memName'];
            $insert['memNo'] = $new['memNo'];
            $insert['posEntry'] = $new['posEntry'];
            $insert['cardClass'] = $new['cardClass'];
            $ret = Db::name('sn_record')->insertGetId($insert);
            //3, 返回code
            if ($ret){
                if ($insert['type'] == '2'){//如果不是信用卡,添加一条记录即可,不执行分销操作
                    return json_encode($return,JSON_UNESCAPED_UNICODE);
                }else{
                    $url = 'http://pos.com/api/Asyncapi/trade';
                    $insert['id'] = $ret;
                    Async::send($url,$insert);
                    return json_encode($return,JSON_UNESCAPED_UNICODE);
                }
            }else{
                $return['resultContent'] = '失败';
                $return['resultCode'] = '9999';
                @file_put_contents('trade.txt','交易接口调用失败---'.json_encode($dataarr).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
                return json_encode($return,JSON_UNESCAPED_UNICODE);
            }
        }catch(Exception $exception){
            $return['resultContent'] = '失败';
            $return['resultCode'] = '9999';
            @file_put_contents('trade.txt','交易接口调用失败---'.json_encode($dataarr).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
            return json_encode($return,JSON_UNESCAPED_UNICODE);
        }
    }
    /*
     * pos心跳接口
     * */
    public function heart()
    {
        $arr = $this->des();
        if($arr[0] == '123456'){
            $return['resultContent'] = '心跳连接成功';
            $return['resultCode'] = '0000';
            return json_encode($return,JSON_UNESCAPED_UNICODE);
        }else{
            $return['resultContent'] = '失败';
            $return['resultCode'] = '9999';
            @file_put_contents('heart.txt','心跳失败'.'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
            return json_encode($return,JSON_UNESCAPED_UNICODE);
        }
    }

    /*
     * 绑定终端实时推送接口
     * */
    public function bind()
    {
        $return['resultContent'] = '成功';
        $return['resultCode'] = '0000';
        try{
            $new = [];
            $dataarr = $this->des();
            foreach ($dataarr as $k=>$v){
                $v = rtrim($v,'}');
                $v = ltrim($v,'{');
                $v = trim($v,'"');
                $value = explode('":"',$v);
                $new[$value[0]] = $value[1];
            }
            $new['time'] = time();
            $ret = Db::name('sn_bind')->insertGetId($new);
            //返回code
            if ($ret){
                //可以把数组传给异步处理,然后直接返回 成功 剩下的业务逻辑让异步去做,但是我们先不用这样的方式了,并发数不大
                $url = 'http://pos.com/api/Asyncapi/bind';//根据sn号,绑定用户
                Async::send($url,$new);
                return json_encode($return,JSON_UNESCAPED_UNICODE);

            }else{
                $return['resultContent'] = '失败';
                $return['resultCode'] = '9999';
                @file_put_contents('bind.txt','绑定接口调用失败---'.json_encode($dataarr).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
                return json_encode($return,JSON_UNESCAPED_UNICODE);
            }
        }catch(Exception $exception){
            $return['resultContent'] = '失败';
            $return['resultCode'] = '9999';
            @file_put_contents('bind.txt','绑定接口调用失败---'.json_encode($dataarr).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
            return json_encode($return,JSON_UNESCAPED_UNICODE);
        }
    }

    /*
     * 统一解密
     * @param $type int 类型 1-交易数据 2-绑定数据 3-心跳
     * */
    private function des()
    {
        $str = file_get_contents('php://input');
        @file_put_contents('1.txt',$str.'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
        $arr = json_decode($str,1);
        //1,先对one 进行rsa 解密,获取 randomkey
        $randomkey = $this->_Dpos->decode($this->_Dpos->public_key,$arr['one']);
        if ($randomkey == '0003' || $randomkey =='0004'){
            $return['resultContent'] = '失败';
            $return['resultCode'] = '0003';
            return json_encode($return,JSON_UNESCAPED_UNICODE);
        }
        $deskey =  $randomkey.'3desKeyPart1';
        $data = $this->_Dpos->decrypt($arr['two'],$deskey);
        $dataarr = explode(',',$data);
        return $dataarr;
    }

}