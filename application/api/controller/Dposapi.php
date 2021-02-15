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

/*
     * 交易测试密文:{"one":"PtFtbxTKNDY+o9hIXwCrSOpWxyzxOUBS5uKGDHb0pn9eqDblhEoX/tHpMxWJhmfKT/9QtsFitnCM6znIqQ2lhw==","two":"uKyIovE3KG6b5dfJikXQ3Wa1CzXMSFOzn2A6iSY6IukNrMFBeJoz2IroGMkcmKE95e3p8/vqh5lQWAMNV3wF2aLNit+Nh3Pzpe9H453nzOfCsPjmbeKept5wndC28QIUyCGP89fRM+C9h33fl4SyTj5BRXCeweCZehMktlroEDzvmnRoVov83R0hAYJ8Dmw8riffGEulOiYxQJDQTQSIFeMjd9YvUlQ5Uzi4qvZfjnQjYJFNrtYAVaC0T8XuSJHh5yLYN/Dih7T5lKj3X9zOcEgXxah8VqYBQJwC9fML9Tbw1K7xxl1HlXXIUx1XoD+fZB2UXGv9ekymnveQXIeKClpDZj5JEmWTMvCY91O/rYDgfXuxb5AWpCtdq0iP4xfWvvGel+62pxo6g6fs002F3ptoKUugqZt0X7p/1ceZ2ryaNttPXFg6vJX+Z2qJq9PVgvZd+ACOKE3W0rniPSl9fjAMhUmpgdm9vcahsaeCtIhi0FVp3kQN5pJ5wRL1N1xMT4PP0gp0ZfNbDjESk3Rkiyz7BRmMhrJpZ2cnNcnulLLV1DaMv5vaLAZHjLzwnyQ2zf2SFYk9e7MxlW7ImBJUuxt/1urLP/HdG4a5SaOzyA60/of/X6DFtF4t7SxwSgy/wS+uaDAFjqKq4cV3W0UxQRzioid2AGNsHGo8j9cUiBL+ACRwmtWHasQhZekeuP1qU3/JfcoggT4i0c1rOdP3e1kEsSUmaUh9hAn1xhQZbRU5LzMzG4xq4w==","three":"cfe0732aa1e2c88fe2400879ab734f1c726f5df0"}
     * 绑定终端测试密文:{"one":"yJrrFErnz3qfNIEn6aGDImX6CBaENJhTnx7hbkrvD1yWny/BU0mNvkmqN9GKnGYwNHhs3rC+OuRChTaxFCnbrw==","two":"b9c+ZcFHuTI=","three":"d7a8e7ca98eeb3b9f9dd7666a538bae065661961"}
     * */
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
        //$this->_Redis = Redis::getInstance()->getRedisConn();
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
                @file_put_contents('trade.txt','交易接口调用失败---'.json_encode($dataarr,JSON_UNESCAPED_UNICODE).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
                return json_encode($return,JSON_UNESCAPED_UNICODE);
            }
        }catch(Exception $exception){
            $return['resultContent'] = '失败';
            $return['resultCode'] = '9999';
            @file_put_contents('trade.txt','交易接口调用失败---'.json_encode($dataarr,JSON_UNESCAPED_UNICODE).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
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
                @file_put_contents('bind.txt','绑定接口调用失败---'.json_encode($dataarr,JSON_UNESCAPED_UNICODE).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
                return json_encode($return,JSON_UNESCAPED_UNICODE);
            }
        }catch(Exception $exception){
            $return['resultContent'] = '失败';
            $return['resultCode'] = '9999';
            @file_put_contents('bind.txt','绑定接口调用失败---'.json_encode($dataarr,JSON_UNESCAPED_UNICODE).'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
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