<?php
declare(strict_types=1);
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — HomeCare</title>
    <style>
        :root{--primary:#2c3e50;--accent:#3498db;--danger:#e74c3c;--success:#27ae60;--warning:#f39c12;--bg:#f5f6fa;--card:#fff}
        *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
        body{background:var(--bg);color:#333;line-height:1.6}
        a{color:var(--accent);text-decoration:none}
        .layout{display:flex;min-height:100vh}
        /* Sidebar */
        .sidebar{width:260px;background:var(--primary);color:#fff;position:fixed;height:100vh;overflow-y:auto}
        .sidebar-brand{padding:24px 20px;font-size:18px;font-weight:600;border-bottom:1px solid rgba(255,255,255,.1)}
        .sidebar-nav{padding:12px 0}
        .nav-item{display:block;padding:12px 20px;color:rgba(255,255,255,.85);font-size:14px;transition:.2s}
        .nav-item:hover,.nav-item.active{background:rgba(255,255,255,.08);color:#fff}
        .nav-item .icon{display:inline-block;width:24px}
        /* Content */
        .content{margin-left:260px;flex:1;padding:32px;max-width:1200px}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
        .page-header h1{font-size:24px;color:var(--primary)}
        /* Components */
        .card{background:var(--card);border-radius:8px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:24px}
        .btn{display:inline-block;padding:10px 20px;border-radius:6px;font-size:14px;font-weight:500;border:none;cursor:pointer;transition:.2s}
        .btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:#2980b9}
        .btn-secondary{background:#ecf0f1;color:#555}.btn-secondary:hover{background:#dde1e2}
        .btn-danger{background:var(--danger);color:#fff}.btn-danger:hover{background:#c0392b}
        .btn-sm{padding:6px 14px;font-size:13px}
        .table{width:100%;border-collapse:collapse;font-size:14px}
        .table th{text-align:left;padding:14px 12px;border-bottom:2px solid #eee;color:#7f8c8d;font-weight:600;text-transform:uppercase;font-size:12px;letter-spacing:.5px}
        .table td{padding:14px 12px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
        .table tr:hover{background:#fafbfc}
        .badge{display:inline-block;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600;text-transform:uppercase}
        .badge-red{background:#fee;color:#c33}.badge-orange{background:#fff3e0;color:#e65100}
        .badge-green{background:#e8f5e9;color:#2e7d32}.badge-gray{background:#eee;color:#666}
        .status{display:inline-block;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:500}
        .status.active{background:#e8f5e9;color:#2e7d32}.status.inactive{background:#fee;color:#c33}
        .alert{padding:14px 18px;border-radius:6px;margin-bottom:20px;font-size:14px;border-left:4px solid}
        .alert-success{background:#e8f5e9;color:#2e7d32;border-color:#27ae60}
        .alert-error{background:#fee;color:#c33;border-color:#e74c3c}
        .user-cell{display:flex;align-items:center;gap:12px}
        .avatar-sm{width:36px;height:36px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px}
        .user-cell .name{font-weight:500;color:#2c3e50}.user-cell .email{font-size:12px;color:#7f8c8d}
        .meta{color:#7f8c8d;font-size:13px;margin-bottom:16px}
        /* Form */
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .form-group{display:flex;flex-direction:column}
        .form-group.full-width{grid-column:1/-1}
        .form-group label{font-size:13px;font-weight:500;color:#555;margin-bottom:6px}
        .form-group input,.form-group select,.form-group textarea{padding:10px 14px;border:2px solid #e1e1e1;border-radius:6px;font-size:14px}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--accent)}
        .form-group small{color:#95a5a6;font-size:12px;margin-top:4px}
        .form-actions{display:flex;gap:12px;align-items:center;margin-top:8px}
        .toggle{display:flex;align-items:center;gap:8px;cursor:pointer}
        .toggle input{width:18px;height:18px}
        @media(max-width:768px){.sidebar{width:100%;position:relative;height:auto}.content{margin-left:0}.form-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="layout">