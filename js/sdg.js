function filterSdgBy(sel)
{
    var ajaxHandler = new ajaxHelper();
    ajaxHandler.addParams(
        {
            module: 'SDG',
            action: 'filter',
            siteId: document.getElementById("filterBySiteId").options[document.getElementById("filterBySiteId").selectedIndex].text,
            name: document.getElementById("filterByName").options[document.getElementById("filterByName").selectedIndex].text,
            from: document.getElementById("filterByFrom").options[document.getElementById("filterByFrom").selectedIndex].text,
            to: document.getElementById("filterByTo").options[document.getElementById("filterByTo").selectedIndex].text,
            status: document.getElementById("filterByStatus").options[document.getElementById("filterByStatus").selectedIndex].text,
        },
        'POST'
    );
    ajaxHandler.setFormat('html');
    ajaxHandler.setCallback(function (response) {
        $('#sdg').html(response);
    });
    ajaxHandler.send();
}
