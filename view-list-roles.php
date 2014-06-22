<?php
require_once 'view-header.php';
?>

<h1>Choose a Role</h1>
<form method="get" action="index.php">
    <select name="role" id="option-list"></select>
    <button type="submit">Next</button>
	<input type="hidden" name="route" value="view-role">	
</form>

<h1>View existing Data</h1>
<form method="post" action="index.php">
    <input type="hidden" name="route" value="get-data">
    <input type="text" name="ticket" placeholder="Ticket Number" required>
    <button type="submit">Get the Data</button>
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