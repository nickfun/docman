<?php
require_once 'view-header.php';
?>

<h1 id="header"></h1>

<ul id="main"></ul>

<script>

    var DATA = <?= json_encode($DATA, JSON_PRETTY_PRINT) ?>;

    var groups = new Backbone.Collection(DATA.groups);
    var options = new Backbone.Collection(DATA.options);

    function optionList(options) {
        var $list = $("<ul></ul>");
        options.each(function(option) {
            $list.append($("<li>" + option.get("title") + "</li>"));
        });
        return $list;
    }

    $(function() {
        $('#header').text(DATA.role.title);
    });

    $(function() {
        var $list = $('#main');
        _.each(DATA.groupOptionMap, function(row) {
            group = groups.get(row.groupid);
            $li = $("<li>" + group.get("title") + "</li>");
            // TODO build a filtered options collection
            $li.append(optionList(options));
            $list.append($li);
        });
    });

</script>

</body>
</html>