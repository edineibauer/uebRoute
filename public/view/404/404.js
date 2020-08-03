$(function () {
    if (!$("svg.waves").length) {
        getTemplates().then(tpl => {
            $("#core-content").after(Mustache.render(tpl.wavesBottom));
        });
    }
});