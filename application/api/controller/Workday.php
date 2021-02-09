<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/9 0009
 * Time: 13:20
 */

namespace app\api\controller;


use think\Config;

class Workday
{
    //计算出包含假期 周末等等的下一个工作日
    public function abc($date = '2021-10-08'){
        //下一天时间
        $after =  date("Y-m-d",(strtotime($date) + 3600*24));
        $holidays = Config::get('holiday');
        foreach ($holidays as $k=>$v){
            $start_day = $v[0];//某个假期开始日期
            $end_day = $v[1];//某个假期结束日期
            //是不是节假日的前一天
            if ( $after == $start_day ){
                //开始时间加上节假日的天数的下一天就是下一个工作日
                return date("Y-m-d",(strtotime($start_day) + 3600*24*$v[2]));
            }elseif ($this->in($date,$start_day,$end_day)){//是不是在节假日当中,如果在节假日当中,就获取最后一天+1就是下一个工作日
                return date("Y-m-d",(strtotime($end_day) + 3600*24));
            }
        }
        //如果不是以上两种情况 , 正常判断周末就行
        $last = date('Y-m-d', strtotime($date . ' +1 Weekday'));
        //判断一下后面周日是否是节假日开始那天
        foreach ($holidays as $k=>$v){
            $start_day = $v[0];//某个假期开始日期
            $end_day = $v[1];//某个假期结束日期
            if ($this->in($last,$start_day,$end_day)){//是不是在节假日当中,如果在节假日当中,就获取最后一天+1就是下一个工作日
                return date("Y-m-d",(strtotime($end_day) + 3600*24));
            }
        }
        return strtotime($last);//当天0点时间戳
        //如果想把调休的那天也算成工作日, 那么定义一个调休数组,将这里的返回值放入那个数组中循环判断一下
        //下一天是否是调休日,如果下一天是的话 就返回下一天就行,这里就不做判断了.
    }

    public function in($date,$start,$end){
        //判断某个日期,是否在两个日期当中
        $date = strtotime($date);

        $start = strtotime($start);
        $end = strtotime($end);
        if($start <= $date && $date <= $end) {
            return 1;//在其中
        }else{
            return 0;//不再其中
        }
    }
}