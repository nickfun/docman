<?php
require_once 'view-header.php';
?>

<h1>Choose a Role</h1>
<form method="get" action="index.php">
    <select name="role" id="option-list"></select>
    <button type="submit">Next</button>
</form>

<script>
    var roles = <?= json_encode($DATA); ?>;
    $(function() {
        $sel = $('#option-list');
        _.each(roles, function(role) {
            $opt = $("<option value=" + role.id + ">" + role.title + "</option>");
            $sel.append($opt);
        });
    });
</script>

</body>
</html>