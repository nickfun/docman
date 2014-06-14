<?php
require_once 'view-header.php';
?>

<h1></h1>

<li id="main"></li>

<script>

var data = <?= json_encode($DATA, JSON_PRETTY_PRINT) ?>;


</script>

</body>
</html>