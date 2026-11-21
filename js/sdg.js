$(document).on('change', '#filters select', function () {
    function selected(id)
    {
        var select = document.getElementById(id);
        return select.options[select.selectedIndex].text;
    }

    var ajaxHandler = new ajaxHelper();
    ajaxHandler.addParams(
        {
            module: 'SDG',
            action: 'filter',
            siteId: selected('filterBySiteId'),
            name: selected('filterByName'),
            from: selected('filterByFrom'),
            to: selected('filterByTo'),
            status: selected('filterByStatus'),
        },
        'POST'
    );
    ajaxHandler.setFormat('html');
    ajaxHandler.setCallback(function (response) {
        $('#sdg').html(response);
    });
    ajaxHandler.send();
});
