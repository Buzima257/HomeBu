<?php
declare(strict_types=1);

$current = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$role = $_SESSION['admin_role'] ?? '';
?>

<aside class="sidebar">
    <div class="sidebar-brand">🏠 HomeCare</div>
    <nav class="sidebar-nav">
        
        <!-- ─── DASHBOARD ─── -->
        <a href="<?= BASE_URL ?>dashboard/" class="nav-item <?= $current === 'index.php' ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        
        <!-- ─── CONTENT ─── -->
        <div style="padding:20px 20px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.4);font-weight:600">Content</div>
        
        <a href="<?= BASE_URL ?>dashboard/modules/articles/" class="nav-item <?= $currentDir === 'articles' ? 'active' : '' ?>">
            <span class="icon">📝</span> Articles
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/testimonials/" class="nav-item <?= $currentDir === 'testimonials' ? 'active' : '' ?>">
            <span class="icon">💬</span> Testimonials
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/announcements/" class="nav-item <?= $currentDir === 'announcements' ? 'active' : '' ?>">
            <span class="icon">📢</span> Announcements
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/team_members/" class="nav-item <?= $currentDir === 'team_members' ? 'active' : '' ?>">
            <span class="icon">👤</span> Team Members
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/organization_history/" class="nav-item <?= $currentDir === 'organization_history' ? 'active' : '' ?>">
            <span class="icon">📅</span> History
        </a>
        
        <!-- ─── STATIC & MEDIA ─── -->
        <div style="padding:20px 20px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.4);font-weight:600">Static & Media</div>
        
        <a href="<?= BASE_URL ?>dashboard/modules/static_sections/" class="nav-item <?= $currentDir === 'static_sections' ? 'active' : '' ?>">
            <span class="icon">📄</span> Static Sections
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/static_content/" class="nav-item <?= $currentDir === 'static_content' ? 'active' : '' ?>">
            <span class="icon">🏢</span> About / Contact
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/partners/" class="nav-item <?= $currentDir === 'partners' ? 'active' : '' ?>">
            <span class="icon">🤝</span> Partners
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/social_links/" class="nav-item <?= $currentDir === 'social_links' ? 'active' : '' ?>">
            <span class="icon">🔗</span> Social Links
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/impact_stats/" class="nav-item <?= $currentDir === 'impact_stats' ? 'active' : '' ?>">
            <span class="icon">📈</span> Impact Stats
        </a>
        
        <!-- ─── COMMUNICATION ─── -->
        <div style="padding:20px 20px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.4);font-weight:600">Communication</div>
        
        <a href="<?= BASE_URL ?>dashboard/modules/messages/" class="nav-item <?= $currentDir === 'messages' ? 'active' : '' ?>">
            <span class="icon">✉️</span> Messages
            <?php 
            // Badge nouveau message (optionnel)
            try {
                $newMsg = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'new'")->fetchColumn();
                if ($newMsg > 0): ?>
                    <span style="background:#e74c3c;color:#fff;font-size:10px;padding:2px 6px;border-radius:10px;margin-left:6px"><?= (int)$newMsg ?></span>
                <?php endif;
            } catch (PDOException $e) {}
            ?>
        </a>
        <a href="<?= BASE_URL ?>dashboard/modules/forms_builder/" class="nav-item <?= $currentDir === 'forms_builder' ? 'active' : '' ?>">
            <span class="icon">📋</span> Forms Builder
        </a>
        
        <!-- ─── ADMINISTRATION (super_admin only) ─── -->
        <?php if ($role === 'super_admin'): ?>
            <div style="padding:20px 20px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.4);font-weight:600">Administration</div>
            
            <a href="<?= BASE_URL ?>dashboard/modules/admins/admin_list.php" class="nav-item <?= $currentDir === 'admins' ? 'active' : '' ?>">
                <span class="icon">🔐</span> Administrators
            </a>
            <a href="<?= BASE_URL ?>dashboard/modules/audit_logs/" class="nav-item <?= $currentDir === 'audit_logs' ? 'active' : '' ?>">
                <span class="icon">📜</span> Audit Logs
            </a>
            <a href="<?= BASE_URL ?>dashboard/modules/settings/" class="nav-item <?= $currentDir === 'settings' ? 'active' : '' ?>">
                <span class="icon">⚙️</span> Settings
            </a>
        <?php endif; ?>
        
        <!-- ─── LOGOUT ─── -->
        <div style="margin-top:24px;padding:20px;border-top:1px solid rgba(255,255,255,.1)">
            <a href="<?= BASE_URL ?>logout.php" class="nav-item" style="color:#e74c3c">
                <span class="icon">🚪</span> Logout
            </a>
        </div>
    </nav>
</aside>