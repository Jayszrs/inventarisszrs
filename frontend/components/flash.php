<?php

$flash = pull_flash();
?>
<?php if ($flash): ?>
  <div class="toast <?= h($flash['type']) ?>">
    <?= h($flash['message']) ?>
  </div>
<?php endif; ?>
