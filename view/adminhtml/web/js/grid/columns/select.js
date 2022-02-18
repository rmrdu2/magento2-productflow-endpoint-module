define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
], function (_, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Productflow_Endpoint/ui/grid/cells/text'
        },
        getStatusColor: function (row) {
            
            if (row.status == '1') {
                return '#FFAE33';
            }
            if (row.status == "2") {
                return '#2C8745';
            }
            if (row.status == "3") {
                return '#E23F27';
            }
            return '#929292';
        }
    });
});