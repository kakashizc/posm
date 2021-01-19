define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
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
                        {field: 'order_no', title: __('Order_no')},
                        {field: 'u_id', title: __('U_id')},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4')}, formatter: Table.api.formatter.status},
                        {field: 'goods_id', title: __('Goods_id')},
                        {field: 'num', title: __('Num')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'total', title: __('Total'), operate:'BETWEEN'},
                        {field: 'auser.mobile', title: __('Auser.mobile')},
                        {field: 'auser.nickName', title: __('Auser.nickname')},
                        {field: 'auser.avatar', title: __('Auser.avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'agoods.name', title: __('Agoods.name')},
                        {field: 'agoods.image', title: __('Agoods.image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'agoods.price', title: __('Agoods.price'), operate:'BETWEEN'},
                        {field: 'agoods.factory', title: __('Agoods.factory')},
                        {field: 'agoods.type', title: __('Agoods.type')},
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