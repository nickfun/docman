<?php
require_once 'view-header.php';
?>

<h1 id="header"></h1>

<div id="main"></div>

<script type="text/template" id="tpl-optionlist">
    <li class="option">
    <label>
    <input type="checkbox" name="option[<%- id %>]">
    <%- title %>
    </label>
    </li>
</script>

<script>

    var RAWDATA = <?= json_encode($DATA, JSON_PRETTY_PRINT) ?>;
    window.DATA = {};
    window.DATA.groups = new Backbone.Collection(RAWDATA.groups);
    window.DATA.options = new Backbone.Collection(RAWDATA.options);
    window.DATA.groupOptionsMap = new Backbone.Collection(RAWDATA.groupOptionMap);

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
            var optionList = new Backbone.Collection();
            _.each(this.model.get('options'), function(id) {
                optionList.add(window.DATA.options.get(id));
            });
            var view = new OptionListView({
                collection: optionList
            });
            view.render();
            var group = window.DATA.groups.get(this.model.get('group_id'));
            debugger;
            this.$el.append("<span class='group'>" + group.get('title') + '</span>');
            this.$el.append(view.$el);
            return this;
        }
    });

    $(function() {
        $('#header').text(RAWDATA.role.title);
    });

    $(function() {
        var $main = $('#main');
        window.DATA.groupOptionsMap.each(function(model) {
            //debugger;
            var view = new GroupListView({
                model: model
            });
            view.render();
            $main.append(view.$el);
        });
    });

</script>

</body>
</html>