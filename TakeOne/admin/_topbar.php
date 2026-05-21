<?php
$topbar_title = $topbar_title ?? 'Panel';
$topbar_sub   = $topbar_sub   ?? '';

$usuario  = $_SESSION['usuario'] ?? [];
$uname    = $usuario['username'] ?? '';

$color = '#e5533d';

$partes    = explode(' ', trim($uname));
$iniciales = strtoupper(count($partes) >= 2
    ? substr($partes[0], 0, 1) . substr($partes[1], 0, 1)
    : substr($uname, 0, 2));
?>
<header class="admin-topbar">
    <div>
        <h1 class="admin-topbar__title"><?= htmlspecialchars($topbar_title) ?></h1>
        <?php if ($topbar_sub !== ''): ?>
            <p class="admin-topbar__sub"><?= htmlspecialchars($topbar_sub) ?></p>
        <?php endif; ?>
    </div>
    <div class="admin-topbar__user">
        <div class="admin-avatar"
            style="background:<?= $color ?>"
            title="<?= htmlspecialchars($uname) ?>">
            <?= $iniciales ?>
        </div>
        <span><?= htmlspecialchars($uname) ?></span>
    </div>
</header>
<?php unset($topbar_title, $topbar_sub, $usuario, $uname, $color, $partes, $iniciales); ?>