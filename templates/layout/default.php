<?php
/**
 * Minimal layout for standalone testing.
 *
 * @var \Cake\View\View $this
 */
?>
<!DOCTYPE html>
<html>
<head><title><?= htmlspecialchars($this->fetch('title') ?? '') ?></title></head>
<body><?= $this->fetch('content') ?></body>
</html>
