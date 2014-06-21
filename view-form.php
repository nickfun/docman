<?php
require_once 'view-header.php';
?>

<h1 id="header" class="role"></h1>

<form method="post" action="index.php" role="form">
    <input type="hidden" name="route" value="submit-form">
    <input type="hidden" name="allOptionIds" id="allOptionIds" value="">
    <input type="hidden" name="roleid" value='<?= $DATA['roleId'] ?>'>
    <div id="main"></div>
    <h2>Meta</h2>
    <div class="formcontrol">
        <label>
            <input type="text" name="author" required>
            Your Name
        </label>
    </div>
    <div class="formcontrol">
        <label>
            <input type="text" name="ticket" required>
            Ticket Number
        </label>
    </div> 
    <div class="formcontrol">
        <label>
            <input type="text" name="date" id="date" readonly="readonly" class="noinput">
            Timestamp
        </label>
    </div>
    <div class="formcontrol">
        <label>
            Notes
            <textarea name="notes" required></textarea>
        </label>
    </div>
    <div class="formcontrol">
        <button type="submit" class="btn btn-default">Save to database, Create PDF</button>
    </div>

</form>

<div class="cancel">
    <a href="index.php">Cancel!</a>
</div>


<script type="text/template" id="tpl-optionlist">
    <div class="formcontrol">
    <label>
    <input type="checkbox"  name="option[<%- id %>]" class="form-control">
    <%- title %>
    </label>
    </div>
</script>

<script>

    var RAWDATA = <?= json_encode($DATA, JSON_PRETTY_PRINT) ?>;
    window.DATA = {};
    window.DATA.groups = new Backbone.Collection(RAWDATA.groups);
    window.DATA.options = new Backbone.Collection(RAWDATA.options);
    window.DATA.groupOptionsMap = new Backbone.Collection(RAWDATA.groupOptionMap);
    
    window.DATA.options.toCsvList = function() {
        var str = this.reduce(function(memo, m) {
            return m.id +"," + memo;
        }, "");
        return str.substring(0, str.length -1);
    };

    var OptionListView = Backbone.View.extend({
        tpl: _.template($("#tpl-optionlist").text()),
        tagName: 'div',
        className: 'aaaaaaa',
        render: function() {
            this.$el.empty();
            var that = this;
            this.collection.each(function(row) {
                var result = that.tpl(row.attributes);
                //console.log("Result: ", result);
                that.$el.append(result);
            });
            return this;
        }
    });

    var GroupListView = Backbone.View.extend({
        tpl: _.template($("#tpl-grouplist").text()),
        tagName: 'div',
        className: 'bbbbbbb',
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
            this.$el.append("<h2 class='cccccc'>" + group.get('title') + '</h2>');
            this.$el.append(view.$el);
            return this;
        }
    });

    $(function() {
        $('#header').text(RAWDATA.role.title);
        var listOfIds = DATA.options.toCsvList();
        $('#allOptionIds').val(listOfIds);
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

    $('#date').val(new Date());

</script>

</body>
</html>