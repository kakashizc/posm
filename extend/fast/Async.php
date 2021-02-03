<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/3 0003
 * Time: 12:55
 */

namespace fast;

/*
 * 异步处理类
 * */
class Async
{
    /*
      需要注意的是我们需要手动拼出header头信息。通过打开注释部分，可以查看请求返回结果，但这时候又变成同步的了，因为程序会等待返回结果才结束。
    实际测试的时候发现，不忽略执行结果，调试的时候每次都会成功发送sock请求；但忽略执行结果，经常看到没有成功发送sock请求。查看nginx日志，发现很多状态码为499的请求。
    后来找到了原因：fwrite之后马上执行fclose，nginx会直接返回499，不会把请求转发给php处理。
    客户端主动端口请求连接时，NGINX 不会将该请求代理给上游服务（FastCGI PHP 进程），这个时候 access log 中会以 499 记录这个请求。
    解决方案：
        1)nginx.conf增加配置
        #忽略客户端中断
        fastcgi_ignore_client_abort on;
        2)fwrite之后使用usleep函数休眠20毫秒：
        usleep(20000);
      */

    /*
     * @param string $url 异步脚本地址
     * @param array  $param post 数据
     * */
    public static function send($url, $param)
    {

        $host = parse_url($url, PHP_URL_HOST);
        $port = 80;
        $errno = '';
        $errstr = '';
        $timeout = 30;

        $data = http_build_query($param);

        // create connect
        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$fp) {
            return false;
        }

        // send request
        $out = "POST ${url} HTTP/1.1\r\n";
        $out .= "Host:${host}\r\n";
        $out .= "Content-type:application/x-www-form-urlencoded\r\n";
        $out .= "Content-length:" . strlen($data) . "\r\n";
        $out .= "Connection:close\r\n\r\n";
        $out .= "${data}";

        fwrite($fp, $out);

        //忽略执行结果；否则等待返回结果
//        if(APP_DEBUG === true){
        if (false) {
            $ret = '';
            while (!feof($fp)) {
                $ret .= fgets($fp, 128);
            }
        }
        usleep(20000); //fwrite之后马上执行fclose，nginx会直接返回499
        fclose($fp);
    }
    /*
     * get 方式
     * */
    public static function get($url, $param){
        $host = parse_url($url, PHP_URL_HOST);
        $port = 80;
        $errno = '';
        $errstr = '';
        $timeout = 30;

        $url = $url.'?'.http_build_query($param);

        // create connect
        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);

        if(!$fp){
            return false;
        }

        // send request
        $out = "GET ${url} HTTP/1.1\r\n";
        $out .= "Host:${host}\r\n";
        $out .= "Connection:close\r\n\r\n";

        fwrite($fp, $out);

        //忽略执行结果；否则等待返回结果
//        if(APP_DEBUG === true){
        if(false){
            $ret = '';
            while (!feof($fp)) {
                $ret .= fgets($fp, 128);
            }
        }

        usleep(20000); //fwrite之后马上执行fclose，nginx会直接返回499

        fclose($fp);
    }
}