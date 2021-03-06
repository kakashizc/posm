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
     * 测试环境的版本数据 :
     * 交易测试密文:{"one":"PtFtbxTKNDY+o9hIXwCrSOpWxyzxOUBS5uKGDHb0pn9eqDblhEoX/tHpMxWJhmfKT/9QtsFitnCM6znIqQ2lhw==","two":"uKyIovE3KG6b5dfJikXQ3Wa1CzXMSFOzn2A6iSY6IukNrMFBeJoz2IroGMkcmKE95e3p8/vqh5lQWAMNV3wF2aLNit+Nh3Pzpe9H453nzOfCsPjmbeKept5wndC28QIUyCGP89fRM+C9h33fl4SyTj5BRXCeweCZehMktlroEDzvmnRoVov83R0hAYJ8Dmw8riffGEulOiYxQJDQTQSIFeMjd9YvUlQ5Uzi4qvZfjnQjYJFNrtYAVaC0T8XuSJHh5yLYN/Dih7T5lKj3X9zOcEgXxah8VqYBQJwC9fML9Tbw1K7xxl1HlXXIUx1XoD+fZB2UXGv9ekymnveQXIeKClpDZj5JEmWTMvCY91O/rYDgfXuxb5AWpCtdq0iP4xfWvvGel+62pxo6g6fs002F3ptoKUugqZt0X7p/1ceZ2ryaNttPXFg6vJX+Z2qJq9PVgvZd+ACOKE3W0rniPSl9fjAMhUmpgdm9vcahsaeCtIhi0FVp3kQN5pJ5wRL1N1xMT4PP0gp0ZfNbDjESk3Rkiyz7BRmMhrJpZ2cnNcnulLLV1DaMv5vaLAZHjLzwnyQ2zf2SFYk9e7MxlW7ImBJUuxt/1urLP/HdG4a5SaOzyA60/of/X6DFtF4t7SxwSgy/wS+uaDAFjqKq4cV3W0UxQRzioid2AGNsHGo8j9cUiBL+ACRwmtWHasQhZekeuP1qU3/JfcoggT4i0c1rOdP3e1kEsSUmaUh9hAn1xhQZbRU5LzMzG4xq4w==","three":"cfe0732aa1e2c88fe2400879ab734f1c726f5df0"}
     * 绑定中断测试密文: {"one":"lOiRGoVaBQfNeAJpRdzF22BpuqB3477NWh/eK6AbQW1duu54SaSlKuzTIKcnG1DOBD+UdPI1n4Jw8DaV3aAPOA==","two":"TmiPTGtwKXG1v0UIbvNeADeTD496mO5Y/8VcfVHk0iKGlLwyLALRiDX3i6qM5r1pzggJ1WqKCISUereMIcUnKvowiH5T9hf6NpAQpgXCKFnToWbTGtp2Nu0+KgPPvmgTPSD0ITkydcsDhgRpUpHRXW9hWvjmPAHSAMB1cEWIHydjqLi5m5yH29qxB4WhLSrN2/Q1KXn/3Iw4dTC0AlkuHQroaFkaVlL9l266JLgLCMcvSYRg7ry7lBefG/z+9UlGE01eZ2U+vs6xUpEyPfU9BanfuL0TAIkewP6xG4lks7XoOg+UZo/8Vu+lJGe+ZRgD2Qddno8LLhvt4MQjHqBRmo7OPYYER+UxBAVxBXmcJ8E=","three":"c3108233407d7f70cf652a1a2163a1f477be716a"}
     * 心跳连接测试密文:{"one":"yJrrFErnz3qfNIEn6aGDImX6CBaENJhTnx7hbkrvD1yWny/BU0mNvkmqN9GKnGYwNHhs3rC+OuRChTaxFCnbrw==","two":"b9c+ZcFHuTI=","three":"d7a8e7ca98eeb3b9f9dd7666a538bae065661961"}
     *
     * 生产环境:
     * 交易测试密文:{"one":"WMYV6w2YzYOfvBmk/ZSvrtzPF0hIMIPPnJqeWx/WGQYRKkfGk+Yho0/81qGHoVsryATW3S5QkkMi78SC7hoTuQ==","two":"G21DdHoRPsBQKbOE9QeitBYGcGyWBdiXRPtsLvq58o4fo3gztRG9GZgu2VwX1thl1kCjoNgk42RykDW9q56tLkhFPRLBR1C6ZPRJlkyKvQc19kPF3pb0Ove+xXrayZm2uHZ5dxGdPtWHIyrbozcmBZ4+8cEVyaT3majFK0VVsnbJiVaDt6F7ekaGRT5hSBMwKmabtt9uz//cvL2HdAKtiP0foX9yUolV+dbkQRfl+gCVXAl3BhiaJaMZyOQg1+sdNCLytk5p7ZcKhq/lnr9rV5BY+XZz2uTLD5p/Iwb7gr5tuIkEeVrIRE95STwf6V5/49CRxCkLSaNaNbmnGeojTenkU2quDCdK2wOjn5hVlLNnzbI7/9Ee9sfDxAy2ZeC/5k4dturIRODk2dyhGtZLUYC4A2J7bGma/TJ7XsAMwKN7xDkGEbVjI4nfYYEsMLwV10AnaQNMdkBYDSQhIqldk56sPoh2JCR87ulUq3c4JCcIDJ7MWlZ4W0VvAvK1PQ7TBkq9TPPIiStdpWK89klpPvns5EYiJ8p8TIQ7uEpuyp9mwxdh/+69F9CVnlikNuBB/Y7sZS4Dy9IDsThdt8X3g7qvajgHkN+RDw1hJ02m8kmyEVSpxJsnzfYa5mjfy0ayf6KgaWGx58DIWCKP5ynhM3asFWa+qF0hGtAlr3Pv766CP45SigSJbYyySM45v4VmXoCkIG75rejK3WYbu0LwdsRBcy/L1NSD4qrxW3IQd//TbsoGfJ4YHA==","three":"df89c4819a50bf65456419c74831cf222a91c500"}
     * 绑定中断测试密文:
     * 心跳连接测试密文:{"one":"ju/bGJTSBMREffM/ypbZ4cw5Vkj5NYn728dyUEsvCp2CMojQh7sNnl7Nmkeg9C9hA8SWRIuLcLQT3ytHRfnyCA==","two":"CPNXbrYGzpEywjANkAMa+A==","three":"863445f5d19b818139afde4195f87ad3d7a96759"}
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
            if ($new['agentNo'] == 'yxbhzys01'){ //这是线下模式的代理商号, 不进入系统,线上的是 liguofa
                return json_encode($return,JSON_UNESCAPED_UNICODE);
            }
            $user = AgoodsSn::get(['sn'=>$new['snNo']]);
            $insert['snNo'] = $new['snNo'];
            $insert['time'] = strtotime($new['transDate'].$new['transTime']);
            $insert['ctime'] = time();//记录创建时间
            $insert['money'] = $new['transAmt'];
            $insert['u_id'] = $user->ac_id??0;//机具所属用户id
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
            @$this->setlog_data(json_encode($new).'||'.date('Y-m-d H:i:s',time())."\n");
            $ret = Db::name('sn_record')->insertGetId($insert);
            //3, 返回code
            if ($ret){
                if ($insert['type'] == '2'){//如果不是信用卡,添加一条记录即可,不执行分销操作
                    return json_encode($return,JSON_UNESCAPED_UNICODE);
                }else{
                    $url = 'http://www.yongshunjinfu.com/api/Asyncapi/trade';
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
        if($arr[0] == 'ysjf.bpos'){
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
            if ($new['agentNo'] == 'yxbhzys01'){ //这是线下模式的代理商号, 不进入系统,线上的是 liguofa
                return json_encode($return,JSON_UNESCAPED_UNICODE);
            }
            $new['time'] = time();
            @$this->setlog_data(json_encode($new).'||'.date('Y-m-d H:i:s',time())."\n");
            $ret = Db::name('sn_bind')->insertGetId($new);
            //返回code
            if ($ret){
                //可以把数组传给异步处理,然后直接返回 成功 剩下的业务逻辑让异步去做,但是我们先不用这样的方式了,并发数不大
                $url = 'http://www.yongshunjinfu.com/api/Asyncapi/bind';//根据sn号,绑定用户
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
        //if ( strlen($str) > 200 )@file_put_contents('1.txt',$str.'||'.date('Y-m-d H:i:s',time())."\n",FILE_APPEND);
        if ( strlen($str) > 200 ) $this->setlog($str.'||'.date('Y-m-d H:i:s',time())."\n");
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
    
    private function setlog($str)
    {
        $date = date('Y-m-d',time());
        $log_name = 'log/'.$date.'.log';
        error_log($str, 3, $log_name);
    }
    private function setlog_data($str)
    {
        $date = date('Y-m-d',time());
        $log_name = 'logdata/'.$date.'.log';
        error_log($str, 3, $log_name);
    }

}