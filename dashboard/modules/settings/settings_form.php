<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/SettingController.php';

$auth = new Auth($db);
$auth->requireRole('super_admin');

$ctrl = new SettingController($db);
$settings = $ctrl->getAll();

if (!is_array($settings)) {
    $settings = [];
}

function settingVal(array $settings, string $key, string $default = ''): string {
    if (!isset($settings[$key]) || !is_array($settings[$key])) {
        return $default;
    }
    $val = $settings[$key]['setting_value'] ?? null;
    return ($val === null) ? $default : (string)$val;
}

function settingChecked(array $settings, string $key): string {
    $val = settingVal($settings, $key);
    return ($val === '1' || $val === 'true') ? 'checked' : '';
}

$pageTitle = 'Global Settings';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<<main class="content">
    <div class="page-header">
        <h1>⚙️ Global Settings</h1>
        <span class="badge badge-red">super_admin only</span>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?= (int)$_GET['success'] ?> setting(s) updated successfully</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="settings_api.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="update_all">
        
        <!-- ════════════════════════════════════════
             📤 UPLOADS
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #f39c12">
            <h3 style="color:#f39c12;margin-bottom:20px">📤 Upload Limits</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Image Max Size (MB)</label>
                    <input type="number" name="image_max_size_mb" value="<?= (int)settingVal($settings, 'image_max_size_mb', '5') ?>" min="1" max="100">
                </div>
                <div class="form-group">
                    <label>Image Min Size (KB)</label>
                    <input type="number" name="image_min_size_kb" value="<?= (int)settingVal($settings, 'image_min_size_kb', '100') ?>" min="1" max="10240">
                </div>
                <div class="form-group">
                    <label>Image Max Width (px)</label>
                    <input type="number" name="image_max_width_px" value="<?= (int)settingVal($settings, 'image_max_width_px', '4000') ?>" min="100">
                </div>
                <div class="form-group">
                    <label>Image Max Height (px)</label>
                    <input type="number" name="image_max_height_px" value="<?= (int)settingVal($settings, 'image_max_height_px', '4000') ?>" min="100">
                </div>
                <div class="form-group">
                    <label>Image Min Width (px)</label>
                    <input type="number" name="image_min_width_px" value="<?= (int)settingVal($settings, 'image_min_width_px', '800') ?>" min="100">
                </div>
                <div class="form-group">
                    <label>Image Min Height (px)</label>
                    <input type="number" name="image_min_height_px" value="<?= (int)settingVal($settings, 'image_min_height_px', '600') ?>" min="100">
                </div>
                <div class="form-group">
                    <label>Video Max Size (MB)</label>
                    <input type="number" name="video_max_size_mb" value="<?= (int)settingVal($settings, 'video_max_size_mb', '50') ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Video Min Size (KB)</label>
                    <input type="number" name="video_min_size_kb" value="<?= (int)settingVal($settings, 'video_min_size_kb', '500') ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Short Video Max Size (MB)</label>
                    <input type="number" name="short_video_max_size_mb" value="<?= (int)settingVal($settings, 'short_video_max_size_mb', '10') ?>" min="1" max="50">
                </div>
                <div class="form-group">
                    <label>Short Video Max Duration (sec)</label>
                    <input type="number" name="short_video_max_duration_sec" value="<?= (int)settingVal($settings, 'short_video_max_duration_sec', '60') ?>" min="1" max="300">
                </div>
                <div class="form-group">
                    <label>Icon Max Size (KB)</label>
                    <input type="number" name="icon_max_size_kb" value="<?= (int)settingVal($settings, 'icon_max_size_kb', '500') ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Icon Min Size (KB)</label>
                    <input type="number" name="icon_min_size_kb" value="<?= (int)settingVal($settings, 'icon_min_size_kb', '5') ?>" min="1">
                </div>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             🔐 SECURITY
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #e74c3c;margin-top:24px">
            <h3 style="color:#e74c3c;margin-bottom:20px">🔐 Security</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" name="session_timeout_minutes" value="<?= (int)settingVal($settings, 'session_timeout_minutes', '30') ?>" min="5" max="1440">
                    <small>Auto-logout after inactivity</small>
                </div>
                <div class="form-group">
                    <label>Max Login Attempts</label>
                    <input type="number" name="max_login_attempts" value="<?= (int)settingVal($settings, 'max_login_attempts', '5') ?>" min="1" max="20">
                </div>
                <div class="form-group">
                    <label>Lockout Duration (minutes)</label>
                    <input type="number" name="lockout_duration_minutes" value="<?= (int)settingVal($settings, 'lockout_duration_minutes', '15') ?>" min="1" max="1440">
                </div>
                <div class="form-group full-width" style="display:flex;gap:20px;align-items:center">
                    <label class="toggle">
                        <input type="checkbox" name="enforce_ip_check" value="1" <?= settingChecked($settings, 'enforce_ip_check') ?>>
                        <span>Enforce IP Check (disconnect if IP changes during session)</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             🏗️ STATIC SECTIONS EDIT
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #9b59b6;margin-top:24px">
            <h3 style="color:#9b59b6;margin-bottom:20px">🏗️ Static Sections Control</h3>
            
            <div class="form-group full-width" style="display:flex;gap:20px;align-items:center;margin-bottom:16px">
                <label class="toggle">
                    <input type="checkbox" name="allow_static_section_edit" value="1" <?= settingChecked($settings, 'allow_static_section_edit') ?>>
                    <span>Allow admins to edit static sections (Mission, Vision, etc.)</span>
                </label>
                <small style="color:#95a5a6">If OFF, only super_admin can modify them</small>
            </div>
            
            <div class="form-group full-width" style="display:flex;gap:20px;align-items:center">
                <label class="toggle">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= settingChecked($settings, 'maintenance_mode') ?>>
                    <span>Maintenance Mode (public site shows maintenance page)</span>
                </label>
                <small style="color:#e74c3c">Dashboard remains accessible</small>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             🌐 PUBLIC SECTIONS TOGGLES
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #27ae60;margin-top:24px">
            <h3 style="color:#27ae60;margin-bottom:20px">🌐 Public Website Sections</h3>
            <p style="color:#7f8c8d;margin-bottom:16px;font-size:13px">Toggle ON/OFF each section on the public frontend. Data is preserved, just hidden.</p>
            
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
                <?php 
                $sectionToggles = [
                    'show_mission_section' => 'Mission',
                    'show_vision_section' => 'Vision',
                    'show_values_section' => 'Values',
                    'show_history_section' => 'History / Timeline',
                    'show_sectors_section' => 'Sectors',
                    'show_zones_section' => 'Zones',
                    'show_stats_section' => 'Impact Stats',
                    'show_team_section' => 'Team Members',
                    'show_articles_section' => 'Articles / Activities',
                    'show_testimonials_section' => 'Testimonials',
                    'show_announcements_section' => 'Announcements',
                    'show_partners_section' => 'Partners',
                    'show_donate_section' => 'Donate / Support',
                    'show_contact_section' => 'Contact'
                ];
                foreach ($sectionToggles as $key => $label): ?>
                <label class="toggle" style="background:#f8f9fa;padding:10px 14px;border-radius:6px;border:1px solid #e1e1e1;cursor:pointer">
                    <input type="checkbox" name="<?= $key ?>" value="1" <?= settingChecked($settings, $key) ?>>
                    <span style="font-weight:500;color:#2c3e50"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             👥 TEAM & HISTORY DEFAULTS
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #3498db;margin-top:24px">
            <h3 style="color:#3498db;margin-bottom:20px">👥 Team & History Defaults</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Team Members Per Page</label>
                    <input type="number" name="team_members_per_page" value="<?= (int)settingVal($settings, 'team_members_per_page', '12') ?>" min="1" max="100">
                </div>
                <div class="form-group">
                    <label>History Events Per Page</label>
                    <input type="number" name="history_events_per_page" value="<?= (int)settingVal($settings, 'history_events_per_page', '20') ?>" min="1" max="100">
                </div>
                <div class="form-group full-width" style="display:flex;gap:20px">
                    <label class="toggle">
                        <input type="checkbox" name="team_show_email_default" value="1" <?= settingChecked($settings, 'team_show_email_default') ?>>
                        <span>Show email by default for new team members</span>
                    </label>
                    <label class="toggle">
                        <input type="checkbox" name="team_show_phone_default" value="1" <?= settingChecked($settings, 'team_show_phone_default') ?>>
                        <span>Show phone by default for new team members</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             🎨 THEME
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #e67e22;margin-top:24px">
            <h3 style="color:#e67e22;margin-bottom:20px">🎨 Appearance</h3>
            
            <div class="form-group">
                <label>Active Theme Folder</label>
                <input type="text" name="theme_active" value="<?= htmlspecialchars(settingVal($settings, 'theme_active', 'theme-01-classic')) ?>" placeholder="theme-01-classic">
                <small>Used after client selects final theme</small>
            </div>
        </div>
        
        <div class="form-actions" style="margin-top:24px">
            <button type="submit" class="btn btn-primary">💾 Save All Settings</button>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>