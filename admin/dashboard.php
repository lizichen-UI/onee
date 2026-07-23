<?php
require_once __DIR__ . '/auth.php';
require_login();
_ad_enforce_lock();
$content = read_json(CONTENT_FILE) ?: [];
$admin = read_json(ADMIN_FILE) ?: [];
$hideAd = !empty($admin['hide_ad']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>管理后台 - 小狐务器</title>
    <link rel="stylesheet" href="../style/admin.css?v=<?= time() ?>">
    <script>
    // 早期应用自定义后台背景（避免闪烁）
    (function(){
        try {
            var raw = localStorage.getItem('admin_bg_settings_v1');
            if (!raw) return;
            var s = JSON.parse(raw);
            if (!s || !s.mode || s.mode === 'default') return;
            var html = document.documentElement;
            html.setAttribute('data-admin-bg', s.mode);
            if (s.hidePattern) html.setAttribute('data-admin-hide-pattern', '1');
            var st = html.style;
            if (s.mode === 'solid' && s.color1) {
                st.setProperty('--admin-bg-custom', s.color1);
            } else if (s.mode === 'gradient' && s.color1 && s.color2) {
                var ang = (typeof s.angle === 'number' ? s.angle : 135);
                st.setProperty('--admin-bg-custom', 'linear-gradient(' + ang + 'deg, ' + s.color1 + ', ' + s.color2 + ')');
            } else if (s.mode === 'image' && s.imageUrl) {
                st.setProperty('--admin-bg-image', 'url("' + s.imageUrl.replace(/"/g, '\\"') + '")');
            }
            if (typeof s.blur === 'number')    st.setProperty('--admin-blur-strength', s.blur + 'px');
            if (typeof s.overlay === 'number') st.setProperty('--admin-overlay-alpha', (s.overlay / 100));
        } catch(e) {}
    })();
    </script>
</head>
<body>
    <!-- 背景 -->
    <div class="admin-bg">
        <div class="admin-pattern">
            <svg class="admin-cube-svg" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="cube" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                        <path d="M30 0 L60 15 L60 41 L30 52 L0 41 L0 15 Z" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/>
                        <path d="M30 26 L60 15 M30 26 L0 15 M30 26 L30 52" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#cube)"/>
            </svg>
        </div>
    </div>
    <div class="admin-blur"></div>

    <div class="admin-wrap">
        <!-- 顶部栏 -->
        <header class="admin-header">
            <div class="admin-header-top">
                <div class="admin-brand">
                    <span class="brand-name">小狐务器</span>
                    <span class="brand-badge">管理后台</span>
                </div>
                <div class="admin-actions">
                    <a href="../" target="_blank" class="action-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <span>查看前台</span>
                    </a>
                    <form method="post" action="logout.php" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="action-link action-logout" style="border:none;background:none;cursor:pointer;font:inherit;color:inherit;padding:0;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>退出</span>
                        </button>
                    </form>
                </div>
            </div>
            <?php if (!$hideAd): ?>
            <!-- 改了广告被锁了不要在评论区装无辜哭闹然后开始诋毁别人，跟个傻逼一样 -->
            <!-- 广告横幅 -->
            <div class="ad-banner" id="adBanner">
                <a href="https://www.rainyun.com/freehost_" target="_blank" rel="noopener" class="ad-banner-link">
                    <div class="ad-banner-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>
                    </div>
                    <div class="ad-banner-content">
                        <span class="ad-banner-title">雨云</span>
                        <span class="ad-banner-sep">·</span>
                        <span class="ad-banner-desc">服务上万用户低成本上云！</span>
                    </div>
                    <div class="ad-banner-cta">
                        了解详情
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </div>
                </a>
                <button type="button" class="ad-banner-close" id="adCloseBtn" title="关闭广告">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <span class="ad-banner-badge">AD</span>
            </div>
            <?php endif; ?>
        </header>

        <div class="admin-body">
            <!-- 侧边导航 -->
            <nav class="admin-nav">
                <div class="nav-item active" data-tab="site">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    <span>站点设置</span>
                </div>
                <div class="nav-item" data-tab="intro">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>个人简介</span>
                </div>
                <div class="nav-item" data-tab="skills">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <span>技能管理</span>
                </div>
                <div class="nav-item" data-tab="projects">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    <span>项目管理</span>
                </div>
                <div class="nav-item" data-tab="posts">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>帖子管理</span>
                </div>
                <div class="nav-item" data-tab="contact">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>联系方式</span>
                </div>
                <div class="nav-item" data-tab="others">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>其他设置</span>
                </div>
                <div class="nav-divider"></div>
                <div class="nav-item" data-tab="smtp">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <span>SMTP 邮件</span>
                </div>
                <div class="nav-item" data-tab="messages">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>
                    <span>消息管理</span>
                    <span class="nav-badge" id="msgBadge" style="display:none">0</span>
                </div>
                <div class="nav-divider"></div>
                <div class="nav-item" data-tab="payment">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <span>支付设置</span>
                </div>
                <div class="nav-item" data-tab="shop">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <span>商铺设置</span>
                </div>
                <div class="nav-divider"></div>
                <div class="nav-item" data-tab="backup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>数据备份</span>
                </div>
                <div class="nav-item" data-tab="stats">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span>访客统计</span>
                </div>
                <div class="nav-item" data-tab="oplogs">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 3v18h18"/><path d="M7 14v4"/><path d="M12 10v8"/><path d="M17 6v12"/></svg>
                    <span>操作日志</span>
                </div>
                <div class="nav-divider"></div>
                <div class="nav-item" data-tab="appearance">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                    <span>外观主题</span>
                </div>
                <div class="nav-item" data-tab="password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>修改密码</span>
                </div>
                <div class="nav-divider"></div>
                <div class="nav-item nav-item-danger" data-tab="nuke">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    <span>一键跑路</span>
                </div>

                <div class="nav-footer">
                    <span>小狐务器 · 个人主页系统</span>
                </div>
            </nav>

            <!-- 主内容区 -->
            <main class="admin-main">

                <!-- ========== 站点设置 ========== -->
                <section class="tab-panel active" id="tab-site">
                    <div class="panel-header">
                        <h2>站点设置</h2>
                        <p class="panel-desc">修改头像、标题、副标题和像素文字内容</p>
                    </div>

                    <!-- 头像上传 -->
                    <div class="glass-card avatar-upload-card">
                        <div class="avatar-upload-wrap">
                            <div class="avatar-preview" id="avatarPreview">
                                <div class="avatar-preview-placeholder" id="avatarPlaceholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <img id="avatarImg" class="avatar-preview-img" src="" alt="头像" style="display:none">
                            </div>
                            <div class="avatar-upload-info">
                                <h4>头像设置</h4>
                                <p class="form-hint">支持 JPG/PNG/GIF/WebP，不超过 5MB，将自动裁剪为正方形</p>
                                <div class="avatar-upload-actions">
                                    <label class="btn btn-primary btn-sm" for="avatarFile">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        上传头像
                                    </label>
                                    <button type="button" class="btn btn-outline btn-sm" id="deleteAvatarBtn" style="display:none">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        删除
                                    </button>
                                    <input type="file" id="avatarFile" accept="image/*" style="display:none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 背景设置 -->
                    <div class="glass-card" style="margin-top: 14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">背景设置</h4>

                        <!-- 背景模式选择 -->
                        <div class="form-group">
                            <label>背景模式</label>
                            <div class="bg-mode-selector" id="bgModeSelector">
                                <label class="bg-mode-option active" data-mode="default">
                                    <div class="bg-mode-preview bg-mode-default-preview"></div>
                                    <span>默认纹理</span>
                                </label>
                                <label class="bg-mode-option" data-mode="image">
                                    <div class="bg-mode-preview bg-mode-image-preview" id="bgModeImageThumb"></div>
                                    <span>自定义图片</span>
                                </label>
                            </div>
                        </div>

                        <!-- 背景图片上传（仅图片模式显示） -->
                        <div class="bg-image-section" id="bgImageSection" style="display:none;">
                            <div class="form-group">
                                <label>背景图片</label>
                                <div class="bg-upload-row">
                                    <div class="bg-thumb" id="bgThumb">
                                        <div class="bg-thumb-empty" id="bgThumbEmpty">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        </div>
                                        <img id="bgThumbImg" class="bg-thumb-img" src="" alt="背景预览" style="display:none">
                                    </div>
                                    <div class="bg-upload-actions">
                                        <label class="btn btn-primary btn-sm" for="bgFile">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                            上传图片
                                        </label>
                                        <button type="button" class="btn btn-outline btn-sm" id="deleteBgBtn" style="display:none">删除</button>
                                        <input type="file" id="bgFile" accept="image/*" style="display:none">
                                    </div>
                                </div>
                                <span class="form-hint">支持 JPG/PNG/GIF/WebP，不超过 10MB</span>
                            </div>
                        </div>

                        <!-- 模糊度和暗度滑块 -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label>背景模糊度: <span id="blurValue">6</span>px</label>
                                <input type="range" id="bgBlurRange" name="bg_blur" min="0" max="30" step="1" value="6" class="range-input">
                            </div>
                            <div class="form-group">
                                <label>遮罩不透明度: <span id="opacityValue">70</span>%</label>
                                <input type="range" id="bgOpacityRange" name="bg_opacity" min="0" max="100" step="5" value="70" class="range-input">
                                <span class="form-hint">数值越高背景越暗，文字越清晰</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveBgBtn">保存背景设置</button>
                        </div>
                    </div>

                    <form class="glass-card" id="siteForm" style="margin-top: 14px;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>标题前缀</label>
                                <input type="text" name="title_prefix" value="<?= e($content['title_prefix'] ?? '') ?>" placeholder="欢迎来到">
                            </div>
                            <div class="form-group">
                                <label>主标题</label>
                                <input type="text" name="title" value="<?= e($content['title'] ?? '') ?>" placeholder="小狐务器">
                            </div>
                            <div class="form-group">
                                <label>副标题前缀</label>
                                <input type="text" name="subtitle_prefix" value="<?= e($content['subtitle_prefix'] ?? '') ?>" placeholder="Welcome...">
                            </div>
                            <div class="form-group">
                                <label>副标题名称</label>
                                <input type="text" name="subtitle_name" value="<?= e($content['subtitle_name'] ?? '') ?>" placeholder="Xiaohu Server">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>像素横幅文字</label>
                            <input type="text" name="pixel_text" value="<?= e($content['pixel_text'] ?? '') ?>" placeholder="我爱雨云" maxlength="4">
                            <span class="form-hint">显示在标题下方的像素点阵动画文字（最多 4 个字）</span>
                        </div>
                        <?php
                            $pixelMode = $content['pixel_mode'] ?? 'text';
                            // 兼容旧值
                            if ($pixelMode === 'svg') $pixelMode = 'snake';
                            if ($pixelMode === 'off') $pixelMode = 'text';
                        ?>
                        <div class="form-group">
                            <label>像素横幅显示模式</label>
                            <div class="pixel-mode-options">
                                <label class="pixel-mode-option <?= $pixelMode === 'text' ? 'active' : '' ?>">
                                    <input type="radio" name="pixel_mode" value="text" <?= $pixelMode === 'text' ? 'checked' : '' ?>>
                                    <span class="pixel-mode-title">文字</span>
                                    <span class="pixel-mode-desc">默认像素点阵动画</span>
                                </label>
                                <label class="pixel-mode-option <?= $pixelMode === 'snake' ? 'active' : '' ?>">
                                    <input type="radio" name="pixel_mode" value="snake" <?= $pixelMode === 'snake' ? 'checked' : '' ?>>
                                    <span class="pixel-mode-title">蛇形横幅</span>
                                    <span class="pixel-mode-desc">GitHub 贡献蛇样式</span>
                                </label>
                            </div>
                            <span class="form-hint">「蛇形横幅」会使用内置 <code>APP/snake-Light.svg</code>。</span>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">保存设置</button>
                        </div>
                    </form>

                    <div class="glass-card" style="margin-top:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">站点公告横幅</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>显示公告横幅</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="announcementEnabled" <?= !empty($content['announcement']['enabled']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>公告文字</label>
                            <input type="text" id="announcementText" value="<?= e($content['announcement']['text'] ?? '') ?>" maxlength="120" placeholder="例如：网站迁移中，请优先使用新域名">
                        </div>
                        <div class="form-group">
                            <label>公告链接</label>
                            <input type="url" id="announcementLink" value="<?= e($content['announcement']['link'] ?? '') ?>" placeholder="https://example.com">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveAnnouncementBtn">保存公告设置</button>
                        </div>
                    </div>

                    <!-- 渐变色设置 -->
                    <div class="glass-card" style="margin-top: 14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">标题渐变色</h4>

                        <!-- 实时预览 -->
                        <div class="gradient-preview" id="gradientPreview">
                            <span class="gradient-preview-text" id="gradientPreviewText">小狐务器</span>
                        </div>

                        <!-- 色块列表 -->
                        <div class="form-group">
                            <label>渐变颜色（点击修改，最少 2 个）</label>
                            <div class="gradient-colors" id="gradientColors"></div>
                            <div class="gradient-color-actions">
                                <button type="button" class="btn btn-outline btn-sm" id="addColorBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    添加颜色
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="resetColorsBtn">恢复默认</button>
                            </div>
                        </div>

                        <!-- 预设方案 -->
                        <div class="form-group">
                            <label>快速预设</label>
                            <div class="gradient-presets" id="gradientPresets">
                                <button type="button" class="gradient-preset" data-colors="#ff6b6b,#ffd93d,#6bcb77,#4d96ff,#9b59b6" title="彩虹">
                                    <span style="background:linear-gradient(90deg,#ff6b6b,#ffd93d,#6bcb77,#4d96ff,#9b59b6)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#f093fb,#f5576c" title="粉紫">
                                    <span style="background:linear-gradient(90deg,#f093fb,#f5576c)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#4facfe,#00f2fe" title="天蓝">
                                    <span style="background:linear-gradient(90deg,#4facfe,#00f2fe)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#43e97b,#38f9d7" title="薄荷">
                                    <span style="background:linear-gradient(90deg,#43e97b,#38f9d7)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#fa709a,#fee140" title="日落">
                                    <span style="background:linear-gradient(90deg,#fa709a,#fee140)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#a18cd1,#fbc2eb" title="淡紫">
                                    <span style="background:linear-gradient(90deg,#a18cd1,#fbc2eb)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#ffecd2,#fcb69f" title="暖橙">
                                    <span style="background:linear-gradient(90deg,#ffecd2,#fcb69f)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#ff9a9e,#fecfef,#fdfcfb" title="樱花">
                                    <span style="background:linear-gradient(90deg,#ff9a9e,#fecfef,#fdfcfb)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#667eea,#764ba2" title="靛蓝">
                                    <span style="background:linear-gradient(90deg,#667eea,#764ba2)"></span>
                                </button>
                                <button type="button" class="gradient-preset" data-colors="#f7971e,#ffd200" title="金黄">
                                    <span style="background:linear-gradient(90deg,#f7971e,#ffd200)"></span>
                                </button>
                            </div>
                        </div>

                        <!-- 动画开关 -->
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>流动动画效果</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="gradientAnimateToggle" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">开启后渐变色会持续流动，关闭则为静态渐变</span>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveGradientBtn">保存渐变设置</button>
                        </div>
                    </div>
                </section>

                <!-- ========== 个人简介 ========== -->
                <section class="tab-panel" id="tab-intro">
                    <div class="panel-header">
                        <h2>个人简介</h2>
                        <p class="panel-desc">编辑首页的个人简介段落内容</p>
                    </div>
                    <form class="glass-card" id="introForm">
                        <div class="form-group">
                            <label>简介内容</label>
                            <textarea name="intro" rows="6" placeholder="在这里介绍你自己..."><?= e($content['intro'] ?? '') ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">保存简介</button>
                        </div>
                    </form>
                </section>

                <!-- ========== 技能管理 ========== -->
                <section class="tab-panel" id="tab-skills">
                    <div class="panel-header">
                        <h2>技能管理</h2>
                        <p class="panel-desc">添加、删除和排序你的技能标签</p>
                    </div>
                    <div class="glass-card">
                        <div class="skill-input-row">
                            <input type="text" id="newSkill" placeholder="输入新技能名称..." class="skill-input">
                            <button type="button" class="btn btn-primary" id="addSkillBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                添加
                            </button>
                        </div>
                        <div class="skills-list" id="skillsList">
                            <?php foreach (($content['skills'] ?? []) as $skill): ?>
                                <div class="skill-item">
                                    <span class="skill-text"><?= e($skill) ?></span>
                                    <button type="button" class="skill-remove" title="删除">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveSkillsBtn">保存技能</button>
                        </div>
                    </div>
                </section>

                <!-- ========== 项目管理 ========== -->
                <section class="tab-panel" id="tab-projects">
                    <div class="panel-header">
                        <h2>项目管理</h2>
                        <p class="panel-desc">管理首页展示的项目卡片</p>
                    </div>
                    <div class="glass-card">
                        <div class="projects-editor" id="projectsEditor">
                            <?php foreach (($content['projects'] ?? []) as $i => $proj): ?>
                                <div class="project-edit-card" data-index="<?= $i ?>">
                                    <div class="project-edit-header">
                                        <span class="project-edit-num">#<?= $i + 1 ?></span>
                                        <button type="button" class="btn-icon project-remove" title="删除项目">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>项目名称</label>
                                            <input type="text" name="proj_title" value="<?= e($proj['title']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>图标类型</label>
                                            <select name="proj_icon">
                                                <?php
                                                $icons = ['globe' => '地球', 'code' => '代码', 'layout' => '布局', 'sparkle' => '星光', 'server' => '服务器', 'palette' => '调色板', 'terminal' => '终端', 'book' => '书本'];
                                                foreach ($icons as $val => $label):
                                                ?>
                                                    <option value="<?= e($val) ?>" <?= ($proj['icon'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>项目描述</label>
                                        <input type="text" name="proj_desc" value="<?= e($proj['desc']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>项目链接 <span style="opacity:.5;font-weight:400">（选填，留空则不跳转）</span></label>
                                        <input type="url" name="proj_link" value="<?= e($proj['link'] ?? '') ?>" placeholder="https://">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline add-project-btn" id="addProjectBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            添加项目
                        </button>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveProjectsBtn">保存项目</button>
                        </div>
                    </div>
                </section>

                <!-- ========== 帖子管理 ========== -->
                <section class="tab-panel" id="tab-posts">
                    <div class="panel-header">
                        <h2>帖子管理</h2>
                        <p class="panel-desc">管理前台展示的帖子内容，支持设置封面图</p>
                    </div>

                    <!-- 帖子模块开关 -->
                    <div class="glass-card" style="margin-bottom:14px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="toggle-label">
                                <span>显示帖子模块</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="postsEnabledTop" <?= !empty($content['posts_enabled']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">关闭后前台将隐藏整个帖子区块</span>
                        </div>
                        <div class="form-actions" style="margin-top:12px;">
                            <button type="button" class="btn btn-primary btn-sm" id="savePostsEnabledTopBtn">保存设置</button>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="posts-toolbar">
                            <button type="button" class="btn btn-primary btn-sm" id="addPostBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                新建帖子
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" id="refreshPostsBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                刷新
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" id="openMediaManagerBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                图片管理器
                            </button>
                        </div>

                        <!-- 搜索 + 筛选 + 统计 -->
                        <div class="posts-filter-bar" id="postsFilterBar">
                            <div class="posts-search-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="postsSearchInput" class="posts-search-input" placeholder="搜索帖子标题...">
                            </div>
                            <div class="posts-filter-group">
                                <select id="postsStatusFilter" class="posts-status-filter">
                                    <option value="all">全部状态</option>
                                    <option value="published">已发布</option>
                                    <option value="draft">草稿</option>
                                    <option value="pinned">已置顶</option>
                                </select>
                                <select id="postsTagFilter" class="posts-status-filter">
                                    <option value="">全部标签</option>
                                </select>
                                <select id="postsSortSelect" class="posts-sort-select">
                                    <option value="newest">最新创建</option>
                                    <option value="oldest">最早创建</option>
                                    <option value="title_asc">标题 A→Z</option>
                                    <option value="title_desc">标题 Z→A</option>
                                </select>
                            </div>
                            <div class="posts-stats" id="postsStats"></div>
                        </div>

                        <!-- 批量操作栏 -->
                        <div class="posts-batch-bar" id="postsBatchBar" style="display:none;">
                            <label class="posts-select-all-label">
                                <input type="checkbox" id="postsSelectAll">
                                <span class="cb-visual"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                <span>全选</span>
                            </label>
                            <span class="posts-batch-count" id="postsBatchCount">已选 0 项</span>
                            <div class="posts-batch-actions">
                                <button type="button" class="btn btn-outline btn-sm" id="batchPublishBtn" title="批量发布">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    发布
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="batchUnpublishBtn" title="批量取消发布">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                    取消发布
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" id="batchDeleteBtn" title="批量删除">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    删除
                                </button>
                            </div>
                        </div>

                        <div class="posts-list" id="postsList">
                            <div class="posts-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                <p>暂无帖子，点击上方按钮新建</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========== 联系方式 ========== -->
                <section class="tab-panel" id="tab-contact">
                    <div class="panel-header">
                        <h2>联系方式</h2>
                        <p class="panel-desc">设置首页展示的 QQ / 微信 / 邮箱 / GitHub 信息</p>
                    </div>
                    <form class="glass-card" id="contactForm">
                        <div class="form-group">
                            <label>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="label-icon"><circle cx="12" cy="12" r="10"/><path d="M8 15a3.5 3.5 0 0 0 8 0"/></svg>
                                QQ
                            </label>
                            <input type="text" name="qq" value="<?= e($content['contact']['qq'] ?? '') ?>" placeholder="你的 QQ 号">
                        </div>
                        <div class="form-group">
                            <label>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="label-icon"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>
                                微信号
                            </label>
                            <input type="text" name="wechat" value="<?= e($content['contact']['wechat'] ?? '') ?>" placeholder="your_wechat">
                        </div>
                        <div class="form-group">
                            <label>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="label-icon"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                邮箱
                            </label>
                            <input type="email" name="email" value="<?= e($content['contact']['email'] ?? '') ?>" placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="label-icon"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
                                GitHub
                            </label>
                            <input type="text" name="github" value="<?= e($content['contact']['github'] ?? '') ?>" placeholder="github.com/username">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">保存联系方式</button>
                        </div>
                    </form>
                </section>

                <!-- ========== 其他设置 ========== -->
                <section class="tab-panel" id="tab-others">
                    <div class="panel-header">
                        <h2>其他设置</h2>
                        <p class="panel-desc">终端展示内容和音乐播放器的开关设置</p>
                    </div>

                    <!-- 终端展示设置 -->
                    <div class="glass-card">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">终端展示</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>启用终端展示</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="terminalEnabled" <?= (!empty($content['terminal']['enabled'])) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">关闭后前台将不显示终端卡片</span>
                        </div>
                        <div class="form-group" style="margin-top:16px;">
                            <label>终端标题</label>
                            <input type="text" id="terminalTitle" value="<?= e($content['terminal']['title'] ?? 'bash') ?>" placeholder="bash">
                        </div>
                        <div class="form-group">
                            <label>终端内容（每行一条，支持命令/输出/提示符）</label>
                            <div class="form-hint" style="margin-bottom:8px;">
                                <span style="color:#50fa7b;">$</span> = 命令行 &nbsp;
                                <span style="color:#ffb86c;">#</span> = 输出 &nbsp;
                                <span style="color:#50fa7b;">></span> = 提示符
                            </div>
                            <textarea id="terminalContent" rows="8" placeholder="$ npm install next&#10;+ next@10.2.3&#10;added 1 package&#10;$"><?php
                                if (!empty($content['terminal']['commands'])) {
                                    foreach ($content['terminal']['commands'] as $cmd) {
                                        $prefix = $cmd['type'] === 'command' ? '$ ' : ($cmd['type'] === 'output' ? '# ' : '> ');
                                        echo e($prefix . $cmd['content']) . "\n";
                                    }
                                }
                            ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveTerminalBtn">保存终端设置</button>
                        </div>
                    </div>

                    <!-- 音乐播放器设置 -->
                    <div class="glass-card music-player-settings" style="margin-top:14px;">
                        <h4>音乐播放器</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>启用音乐播放器</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="musicPlayerEnabled" <?= (!empty($content['music_player']['enabled'])) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">关闭后前台将不显示右下角的音乐播放器</span>
                        </div>

                        <div class="form-group music-mode-group">
                            <label class="music-section-label">播放模式</label>
                            <div class="music-mode-options">
                                <label class="music-mode-option">
                                    <input type="radio" name="musicMode" value="random" id="musicModeRandom" <?= (empty($content['music_player']['mode']) || ($content['music_player']['mode'] ?? '') === 'random') ? 'checked' : '' ?>> 随机模式（在线API）
                                </label>
                                <label class="music-mode-option">
                                    <input type="radio" name="musicMode" value="custom" id="musicModeCustom" <?= (($content['music_player']['mode'] ?? '') === 'custom') ? 'checked' : '' ?>> 自定义歌单
                                </label>
                            </div>
                        </div>

                        <div id="musicPlaylistPanel" class="music-playlist-panel">
                            <label class="music-section-label">歌单列表</label>
                            <div id="musicPlaylistBody" class="music-playlist-body"></div>
                            <div class="music-playlist-footer">
                                <button type="button" class="btn btn-sm" id="musicAddSongBtn">+ 添加歌曲</button>
                                <span class="form-hint">支持直接填写 MP3 链接，封面地址可留空</span>
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top:14px;">
                            <button type="button" class="btn btn-primary" id="saveMusicPlayerBtn">保存播放器设置</button>
                        </div>
                    </div>

                    <div class="glass-card" style="margin-top:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">社交媒体滚动</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>启用左侧社交媒体滚动条</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="socialMarqueeEnabled" <?= !array_key_exists('social_marquee_enabled', $content) || !empty($content['social_marquee_enabled']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">关闭后，前台头像下方的社交平台滚动条将隐藏（默认开启）</span>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveSocialMarqueeBtn">保存滚动条设置</button>
                        </div>
                    </div>

                    <div class="glass-card" style="margin-top:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">项目链接跳转确认</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>启用项目跳转二次确认弹窗</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="projectLinkConfirmEnabled" <?= !empty($content['project_link_confirm']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">开启后，点击前台项目链接时会先显示确认弹窗</span>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveProjectLinkConfirmBtn">保存跳转确认设置</button>
                        </div>
                    </div>

                    <!-- 赞助配置 -->
                    <div class="glass-card" style="margin-top:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">赞助 / 打赏</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>启用赞助侧边栏按钮</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="sponsorEnabled" <?= !empty($content['sponsor']['enabled']) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">开启后前台右侧会显示赞助悬浮按钮</span>
                        </div>
                        <div class="form-group" style="margin-top:14px;">
                            <label>弹窗标题</label>
                            <input type="text" id="sponsorTitle" value="<?= e($content['sponsor']['title'] ?? '请我喝杯咖啡') ?>" placeholder="请我喝杯咖啡">
                        </div>
                        <div class="form-group">
                            <label>弹窗副标题</label>
                            <input type="text" id="sponsorSubtitle" value="<?= e($content['sponsor']['subtitle'] ?? '你的支持是我创作的动力 ☕') ?>" placeholder="你的支持是我创作的动力 ☕">
                        </div>
                        <div class="form-group" style="margin-top:16px;">
                            <label style="margin-bottom:10px;display:block;">收款二维码（最多 4 个）</label>
                            <div id="sponsorQrList" class="sponsor-qr-grid">
                                <?php
                                $qrcodes = $content['sponsor']['qrcodes'] ?? [];
                                if (empty($qrcodes)) $qrcodes = [['label'=>'微信支付','image'=>''],['label'=>'支付宝','image'=>'']];
                                foreach ($qrcodes as $idx => $qr):
                                    $qrImg = $qr['image'] ?? '';
                                    $qrLabel = $qr['label'] ?? '';
                                ?>
                                <div class="sponsor-qr-row" data-idx="<?= $idx ?>">
                                    <div class="sponsor-qr-preview-wrap">
                                        <?php if ($qrImg): ?>
                                        <img src="../<?= e($qrImg) ?>" alt="二维码" class="sponsor-qr-thumb">
                                        <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="sponsor-qr-thumb-placeholder"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h.01M14 17h.01M17 14h.01M17 17h.01M20 14h.01M20 17h.01M20 20h.01M17 20h.01M14 20h.01"/></svg>
                                        <?php endif; ?>
                                        <div class="sponsor-qr-hover">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                            <span>上传</span>
                                        </div>
                                        <?php if ($qrImg): ?>
                                        <button type="button" class="sponsor-qr-delete" data-idx="<?= $idx ?>" title="删除">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" class="sponsor-qr-label-input" value="<?= e($qrLabel) ?>" placeholder="标签名">
                                    <input type="file" class="sponsor-qr-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" data-idx="<?= $idx ?>">
                                    <input type="hidden" class="sponsor-qr-image-val" value="<?= e($qrImg) ?>">
                                </div>
                                <?php endforeach; ?>
                                <button type="button" class="sponsor-qr-add" id="addSponsorQrBtn" <?= count($qrcodes) >= 4 ? 'style="display:none;"' : '' ?>>
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    <span>添加</span>
                                </button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveSponsorBtn">保存赞助设置</button>
                        </div>
                    </div>

                </section>

                <!-- ========== SMTP 邮件 ========== -->
                <section class="tab-panel" id="tab-smtp">
                    <div class="panel-header">
                        <h2>SMTP 邮件设置</h2>
                        <p class="panel-desc">配置 SMTP 服务器，用于接收留言通知和回复访客邮件</p>
                    </div>
                    <form class="glass-card" id="smtpForm">
                        <div class="form-grid">
                            <div class="form-group" style="margin-bottom:0;grid-column:1/-1;">
                                <label class="toggle-label">
                                    <span>启用 SMTP 邮件功能</span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="smtpEnabled">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </label>
                                <span class="form-hint">关闭后，所有邮件发送（留言通知、邮件回复、密码重置验证码、卡密发货）均不可用；连接测试不受此开关限制</span>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>SMTP 服务器</label>
                                <input type="text" name="host" placeholder="smtp.example.com">
                            </div>
                            <div class="form-group form-grid-half">
                                <div class="form-group">
                                    <label>端口</label>
                                    <input type="number" name="port" value="587" placeholder="587">
                                </div>
                                <div class="form-group">
                                    <label>加密方式</label>
                                    <select name="encryption">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">无</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>SMTP 用户名</label>
                                <input type="text" name="username" placeholder="your@email.com">
                            </div>
                            <div class="form-group">
                                <label>SMTP 密码</label>
                                <input type="password" name="password" placeholder="留空表示不修改">
                                <span class="form-hint">部分邮箱需使用授权码而非登录密码</span>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>发件人名称</label>
                                <input type="text" name="from_name" placeholder="小狐务器">
                            </div>
                            <div class="form-group">
                                <label>发件人邮箱</label>
                                <input type="email" name="from_email" placeholder="your@email.com">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">保存配置</button>
                            <button type="button" class="btn btn-outline" id="testSmtpBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                测试连接
                            </button>
                        </div>
                        <div class="smtp-log" id="smtpLog" style="display:none">
                            <h4>连接日志</h4>
                            <pre id="smtpLogContent"></pre>
                        </div>
                    </form>
                </section>

                <!-- ========== 消息管理 ========== -->
                <section class="tab-panel" id="tab-messages">
                    <div class="panel-header">
                        <h2>消息管理</h2>
                        <p class="panel-desc">查看访客留言，通过 SMTP 回复邮件</p>
                    </div>

                    <!-- 免打扰设置 -->
                    <div class="glass-card dnd-card" style="margin-bottom: 14px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="toggle-label">
                                <div class="dnd-label-content">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="label-icon"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    <div>
                                        <span>消息免打扰</span>
                                        <span class="dnd-status-text" id="dndStatusText">关闭 · 新消息将发送邮件通知</span>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="dndToggle">
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="messages-toolbar">
                            <button type="button" class="btn btn-outline btn-sm" id="refreshMsgsBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                刷新
                            </button>
                        </div>
                        <div class="messages-list" id="messagesList">
                            <div class="messages-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <p>暂无消息</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========== 支付设置 ========== -->
                <section class="tab-panel" id="tab-payment">
                    <div class="panel-header">
                        <h2>支付设置</h2>
                        <p class="panel-desc">配置易支付接口，用于在线收款与支付功能</p>
                    </div>

                    <form class="glass-card" id="paymentForm">
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>启用支付功能</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="enabled" id="paymentEnabled">
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                        </div>

                        <div class="form-group">
                            <label>支付接口地址</label>
                            <input type="url" name="api_url" placeholder="易支付平台地址">
                            <span class="form-hint">推荐自己搭建，此类平台很多跑路狗请慎重选择！</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>商户ID (PID)</label>
                                <input type="text" name="pid" placeholder="平台商户ID" inputmode="numeric">
                            </div>
                            <div class="form-group">
                                <label>商户密钥 (KEY) / 商户MD5密钥 </label>
                                <input type="password" name="key" placeholder="平台商户密钥">
                                <span class="form-hint">采用V1接口可接入大部分老平台</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>默认支付方式</label>
                            <select name="default_type">
                                <option value="alipay">支付宝</option>
                                <option value="wxpay">微信支付</option>
                                <option value="qqpay">QQ 钱包</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                保存配置
                            </button>
                        </div>
                    </form>

                    <!-- 接口测试 -->
                    <div class="glass-card" style="margin-top:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">接口连通性测试</h4>
                        <p class="panel-desc" style="margin-bottom:14px;">验证接口地址、商户ID、密钥是否正确配置，查询商户账户信息</p>
                        <div class="form-actions" style="margin-bottom:0;">
                            <button type="button" class="btn btn-outline" id="testPaymentBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                测试连接
                            </button>
                        </div>
                        <div class="payment-test-result" id="paymentTestResult" style="display:none;margin-top:14px;">
                            <div class="payment-info-grid" id="paymentInfoGrid"></div>
                        </div>
                    </div>

                    <!-- 支付测试订单 -->
                    <div class="glass-card" style="margin-top:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">发起测试支付</h4>
                        <p class="panel-desc" style="margin-bottom:14px;">创建一笔小额测试订单，验证完整支付流程是否正常</p>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>支付方式</label>
                                <select id="testPayType">
                                    <option value="alipay">支付宝</option>
                                    <option value="wxpay">微信支付</option>
                                    <option value="qqpay">QQ 钱包</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>测试金额（元）</label>
                                <input type="text" id="testPayAmount" value="0.01" placeholder="0.01" inputmode="decimal">
                                <span class="form-hint">范围 0.01 ~ 100 元</span>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="testPayOrderBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                发起测试支付
                            </button>
                        </div>
                        <div class="payment-order-result" id="paymentOrderResult" style="display:none;margin-top:14px;">
                            <div class="payment-order-info" id="paymentOrderInfo"></div>
                        </div>
                    </div>
                </section>

                <!-- ========== 商铺设置 ========== -->
                <section class="tab-panel" id="tab-shop">
                    <div class="panel-header">
                        <h2>商铺设置</h2>
                        <p class="panel-desc">卡密发卡商城，支持多支付方式、自动发货、订单管理</p>
                    </div>

                    <!-- 子标签页切换 -->
                    <div class="shop-tabs" role="tablist">
                        <button type="button" class="shop-tab active" data-shop-tab="overview" role="tab">数据概览</button>
                        <button type="button" class="shop-tab" data-shop-tab="products" role="tab">商品管理</button>
                        <button type="button" class="shop-tab" data-shop-tab="orders" role="tab">订单管理</button>
                        <button type="button" class="shop-tab" data-shop-tab="cards" role="tab">卡密管理</button>
                        <button type="button" class="shop-tab" data-shop-tab="marketing" role="tab">营销/通知</button>
                    </div>

                    <!-- 概览 -->
                    <div class="shop-subpanel active" id="shop-panel-overview">
                        <div class="shop-stat-grid">
                            <div class="shop-stat-card">
                                <div class="shop-stat-label">7日流水</div>
                                <div class="shop-stat-value" id="statRevenue">¥ 0.00</div>
                            </div>
                            <div class="shop-stat-card">
                                <div class="shop-stat-label">已付订单</div>
                                <div class="shop-stat-value" id="statPaid">0</div>
                            </div>
                            <div class="shop-stat-card">
                                <div class="shop-stat-label">待支付</div>
                                <div class="shop-stat-value" id="statPending">0</div>
                            </div>
                            <div class="shop-stat-card">
                                <div class="shop-stat-label">异常订单</div>
                                <div class="shop-stat-value shop-stat-danger" id="statFailed">0</div>
                            </div>
                        </div>

                        <div class="glass-card" style="margin-top:14px;">
                            <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">最近 7 天流水趋势</h4>
                            <div class="shop-chart" id="shopChart"></div>
                        </div>

                        <div class="shop-two-col">
                            <div class="glass-card">
                                <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">畅销商品 TOP 5</h4>
                                <div class="shop-top-list" id="topProductsList">
                                    <div class="shop-empty">暂无销售数据</div>
                                </div>
                            </div>
                            <div class="glass-card">
                                <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">低库存预警（&lt; 5）</h4>
                                <div class="shop-top-list" id="lowStockList">
                                    <div class="shop-empty">库存充足</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 商品管理 -->
                    <div class="shop-subpanel" id="shop-panel-products">
                        <div class="shop-toolbar">
                            <button type="button" class="btn btn-primary btn-sm" id="addProductBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                新增商品
                            </button>
                            <select id="productCategoryFilter" class="posts-status-filter" title="按分类筛选">
                                <option value="">全部分类</option>
                            </select>
                            <button type="button" class="btn btn-outline btn-sm" id="refreshProductsBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                刷新
                            </button>
                            <div class="shop-dropdown" id="productsBatchDropdown">
                                <button type="button" class="btn btn-outline btn-sm shop-dropdown-trigger">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                                    快捷操作
                                </button>
                                <div class="shop-dropdown-menu">
                                    <button type="button" data-prod-batch="publish-all">一键全部发布</button>
                                    <button type="button" data-prod-batch="unpublish-all">一键全部下架</button>
                                    <div class="shop-dropdown-divider"></div>
                                    <button type="button" data-prod-batch="cleanup-empty" class="text-danger">清理零库存商品</button>
                                </div>
                            </div>
                        </div>
                        <div class="shop-product-grid" id="shopProductGrid">
                            <div class="shop-empty">暂无商品，请点击上方按钮新增</div>
                        </div>
                    </div>

                    <!-- 订单管理 -->
                    <div class="shop-subpanel" id="shop-panel-orders">
                        <div class="shop-orders-toolbar">
                            <div class="shop-orders-filters">
                                <select id="orderStatusFilter" class="posts-status-filter">
                                    <option value="">全部订单</option>
                                    <option value="1">已支付</option>
                                    <option value="0">待支付</option>
                                    <option value="3">异常（缺货）</option>
                                </select>
                                <input type="text" id="orderSearchInput" class="form-input-inline" placeholder="搜索订单号 / 商品 / IP" style="min-width:200px;">
                                <input type="date" id="orderStartDate" class="form-input-inline" title="开始日期">
                                <span style="color:rgba(255,255,255,0.4)">~</span>
                                <input type="date" id="orderEndDate" class="form-input-inline" title="结束日期">
                                <button type="button" class="btn btn-primary btn-sm" id="applyOrderFilterBtn">筛选</button>
                                <button type="button" class="btn btn-outline btn-sm" id="resetOrderFilterBtn">重置</button>
                                <button type="button" class="btn btn-outline btn-sm" id="refreshOrdersBtn">刷新</button>
                            </div>
                            <div class="shop-orders-tools">
                                <button type="button" class="btn btn-outline btn-sm" id="exportOrdersBtn" title="导出当前筛选结果为 CSV">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    导出 CSV
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="cleanupPendingBtn" title="清理长时间未支付的订单">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                    清理超时
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="toggleOrderBulkBtn">批量模式</button>
                            </div>
                        </div>
                        <div class="shop-orders-bulkbar" id="ordersBulkBar" style="display:none;">
                            <label class="bulk-check">
                                <input type="checkbox" id="orderSelectAll">
                                <span>全选</span>
                            </label>
                            <span class="bulk-info">已选 <strong id="orderSelectedCount">0</strong> 条</span>
                            <span class="bulk-actions">
                                <button type="button" class="btn btn-outline btn-sm" data-bulk="mark-paid">标为已支付</button>
                                <button type="button" class="btn btn-outline btn-sm" data-bulk="mark-pending">标为待支付</button>
                                <button type="button" class="btn btn-outline btn-sm" data-bulk="mark-failed">标为异常</button>
                                <button type="button" class="btn btn-danger btn-sm" data-bulk="delete">删除选中</button>
                            </span>
                        </div>
                        <div class="shop-order-list" id="shopOrderList">
                            <div class="shop-empty">暂无订单</div>
                        </div>
                    </div>

                    <!-- 卡密管理 -->
                    <div class="shop-subpanel" id="shop-panel-cards">
                        <div class="shop-cards-stat-bar">
                            <div class="shop-cards-stat-item">总计 <strong id="cardsStatTotal">0</strong></div>
                            <div class="shop-cards-stat-item">可用 <strong id="cardsStatAvail" class="shop-stat-ok">0</strong></div>
                            <div class="shop-cards-stat-item">已售 <strong id="cardsStatUsed">0</strong></div>
                        </div>
                        <div class="shop-orders-toolbar">
                            <div class="shop-orders-filters">
                                <select id="cardsProductFilter" class="posts-status-filter">
                                    <option value="">全部商品</option>
                                </select>
                                <select id="cardsUsedFilter" class="posts-status-filter">
                                    <option value="">全部状态</option>
                                    <option value="0">可用</option>
                                    <option value="1">已售</option>
                                </select>
                                <input type="text" id="cardsSearchInput" class="form-input-inline" placeholder="搜索卡密内容 / 订单号" style="min-width:180px;">
                                <button type="button" class="btn btn-primary btn-sm" id="applyCardsFilterBtn">筛选</button>
                                <button type="button" class="btn btn-outline btn-sm" id="resetCardsFilterBtn">重置</button>
                                <button type="button" class="btn btn-outline btn-sm" id="refreshCardsBtn">刷新</button>
                            </div>
                            <div class="shop-orders-tools">
                                <button type="button" class="btn btn-outline btn-sm" id="cleanupUsedCardsBtn" title="清理所有已售出的卡密记录（释放存储空间，订单记录不受影响）">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                    清理已售
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="toggleCardsBulkBtn">批量模式</button>
                            </div>
                        </div>
                        <div class="shop-orders-bulkbar" id="cardsBulkBar" style="display:none;">
                            <label class="bulk-check">
                                <input type="checkbox" id="cardsSelectAll">
                                <span>全选</span>
                            </label>
                            <span class="bulk-info">已选 <strong id="cardsSelectedCount">0</strong> 条</span>
                            <span class="bulk-actions">
                                <button type="button" class="btn btn-danger btn-sm" data-cards-bulk="delete">删除选中（仅未售）</button>
                                <button type="button" class="btn btn-outline btn-sm btn-danger" data-cards-bulk="force-delete">强制删除选中</button>
                            </span>
                        </div>
                        <div class="cards-mgr-list" id="cardsMgrList">
                            <div class="shop-empty">暂无卡密</div>
                        </div>
                    </div>

                    <!-- 营销/通知 -->
                    <div class="shop-subpanel" id="shop-panel-marketing">
                        <div class="shop-two-col">
                            <form class="glass-card" id="shopMarketingForm">
                                <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">订单通知</h4>
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>新订单通知</span>
                                        <label class="toggle-switch"><input type="checkbox" name="notify_new_order"><span class="toggle-slider"></span></label>
                                    </label>
                                    <span class="form-hint">买家创建订单后向管理员邮箱发送提醒。</span>
                                </div>
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>支付成功通知</span>
                                        <label class="toggle-switch"><input type="checkbox" name="notify_paid_order"><span class="toggle-slider"></span></label>
                                    </label>
                                    <span class="form-hint">订单发卡成功后向管理员邮箱发送提醒。</span>
                                </div>
                                <div class="form-group">
                                    <label>管理员通知邮箱</label>
                                    <input type="email" name="notify_email" placeholder="默认使用 SMTP 发件邮箱">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">保存设置</button>
                                </div>
                            </form>
                            <form class="glass-card" id="shopCouponForm">
                                <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">优惠码</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>优惠码</label>
                                        <input type="text" name="code" placeholder="FOX10" maxlength="32">
                                    </div>
                                    <div class="form-group">
                                        <label>优惠类型</label>
                                        <select name="type">
                                            <option value="percent">百分比折扣</option>
                                            <option value="fixed">固定减免</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>优惠值</label>
                                        <input type="text" name="value" placeholder="10 或 5.00">
                                    </div>
                                    <div class="form-group">
                                        <label>最低金额</label>
                                        <input type="text" name="min_amount" placeholder="0">
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>使用次数</label>
                                        <input type="number" name="max_uses" min="0" placeholder="0 不限制">
                                    </div>
                                    <div class="form-group">
                                        <label>状态</label>
                                        <select name="enabled">
                                            <option value="1">启用</option>
                                            <option value="0">停用</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">添加/更新</button>
                                </div>
                                <div class="shop-coupon-list" id="shopCouponList"></div>
                            </form>
                        </div>
                    </div>

                    <!-- 订单编辑弹窗 -->
                    <div class="product-modal-overlay" id="orderEditOverlay" style="display:none;">
                        <div class="product-modal-dialog" role="dialog" aria-modal="true">
                            <div class="product-modal-header">
                                <h3>订单详情 / 编辑</h3>
                                <button type="button" class="product-modal-close" id="orderEditClose" aria-label="关闭">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            <form class="product-modal-body" id="orderEditForm">
                                <div class="order-edit-meta" id="orderEditMeta"></div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>订单状态</label>
                                        <select name="status" id="orderEditStatus">
                                            <option value="0">待支付</option>
                                            <option value="1">已支付</option>
                                            <option value="3">异常缺货</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>金额（元）</label>
                                        <input type="text" name="amount" id="orderEditAmount" inputmode="decimal">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>卡密内容</label>
                                    <textarea name="card_content" id="orderEditCard" rows="4" placeholder="可手动填写/修改卡密发送给客户"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>备注（仅后台可见）</label>
                                    <textarea name="note" id="orderEditNote" rows="2" maxlength="500" placeholder="例如：客服已联系、手动退款等"></textarea>
                                </div>
                                <div class="form-actions" style="justify-content:space-between;">
                                    <button type="button" class="btn btn-outline btn-sm" id="orderReissueBtn" title="从卡密池自动分配一条可用卡密，并将订单标记为已支付">自动补发卡密</button>
                                    <div style="display:flex; gap:8px;">
                                        <button type="button" class="btn btn-outline" id="orderEditCancel">取消</button>
                                        <button type="submit" class="btn btn-primary">保存</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- 商品编辑弹窗 -->
                <div class="product-modal-overlay" id="productModalOverlay" style="display:none;">
                    <div class="product-modal-dialog" role="dialog" aria-modal="true">
                        <div class="product-modal-header">
                            <h3 id="productModalTitle">新增商品</h3>
                            <button type="button" class="product-modal-close" id="productModalClose" aria-label="关闭">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <form class="product-modal-body" id="productForm">
                            <input type="hidden" name="id">
                            <div class="form-group">
                                <label>商品名称 <span style="color:#ff6b6b">*</span></label>
                                <input type="text" name="name" maxlength="80" required>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>价格（元） <span style="color:#ff6b6b">*</span></label>
                                    <input type="text" name="price" inputmode="decimal" placeholder="9.90" required>
                                </div>
                                <div class="form-group">
                                    <label>排序值（越小越靠前）</label>
                                    <input type="number" name="sort" value="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>商品分类</label>
                                <div class="product-cat-combo">
                                    <select name="category" id="productCategorySelect">
                                        <option value="">未分类</option>
                                    </select>
                                    <button type="button" class="btn btn-outline btn-sm" id="addCategoryBtn" title="新增分类">+</button>
                                    <button type="button" class="btn btn-outline btn-sm btn-danger" id="delCategoryBtn" title="删除当前分类">−</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>商品缩略图</label>
                                <div class="product-thumb-upload" id="productThumbUpload">
                                    <input type="file" id="productThumbFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                                    <div class="product-thumb-preview" id="productThumbPreview">
                                        <div class="product-thumb-placeholder" id="productThumbPlaceholder">
                                            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <span>点击或拖拽上传图片</span>
                                            <span class="product-thumb-hint">支持 JPG/PNG/GIF/WebP，最大 5MB</span>
                                        </div>
                                        <img id="productThumbImg" src="" alt="缩略图预览" style="display:none">
                                        <button type="button" class="product-thumb-remove" id="productThumbRemove" title="移除图片" style="display:none">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-actions" style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                                    <button type="button" class="btn btn-outline btn-sm" id="selectProductThumbFromLibBtn">从图片库选择</button>
                                    <button type="button" class="btn btn-outline btn-sm" id="toggleProductThumbUrlBtn">输入图片 URL</button>
                                </div>
                                <div class="form-group" id="productThumbUrlWrap" style="display:none; margin-top:8px;">
                                    <input type="text" name="thumbnail" placeholder="https://... 或 /admin/img.php?t=...">
                                    <span class="form-hint">优先使用上传或从图片库选择；手动填写时需以 https:// 或 / 开头</span>
                                </div>
                                <input type="hidden" name="thumbnail_hidden" id="productThumbHidden">
                            </div>
                            <div class="form-group">
                                <label>商品简介</label>
                                <div class="rich-editor-host" id="richEditorHostProduct"></div>
                                <span class="form-hint">富文本编辑，展示于商品弹窗中</span>
                            </div>
                            <div class="form-group">
                                <label>支持的支付方式</label>
                                <div class="pay-type-checks">
                                    <label class="pay-check"><input type="checkbox" name="pay_types" value="alipay" checked> 支付宝</label>
                                    <label class="pay-check"><input type="checkbox" name="pay_types" value="wxpay" checked> 微信</label>
                                    <label class="pay-check"><input type="checkbox" name="pay_types" value="qqpay" checked> QQ钱包</label>
                                </div>
                                <span class="form-hint">不勾选即为全部支持；可限制商品只接受某种支付方式</span>
                            </div>
                            <div class="form-group">
                                <label class="product-publish-toggle">
                                    <input type="checkbox" name="published" id="productPublished">
                                    <span>立即发布（勾选后商品在前台商铺可见；取消勾选为草稿）</span>
                                </label>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" id="productCancelBtn">取消</button>
                                <button type="submit" class="btn btn-primary">保存</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 卡密管理弹窗 -->
                <div class="product-modal-overlay" id="cardsModalOverlay" style="display:none;">
                    <div class="product-modal-dialog product-modal-large" role="dialog" aria-modal="true">
                        <div class="product-modal-header">
                            <h3 id="cardsModalTitle">卡密管理</h3>
                            <button type="button" class="product-modal-close" id="cardsModalClose" aria-label="关闭">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div class="product-modal-body">
                            <div class="form-group">
                                <label>批量导入卡密（每行一条）</label>
                                <textarea id="cardsImportArea" rows="5" placeholder="卡密1&#10;卡密2&#10;卡密3"></textarea>
                            </div>
                            <div class="form-actions" style="margin-bottom:14px;">
                                <button type="button" class="btn btn-primary btn-sm" id="importCardsBtn">导入</button>
                            </div>
                            <div class="cards-stat" id="cardsStat"></div>
                            <div class="cards-list" id="cardsList">
                                <div class="shop-empty">暂无卡密</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== 数据备份 ========== -->
                <section class="tab-panel" id="tab-backup">
                    <div class="panel-header">
                        <h2>数据备份与恢复</h2>
                        <p class="panel-desc">备份范围：data/ 下的全部 JSON 数据文件、配置文件以及子目录中的媒体（头像、背景、消息图片、赞助二维码等）</p>
                    </div>

                    <!-- 导出 -->
                    <div class="glass-card">
                        <div class="backup-card-head">
                            <h3>导出备份</h3>
                            <span class="backup-hint">三种导出方式可选</span>
                        </div>
                        <div class="backup-actions">
                            <button type="button" class="btn btn-primary" id="exportDownloadBtn" data-mode="download" title="生成备份并直接下载到本机，不在服务器留存">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                下载到本机
                            </button>
                            <button type="button" class="btn btn-outline" id="exportServerBtn" data-mode="save" title="生成备份并保存到服务器，可在下方列表管理">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 12H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                                保存到服务器
                            </button>
                            <button type="button" class="btn btn-outline" id="exportBothBtn" data-mode="both" title="同时保存到服务器并下载到本机">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M3 21h18"/></svg>
                                保存并下载
                            </button>
                        </div>
                    </div>

                    <!-- 服务器历史备份 -->
                    <div class="glass-card" style="margin-top:16px">
                        <div class="backup-card-head">
                            <h3>服务器历史备份</h3>
                            <div class="backup-card-tools">
                                <span class="backup-hint" id="backupKeepHint">保留份数：-</span>
                                <button type="button" class="btn btn-outline btn-sm" id="backupSettingsBtn">设置</button>
                                <button type="button" class="btn btn-outline btn-sm" id="refreshBackupsBtn" title="刷新">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="backup-list" id="backupList">
                            <div class="shop-empty">加载中...</div>
                        </div>
                    </div>

                    <!-- 恢复 -->
                    <div class="glass-card" style="margin-top:16px">
                        <div class="backup-card-head">
                            <h3>从备份文件恢复</h3>
                            <span class="backup-hint">先预览再勾选恢复项</span>
                        </div>
                        <p style="color:rgba(255,255,255,0.5);font-size:13px;margin-bottom:14px">上传外部 ZIP 备份后会先解析内容，你可以选择性恢复 JSON 数据文件、媒体目录中的图片等。</p>
                        <input type="file" id="importBackupFile" accept=".zip" style="display:none">
                        <button type="button" class="btn btn-primary" id="importBackupBtn">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            选择备份文件并预览
                        </button>
                    </div>
                </section>

                <!-- 备份设置 Modal -->
                <div class="product-modal-overlay" id="backupSettingsOverlay" style="display:none;">
                    <div class="product-modal-dialog" role="dialog" aria-modal="true">
                        <div class="product-modal-header">
                            <h3>备份设置</h3>
                            <button type="button" class="product-modal-close" id="backupSettingsClose" aria-label="关闭">&times;</button>
                        </div>
                        <form class="product-modal-body" id="backupSettingsForm">
                            <div class="form-group">
                                <label>保留份数（手动备份）</label>
                                <input type="number" id="backupKeepInput" min="1" max="100" value="10">
                                <p style="color:rgba(255,255,255,0.4);font-size:12px;margin-top:6px">超过份数时自动删除最旧的手动备份（auto_ 开头的恢复回滚备份单独保留）</p>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="backupAutoRollbackInput" checked>
                                    <span>恢复前自动创建回滚备份（推荐）</span>
                                </label>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" id="backupSettingsCancel">取消</button>
                                <button type="submit" class="btn btn-primary">保存</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 恢复预览 Modal -->
                <div class="product-modal-overlay" id="restorePreviewOverlay" style="display:none;">
                    <div class="product-modal-dialog product-modal-large" role="dialog" aria-modal="true">
                        <div class="product-modal-header">
                            <h3>恢复预览</h3>
                            <button type="button" class="product-modal-close" id="restorePreviewClose" aria-label="关闭">&times;</button>
                        </div>
                        <div class="product-modal-body">
                            <div class="restore-summary" id="restoreSummary"></div>
                            <div class="restore-toolbar">
                                <button type="button" class="btn btn-outline btn-sm" id="restoreSelectAll">全选</button>
                                <button type="button" class="btn btn-outline btn-sm" id="restoreSelectNone">全不选</button>
                                <button type="button" class="btn btn-outline btn-sm" id="restoreSelectData">仅数据 JSON</button>
                                <label class="checkbox-label" style="margin-left:auto;">
                                    <input type="checkbox" id="restoreAutoRollback" checked>
                                    <span>恢复前先自动备份当前数据</span>
                                </label>
                            </div>
                            <div class="restore-section" id="restoreDataSection">
                                <h4>数据文件 <span class="restore-count" id="restoreDataCount">0</span></h4>
                                <div class="restore-list" id="restoreDataList"></div>
                            </div>
                            <div class="restore-section" id="restoreMediaSection">
                                <h4>媒体文件 <span class="restore-count" id="restoreMediaCount">0</span></h4>
                                <div class="restore-list" id="restoreMediaList"></div>
                            </div>
                            <div class="form-actions" style="justify-content:space-between;align-items:center;margin-top:18px;">
                                <span id="restoreSelectedHint" style="color:rgba(255,255,255,0.5);font-size:13px"></span>
                                <div style="display:flex;gap:10px;">
                                    <button type="button" class="btn btn-outline" id="restoreCancelBtn">取消</button>
                                    <button type="button" class="btn btn-danger" id="restoreConfirmBtn">执行恢复</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== 访客统计 ========== -->
                <section class="tab-panel" id="tab-stats">
                    <div class="panel-header">
                        <h2>访客统计</h2>
                        <p class="panel-desc">查看网站访问数据</p>
                    </div>
                    <div class="glass-card">
                        <div class="stats-summary" id="statsSummary">
                            <div class="stats-card">
                                <div class="stats-card-label">今日 PV</div>
                                <div class="stats-card-value" id="statsTodayPV">-</div>
                            </div>
                            <div class="stats-card">
                                <div class="stats-card-label">今日 UV</div>
                                <div class="stats-card-value" id="statsTodayUV">-</div>
                            </div>
                            <div class="stats-card">
                                <div class="stats-card-label">总 PV</div>
                                <div class="stats-card-value" id="statsTotalPV">-</div>
                            </div>
                            <div class="stats-card">
                                <div class="stats-card-label">总 UV</div>
                                <div class="stats-card-value" id="statsTotalUV">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="glass-card" style="margin-top:16px">
                        <h3 style="margin-bottom:16px">最近 30 天趋势</h3>
                        <div class="stats-chart-wrap">
                            <canvas id="statsChart" height="220"></canvas>
                        </div>
                    </div>
                </section>

                <section class="tab-panel" id="tab-oplogs">
                    <div class="panel-header">
                        <h2>操作日志</h2>
                        <p class="panel-desc">记录后台关键操作、时间与来源 IP</p>
                    </div>
                    <div class="glass-card">
                        <div class="messages-toolbar" style="justify-content:space-between;gap:10px;">
                            <button type="button" class="btn btn-outline btn-sm" id="refreshOpLogsBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                刷新
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" id="clearOpLogsBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                清空日志
                            </button>
                        </div>
                        <div class="messages-list" id="opLogsList">
                            <div class="messages-empty">
                                <p>暂无日志</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========== 外观主题 ========== -->
                <section class="tab-panel" id="tab-appearance">
                    <div class="panel-header">
                        <h2>外观主题</h2>
                        <p class="panel-desc">自定义后台管理界面的背景（仅本浏览器生效，不影响前台）</p>
                    </div>

                    <div class="glass-card" style="max-width: 720px;">
                        <div class="form-group">
                            <label>背景模式</label>
                            <div class="bg-mode-tabs" id="bgModeTabs">
                                <button type="button" class="bg-mode-btn active" data-mode="default">默认</button>
                                <button type="button" class="bg-mode-btn" data-mode="solid">纯色</button>
                                <button type="button" class="bg-mode-btn" data-mode="gradient">渐变</button>
                                <button type="button" class="bg-mode-btn" data-mode="image">图片</button>
                            </div>
                        </div>

                        <div class="form-group bg-field" data-show-modes="solid gradient">
                            <label id="bgColor1Label">主色</label>
                            <div class="bg-color-row">
                                <input type="color" id="bgColor1" value="#181c21">
                                <input type="text" id="bgColor1Text" value="#181c21" placeholder="#181c21" style="max-width:140px;">
                            </div>
                        </div>

                        <div class="form-group bg-field" data-show-modes="gradient">
                            <label>辅色</label>
                            <div class="bg-color-row">
                                <input type="color" id="bgColor2" value="#414345">
                                <input type="text" id="bgColor2Text" value="#414345" placeholder="#414345" style="max-width:140px;">
                            </div>
                        </div>

                        <div class="form-group bg-field" data-show-modes="gradient">
                            <label>渐变角度 <span class="bg-range-val" id="bgAngleVal">135°</span></label>
                            <input type="range" id="bgAngle" min="0" max="360" step="1" value="135">
                        </div>

                        <div class="form-group bg-field" data-show-modes="image">
                            <label>背景图片</label>
                            <div class="bg-image-row">
                                <input type="text" id="bgImageUrl" placeholder="粘贴图片 URL，或使用下方按钮上传">
                                <label for="bgImageFile" class="btn btn-outline btn-sm" style="white-space:nowrap;">本地上传</label>
                                <input type="file" id="bgImageFile" accept="image/*" style="display:none;">
                            </div>
                            <p class="form-hint">本地上传将以 Data URL 存入 localStorage（建议小于 2MB），仅本浏览器可见</p>
                        </div>

                        <div class="form-group bg-field" data-show-modes="solid gradient image">
                            <label>背景模糊 <span class="bg-range-val" id="bgBlurVal">0px</span></label>
                            <input type="range" id="bgBlur" min="0" max="30" step="1" value="0">
                        </div>

                        <div class="form-group bg-field" data-show-modes="image">
                            <label>暗色蒙版 <span class="bg-range-val" id="bgOverlayVal">50%</span></label>
                            <input type="range" id="bgOverlay" min="0" max="90" step="1" value="50">
                        </div>

                        <div class="form-group bg-field" data-show-modes="solid gradient image">
                            <label class="checkbox-label">
                                <input type="checkbox" id="bgHidePattern">
                                <span>隐藏默认网格图案</span>
                            </label>
                        </div>

                        <div class="form-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="button" class="btn btn-primary" id="bgSaveBtn">保存设置</button>
                            <button type="button" class="btn btn-outline" id="bgResetBtn">恢复默认</button>
                        </div>
                    </div>
                </section>

                <!-- ========== 修改密码 ========== -->
                <section class="tab-panel" id="tab-password">
                    <div class="panel-header">
                        <h2>修改密码</h2>
                        <p class="panel-desc">修改管理后台的登录密码</p>
                    </div>
                    <form class="glass-card" id="passwordForm" style="max-width: 480px;">
                        <div class="form-group">
                            <label>当前密码</label>
                            <input type="password" name="old_password" required placeholder="请输入当前密码">
                        </div>
                        <div class="form-group">
                            <label>新密码</label>
                            <input type="password" name="new_password" required placeholder="不少于8位">
                        </div>
                        <div class="form-group">
                            <label>确认新密码</label>
                            <input type="password" name="confirm_password" required placeholder="再次输入新密码">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">修改密码</button>
                        </div>
                    </form>

                    <!-- 广告设置 -->
                    <div class="glass-card" style="margin-top: 14px; max-width: 480px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="toggle-label">
                                <span>永久关闭个性化广告</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="hideAdToggle" <?= $hideAd ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">开启后将不再显示后台顶部的推广信息</span>
                        </div>
                    </div>
                </section>

                <!-- ========== 一键跑路 ========== -->
                <section class="tab-panel" id="tab-nuke">
                    <div class="panel-header">
                        <h2 class="danger-title">一键跑路</h2>
                        <p class="panel-desc">永久删除网站目录下的所有文件，此操作不可恢复</p>
                    </div>

                    <!-- 模式选择 -->
                    <div class="glass-card" style="margin-bottom:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">执行模式</h4>
                        <div class="nuke-mode-selector">
                            <label class="nuke-mode-card active" data-mode="manual">
                                <div class="nuke-mode-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                </div>
                                <div class="nuke-mode-info">
                                    <strong>手动模式</strong>
                                    <span>点击按钮后手动确认执行</span>
                                </div>
                            </label>
                            <label class="nuke-mode-card" data-mode="auto">
                                <div class="nuke-mode-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="nuke-mode-info">
                                    <strong>自动模式</strong>
                                    <span>到达设定时间后自动执行</span>
                                </div>
                            </label>
                        </div>

                        <!-- 自动模式设置 -->
                        <div class="nuke-auto-settings" id="nukeAutoSettings" style="display:none;">
                            <div class="form-group" style="margin-top:14px;">
                                <label>自动执行倒计时（天）</label>
                                <div class="nuke-days-input">
                                    <input type="number" id="nukeDaysInput" min="1" max="365" value="7" placeholder="天数">
                                    <span class="nuke-days-suffix">天后自动执行</span>
                                </div>
                                <span class="form-hint">设定后，从保存时刻起倒计时，到期后前端访问时将自动触发删除</span>
                            </div>
                            <div class="nuke-countdown-status" id="nukeCountdownStatus" style="display:none;">
                                <div class="nuke-countdown-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="nuke-countdown-info">
                                    <span class="nuke-countdown-label">自动跑路倒计时</span>
                                    <span class="nuke-countdown-time" id="nukeCountdownTime">--</span>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm" id="nukeCancelAutoBtn">取消定时</button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveNukeModeBtn">保存模式设置</button>
                        </div>
                    </div>

                    <!-- 跑路后跳转设置 -->
                    <div class="glass-card" style="margin-bottom:14px;">
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:14px;">跑路后跳转</h4>
                        <div class="form-group">
                            <label class="toggle-label">
                                <span>执行后自动跳转到指定链接</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="nukeRedirectToggle">
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>
                            <span class="form-hint">开启后，跑路执行完成会自动跳转到下方设置的链接</span>
                        </div>
                        <div id="nukeRedirectSection" style="display:none;">
                            <div class="form-group">
                                <label>跳转链接</label>
                                <input type="url" id="nukeRedirectUrl" placeholder="https://example.com">
                                <span class="form-hint">跑路成功后浏览器将自动跳转到此地址</span>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="saveNukeRedirectBtn">保存跳转设置</button>
                        </div>
                    </div>

                    <!-- 危险区域 -->
                    <div class="glass-card danger-zone">
                        <div class="danger-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <h3 class="danger-heading">危险操作</h3>
                        <p class="danger-text">
                            点击下方按钮将 <strong>永久删除</strong> 网站根目录下的所有文件和文件夹，包括：
                        </p>
                        <ul class="danger-list">
                            <li>所有前端页面和样式文件</li>
                            <li>管理后台程序</li>
                            <li>上传的头像和背景图片</li>
                            <li>所有留言数据和配置</li>
                        </ul>
                        <p class="danger-text danger-warn">
                            此操作执行后无法撤销，所有数据将永久丢失！
                        </p>
                        <button type="button" class="btn btn-nuke" id="nukeBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            立即执行跑路
                        </button>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- 跑路确认弹窗 -->
    <div class="modal-overlay" id="nukeModal">
        <div class="modal-card nuke-modal">
            <div class="modal-header">
                <h3 class="danger-title">最终确认</h3>
                <button type="button" class="modal-close" id="closeNukeModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="nuke-confirm-body">
                <div class="nuke-warn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <p class="nuke-confirm-text">你即将删除网站的所有文件，此操作<strong>不可恢复</strong>。</p>
                <p class="nuke-confirm-text">请在下方输入 <code>确认删除</code> 并验证密码以继续：</p>
                <div class="form-group">
                    <input type="text" id="nukeConfirmInput" placeholder="请输入"确认删除"" autocomplete="off" spellcheck="false">
                </div>
                <div class="form-group">
                    <input type="password" id="nukePasswordInput" placeholder="请输入当前登录密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-nuke" id="nukeConfirmBtn" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        永久删除所有文件
                    </button>
                    <button type="button" class="btn btn-outline" id="nukeCancelBtn">取消</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 回复弹窗 -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>回复邮件</h3>
                <button type="button" class="modal-close" id="closeReplyModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="replyForm">
                <input type="hidden" name="msg_id" id="replyMsgId">
                <div class="form-group">
                    <label>收件人</label>
                    <input type="email" name="to" id="replyTo" readonly>
                </div>
                <div class="form-group">
                    <label>主题</label>
                    <input type="text" name="subject" id="replySubject" placeholder="回复主题">
                </div>
                <div class="form-group">
                    <label>回复内容</label>
                    <textarea name="body" id="replyBody" rows="6" placeholder="输入回复内容..." required></textarea>
                </div>
                <div class="form-group">
                    <label>快捷回复模板</label>
                    <div class="reply-template-row">
                        <select id="replyTemplateSelect">
                            <option value="">选择模板快速填充</option>
                        </select>
                        <button type="button" class="btn btn-outline btn-sm" id="saveReplyTemplateBtn">保存为模板</button>
                        <button type="button" class="btn btn-danger btn-sm" id="deleteReplyTemplateBtn">删除模板</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>附带图片 <span style="opacity:.5;font-weight:400">（可选，将作为邮件附件发送）</span></label>
                    <div class="reply-image-area" id="replyImageArea">
                        <input type="file" id="replyImageFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                        <div class="reply-image-add" id="replyImageAdd">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </div>
                        <div class="reply-image-thumb" id="replyImageThumb" style="display:none">
                            <img id="replyImagePreviewImg" src="" alt="预览">
                            <button type="button" class="reply-image-remove" id="replyImageRemove" title="移除">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">发送回复</button>
                    <button type="button" class="btn btn-outline" id="cancelReply">取消</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 帖子编辑弹窗 -->
    <div class="modal-overlay" id="postModal">
        <div class="modal-card" style="max-width:700px;">
            <div class="modal-header">
                <h3 id="postModalTitle">新建帖子</h3>
                <button type="button" class="modal-close" id="closePostModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="postForm">
                <input type="hidden" name="post_id" id="postId">
                <div class="form-group">
                    <label>帖子标题 <span style="color:#ff6b6b">*</span></label>
                    <input type="text" name="title" id="postTitle" placeholder="输入帖子标题" required maxlength="200">
                </div>
                <div class="form-group">
                    <label>帖子副标题</label>
                    <input type="text" name="subtitle" id="postSubtitle" placeholder="输入帖子副标题（可选）" maxlength="200">
                    <span class="form-hint">前台列表将显示标题与副标题，正文内容需点击查看</span>
                </div>
                <div class="form-group">
                    <label>帖子封面</label>
                    <div class="post-cover-upload" id="postCoverUpload">
                        <input type="file" id="postCoverFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                        <div class="post-cover-preview" id="postCoverPreview">
                            <div class="post-cover-placeholder" id="postCoverPlaceholder">
                                <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                <span>点击或拖拽上传封面图</span>
                                <span class="post-cover-hint">支持 JPG/PNG/GIF/WebP，最大 5MB</span>
                            </div>
                            <img id="postCoverImg" src="" alt="封面预览" style="display:none">
                            <button type="button" class="post-cover-remove" id="postCoverRemove" title="移除封面" style="display:none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top:10px;">
                        <button type="button" class="btn btn-outline btn-sm" id="selectCoverFromLibraryBtn">从图片库选择封面</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>帖子内容</label>
                    <div class="rich-editor-host" id="richEditorHostPost">
                    <div class="rich-editor" id="richEditor">
                        <div class="rich-toolbar" id="richToolbar">
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="bold" title="加粗 Ctrl+B"><b>B</b></button>
                                <button type="button" data-cmd="italic" title="斜体 Ctrl+I"><i>I</i></button>
                                <button type="button" data-cmd="underline" title="下划线 Ctrl+U"><u>U</u></button>
                                <button type="button" data-cmd="strikeThrough" title="删除线"><s>S</s></button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="formatBlock" data-val="h2" title="大标题">H2</button>
                                <button type="button" data-cmd="formatBlock" data-val="h3" title="小标题">H3</button>
                                <button type="button" data-cmd="formatBlock" data-val="p" title="正文">P</button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="justifyLeft" title="左对齐">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                                </button>
                                <button type="button" data-cmd="justifyCenter" title="居中对齐">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                                </button>
                                <button type="button" data-cmd="justifyRight" title="右对齐">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                                </button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="insertUnorderedList" title="无序列表">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                                </button>
                                <button type="button" data-cmd="insertOrderedList" title="有序列表">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="4" y="8" fill="currentColor" stroke="none" font-size="7" font-weight="600">1</text><text x="4" y="14" fill="currentColor" stroke="none" font-size="7" font-weight="600">2</text><text x="4" y="20" fill="currentColor" stroke="none" font-size="7" font-weight="600">3</text></svg>
                                </button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="formatBlock" data-val="blockquote" title="引用">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
                                </button>
                                <button type="button" data-cmd="insertHorizontalRule" title="分割线">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"/></svg>
                                </button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="createLink" title="插入链接">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                </button>
                                <button type="button" data-cmd="insertImage" title="插入图片">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="foreColor" title="文字颜色" class="rich-color-btn">
                                    <span class="rich-color-label">A</span>
                                    <span class="rich-color-bar" id="richColorBar"></span>
                                    <input type="color" id="richColorPicker" value="#ff6b6b" class="rich-color-input">
                                </button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" id="richEmojiBtn" title="插入表情" class="rich-emoji-trigger">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                </button>
                            </div>
                            <div class="rich-toolbar-group">
                                <button type="button" data-cmd="removeFormat" title="清除格式（去掉加粗、颜色等样式）">
                                    <span style="font-size:12px;letter-spacing:-1px"><s>T</s></span>
                                </button>
                            </div>
                            <div class="rich-toolbar-group rich-toolbar-novel" id="richNovelGroup" style="display:none;">
                                <button type="button" id="richInsertPageBreakBtn" title="插入分页符（小说模式：前台按此处切页）">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="8" x2="9" y2="8"/><line x1="15" y1="8" x2="21" y2="8"/><line x1="3" y1="16" x2="9" y2="16"/><line x1="15" y1="16" x2="21" y2="16"/><line x1="12" y1="3" x2="12" y2="21" stroke-dasharray="2 2"/></svg>
                                    <span style="font-size:11px;margin-left:2px;">分页</span>
                                </button>
                            </div>
                            <div class="rich-toolbar-group rich-toolbar-md-toggle">
                                <button type="button" id="richMdToggleBtn" title="切换 Markdown 编辑模式" class="rich-md-btn">MD</button>
                            </div>
                        </div>
                        <div class="rich-body" id="richEditorBody" contenteditable="true"></div>
                        <div class="md-editor-wrap" id="mdEditorWrap" style="display:none">
                            <textarea class="md-editor-input" id="mdEditorInput" placeholder="在这里输入 Markdown 内容...

# 标题
**加粗** *斜体* `代码`
- 列表项
> 引用

```代码块```"></textarea>
                            <div class="md-editor-preview" id="mdEditorPreview"></div>
                        </div>
                    </div>
                    </div>
                    <textarea name="content" id="postContent" style="display:none"></textarea>
                    <span class="form-hint" id="richEditorHint">可视化编辑，所见即所得，直接排版内容</span>
                </div>
                <div class="form-group">
                    <label>标签</label>
                    <div class="post-tags-input-wrap" id="postTagsWrap">
                        <div class="post-tags-list" id="postTagsList"></div>
                        <input type="text" id="postTagsInput" class="post-tags-input" placeholder="输入标签后按回车添加" maxlength="20">
                    </div>
                    <span class="form-hint">最多 10 个标签，每个最长 20 字</span>
                </div>
                <div class="form-group" style="display:flex;gap:16px;flex-wrap:wrap;">
                    <label class="toggle-label">
                        <span>发布帖子</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="postPublished" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                    <label class="toggle-label">
                        <span>置顶</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="postPinned">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                    <label class="toggle-label" title="启用后前台展示『进入沉浸阅读』按钮，并按分页符或字数自动切页">
                        <span>小说模式</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="postNovelMode">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                </div>
                <div class="form-group" id="postNovelOptions" style="display:none;">
                    <label>每页约字数（无分页符时生效）</label>
                    <input type="number" id="postCharsPerPage" min="200" max="20000" step="100" value="1500">
                    <span class="form-hint">建议 1000–3000 之间。可在富文本工具栏点击「分页符」精准控制每页结束位置（章节切分）；未插入分页符时，前台按段落边界自动切页</span>
                </div>
                <div class="form-group">
                    <label>定时发布（可选）</label>
                    <input type="datetime-local" id="postScheduledAt">
                    <span class="form-hint">设置未来时间后会自动转为草稿，到时间自动发布</span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="postSubmitBtn">发布帖子</button>
                    <button type="button" class="btn btn-outline" id="cancelPost">取消</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="mediaManagerModal">
        <div class="modal-card" style="max-width:780px;">
            <div class="modal-header">
                <h3>图片管理器</h3>
                <button type="button" class="modal-close" id="closeMediaManagerModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="messages-toolbar" style="justify-content:space-between;">
                <div class="posts-filter-group">
                    <select id="mediaTypeFilter" class="posts-status-filter">
                        <option value="all">全部图片</option>
                        <option value="cover">帖子封面</option>
                        <option value="message">消息附图</option>
                    </select>
                </div>
                <button type="button" class="btn btn-outline btn-sm" id="refreshMediaManagerBtn">刷新</button>
            </div>
            <div class="media-grid" id="mediaGrid"></div>
        </div>
    </div>

    <!-- Toast 通知 -->
    <div class="toast-container" id="toastContainer"></div>

    <script>window.__musicPlaylist = <?= json_encode($content['music_player']['playlist'] ?? [], JSON_UNESCAPED_UNICODE) ?>;</script>
    <script src="script.js?v=<?= time() ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/turndown@7/dist/turndown.js" defer></script>
</body>
</html>
