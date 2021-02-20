define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sn_record_bak/index' + location.search,
                    add_url: 'sn_record_bak/add',
                    edit_url: 'sn_record_bak/edit',
                    del_url: 'sn_record_bak/del',
                    multi_url: 'sn_record_bak/multi',
                    table: 'sn_record_bak',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'snNo', title: __('Snno')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'time', title: __('Time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'u_id', title: __('U_id')},
                        {field: 'date', title: __('Date')},
                        {field: 'agentNo', title: __('Agentno')},
                        {field: 'keyRsp', title: __('Keyrsp')},
                        {field: 'cardNo', title: __('Cardno')},
                        {field: 'cardBankName', title: __('Cardbankname')},
                        {field: 'transType', title: __('Transtype')},
                        {field: 'transDate', title: __('Transdate')},
                        {field: 'fee', title: __('Fee'), operate:'BETWEEN'},
                        {field: 'cardClass', title: __('Cardclass'), searchList: {"00":__('Cardclass 00'),"01":__('Cardclass 01'),"02":__('Cardclass 02'),"03":__('Cardclass 03'),"04":__('Cardclass 04'),"10":__('Cardclass 10'),"11":__('Cardclass 11'),"12":__('Cardclass 12'),"14":__('Cardclass 14'),"20":__('Cardclass 20'),"21":__('Cardclass 21'),"22":__('Cardclass 22')}, formatter: Table.api.formatter.normal},
                        {field: 'memName', title: __('Memname')},
                        {field: 'memNo', title: __('Memno')},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'agentId', title: __('Agentid')},
                        {field: 'posEntry', title: __('Posentry')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'ftime', title: __('Ftime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'auser.mobile', title: __('Auser.mobile')},
                        {field: 'auser.indent_name', title: __('Auser.indent_name')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});