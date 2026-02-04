<?php
/**
 * Minimal 5xx error template for standalone testing.
 *
 * @var \Cake\View\View $this
 * @var string $message
 * @var string $url
 */
?>
<h2><?= htmlspecialchars($message ?? '') ?></h2>
<p><?= htmlspecialchars($url ?? '') ?></p>
