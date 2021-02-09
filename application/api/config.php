<?php

//配置文件
return [
    'exception_handle'        => '\\app\\api\\library\\ExceptionHandle',
    //定义返回参数相关值

    //刷卡的类型
    'cardClass' => [
        '00' => '贷记卡',
        '01' => '借记卡',
        '02' => '准贷记卡',
        '03' => '预付卡',
        '04' => '银联扫码(1千以上)',
        '10' => '云闪付',
        '11' => '云闪付',
        '12' => '云闪付',
        '14' => '云闪付',
        '20' => '云闪付',
        '21' => '云闪付',
        '22' => '云闪付'
    ],

    //交易类型 -> 到账时间
    'transType' => [
        '1011' => 'T1',//T1为第二个工作日到账
        '2011' => '0', //消费撤销
        '1171' => 'D0',//D0为当天实时到账
        '1191' => 'T1',
        '1181' => 'D0',
        '1681' => 'D0',
        '1691' => 'T1',
        '1391' => 'T1',
        '1381' => 'D0',
        '1581' => 'D0',
        '1591' => 'T1',
        '1291' => 'T1',
        '1281' => 'D0',
        '1481' => 'D0',
        '1491' => 'T1',
    ],
    //交易类型->文字说明
    'transName' => [
        '1011' => '消费',
        '2011' => '消费撤销',
        '1171' => '实时收款',
        '1191' => '银联反扫',
        '1181' => '银联反扫',
        '1681' => '银联正扫',
        '1691' => '银联正扫',
        '1391' => '支付宝反扫',
        '1381' => '支付宝反扫',
        '1581' => '支付宝正扫',
        '1591' => '支付宝正扫',
        '1291' => '微信反扫',
        '1281' => '微信反扫',
        '1481' => '微信正扫',
        '1491' => '微信正扫',
    ],
    //支付方式
    'posEntry'  => [
        '05' => '插卡',
        '02' => '刷卡',
        '07' => '非接',
        '98' => '非接',
        '01' => '手工',
        '03' => '正扫/反扫',
    ],

    //节假日配置
    'holiday'  => [
        ['2021-01-01','2021-01-03','3'],//元旦
        ['2021-02-11','2021-02-17','7'],//春节
        ['2021-04-03','2021-04-05','3'],//清明
        ['2021-05-01','2021-05-05','5'],//劳动
        ['2021-06-12','2021-06-14','3'],//端午
        ['2021-09-19','2021-09-21','3'],//中秋
        ['2021-10-01','2021-10-07','7'],//国庆
    ]
];
