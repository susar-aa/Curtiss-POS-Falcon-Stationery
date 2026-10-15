<?php
/**
 * Curtiss POS - UI Shell Footer Template
 */

$timeout = (int)Settings::get('session_inactivity_timeout', (string)DEFAULT_INACTIVITY_TIMEOUT);
?>
<script>
    window.CURTISS_POS_TIMEOUT = <?= $timeout ?>;
</script>
<script src="assets/js/app.js"></script>
<script src="assets/js/timeout.js"></script>
</body>
</html>
