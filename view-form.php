<?php
require_once 'view-header.php';
?>

<h1 id="header"></h1>

<ul id="main"></ul>

<script type="text/template" id="tpl-optionlist">
    <li class="option">
        <label>
        <input type="checkbox" name="option[<%- id %>]">
        <%- title %>
        </label>
    </li>
</script>

<script>

    var DATA = <?= json_encode($DATA, JSON_PRETTY_PRINT) ?>;

    var groups = new Backbone.Collection(DATA.groups);
    var options = new Backbone.Collection(DATA.options);

    var OptionListView = Backbone.View.extend({
        tpl: _.template($("#tpl-optionlist").text()),
        tagName: 'ul',
        className: 'option-list',
        render: function() {
            this.$el.empty();
            var that = this;
            this.collection.each(function(row) {
                var result = that.tpl(row.attributes);
                console.log("Result: ", result);
                that.$el.append(result);
            });
            return this;
        }
    });
    
    var GroupListView = Backbone.View.extend({
        tpl: _.template($("#tpl-grouplist").text()),
        tagName: 'ul',
        className: 'group-list',
        render: function() {
            this.$el.empty();
            var that = this;
            
            return this;
        }
    });

    var z = new OptionListView({
        collection: options
    });

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