// ==================== 社交媒体模块加载（延迟至空闲时） ====================
var _marqueeLoaded = false;
function _loadMarquee() {
    if (_marqueeLoaded) return;
    _marqueeLoaded = true;
    fetch('APP/index.html').then(function(r) { return r.text(); }).catch(function() { return ''; }).then(function(html) {
        if (!html) return;
        var container = document.getElementById('appMarquee');
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        doc.querySelectorAll('script').forEach(function(s) { s.remove(); });
        doc.querySelectorAll('*').forEach(function(el) {
            [...el.attributes].forEach(function(attr) {
                if (attr.name.toLowerCase().startsWith('on')) el.removeAttribute(attr.name);
            });
        });
        container.innerHTML = doc.body.innerHTML;
    });
}
function _scheduleLoadMarquee() {
    if (typeof requestIdleCallback === 'function') {
        requestIdleCallback(_loadMarquee, { timeout: 3000 });
    } else {
        setTimeout(_loadMarquee, 1500);
    }
}

// ==================== 像素横幅渲染 ====================
var _pixelAnimationId = null;
var _pixelObserver = null;

function renderPixelBanner(text) {
    if (_pixelAnimationId) {
        cancelAnimationFrame(_pixelAnimationId);
        _pixelAnimationId = null;
    }
    if (_pixelObserver) {
        _pixelObserver.disconnect();
        _pixelObserver = null;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const grid = document.getElementById('pixelGrid');

    const containerWidth = grid.parentElement.clientWidth || 420;
    const baseGap = 1;
    var cols, rows, cellSize;

    if (containerWidth < 500) {
        cellSize = 4;
        cols = Math.floor((containerWidth + baseGap) / (cellSize + baseGap));
        cols = Math.max(35, Math.min(cols, 65));
        rows = 14;
    } else {
        var baseCols = 70;
        cellSize = Math.floor((containerWidth + baseGap) / baseCols) - baseGap;
        cellSize = Math.max(3, Math.min(cellSize, 6));
        cols = baseCols;
        rows = 16;
    }

    var gridTotalW = cols * cellSize + (cols - 1) * baseGap;
    while (gridTotalW > containerWidth && cols > 35) {
        cols--;
        gridTotalW = cols * cellSize + (cols - 1) * baseGap;
    }

    if (cellSize <= 3) rows = 12;
    else if (cellSize <= 4) rows = Math.min(rows, 14);

    const textCanvas = document.createElement('canvas');
    textCanvas.width = cols;
    textCanvas.height = rows;
    const tctx = textCanvas.getContext('2d');
    tctx.fillStyle = '#000';
    tctx.fillRect(0, 0, cols, rows);
    tctx.fillStyle = '#fff';
    var fontSize = rows <= 12 ? 11 : (rows <= 14 ? 12 : 14);
    var fontWeight = containerWidth < 500 ? '100' : '100';
    tctx.font = 'italic ' + fontWeight + ' ' + fontSize + 'px "Microsoft YaHei", "PingFang SC", sans-serif';
    tctx.textAlign = 'center';
    tctx.textBaseline = 'middle';
    tctx.letterSpacing = '8px';
    const spacing = -5;
    const totalWidth = tctx.measureText(text).width + spacing * (text.length - 1);
    let x = (cols - totalWidth) / 2;
    for (const ch of text) {
        const w = tctx.measureText(ch).width;
        tctx.fillText(ch, x + w / 2 + 3, rows / 2);
        x += w + spacing;
    }
    var imageData = tctx.getImageData(0, 0, cols, rows);

    var topRow = rows, bottomRow = 0;
    for (var sr = 0; sr < rows; sr++) {
        for (var sc = 0; sc < cols; sc++) {
            if (imageData.data[(sr * cols + sc) * 4] > 80) {
                if (sr < topRow) topRow = sr;
                if (sr > bottomRow) bottomRow = sr;
            }
        }
    }
    if (topRow <= bottomRow) {
        var textHeight = bottomRow - topRow + 1;
        var idealTop = Math.floor((rows - textHeight) / 2);
        idealTop = Math.max(1, Math.min(idealTop, rows - textHeight - 1));
        var shift = idealTop - topRow;
        if (shift !== 0) {
            var oldData = new Uint8ClampedArray(imageData.data);
            imageData.data.fill(0);
            for (var sr2 = 0; sr2 < rows; sr2++) {
                var dstRow = sr2 + shift;
                if (dstRow < 0 || dstRow >= rows) continue;
                for (var sc2 = 0; sc2 < cols; sc2++) {
                    var srcIdx = (sr2 * cols + sc2) * 4;
                    var dstIdx = (dstRow * cols + sc2) * 4;
                    imageData.data[dstIdx]     = oldData[srcIdx];
                    imageData.data[dstIdx + 1] = oldData[srcIdx + 1];
                    imageData.data[dstIdx + 2] = oldData[srcIdx + 2];
                    imageData.data[dstIdx + 3] = oldData[srcIdx + 3];
                }
            }
        }
    }

    grid.innerHTML = '';
    grid.style.display = 'grid';
    grid.style.gridTemplateColumns = 'repeat(' + cols + ', ' + cellSize + 'px)';
    grid.style.gap = baseGap + 'px';

    var activePixels = [];
    var frag = document.createDocumentFragment();
    for (var r = 0; r < rows; r++) {
        for (var c = 0; c < cols; c++) {
            var idx = (r * cols + c) * 4;
            var el = document.createElement('div');
            el.style.cssText = 'width:' + cellSize + 'px;height:' + cellSize + 'px;border-radius:1px';
            if (imageData.data[idx] > 80) {
                el.className = 'pixel-active';
                activePixels.push({ el: el, c: c });
            } else {
                el.style.background = 'rgba(255,255,255,0.1)';
            }
            frag.appendChild(el);
        }
    }
    grid.appendChild(frag);

    if (prefersReducedMotion) {
        for (var i = 0; i < activePixels.length; i++) {
            activePixels[i].el.style.background = 'hsl(200,80%,60%)';
        }
        return;
    }

    var offset = 0;
    function animate() {
        for (var i = 0; i < activePixels.length; i++) {
            var p = activePixels[i];
            var hue = ((p.c / cols) * 300 + offset) % 360;
            if (hue < 0) hue += 360;
            p.el.style.background = 'hsl(' + hue + ',80%,60%)';
        }
        offset -= 0.4;
        _pixelAnimationId = requestAnimationFrame(animate);
    }

    _pixelObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                if (!_pixelAnimationId) animate();
            } else {
                if (_pixelAnimationId) {
                    cancelAnimationFrame(_pixelAnimationId);
                    _pixelAnimationId = null;
                }
            }
        });
    }, { threshold: 0.1 });

    _pixelObserver.observe(grid.parentElement);
}

// 像素横幅初始文字由后台 get_content 决定，此处不提前渲染
// 避免移动端 resize 事件用硬编码文字覆盖后台设置
var _pixelResizeTimer;
var _lastPixelText = '我爱雨云'; // 兜底默认值，get_content 成功后会被覆盖
var _pixelTextReady = false;    // 标记后台文字是否已就绪
var _lastPixelMode = 'text';    // 'text' | 'snake'
var _lastPixelSvg = '';
var _snakeSvgPromise = null;
function loadSnakeSvg() {
    if (_lastPixelSvg) return Promise.resolve(_lastPixelSvg);
    if (_snakeSvgPromise) return _snakeSvgPromise;
    _snakeSvgPromise = fetch('APP/snake-Light.svg', { cache: 'force-cache' })
        .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
        .then(function (txt) { _lastPixelSvg = txt; return txt; })
        .catch(function () { _snakeSvgPromise = null; return ''; });
    return _snakeSvgPromise;
}

// 渲染 SVG 模式横幅：将原始 SVG 注入容器，并强制宽度自适应、高度受限
function renderPixelSvg(svgMarkup) {
    var grid = document.getElementById('pixelGrid');
    var banner = grid && grid.parentElement;
    if (!grid || !banner) return;
    if (_pixelAnimationId) { cancelAnimationFrame(_pixelAnimationId); _pixelAnimationId = null; }
    if (_pixelObserver) { _pixelObserver.disconnect(); _pixelObserver = null; }
    grid.innerHTML = svgMarkup;
    grid.classList.add('pixel-grid--svg');
    banner.classList.add('pixel-banner--svg');
    grid.style.display = 'block';
    grid.style.gridTemplateColumns = '';
    grid.style.gap = '';
    var svgEl = grid.querySelector('svg');
    if (svgEl) {
        // 移除固定 width/height，强制按容器自适应
        svgEl.removeAttribute('width');
        svgEl.removeAttribute('height');
        svgEl.style.width = '100%';
        svgEl.style.height = 'auto';
        svgEl.style.maxWidth = '100%';
        svgEl.style.maxHeight = '110px';
        svgEl.style.display = 'block';
        // 始终靠左上对齐
        svgEl.setAttribute('preserveAspectRatio', 'xMinYMin meet');
        // 重写 viewBox，去掉内部左/上的空白（snake-Light.svg 默认 viewBox="-16 -32 880 192"）
        var vb = svgEl.getAttribute('viewBox');
        if (vb) {
            var parts = vb.trim().split(/[\s,]+/).map(parseFloat);
            if (parts.length === 4 && parts.every(function (n) { return !isNaN(n); })) {
                var minX = parts[0], minY = parts[1], w = parts[2], h = parts[3];
                if (minX < 0) { w += minX; minX = 0; }
                if (minY < 0) { h += minY; minY = 0; }
                svgEl.setAttribute('viewBox', minX + ' ' + minY + ' ' + w + ' ' + h);
            }
        }
    }
}

// 根据后台配置切换横幅模式
function applyPixelBanner(mode, _svgMarkup, text) {
    // 兼容旧值
    if (mode === 'svg') mode = 'snake';
    if (mode === 'off') mode = 'text';
    _lastPixelMode = mode || 'text';
    var grid = document.getElementById('pixelGrid');
    var banner = grid && grid.parentElement;
    if (!grid || !banner) return;
    banner.style.display = '';
    if (_lastPixelMode === 'snake') {
        loadSnakeSvg().then(function (svg) {
            if (_lastPixelMode !== 'snake') return; // 模式已切换
            if (svg) {
                renderPixelSvg(svg);
            } else {
                // 加载失败兜底为文字
                grid.classList.remove('pixel-grid--svg');
                banner.classList.remove('pixel-banner--svg');
                renderPixelBanner(text);
            }
        });
        return;
    }
    // 默认/text：恢复网格样式
    grid.classList.remove('pixel-grid--svg');
    banner.classList.remove('pixel-banner--svg');
    renderPixelBanner(text);
}
function runWhenIdle(fn, timeout) {
    if (typeof requestIdleCallback === 'function') {
        requestIdleCallback(fn, { timeout: timeout || 2000 });
    } else {
        setTimeout(fn, Math.min(timeout || 1000, 1000));
    }
}
window.addEventListener('resize', function() {
    clearTimeout(_pixelResizeTimer);
    _pixelResizeTimer = setTimeout(function() {
        // 仅在文字模式下需要重新计算像素网格；蛇形 SVG 模式无需处理
        if (_lastPixelMode === 'text') renderPixelBanner(_lastPixelText);
    }, 200);
});

// ==================== 主逻辑（内容加载、邮件、导航、帖子、回复） ====================
(function () {
    const iconSVG = {
        globe:   '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        code:    '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        layout:  '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
        sparkle: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/></svg>',
        server:  '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>',
        palette: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
        terminal:'<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>',
        book:    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>'
    };

    var _escDiv = document.createElement('div');
    function esc(str) { _escDiv.textContent = str; return _escDiv.innerHTML; }
    var _projectLinkConfirmEnabled = false;
    var _projectPendingUrl = '';
    var projectConfirmOverlay = document.getElementById('projectConfirmOverlay');
    var projectConfirmUrl = document.getElementById('projectConfirmUrl');
    var projectConfirmCancel = document.getElementById('projectConfirmCancel');
    var projectConfirmOk = document.getElementById('projectConfirmOk');

    function normalizeProjectUrl(url) {
        var s = (url || '').trim();
        if (!/^https?:\/\//i.test(s)) return '';
        try {
            var u = new URL(s, window.location.href);
            if (u.protocol !== 'http:' && u.protocol !== 'https:') return '';
            return u.href;
        } catch (e) {
            return '';
        }
    }

    function closeProjectConfirm() {
        _projectPendingUrl = '';
        if (projectConfirmOverlay) {
            projectConfirmOverlay.classList.remove('show');
            projectConfirmOverlay.setAttribute('aria-hidden', 'true');
        }
    }

    function openProjectConfirm(url) {
        if (!projectConfirmOverlay || !projectConfirmUrl) {
            window.open(url, '_blank', 'noopener,noreferrer');
            return;
        }
        _projectPendingUrl = url;
        projectConfirmUrl.textContent = url;
        projectConfirmOverlay.classList.add('show');
        projectConfirmOverlay.setAttribute('aria-hidden', 'false');
    }

    function sendTrackVisit() {
        fetch('admin/api.php?action=track_visit', { method: 'POST', keepalive: true }).catch(function() {});
    }
    if (typeof requestIdleCallback === 'function') {
        requestIdleCallback(sendTrackVisit, { timeout: 2000 });
    } else {
        setTimeout(sendTrackVisit, 1200);
    }

    // 自动跑路检测
    fetch('admin/api.php?action=check_nuke')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.nuked) {
                if (res.redirect_url) {
                    window.location.href = res.redirect_url;
                } else {
                    document.body.innerHTML =
                        '<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#181c21;color:#fff;font-family:sans-serif;">' +
                        '<div style="text-align:center;padding:40px;">' +
                        '<div style="font-size:48px;margin-bottom:16px;">&#128075;</div>' +
                        '<h1 style="font-size:28px;margin-bottom:8px;">再见！</h1>' +
                        '<p style="color:rgba(255,255,255,0.5);font-size:14px;">网站已自动清空。</p>' +
                        '</div></div>';
                }
                throw new Error('nuked');
            }
        })
        .catch(() => {});

    // 从后端加载动态内容
    fetch('admin/api.php?action=get_content')
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) return;
            const d = res.data;

            // 社交媒体滚动开关（默认开启，仅当显式 false 时关闭）
            const marqueeEl = document.getElementById('appMarquee');
            if (d.social_marquee_enabled === false) {
                if (marqueeEl) marqueeEl.style.display = 'none';
            } else {
                if (marqueeEl) marqueeEl.style.display = '';
                _scheduleLoadMarquee();
            }

            const annBar = document.getElementById('announcementBar');
            const annLink = document.getElementById('announcementLinkEl');
            const annText = document.getElementById('announcementTextEl');
            if (annBar && annLink && annText) {
                const ann = d.announcement || {};
                const annEnabled = !!ann.enabled && !!ann.text;
                if (annEnabled) {
                    annText.textContent = ann.text;
                    annLink.href = (/^https?:\/\//i.test(ann.link || '') ? ann.link : '#');
                    annLink.target = ann.link ? '_blank' : '_self';
                    annLink.rel = ann.link ? 'noopener noreferrer' : '';
                    annBar.style.display = '';
                } else {
                    annBar.style.display = 'none';
                }
            }

            // 背景设置
            const bgMode = d.bg_mode || 'default';
            const bgBlur = d.bg_blur ?? 6;
            const bgOpacity = d.bg_opacity ?? 70;

            const bgBlurEl = document.querySelector('.bg-blur');
            if (bgBlurEl) {
                bgBlurEl.style.backdropFilter = 'blur(' + bgBlur + 'px)';
                bgBlurEl.style.webkitBackdropFilter = 'blur(' + bgBlur + 'px)';
            }

            if (bgMode === 'image' && d.bg_image) {
                const bgEl = document.querySelector('.bg');
                if (bgEl) {
                    bgEl.innerHTML = '';
                    bgEl.style.backgroundImage = 'url(' + d.bg_image + ')';
                    bgEl.style.backgroundSize = 'cover';
                    bgEl.style.backgroundPosition = 'center';
                    bgEl.style.backgroundRepeat = 'no-repeat';
                }
                if (bgBlurEl) {
                    bgBlurEl.style.background = 'rgba(24, 28, 33, ' + (bgOpacity / 100) + ')';
                }
            } else {
                if (bgOpacity != 70 && bgBlurEl) {
                    bgBlurEl.style.background = 'rgba(24, 28, 33, ' + (bgOpacity / 100 * 0.3) + ')';
                }
            }

            // 头像
            if (d.avatar) {
                document.querySelectorAll('.avatar, .mobile-avatar').forEach(function(avatarEl) {
                    const placeholder = avatarEl.querySelector('.avatar-placeholder');
                    if (placeholder) placeholder.style.display = 'none';
                    const existImg = avatarEl.querySelector('.avatar-img');
                    if (existImg) existImg.remove();
                    const img = document.createElement('img');
                    img.className = 'avatar-img';
                    img.loading = 'lazy';
                    img.decoding = 'async';
                    img.src = d.avatar;
                    img.alt = '头像';
                    avatarEl.appendChild(img);
                });
            }

            // 标题
            const tp = document.querySelector('.title-prefix');
            const tt = document.querySelector('.title');
            if (tp && d.title_prefix) tp.textContent = d.title_prefix;
            if (tt && d.title) tt.textContent = d.title;

            // 副标题
            const sp = document.querySelector('.subtitle-prefix');
            const sn = document.querySelector('.subtitle-name');
            if (sp && d.subtitle_prefix) sp.textContent = d.subtitle_prefix;
            if (sn && d.subtitle_name) sn.textContent = d.subtitle_name;

            // 渐变色
            if (d.gradient_colors && d.gradient_colors.length >= 2) {
                const gradColors = d.gradient_colors.join(', ');
                const gradCSS = 'linear-gradient(135deg, ' + gradColors + ')';
                const animate = d.gradient_animate !== false;
                [tt, sn].forEach(el => {
                    if (!el) return;
                    el.style.background = gradCSS;
                    el.style.webkitBackgroundClip = 'text';
                    el.style.webkitTextFillColor = 'transparent';
                    el.style.backgroundClip = 'text';
                    if (animate) {
                        el.style.backgroundSize = '200% 200%';
                        el.style.animation = 'gradientText 4s ease infinite';
                    } else {
                        el.style.backgroundSize = '100% 100%';
                        el.style.animation = 'none';
                    }
                });
            }

            // 像素横幅
            if (d.pixel_text) {
                _lastPixelText = d.pixel_text;
            }
            _pixelTextReady = true;
            var pixelMode = d.pixel_mode || 'text';
            applyPixelBanner(pixelMode, d.pixel_svg || '', _lastPixelText);

            // 个人简介
            const introEl = document.querySelector('.intro-text');
            if (introEl && d.intro) introEl.textContent = d.intro;

            // 技能
            if (Array.isArray(d.skills)) {
                const sg = document.querySelector('.skills-grid');
                if (sg) {
                    sg.innerHTML = d.skills.map(s => '<span class="skill-tag">' + esc(s) + '</span>').join('');
                }
            }

            // 项目
            if (Array.isArray(d.projects)) {
                const pg = document.querySelector('.projects-grid');
                if (pg) {
                    pg.innerHTML = d.projects.map(p => {
                        const icon = iconSVG[p.icon] || iconSVG.globe;
                        const inner =
                            '<div class="project-icon">' + icon + '</div>' +
                            '<div class="project-title">' + esc(p.title) + '</div>' +
                            '<div class="project-desc">' + esc(p.desc) + '</div>';
                        if (p.link) {
                            var projectUrl = normalizeProjectUrl(p.link);
                            if (projectUrl) {
                                return '<a class="project-item project-link" data-project-url="' + esc(projectUrl) + '" href="' + esc(projectUrl) + '" target="_blank" rel="noopener noreferrer">' + inner + '</a>';
                            }
                        }
                        return '<div class="project-item">' + inner + '</div>';
                    }).join('');
                }
            }

            // 联系方式
            if (d.contact) {
                const ci = document.querySelectorAll('.contact-item');
                if (ci[0] && d.contact.qq)     ci[0].innerHTML = ci[0].querySelector('svg').outerHTML + ' QQ: ' + esc(d.contact.qq);
                if (ci[1] && d.contact.wechat) ci[1].innerHTML = ci[1].querySelector('svg').outerHTML + ' WeChat: ' + esc(d.contact.wechat);
                if (ci[2] && d.contact.email)  ci[2].innerHTML = ci[2].querySelector('svg').outerHTML + ' Email: ' + esc(d.contact.email);
                if (ci[3] && d.contact.github) ci[3].innerHTML = ci[3].querySelector('svg').outerHTML + ' GitHub: ' + esc(d.contact.github);
            }

            // 终端展示
            const terminalCard = document.getElementById('terminalCard');
            const terminalTitleEl = document.getElementById('terminalTitle');
            const terminalBody = document.getElementById('terminalBody');
            if (d.terminal && d.terminal.enabled !== false) {
                if (terminalCard) terminalCard.style.display = '';
                if (terminalTitleEl && d.terminal.title) terminalTitleEl.textContent = d.terminal.title;
                if (terminalBody && d.terminal.commands && d.terminal.commands.length > 0) {
                    terminalBody.innerHTML = d.terminal.commands.map(cmd => {
                        const cls = cmd.type === 'command' ? 'terminal-command' : (cmd.type === 'prompt' ? 'terminal-prompt' : 'terminal-output');
                        return '<p class="' + cls + '">' + esc(cmd.content) + '</p>';
                    }).join('');
                }
            } else {
                if (terminalCard) terminalCard.style.display = 'none';
            }

            // 音乐播放器配置
            if (d.music_player) {
                if (d.music_player.enabled === false) {
                    window.__musicPlayerDisabled = true;
                }
                window.__musicPlayerConfig = {
                    mode: d.music_player.mode || 'random',
                    playlist: d.music_player.playlist || []
                };
            }

            // 帖子模块开关
            _postsEnabled = (d.posts_enabled === true);
            _projectLinkConfirmEnabled = (d.project_link_confirm === true);

            // 赞助弹窗
            initSponsor(d.sponsor);
        })
        .catch(function() {
            // get_content 失败时乐观启用帖子，由 loadPosts 自行决定是否显示
            _postsEnabled = true;
            // 像素横幅用默认文字兜底渲染
            if (!_pixelTextReady) {
                _pixelTextReady = true;
                applyPixelBanner('text', '', _lastPixelText);
            }
        })
        .finally(function() {
            document.querySelector('.main-content').classList.add('loaded');
            runWhenIdle(function() {
                loadPosts();
                if (typeof window.loadShop === 'function') window.loadShop();
            }, 1800);
        });

    var projectsGrid = document.querySelector('.projects-grid');
    if (projectsGrid) {
        projectsGrid.addEventListener('click', function (e) {
            var link = e.target.closest('a.project-link');
            if (!link) return;
            if (!_projectLinkConfirmEnabled) return;
            var url = normalizeProjectUrl(link.getAttribute('data-project-url') || link.getAttribute('href'));
            if (!url) return;
            e.preventDefault();
            openProjectConfirm(url);
        });
    }

    if (projectConfirmCancel) {
        projectConfirmCancel.addEventListener('click', closeProjectConfirm);
    }
    if (projectConfirmOverlay) {
        projectConfirmOverlay.addEventListener('click', function (e) {
            if (e.target === projectConfirmOverlay) closeProjectConfirm();
        });
    }
    if (projectConfirmOk) {
        projectConfirmOk.addEventListener('click', function () {
            if (!_projectPendingUrl) return;
            var url = _projectPendingUrl;
            closeProjectConfirm();
            window.open(url, '_blank', 'noopener,noreferrer');
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && projectConfirmOverlay && projectConfirmOverlay.classList.contains('show')) {
            closeProjectConfirm();
        }
    });

    // 兜底：API 超过 1.5 秒没响应也显示页面
    setTimeout(function() {
        document.querySelector('.main-content').classList.add('loaded');
    }, 1500);

    // ========== 邮件表单 — 图片上传 ==========
    var emailImageFile = document.getElementById('emailImageFile');
    var emailImageUpload = document.getElementById('emailImageUpload');
    var emailImagePlaceholder = document.getElementById('emailImagePlaceholder');
    var emailImagePreview = document.getElementById('emailImagePreview');
    var emailImagePreviewImg = document.getElementById('emailImagePreviewImg');
    var emailImageRemove = document.getElementById('emailImageRemove');
    var _pendingImageFile = null;
    var _pendingBlobUrl = '';

    if (emailImagePlaceholder) {
        emailImagePlaceholder.addEventListener('click', function () { emailImageFile.click(); });
    }

    if (emailImageUpload) {
        emailImageUpload.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('dragover'); });
        emailImageUpload.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
        emailImageUpload.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('dragover');
            var file = e.dataTransfer.files[0];
            if (file) handleEmailImage(file);
        });
    }

    if (emailImageFile) {
        emailImageFile.addEventListener('change', function () {
            if (this.files[0]) handleEmailImage(this.files[0]);
        });
    }

    function _clearPendingImage() {
        if (_pendingBlobUrl) { URL.revokeObjectURL(_pendingBlobUrl); _pendingBlobUrl = ''; }
        _pendingImageFile = null;
        if (emailImagePreviewImg) emailImagePreviewImg.src = '';
        if (emailImagePreview) emailImagePreview.style.display = 'none';
        if (emailImagePlaceholder) emailImagePlaceholder.style.display = '';
        if (emailImageFile) emailImageFile.value = '';
    }

    function _readFileAsDataURL(file) {
        return new Promise(function(resolve, reject) {
            var reader = new FileReader();
            reader.onload = function(e) { resolve(e.target.result); };
            reader.onerror = function() { reject(new Error('读取文件失败')); };
            reader.readAsDataURL(file);
        });
    }

    function handleEmailImage(file) {
        if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
            alert('仅支持 JPG/PNG/GIF/WebP 格式');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('图片不能超过 2MB');
            return;
        }
        if (_pendingBlobUrl) URL.revokeObjectURL(_pendingBlobUrl);
        _pendingImageFile = file;
        _pendingBlobUrl = URL.createObjectURL(file);
        emailImagePreviewImg.src = _pendingBlobUrl;
        emailImagePreview.style.display = '';
        emailImagePlaceholder.style.display = 'none';
    }

    if (emailImageRemove) {
        emailImageRemove.addEventListener('click', function (e) {
            e.stopPropagation();
            _clearPendingImage();
        });
    }

    // ========== 邮件表单 — 提交 ==========
    const form = document.getElementById('emailReplyForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const name    = (form.querySelector('[name="name"]').value || '').trim();
            const email   = (form.querySelector('[name="from"]').value || '').trim();
            const message = (form.querySelector('[name="message"]').value || '').trim();

            if (!name || !email || !message) {
                alert('请填写完整信息');
                return;
            }

            const btn = form.querySelector('.email-submit');
            const origText = btn.textContent;
            btn.textContent = '发送中...';
            btn.disabled = true;

            var doSubmit = function(imageDataUrl) {
                var payload = { name: name, email: email, message: message };
                if (imageDataUrl) payload.image = imageDataUrl;

                fetch('admin/api.php?action=save_message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(res => {
                    btn.textContent = origText;
                    btn.disabled = false;
                    payload = null;
                    if (res.success) {
                        alert(res.message || '留言发送成功！');
                        form.reset();
                        _clearPendingImage();
                    } else {
                        alert(res.message || '发送失败，请重试');
                    }
                })
                .catch(() => {
                    btn.textContent = origText;
                    btn.disabled = false;
                    alert('网络错误，请稍后重试');
                });
            };

            if (_pendingImageFile) {
                _readFileAsDataURL(_pendingImageFile).then(doSubmit).catch(function() {
                    btn.textContent = origText;
                    btn.disabled = false;
                    alert('图片读取失败，请重试');
                });
            } else {
                doSubmit('');
            }
        });
    }

    // ========== 导航滚动 ==========
    const navMap = {
        'nav-about': 'about',
        'nav-skills': 'skills',
        'nav-projects': 'projects',
        'nav-posts': 'posts',
        'nav-shop': 'shop',
        'nav-contact': 'contact'
    };

    function updateGlider() {
        var wrap = document.querySelector('.radio-container');
        if (!wrap) return;
        var radios = wrap.querySelectorAll('input[name="nav"]');
        var glider = wrap.querySelector('.glider');
        if (!glider) return;
        var activeIndex = -1;
        var visibleIndex = 0;
        radios.forEach(function(r) {
            var label = wrap.querySelector('label[for="' + r.id + '"]');
            var visible = r.style.display !== 'none' && (!label || label.style.display !== 'none');
            if (!visible) return;
            if (r.checked) activeIndex = visibleIndex;
            visibleIndex++;
        });
        if (visibleIndex > 0) wrap.style.setProperty('--total-radio', visibleIndex);
        if (activeIndex < 0) return;
        glider.style.width = '';
        glider.style.transform = 'translateY(' + (activeIndex * 100) + '%)';
    }
    window.updateGlider = updateGlider;

    document.querySelectorAll('.radio-container input[name="nav"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const targetId = navMap[this.id];
            const el = targetId && document.getElementById(targetId);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            updateGlider();
        });
    });

    updateGlider();

    document.querySelectorAll('.glass-radio-group input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var el = document.getElementById('contact');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // ========== 帖子模块 ==========
    var _postsSig = '';
    var _postsEtag = '';
    var _postsAbort = null;
    var _postsEnabled = false;

    var _postLb = null;
    var _postLbCover = null;
    var _postLbTitle = null;
    var _postLbSubtitle = null;
    var _postLbContent = null;
    var _postLbClose = null;
    var _postLbKeyHandler = null;

    function initPostLightbox() {
        if (_postLb) return;
        var lb = document.createElement('div');
        lb.className = 'post-lightbox';
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        lb.innerHTML =
            '<div class="post-lightbox-dialog" role="document">' +
                '<div class="post-lightbox-header">' +
                    '<div class="post-lightbox-headtext">' +
                        '<h3 class="post-lightbox-title"></h3>' +
                        '<p class="post-lightbox-subtitle"></p>' +
                    '</div>' +
                    '<button type="button" class="post-lightbox-close" aria-label="关闭">×</button>' +
                '</div>' +
                '<div class="post-lightbox-body">' +
                    '<img class="post-lightbox-cover" alt="封面">' +
                    '<div class="post-lightbox-content"></div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(lb);
        _postLb = lb;
        _postLbCover = lb.querySelector('.post-lightbox-cover');
        _postLbTitle = lb.querySelector('.post-lightbox-title');
        _postLbSubtitle = lb.querySelector('.post-lightbox-subtitle');
        _postLbContent = lb.querySelector('.post-lightbox-content');
        _postLbClose = lb.querySelector('.post-lightbox-close');

        lb.addEventListener('click', function(e) {
            if (e.target === lb) closePostLightbox();
        });
        if (_postLbClose) _postLbClose.addEventListener('click', closePostLightbox);
    }

    function openPostLightbox(post) {
        initPostLightbox();
        if (!_postLb || !_postLbTitle || !_postLbSubtitle || !_postLbContent) return;

        _postLbTitle.textContent = (post && post.title) ? String(post.title) : '无标题';
        var sub = (post && post.subtitle) ? String(post.subtitle) : '';
        _postLbSubtitle.textContent = sub;
        _postLbSubtitle.style.display = sub ? '' : 'none';

        if (_postLbCover) {
            if (post && post.cover) {
                _postLbCover.src = String(post.cover);
                _postLbCover.style.display = '';
            } else {
                _postLbCover.src = '';
                _postLbCover.style.display = 'none';
            }
        }

        _postLbContent.innerHTML = (post && post.content) ? String(post.content) : '<p style="opacity:.7;margin:0;">暂无内容</p>';

        // 强制小说模式：内容含分页符即视为小说，无论 novel_mode 是否开启
        var _hasPageBreak = !!(post && post.content &&
            /<hr[^>]*\bnovel-page-break\b/i.test(String(post.content)));
        var _forceNovel = !!(post && (post.novel_mode || _hasPageBreak));

        // 小说模式：在内容顶部插入"进入沉浸阅读"按钮
        if (post && _forceNovel && post.content) {
            _injectNovelStyles();
            var enterBar = document.createElement('div');
            enterBar.className = 'novel-enter-bar';
            enterBar.innerHTML =
                '<button type="button" class="novel-enter-btn">' +
                    '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>' +
                    '<span>进入沉浸阅读</span>' +
                '</button>' +
                '<span class="novel-enter-hint">小说模式 · 全屏翻页阅读</span>';
            _postLbContent.insertBefore(enterBar, _postLbContent.firstChild);
            var enterBtn = enterBar.querySelector('.novel-enter-btn');
            if (enterBtn) {
                enterBtn.addEventListener('click', function() {
                    openNovelReader(post);
                });
            }
        }

        _postLbContent.querySelectorAll('a').forEach(function(a) {
            a.setAttribute('target', '_blank');
            a.setAttribute('rel', 'noopener noreferrer');
        });
        _postLbContent.querySelectorAll('img').forEach(function(img) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function(e) {
                e.stopPropagation();
                var src = img.src || img.getAttribute('data-src');
                if (src) openLightbox(src);
            });
        });

        _postLb.classList.add('show');
        document.documentElement.style.overflow = 'hidden';

        if (_postLbKeyHandler) {
            document.removeEventListener('keydown', _postLbKeyHandler);
        }
        _postLbKeyHandler = function(e) {
            if (e.key === 'Escape') closePostLightbox();
        };
        document.addEventListener('keydown', _postLbKeyHandler);

        if (_postLbClose) _postLbClose.focus();
    }

    function closePostLightbox() {
        if (!_postLb) return;
        _postLb.classList.remove('show');
        document.documentElement.style.overflow = '';
        if (_postLbKeyHandler) {
            document.removeEventListener('keydown', _postLbKeyHandler);
            _postLbKeyHandler = null;
        }
    }

    // ========== 小说沉浸阅读器 ==========
    var _novelReader = null;
    var _novelPages = [];
    var _novelPageIdx = 0;
    var _novelKeyHandler = null;
    var _novelStyleInjected = false;
    var NOVEL_PREF_KEY = 'novel_reader_prefs_v1';

    function _novelLoadPrefs() {
        try {
            var raw = localStorage.getItem(NOVEL_PREF_KEY);
            if (!raw) return { fontSize: 18, theme: 'sepia' };
            var p = JSON.parse(raw);
            return {
                fontSize: (p && typeof p.fontSize === 'number') ? p.fontSize : 18,
                theme: (p && typeof p.theme === 'string') ? p.theme : 'sepia'
            };
        } catch (e) { return { fontSize: 18, theme: 'sepia' }; }
    }
    function _novelSavePrefs(p) {
        try { localStorage.setItem(NOVEL_PREF_KEY, JSON.stringify(p)); } catch (e) {}
    }

    function _injectNovelStyles() {
        if (_novelStyleInjected) return;
        _novelStyleInjected = true;
        var css = ''
            + '.novel-enter-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px;margin:0 0 18px;border-radius:12px;background:linear-gradient(135deg,rgba(255,215,100,0.08),rgba(255,215,100,0.02));border:1px solid rgba(255,215,100,0.25)}'
            + '.novel-enter-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:8px;border:1px solid rgba(255,215,100,0.5);background:rgba(255,215,100,0.12);color:#ffd76a;cursor:pointer;font-size:14px;font-weight:500;transition:all .2s}'
            + '.novel-enter-btn:hover{background:rgba(255,215,100,0.22);transform:translateY(-1px)}'
            + '.novel-enter-hint{color:rgba(255,255,255,0.55);font-size:12px}'
            + '.novel-reader{position:fixed;inset:0;z-index:9999;display:none;flex-direction:column;background:#1a1a1a;color:#e8e8e8;font-family:-apple-system,"PingFang SC","Microsoft YaHei",serif}'
            + '.novel-reader.show{display:flex}'
            + '.novel-reader[data-theme="light"]{background:#fafaf7;color:#222}'
            + '.novel-reader[data-theme="sepia"]{background:#f4ecd8;color:#5b4636}'
            + '.novel-reader[data-theme="dark"]{background:#1a1a1a;color:#e8e8e8}'
            + '.novel-reader[data-theme="black"]{background:#000;color:#bbb}'
            + '.novel-rd-top{display:flex;align-items:center;gap:12px;padding:14px 24px;border-bottom:1px solid rgba(128,128,128,0.18);flex-wrap:wrap}'
            + '.novel-rd-title{flex:1;min-width:0;font-size:15px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}'
            + '.novel-rd-tools{display:flex;align-items:center;gap:6px;flex-wrap:wrap}'
            + '.novel-rd-btn{padding:6px 12px;border-radius:6px;border:1px solid rgba(128,128,128,0.3);background:transparent;color:inherit;cursor:pointer;font-size:13px;line-height:1;transition:background .15s}'
            + '.novel-rd-btn:hover{background:rgba(128,128,128,0.15)}'
            + '.novel-rd-btn.active{background:rgba(128,128,128,0.22)}'
            + '.novel-rd-body{flex:1;overflow-y:auto;padding:36px 24px;display:flex;justify-content:center}'
            + '.novel-rd-page{max-width:760px;width:100%;line-height:1.85;letter-spacing:.02em;text-align:justify;word-wrap:break-word}'
            + '.novel-rd-page h1,.novel-rd-page h2,.novel-rd-page h3{margin:1.4em 0 .6em;line-height:1.4}'
            + '.novel-rd-page p{margin:0 0 1em;text-indent:2em}'
            + '.novel-rd-page img{max-width:100%;height:auto;border-radius:6px;display:block;margin:1em auto}'
            + '.novel-rd-page blockquote{border-left:3px solid rgba(128,128,128,0.4);padding:.4em 1em;margin:1em 0;opacity:.85}'
            + '.novel-rd-page hr{border:none;border-top:1px solid rgba(128,128,128,0.3);margin:1.4em 0}'
            + '.novel-rd-page pre{background:rgba(128,128,128,0.12);padding:12px;border-radius:6px;overflow-x:auto}'
            + '.novel-rd-bottom{display:flex;align-items:center;gap:12px;padding:14px 24px;border-top:1px solid rgba(128,128,128,0.18);justify-content:center}'
            + '.novel-rd-pagebtn{padding:8px 22px;border-radius:8px;border:1px solid rgba(128,128,128,0.4);background:transparent;color:inherit;cursor:pointer;font-size:14px;transition:all .15s}'
            + '.novel-rd-pagebtn:hover:not(:disabled){background:rgba(128,128,128,0.15);border-color:rgba(128,128,128,0.6)}'
            + '.novel-rd-pagebtn:disabled{opacity:.35;cursor:not-allowed}'
            + '.novel-rd-pageinfo{min-width:90px;text-align:center;font-size:13px;opacity:.75;font-family:ui-monospace,Menlo,Consolas,monospace}'
            + '@media(max-width:640px){.novel-rd-top{padding:10px 14px}.novel-rd-body{padding:20px 14px}.novel-rd-page{font-size:.95em}.novel-rd-bottom{padding:10px 14px}}';
        var style = document.createElement('style');
        style.setAttribute('data-novel-reader', '1');
        style.textContent = css;
        document.head.appendChild(style);
    }

    // 将 HTML 内容按分页符或字数切分为多页
    function _splitNovelPages(html, charsPerPage) {
        var container = document.createElement('div');
        container.innerHTML = String(html || '');
        var nodes = Array.prototype.slice.call(container.childNodes);

        // 1) 优先按 hr.novel-page-break 切分
        var hasMarker = container.querySelector('hr.novel-page-break, hr[data-novel-page-break]');
        if (hasMarker) {
            var pages = [[]];
            for (var i = 0; i < nodes.length; i++) {
                var n = nodes[i];
                if (n.nodeType === 1 && n.tagName === 'HR' &&
                    (n.classList.contains('novel-page-break') || n.hasAttribute('data-novel-page-break'))) {
                    if (pages[pages.length - 1].length) pages.push([]);
                } else {
                    pages[pages.length - 1].push(n);
                }
            }
            return pages.map(function(arr) {
                var d = document.createElement('div');
                arr.forEach(function(n){ d.appendChild(n.cloneNode(true)); });
                return d.innerHTML;
            }).filter(function(h){ return h.trim() !== ''; });
        }

        // 2) 按字数自动切页（按段落/块边界）
        var per = Math.max(200, Math.min(20000, parseInt(charsPerPage, 10) || 1500));
        var result = [];
        var bufHtml = '';
        var bufLen = 0;
        for (var k = 0; k < nodes.length; k++) {
            var node = nodes[k];
            var hWrap = document.createElement('div');
            hWrap.appendChild(node.cloneNode(true));
            var nodeHtml = hWrap.innerHTML;
            var nodeText = (node.textContent || '').replace(/\s+/g, ' ').trim();
            var nodeLen = nodeText.length;
            // 当前页加上此节点会超阈值且当前页非空 → 先切页
            if (bufLen + nodeLen > per && bufLen > 0) {
                result.push(bufHtml);
                bufHtml = '';
                bufLen = 0;
            }
            bufHtml += nodeHtml;
            bufLen += nodeLen;
        }
        if (bufHtml.trim()) result.push(bufHtml);
        if (!result.length) result.push(String(html || ''));
        return result;
    }

    function _buildNovelReader() {
        if (_novelReader) return _novelReader;
        _injectNovelStyles();
        var rd = document.createElement('div');
        rd.className = 'novel-reader';
        rd.innerHTML =
            '<div class="novel-rd-top">' +
                '<div class="novel-rd-title"></div>' +
                '<div class="novel-rd-tools">' +
                    '<button type="button" class="novel-rd-btn" data-act="font-dec" title="减小字号">A−</button>' +
                    '<button type="button" class="novel-rd-btn" data-act="font-inc" title="增大字号">A+</button>' +
                    '<button type="button" class="novel-rd-btn" data-theme="light" title="明亮">明</button>' +
                    '<button type="button" class="novel-rd-btn" data-theme="sepia" title="护眼">护</button>' +
                    '<button type="button" class="novel-rd-btn" data-theme="dark" title="深色">深</button>' +
                    '<button type="button" class="novel-rd-btn" data-theme="black" title="纯黑">黑</button>' +
                    '<button type="button" class="novel-rd-btn" data-act="close" title="退出阅读 (Esc)">×</button>' +
                '</div>' +
            '</div>' +
            '<div class="novel-rd-body"><div class="novel-rd-page"></div></div>' +
            '<div class="novel-rd-bottom">' +
                '<button type="button" class="novel-rd-pagebtn" data-act="prev">← 上一页</button>' +
                '<span class="novel-rd-pageinfo">1 / 1</span>' +
                '<button type="button" class="novel-rd-pagebtn" data-act="next">下一页 →</button>' +
            '</div>';
        document.body.appendChild(rd);
        _novelReader = rd;

        rd.addEventListener('click', function(e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            var act = btn.getAttribute('data-act');
            var theme = btn.getAttribute('data-theme');
            if (theme) { _setNovelTheme(theme); return; }
            if (act === 'close') { closeNovelReader(); return; }
            if (act === 'prev')  { _novelGoTo(_novelPageIdx - 1); return; }
            if (act === 'next')  { _novelGoTo(_novelPageIdx + 1); return; }
            if (act === 'font-inc' || act === 'font-dec') {
                var page = rd.querySelector('.novel-rd-page');
                if (!page) return;
                var prefs = _novelLoadPrefs();
                prefs.fontSize = Math.max(13, Math.min(28, prefs.fontSize + (act === 'font-inc' ? 1 : -1)));
                page.style.fontSize = prefs.fontSize + 'px';
                _novelSavePrefs(prefs);
            }
        });
        return rd;
    }

    function _setNovelTheme(theme) {
        if (!_novelReader) return;
        _novelReader.setAttribute('data-theme', theme);
        var prefs = _novelLoadPrefs();
        prefs.theme = theme;
        _novelSavePrefs(prefs);
        _novelReader.querySelectorAll('button[data-theme]').forEach(function(b) {
            b.classList.toggle('active', b.getAttribute('data-theme') === theme);
        });
    }

    function _novelGoTo(idx) {
        if (!_novelReader) return;
        if (idx < 0) idx = 0;
        if (idx >= _novelPages.length) idx = _novelPages.length - 1;
        _novelPageIdx = idx;
        var page = _novelReader.querySelector('.novel-rd-page');
        var info = _novelReader.querySelector('.novel-rd-pageinfo');
        var prevBtn = _novelReader.querySelector('button[data-act="prev"]');
        var nextBtn = _novelReader.querySelector('button[data-act="next"]');
        if (page) {
            page.innerHTML = _novelPages[idx] || '';
            page.querySelectorAll('a').forEach(function(a) {
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener noreferrer');
            });
            page.scrollTop = 0;
        }
        var body = _novelReader.querySelector('.novel-rd-body');
        if (body) body.scrollTop = 0;
        if (info) info.textContent = (idx + 1) + ' / ' + _novelPages.length;
        if (prevBtn) prevBtn.disabled = idx <= 0;
        if (nextBtn) nextBtn.disabled = idx >= _novelPages.length - 1;
    }

    function openNovelReader(post) {
        if (!post || !post.content) return;
        _buildNovelReader();
        var per = parseInt(post.chars_per_page, 10) || 1500;
        _novelPages = _splitNovelPages(post.content, per);
        _novelPageIdx = 0;
        var titleEl = _novelReader.querySelector('.novel-rd-title');
        if (titleEl) titleEl.textContent = post.title || '';
        var prefs = _novelLoadPrefs();
        _setNovelTheme(prefs.theme);
        var page = _novelReader.querySelector('.novel-rd-page');
        if (page) page.style.fontSize = prefs.fontSize + 'px';
        _novelGoTo(0);
        _novelReader.classList.add('show');
        document.documentElement.style.overflow = 'hidden';

        if (_novelKeyHandler) document.removeEventListener('keydown', _novelKeyHandler);
        _novelKeyHandler = function(e) {
            if (e.key === 'Escape') closeNovelReader();
            else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); _novelGoTo(_novelPageIdx - 1); }
            else if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') { e.preventDefault(); _novelGoTo(_novelPageIdx + 1); }
        };
        document.addEventListener('keydown', _novelKeyHandler);
    }

    function closeNovelReader() {
        if (!_novelReader) return;
        _novelReader.classList.remove('show');
        // 阅读器关闭后，若帖子详情仍打开则保持 overflow:hidden
        if (!_postLb || !_postLb.classList.contains('show')) {
            document.documentElement.style.overflow = '';
        }
        if (_novelKeyHandler) {
            document.removeEventListener('keydown', _novelKeyHandler);
            _novelKeyHandler = null;
        }
    }

    function loadPosts() {
        var container = document.getElementById('postsGrid');
        var postsCard = document.getElementById('posts');
        var navPostsInput = document.getElementById('nav-posts');
        var navPostsLabel = null;
        if (navPostsInput) {
            navPostsLabel = document.querySelector('label[for="nav-posts"]');
        }
        if (!container) return;

        var navWrap = document.querySelector('.radio-container');
        if (navWrap) navWrap.style.setProperty('--total-radio', _postsEnabled ? '5' : '4');

        if (!_postsEnabled) {
            if (postsCard) postsCard.style.display = 'none';
            if (navPostsInput) navPostsInput.style.display = 'none';
            if (navPostsLabel) navPostsLabel.style.display = 'none';
            if (navPostsInput && navPostsInput.checked) {
                var navProjects = document.getElementById('nav-projects');
                if (navProjects) navProjects.checked = true;
            }
            updateGlider();
            return;
        }

        if (postsCard) postsCard.style.display = '';
        if (navPostsInput) navPostsInput.style.display = '';
        if (navPostsLabel) navPostsLabel.style.display = '';
        updateGlider();

        if (document.hidden) {
            setTimeout(loadPosts, 1000);
            return;
        }

        var controller = (window.AbortController ? new AbortController() : null);
        _postsAbort = controller;

        var t = setTimeout(function() {
            if (controller) controller.abort();
        }, 60000);

        var headers = {};
        if (_postsEtag) headers['If-None-Match'] = _postsEtag;

        fetch('admin/api.php?action=get_posts', {
            method: 'GET',
            headers: headers,
            cache: 'no-cache',
            signal: controller ? controller.signal : undefined
        })
        .then(function(r) {
            clearTimeout(t);
            if (!r.ok) throw new Error('bad status ' + r.status);
            var et = r.headers.get('ETag');
            if (et) _postsEtag = et;
            return r.json();
        })
        .then(function(res) {
            if (!res || !res.success) {
                showPostsEmpty(container, '加载失败');
                return;
            }
            var data = Array.isArray(res.data) ? res.data : [];
            renderPosts(container, data);
        })
        .catch(function(err) {
            clearTimeout(t);
            console.error('加载帖子失败:', err);
            showPostsEmpty(container, '加载失败');
        });
    }

    var _postsDataMap = {};
    var _postImgObserver = null;
    var POSTS_BATCH_SIZE = 6;
    var _postCoverPlaceholderSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

    function getPostImgObserver() {
        if (_postImgObserver) return _postImgObserver;
        _postImgObserver = new IntersectionObserver(function(entries) {
            for (var i = 0; i < entries.length; i++) {
                if (entries[i].isIntersecting) {
                    var img = entries[i].target;
                    var src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                    }
                    _postImgObserver.unobserve(img);
                }
            }
        }, { rootMargin: '0px 200px 0px 200px' });
        return _postImgObserver;
    }

    function createPostItem(post, index) {
        var item = document.createElement('div');
        item.className = 'post-item';
        item.setAttribute('role', 'button');
        item.setAttribute('tabindex', '0');
        item.setAttribute('data-post-idx', index);

        if (post.cover) {
            var img = document.createElement('img');
            img.className = 'post-cover';
            img.alt = post.title || '封面';
            img.decoding = 'async';
            if (index < POSTS_BATCH_SIZE) {
                img.src = post.cover;
            } else {
                img.setAttribute('data-src', post.cover);
                getPostImgObserver().observe(img);
            }
            item.appendChild(img);
        } else {
            var placeholder = document.createElement('div');
            placeholder.className = 'post-cover-placeholder';
            placeholder.innerHTML = _postCoverPlaceholderSvg;
            item.appendChild(placeholder);
        }

        var content = document.createElement('div');
        content.className = 'post-content';
        var h4 = document.createElement('h4');
        h4.className = 'post-title';
        h4.textContent = post.title || '无标题';
        content.appendChild(h4);
        if (post.subtitle) {
            var p = document.createElement('p');
            p.className = 'post-subtitle';
            p.textContent = post.subtitle;
            content.appendChild(p);
        }
        item.appendChild(content);

        return item;
    }

    function renderPostsBatch(container, data, startIdx) {
        var end = Math.min(startIdx + POSTS_BATCH_SIZE, data.length);
        var frag = document.createDocumentFragment();
        for (var i = startIdx; i < end; i++) {
            frag.appendChild(createPostItem(data[i], i));
        }
        container.appendChild(frag);

        if (end < data.length) {
            requestAnimationFrame(function() {
                renderPostsBatch(container, data, end);
            });
        }
    }

    function setupPostsDelegation(container) {
        container.addEventListener('click', function(e) {
            var item = e.target.closest('.post-item');
            if (!item) return;
            var idx = item.getAttribute('data-post-idx');
            if (idx != null && _postsDataMap[idx]) openPostLightbox(_postsDataMap[idx]);
        });
        container.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var item = e.target.closest('.post-item');
            if (!item) return;
            e.preventDefault();
            var idx = item.getAttribute('data-post-idx');
            if (idx != null && _postsDataMap[idx]) openPostLightbox(_postsDataMap[idx]);
        });
    }

    var _postsDelegated = false;

    function renderPosts(container, data) {
        container.textContent = '';

        if (!data.length) {
            showPostsEmpty(container, '暂无帖子内容');
            return;
        }

        var newSig = data.map(function(p) {
            var len = (p && p.content) ? String(p.content).length : 0;
            return (p.id || '') + '|' + (p.title || '') + '|' + (p.subtitle || '') + '|' + (p.cover || '') + '|' + len + '|' + (p.created_at || '');
        }).join('||');

        if (newSig === _postsSig) return;
        _postsSig = newSig;

        _postsDataMap = {};
        for (var i = 0; i < data.length; i++) {
            _postsDataMap[i] = data[i];
        }

        if (_postImgObserver) {
            _postImgObserver.disconnect();
        }

        var wrap = container.parentElement;
        if (!wrap.classList.contains('posts-scroll-wrap')) {
            var outer = document.createElement('div');
            outer.className = 'posts-scroll-wrap';
            wrap.insertBefore(outer, container);
            outer.appendChild(container);
            wrap = outer;
        }

        if (!_postsDelegated) {
            setupPostsDelegation(container);
            _postsDelegated = true;
        }

        renderPostsBatch(container, data, 0);

        wrap.querySelectorAll('.posts-nav').forEach(function(n) { n.remove(); });

        if (data.length > 3) {
            var arrowSvgL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>';
            var arrowSvgR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>';

            var btnL = document.createElement('button');
            btnL.className = 'posts-nav posts-nav-left';
            btnL.innerHTML = arrowSvgL;
            btnL.title = '上一页';
            btnL.type = 'button';

            var btnR = document.createElement('button');
            btnR.className = 'posts-nav posts-nav-right visible';
            btnR.innerHTML = arrowSvgR;
            btnR.title = '下一页';
            btnR.type = 'button';

            wrap.appendChild(btnL);
            wrap.appendChild(btnR);

            function updateNavState() {
                var sl = container.scrollLeft, sw = container.scrollWidth, cw = container.clientWidth;
                btnL.classList.toggle('visible', sl > 10);
                btnR.classList.toggle('visible', sl < sw - cw - 10);
            }

            function scrollBy(dir) {
                var itemW = container.querySelector('.post-item');
                var step = itemW ? (itemW.offsetWidth + 20) : container.clientWidth * 0.8;
                container.scrollBy({ left: dir * step, behavior: 'smooth' });
            }

            btnL.addEventListener('click', function(e) { e.stopPropagation(); scrollBy(-1); });
            btnR.addEventListener('click', function(e) { e.stopPropagation(); scrollBy(1); });
            container.addEventListener('scroll', updateNavState, { passive: true });
            updateNavState();
        }
    }

    function showPostsEmpty(container, text) {
        container.textContent = '';
        var wrap = document.createElement('div');
        wrap.className = 'posts-empty';
        wrap.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">' +
            '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' +
            '<polyline points="14 2 14 8 20 8"/>' +
            '<line x1="16" y1="13" x2="8" y2="13"/>' +
            '<line x1="16" y1="17" x2="8" y2="17"/>' +
            '</svg>' +
            '<span>' + esc(text) + '</span>';
        container.appendChild(wrap);
    }

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && _postsSig === '') {
            loadPosts();
        }
    });

    // ========== 回复模块 ==========
    var _repliesSig = '';
    var _repliesEtag = '';
    var _repliesTimer = null;
    var _repliesAbort = null;
    var _repliesLoadedOnce = false;
    var _lightbox = null;
    var _lightboxImg = null;

    function initLightbox() {
        if (_lightbox) return;
        var lb = document.createElement('div');
        lb.className = 'lightbox';
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        lb.innerHTML =
            '<div class="lightbox-dialog">' +
            '<button type="button" class="lightbox-close" aria-label="关闭">×</button>' +
            '<img class="lightbox-img" alt="预览">' +
            '</div>';
        document.body.appendChild(lb);
        _lightbox = lb;
        _lightboxImg = lb.querySelector('.lightbox-img');

        lb.addEventListener('click', function (e) {
            if (e.target === lb) closeLightbox();
        });
        lb.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeLightbox();
        });
    }

    function openLightbox(src) {
        initLightbox();
        if (!_lightbox || !_lightboxImg) return;

        _lightboxImg.alt = '加载中...';
        _lightbox.classList.add('open');
        document.documentElement.style.overflow = 'hidden';

        _lightboxImg.onload = function() {
            _lightboxImg.alt = '预览';
        };
        _lightboxImg.onerror = function() {
            _lightboxImg.alt = '图片加载失败';
        };

        _lightboxImg.src = src;
    }

    function closeLightbox() {
        if (!_lightbox) return;
        _lightbox.classList.remove('open');
        if (_lightboxImg) _lightboxImg.src = '';
        document.documentElement.style.overflow = '';
    }

    function safeText(v, maxLen) {
        if (v == null) return '';
        var s = String(v);
        if (s.length > (maxLen || 400)) s = s.slice(0, (maxLen || 400));
        return s.trim();
    }

    function normalizeReplyImage(p) {
        if (!p) return '';
        var s = String(p).trim();
        if (s.indexOf('admin/img.php?t=') === 0) return s;
        return '';
    }

    function showRepliesLoading(container) {
        container.textContent = '';
        var wrap = document.createElement('div');
        wrap.className = 'replies-loading';
        wrap.innerHTML =
            '<svg class="replies-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>' +
            '<path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>' +
            '</svg>' +
            '<span>加载中...</span>';
        container.appendChild(wrap);
    }

    function showRepliesEmpty(container, text) {
        container.textContent = '';
        var wrap = document.createElement('div');
        wrap.className = 'replies-empty';
        wrap.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">' +
            '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' +
            '</svg>';
        var p = document.createElement('p');
        p.textContent = text || '暂无回复内容';
        wrap.appendChild(p);
        container.appendChild(wrap);
    }

    function computeRepliesSig(data) {
        if (!Array.isArray(data)) return '';
        return data.map(function (it) {
            return [
                safeText(it && it.name, 60),
                safeText(it && it.email_masked, 80),
                safeText(it && it.reply_time, 60),
                safeText(it && it.message_preview, 500),
                safeText(it && it.reply_preview, 500),
                normalizeReplyImage(it && it.image)
            ].join('\u0000');
        }).join('\u0001');
    }

    function renderReplies(container, data) {
        container.textContent = '';
        var frag = document.createDocumentFragment();

        data.forEach(function (item, index) {
            var name = safeText(item && item.name, 60) || '匿名';
            var timeRaw = safeText(item && item.reply_time, 60);
            var emailMasked = safeText(item && item.email_masked, 80);
            var userContent = safeText(item && item.message_preview, 800);
            var replyContent = safeText(item && item.reply_preview, 800);
            var imgPath = normalizeReplyImage(item && (item.image_url || item.imageUrl || item.image));
            var isFirst = (index === 0);

            if (!userContent && !replyContent) return;

            var card = document.createElement('div');
            card.className = 'reply-item';

            var header = document.createElement('div');
            header.className = 'reply-header';

            var avatar = document.createElement('div');
            avatar.className = 'reply-avatar';
            avatar.innerHTML =
                '<svg class="reply-avatar-svg" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>' +
                '<circle cx="12" cy="7" r="4"></circle>' +
                '</svg>';

            var meta = document.createElement('div');
            meta.className = 'reply-meta';
            var nm = document.createElement('div');
            nm.className = 'reply-name';
            nm.textContent = name;
            var tm = document.createElement('div');
            tm.className = 'reply-time';
            tm.textContent = formatReplyTime(timeRaw) + (emailMasked ? ' · ' + emailMasked : '');
            meta.appendChild(nm);
            meta.appendChild(tm);

            var badge = document.createElement('span');
            badge.className = 'reply-badge';
            badge.innerHTML =
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">' +
                '<polyline points="20 6 9 17 4 12"/>' +
                '</svg>已回复';

            header.appendChild(avatar);
            header.appendChild(meta);
            header.appendChild(badge);
            card.appendChild(header);

            if (imgPath) {
                var imgWrap = document.createElement('div');
                imgWrap.className = 'reply-image';

                var link = document.createElement('a');
                link.href = imgPath;
                link.rel = 'noopener noreferrer';
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    openLightbox(imgPath);
                });

                var img = document.createElement('img');
                img.src = imgPath;
                img.alt = '用户图片';
                img.loading = 'lazy';
                img.decoding = 'async';
                img.referrerPolicy = 'no-referrer';
                img.addEventListener('error', function () {
                    if (imgWrap && imgWrap.parentNode) imgWrap.parentNode.removeChild(imgWrap);
                });

                link.appendChild(img);
                imgWrap.appendChild(link);
                card.appendChild(imgWrap);
            }

            var content = document.createElement('div');
            content.className = 'reply-content';
            if (userContent) {
                var u = document.createElement('div');
                u.className = 'reply-user';
                u.textContent = '用户：' + userContent;
                content.appendChild(u);
            }
            if (replyContent) {
                var a = document.createElement('div');
                a.className = 'reply-admin';
                a.textContent = '管理员：' + replyContent;
                content.appendChild(a);
            }
            card.appendChild(content);

            if (isFirst) {
                frag.appendChild(card);
            } else {
                var wrap = document.createElement('div');
                wrap.className = 'reply-collapsible';

                var summary = [];
                if (name) summary.push(name);
                var timeLabel = formatReplyTime(timeRaw);
                if (timeLabel) summary.push(timeLabel);
                if (emailMasked) summary.push(emailMasked);

                var toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'reply-toggle';

                var baseLabel = summary.join(' · ') || '历史回复';
                var expanded = false;
                function updateToggleLabel() {
                    toggle.textContent = baseLabel + (expanded ? '（点击收起）' : '（点击展开）');
                }
                updateToggleLabel();

                card.classList.add('reply-card-collapsed');

                wrap.appendChild(toggle);
                wrap.appendChild(card);

                toggle.addEventListener('click', function () {
                    expanded = !expanded;
                    if (expanded) {
                        card.classList.remove('reply-card-collapsed');
                    } else {
                        card.classList.add('reply-card-collapsed');
                    }
                    updateToggleLabel();
                });

                frag.appendChild(wrap);
            }
        });

        container.appendChild(frag);
    }

    function scheduleReplies(delayMs) {
        if (_repliesTimer) clearTimeout(_repliesTimer);
        _repliesTimer = setTimeout(loadReplies, delayMs);
    }

    function loadReplies() {
        var container = document.getElementById('repliesList');
        if (!container) return;

        if (document.hidden) {
            scheduleReplies(30000);
            return;
        }

        if (!_repliesLoadedOnce) showRepliesLoading(container);

        if (_repliesAbort) {
            try { _repliesAbort.abort(); } catch (e) {}
        }
        var controller = (window.AbortController ? new AbortController() : null);
        _repliesAbort = controller;

        var t = setTimeout(function () {
            if (controller) controller.abort();
        }, 60000);

        var headers = {};
        if (_repliesEtag) headers['If-None-Match'] = _repliesEtag;

        fetch('admin/api.php?action=get_replies', {
            method: 'GET',
            headers: headers,
            cache: 'no-cache',
            signal: controller ? controller.signal : undefined
        })
        .then(function (r) {
            clearTimeout(t);
            if (r.status === 304) {
                _repliesLoadedOnce = true;
                scheduleReplies(30000);
                return null;
            }
            if (!r.ok) throw new Error('bad status ' + r.status);
            var et = r.headers.get('ETag');
            if (et) _repliesEtag = et;
            return r.json();
        })
        .then(function (res) {
            if (!res) return;
            _repliesLoadedOnce = true;
            var data = (res && res.success && Array.isArray(res.data)) ? res.data : [];
            if (!data.length) {
                _repliesSig = 'EMPTY';
                showRepliesEmpty(container, '暂无回复内容');
                scheduleReplies(30000);
                return;
            }

            var newSig = computeRepliesSig(data);
            if (newSig !== _repliesSig) {
                _repliesSig = newSig;
                renderReplies(container, data);
            }
            scheduleReplies(30000);
        })
        .catch(function (err) {
            clearTimeout(t);
            console.error('加载回复失败:', err);
            scheduleReplies(45000);
            if (!_repliesLoadedOnce) showRepliesEmpty(container, '加载失败，请稍后重试');
        });
    }

    function formatReplyTime(timeStr) {
        if (!timeStr) return '';
        var date = new Date(timeStr.replace(' ', 'T'));
        if (isNaN(date.getTime())) return '';
        var now = new Date();
        var diff = now - date;
        if (diff < 0) diff = 0;
        var days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) return '今天';
        if (days === 1) return '昨天';
        if (days < 7) return days + '天前';
        if (days < 30) return Math.floor(days / 7) + '周前';
        if (days < 365) return Math.floor(days / 30) + '月前';
        return Math.floor(days / 365) + '年前';
    }

    loadReplies();
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (_repliesTimer) { clearTimeout(_repliesTimer); _repliesTimer = null; }
            if (_repliesAbort) { try { _repliesAbort.abort(); } catch(e){} _repliesAbort = null; }
        } else {
            scheduleReplies(200);
        }
    });
})();

// ==================== 滚动渐入动画 ====================
(function() {
    var items = document.querySelectorAll('.fade-in');
    if (!items.length) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach(function(el) { el.classList.add('visible'); });
        return;
    }
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    items.forEach(function(el, i) {
        el.style.transitionDelay = (i * 0.07) + 's';
        observer.observe(el);
    });
})();

// ==================== 赞助弹窗 ====================
function initSponsor(sponsorCfg) {
    var tab = document.getElementById('sponsorTab');
    var overlay = document.getElementById('sponsorOverlay');
    var closeBtn = document.getElementById('sponsorClose');
    var body = document.getElementById('sponsorDialogBody');
    var titleEl = overlay && overlay.querySelector('.sponsor-dialog-title');
    var subtitleEl = overlay && overlay.querySelector('.sponsor-dialog-subtitle');

    if (!tab || !overlay || !body) return;

    // 未启用则隐藏
    if (!sponsorCfg || sponsorCfg.enabled === false) {
        tab.style.display = 'none';
        return;
    }

    // 更新标题/副标题
    if (titleEl && sponsorCfg.title) titleEl.textContent = sponsorCfg.title;
    if (subtitleEl && sponsorCfg.subtitle) subtitleEl.textContent = sponsorCfg.subtitle;

    // 渲染二维码列表
    var qrcodes = Array.isArray(sponsorCfg.qrcodes) ? sponsorCfg.qrcodes : [];
    var validQr = qrcodes.filter(function(q) { return q && q.image; });

    body.innerHTML = '';
    if (!validQr.length) {
        var empty = document.createElement('p');
        empty.className = 'sponsor-empty';
        empty.textContent = '暂未配置收款码';
        body.appendChild(empty);
    } else {
        var list = document.createElement('div');
        list.className = 'sponsor-qr-list';
        validQr.forEach(function(qr) {
            var item = document.createElement('div');
            item.className = 'sponsor-qr-item';

            var wrap = document.createElement('div');
            wrap.className = 'sponsor-qr-img-wrap';

            var img = document.createElement('img');
            // 仅允许 data/sponsor_qr/ 路径下的图片（由后端保证）
            var src = String(qr.image || '');
            if (/^data\/sponsor_qr\/[A-Za-z0-9._-]+\.(jpe?g|png|gif|webp)(\?v=\d+)?$/i.test(src)) {
                img.src = src;
            } else {
                img.src = '';
            }
            img.alt = qr.label || '收款码';
            img.loading = 'lazy';
            wrap.appendChild(img);

            var label = document.createElement('span');
            label.className = 'sponsor-qr-label';
            label.textContent = qr.label || '';

            item.appendChild(wrap);
            item.appendChild(label);
            list.appendChild(item);
        });
        body.appendChild(list);
    }

    // 显示按钮
    tab.style.display = '';

    function openSponsor() {
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        if (closeBtn) closeBtn.focus();
    }

    function closeSponsor() {
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
        tab.focus();
    }

    tab.addEventListener('click', openSponsor);
    tab.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openSponsor(); }
    });
    if (closeBtn) closeBtn.addEventListener('click', closeSponsor);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeSponsor();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('show')) closeSponsor();
    });
}

// ==================== 商铺模块 ====================
(function() {
    var _shopCache = null;       // 原始商品列表（默认 top 4）
    var _shopSearchCache = {};   // q -> 商品列表
    var _shopCurrent = [];       // 当前显示列表
    var _shopSearchTimer = null;
    var _shopCurrentProduct = null;
    var _shopPollTimer = null;
    var _shopCurrentTradeNo = '';
    var _shopPaidAmount = '';
    var _shopEtag = '';

    function $(s) { return document.querySelector(s); }
    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function loadShop(q) {
        var slider = document.getElementById('shopSlider');
        var card = document.getElementById('shop');
        if (!slider || !card) return;

        q = q || '';
        // 使用缓存
        if (q === '' && _shopCache) { _shopCurrent = _shopCache; renderShopSlider(_shopCurrent); return; }
        if (q !== '' && _shopSearchCache[q]) { _shopCurrent = _shopSearchCache[q]; renderShopSlider(_shopCurrent); return; }

        var url = 'admin/api.php?action=get_shop_products';
        if (q !== '') url += '&q=' + encodeURIComponent(q);

        var headers = {};
        if (q === '' && _shopEtag) headers['If-None-Match'] = _shopEtag;

        fetch(url, { headers: headers, cache: 'no-store' })
            .then(function(r) {
                if (r.status === 304) return null;
                if (q === '') {
                    var et = r.headers.get('ETag');
                    if (et) _shopEtag = et;
                }
                return r.json();
            })
            .then(function(j) {
                if (!j) return;
                if (!j.success) { card.style.display = 'none'; return; }
                var list = j.data || [];
                if (q === '') _shopCache = list;
                else _shopSearchCache[q] = list;

                renderShopCategories(list);
                var catSel = document.getElementById('shopCategoryFilter');
                var catVal = catSel ? catSel.value : '';
                _shopCurrent = catVal ? list.filter(function (p) { return p.category === catVal; }) : list;
                if (!list.length && q === '') {
                    // 无已发布商品：隐藏整个模块
                    card.style.display = 'none';
                    var navShop = document.getElementById('nav-shop');
                    var navShopLbl = document.querySelector('label[for="nav-shop"]');
                    if (navShop) navShop.style.display = 'none';
                    if (navShopLbl) navShopLbl.style.display = 'none';
                    if (typeof window.updateGlider === 'function') window.updateGlider();
                    return;
                }
                // 显示模块和导航
                card.style.display = '';
                // 由于初始 display:none 导致 IntersectionObserver 无法触发，手动补充 .visible
                card.classList.add('visible');
                var navShop = document.getElementById('nav-shop');
                var navShopLbl = document.querySelector('label[for="nav-shop"]');
                if (navShop) navShop.style.display = '';
                if (navShopLbl) navShopLbl.style.display = '';
                var navWrap = document.querySelector('.radio-container');
                if (navWrap) {
                    var visibleCount = 4; // 简介/技能/项目/联系
                    var pEnabled = document.getElementById('posts') && document.getElementById('posts').style.display !== 'none';
                    if (pEnabled) visibleCount++;
                    visibleCount++; // 商铺
                    navWrap.style.setProperty('--total-radio', visibleCount);
                }
                if (typeof window.updateGlider === 'function') window.updateGlider();
                renderShopSlider(_shopCurrent);
            })
            .catch(function() { card.style.display = 'none'; });
    }

    function renderShopSlider(list) {
        var slider = document.getElementById('shopSlider');
        if (!slider) return;
        if (!list.length) {
            slider.innerHTML = '<div class="shop-empty-state">未找到匹配的商品</div>';
            updateArrowVisibility();
            return;
        }
        var html = '';
        list.forEach(function(p, i) {
            var thumb = p.thumbnail
                ? '<img src="' + escHtml(p.thumbnail) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">'
                : '';
            var outOfStock = (p.stock || 0) <= 0;
            html += '<div class="shop-item' + (outOfStock ? ' sold-out' : '') + '" data-idx="' + i + '" tabindex="0" role="button">'
                + '<div class="shop-item-thumb">' + thumb
                + (p.pinned ? '<span class="shop-item-badge">置顶</span>' : '')
                + (outOfStock ? '<span class="shop-item-sold">已售罄</span>' : '')
                + '</div>'
                + '<div class="shop-item-body">'
                + '<div class="shop-item-name">' + escHtml(p.name) + '</div>'
                + '<div class="shop-item-price">¥ ' + escHtml(p.price) + '</div>'
                + '</div>'
                + '</div>';
        });
        slider.innerHTML = html;

        // 绑定点击
        slider.querySelectorAll('.shop-item').forEach(function(el) {
            var handler = function() {
                var idx = parseInt(el.getAttribute('data-idx'), 10);
                openShopModal(_shopCurrent[idx]);
            };
            el.addEventListener('click', handler);
            el.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handler(); }
            });
        });

        updateArrowVisibility();
        setTimeout(updateArrowVisibility, 300);
    }

    function renderShopCategories(list) {
        var header = document.querySelector('.shop-header');
        if (!header || document.getElementById('shopCategoryFilter')) return;
        var cats = [];
        (list || []).forEach(function (p) {
            if (p.category && cats.indexOf(p.category) === -1) cats.push(p.category);
        });
        if (!cats.length) return;
        var sel = document.createElement('select');
        sel.id = 'shopCategoryFilter';
        sel.className = 'shop-category-filter';
        sel.innerHTML = '<option value="">全部分类</option>' + cats.map(function (c) {
            return '<option value="' + escHtml(c) + '">' + escHtml(c) + '</option>';
        }).join('');
        sel.addEventListener('change', function () {
            _shopCache = null;
            _shopEtag = '';
            loadShop('');
        });
        header.appendChild(sel);
    }

    function updateArrowVisibility() {
        var slider = document.getElementById('shopSlider');
        var prev = document.getElementById('shopPrevBtn');
        var next = document.getElementById('shopNextBtn');
        if (!slider || !prev || !next) return;
        var canScroll = slider.scrollWidth > slider.clientWidth + 4;
        prev.style.display = canScroll ? '' : 'none';
        next.style.display = canScroll ? '' : 'none';
    }

    // ---- 搜索 ----
    var searchInput = document.getElementById('shopSearchInput');
    var searchClear = document.getElementById('shopSearchClear');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = searchInput.value.trim();
            if (searchClear) searchClear.style.display = q ? '' : 'none';
            clearTimeout(_shopSearchTimer);
            _shopSearchTimer = setTimeout(function() { loadShop(q); }, 250);
        });
    }
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            if (searchInput) { searchInput.value = ''; searchInput.focus(); }
            searchClear.style.display = 'none';
            loadShop('');
        });
    }

    // ---- 滑动 / 拖动 ----
    var slider = document.getElementById('shopSlider');
    var prevBtn = document.getElementById('shopPrevBtn');
    var nextBtn = document.getElementById('shopNextBtn');

    if (prevBtn && slider) {
        prevBtn.addEventListener('click', function() {
            slider.scrollBy({ left: -slider.clientWidth * 0.8, behavior: 'smooth' });
        });
    }
    if (nextBtn && slider) {
        nextBtn.addEventListener('click', function() {
            slider.scrollBy({ left: slider.clientWidth * 0.8, behavior: 'smooth' });
        });
    }

    // 鼠标拖拽滚动（桌面端）
    if (slider) {
        var isDown = false, startX = 0, scrollLeft = 0, moved = false;
        slider.addEventListener('mousedown', function(e) {
            isDown = true; moved = false;
            slider.classList.add('dragging');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', function() { isDown = false; slider.classList.remove('dragging'); });
        slider.addEventListener('mouseup', function() { isDown = false; slider.classList.remove('dragging'); });
        slider.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - slider.offsetLeft;
            var walk = (x - startX) * 1.2;
            if (Math.abs(walk) > 3) moved = true;
            slider.scrollLeft = scrollLeft - walk;
        });
        // 防止拖动后触发 click
        slider.addEventListener('click', function(e) {
            if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; }
        }, true);

        window.addEventListener('resize', updateArrowVisibility);
    }

    // ---- 商品详情弹窗 ----
    var modalOverlay = document.getElementById('shopModalOverlay');
    var modalClose = document.getElementById('shopModalClose');
    var buyBtn = document.getElementById('shopBuyBtn');

    function sanitizeHtml(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var DANGEROUS = 'script,iframe,object,embed,form,input,textarea,button,style,link,meta,base';
        tmp.querySelectorAll(DANGEROUS).forEach(function(el) { el.parentNode.removeChild(el); });
        tmp.querySelectorAll('*').forEach(function(el) {
            Array.from(el.attributes).forEach(function(attr) {
                var n = attr.name.toLowerCase(), v = attr.value;
                if (n.indexOf('on') === 0) { el.removeAttribute(attr.name); return; }
                if ((n === 'href' || n === 'src' || n === 'action') && /^\s*javascript:/i.test(v)) {
                    el.removeAttribute(attr.name);
                }
            });
        });
        return tmp.innerHTML;
    }

    function openShopModal(product) {
        if (!product || !modalOverlay) return;
        _shopCurrentProduct = product;

        var thumb = document.getElementById('shopModalThumb');
        if (thumb) {
            thumb.innerHTML = product.thumbnail
                ? '<img src="' + escHtml(product.thumbnail) + '" alt="" onerror="this.parentNode.innerHTML=\'<div class=shop-modal-thumb-placeholder></div>\'">'
                : '<div class="shop-modal-thumb-placeholder"></div>';
        }
        var title = document.getElementById('shopModalTitle');
        if (title) title.textContent = product.name;

        var price = document.getElementById('shopModalPrice');
        if (price) price.textContent = '¥ ' + product.price;

        var stock = document.getElementById('shopModalStock');
        if (stock) {
            stock.textContent = '库存 ' + (product.stock || 0) + ' 件';
            stock.className = 'shop-modal-stock' + ((product.stock || 0) <= 0 ? ' out' : '');
        }

        var desc = document.getElementById('shopModalDesc');
        if (desc) {
            var rawDesc = product.description || '';
            desc.innerHTML = rawDesc ? sanitizeHtml(rawDesc) : '<span style="opacity:.6">暂无商品简介</span>';
        }

        // 支付方式
        var payBox = document.getElementById('shopPayTypes');
        if (payBox) {
            var payMap = { alipay: ['支付宝', '#1677ff'], wxpay: ['微信', '#07c160'], qqpay: ['QQ钱包', '#12b7f5'] };
            var types = product.pay_types || ['alipay','wxpay','qqpay'];
            var html = '';
            types.forEach(function(t, i) {
                var info = payMap[t];
                if (!info) return;
                html += '<label class="shop-pay-opt' + (i === 0 ? ' active' : '') + '" style="--pay-color:' + info[1] + '">'
                    + '<input type="radio" name="shop_pay_type" value="' + t + '"' + (i === 0 ? ' checked' : '') + '>'
                    + '<span>' + info[0] + '</span></label>';
            });
            payBox.innerHTML = html;
            payBox.querySelectorAll('.shop-pay-opt').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    payBox.querySelectorAll('.shop-pay-opt').forEach(function(o) { o.classList.remove('active'); });
                    opt.classList.add('active');
                });
            });
        }

        // 按钮状态
        if (buyBtn) {
            buyBtn.disabled = (product.stock || 0) <= 0;
            buyBtn.textContent = (product.stock || 0) <= 0 ? '已售罄' : '立即购买';
        }
        // 清空邮箱输入
        var emailInput = document.getElementById('shopBuyerEmail');
        if (emailInput) { emailInput.value = ''; emailInput.classList.remove('invalid'); }
        var couponInput = document.getElementById('shopCouponCode');
        if (couponInput) couponInput.value = '';
        var couponPreview = document.getElementById('shopCouponPreview');
        if (couponPreview) { couponPreview.style.display = 'none'; couponPreview.innerHTML = ''; }

        var statusEl = document.getElementById('shopModalStatus');
        if (statusEl) { statusEl.style.display = 'none'; statusEl.innerHTML = ''; }

        modalOverlay.setAttribute('aria-hidden', 'false');
        modalOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeShopModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('show');
        modalOverlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        _shopCurrentProduct = null;
        _shopCurrentTradeNo = '';
        _shopPaidAmount = '';
        if (_shopPollTimer) { clearInterval(_shopPollTimer); _shopPollTimer = null; }
        // 重置发货页状态
        var modalBody = document.getElementById('shopModalBody');
        var deliveryView = document.getElementById('shopDeliveryView');
        if (modalBody) { modalBody.style.display = ''; modalBody.classList.remove('shop-view-exit'); }
        if (deliveryView) { deliveryView.style.display = 'none'; deliveryView.classList.remove('shop-view-enter'); deliveryView.innerHTML = ''; }
    }

    if (modalClose) modalClose.addEventListener('click', closeShopModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) closeShopModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('show')) closeShopModal();
    });

    // ---- 优惠码实时预览 ----
    var _couponCheckTimer = null;
    (function () {
        var ci = document.getElementById('shopCouponCode');
        if (!ci) return;
        ci.addEventListener('input', function () {
            clearTimeout(_couponCheckTimer);
            var preview = document.getElementById('shopCouponPreview');
            var code = ci.value.trim();
            if (!code || !_shopCurrentProduct) {
                if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
                return;
            }
            _couponCheckTimer = setTimeout(function () {
                fetch('admin/api.php?action=check_shop_coupon', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code, amount: parseFloat(_shopCurrentProduct.price) || 0 })
                })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!preview) return;
                    if (j.success && j.data) {
                        preview.style.display = '';
                        preview.innerHTML = '<span class="coupon-ok">优惠后价格 <b>¥ ' + escHtml(j.data.amount) + '</b>（立省 ¥' + escHtml(j.data.discount) + '）</span>';
                    } else {
                        preview.style.display = '';
                        preview.innerHTML = '<span class="coupon-err">' + escHtml(j.message || '优惠码无效') + '</span>';
                    }
                })
                .catch(function () {
                    if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
                });
            }, 500);
        });
    })();

    // ---- 购买流程 ----
    if (buyBtn) {
        buyBtn.addEventListener('click', function() {
            if (!_shopCurrentProduct || buyBtn.disabled) return;

            // 邮箱验证
            var emailInput = document.getElementById('shopBuyerEmail');
            var email = emailInput ? emailInput.value.trim() : '';
            var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRe.test(email)) {
                if (emailInput) { emailInput.classList.add('invalid'); emailInput.focus(); }
                showShopStatus('error', '请填写有效的收货邮箱地址');
                return;
            }
            if (emailInput) emailInput.classList.remove('invalid');

            var payBox = document.getElementById('shopPayTypes');
            var checked = payBox ? payBox.querySelector('input[name="shop_pay_type"]:checked') : null;
            var payType = checked ? checked.value : 'alipay';
            var couponInput = document.getElementById('shopCouponCode');
            var couponCode = couponInput ? couponInput.value.trim() : '';

            buyBtn.disabled = true;
            buyBtn.textContent = '创建订单中...';

            fetch('admin/api.php?action=create_shop_order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: _shopCurrentProduct.id,
                    pay_type: payType,
                    email: email,
                    coupon_code: couponCode
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(j) {
                buyBtn.disabled = false;
                buyBtn.textContent = '立即购买';
                if (!j.success) {
                    showShopStatus('error', j.message || '下单失败');
                    return;
                }
                _shopCurrentTradeNo = j.data.out_trade_no;
                _shopPaidAmount = j.data.amount || '';
                var payWin = window.open(j.data.pay_url, '_blank');
                var discountText = parseFloat(j.data.discount || '0') > 0 ? '，已优惠 ¥' + j.data.discount : '';
                showShopStatus('info', '已打开支付页面，订单号：' + j.data.out_trade_no + discountText + '。支付完成后卡密将自动显示。');
                startPollingOrder(j.data.out_trade_no);
            })
            .catch(function() {
                buyBtn.disabled = false;
                buyBtn.textContent = '立即购买';
                showShopStatus('error', '网络错误，请稍后重试');
            });
        });
    }

    function showShopStatus(type, msg) {
        var el = document.getElementById('shopModalStatus');
        if (!el) return;
        el.style.display = '';
        el.className = 'shop-modal-status status-' + type;
        el.innerHTML = escHtml(msg);
    }

    function showCardResult(cardContent, outTradeNo) {
        var modalBody = document.getElementById('shopModalBody');
        var deliveryView = document.getElementById('shopDeliveryView');
        if (!modalBody || !deliveryView) return;

        var product = _shopCurrentProduct || {};
        var emailInput = document.getElementById('shopBuyerEmail');
        var email = emailInput ? emailInput.value.trim() : '';
        var tradeNo = outTradeNo || _shopCurrentTradeNo || '-';

        deliveryView.innerHTML = '<div class="delivery-check-circle">'
            + '<svg viewBox="0 0 24 24"><polyline class="delivery-check-path" points="6 12 10 16 18 8"/></svg>'
            + '</div>'
            + '<h3 class="delivery-title">购买成功</h3>'
            + '<p class="delivery-subtitle">感谢您的购买，卡密信息如下</p>'
            + '<div class="delivery-info-table">'
            + '<div class="delivery-info-row"><span class="delivery-info-label">订单编号</span><span class="delivery-info-value">' + escHtml(tradeNo) + '</span></div>'
            + '<div class="delivery-info-row"><span class="delivery-info-label">商品名称</span><span class="delivery-info-value">' + escHtml(product.name || '-') + '</span></div>'
            + '<div class="delivery-info-row"><span class="delivery-info-label">支付金额</span><span class="delivery-info-value" style="color:#ff7b7b;font-weight:600">¥ ' + escHtml(_shopPaidAmount || product.price || '0') + '</span></div>'
            + '<div class="delivery-info-row"><span class="delivery-info-label">收货邮箱</span><span class="delivery-info-value">' + escHtml(email || '-') + '</span></div>'
            + '</div>'
            + '<div class="delivery-card-section">'
            + '<div class="delivery-card-label">卡密信息</div>'
            + '<div class="delivery-card-box"><code>' + escHtml(cardContent) + '</code>'
            + '<button type="button" class="shop-copy-btn" data-v="' + escHtml(cardContent) + '">复制</button></div>'
            + '<div class="delivery-card-hint">卡密已同步发送至您的邮箱，请注意查收</div>'
            + '</div>'
            + '<button type="button" class="delivery-done-btn" id="deliveryDoneBtn">完成</button>';

        var copyBtn = deliveryView.querySelector('.shop-copy-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                var v = copyBtn.getAttribute('data-v');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(v).then(function() {
                        copyBtn.textContent = '已复制';
                        setTimeout(function() { copyBtn.textContent = '复制'; }, 1500);
                    });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = v; document.body.appendChild(ta); ta.select();
                    try { document.execCommand('copy'); copyBtn.textContent = '已复制'; } catch(e) {}
                    document.body.removeChild(ta);
                    setTimeout(function() { copyBtn.textContent = '复制'; }, 1500);
                }
            });
        }

        var doneBtn = document.getElementById('deliveryDoneBtn');
        if (doneBtn) doneBtn.addEventListener('click', closeShopModal);

        // 动画：购买页退出 → 发货页进入
        modalBody.classList.add('shop-view-exit');
        setTimeout(function() {
            modalBody.style.display = 'none';
            deliveryView.style.display = 'block';
            requestAnimationFrame(function() {
                deliveryView.classList.add('shop-view-enter');
            });
        }, 300);

        // 刷新商品列表更新库存
        _shopCache = null; _shopEtag = '';
        setTimeout(function() { loadShop(''); }, 500);
    }

    function startPollingOrder(outTradeNo) {
        if (_shopPollTimer) clearInterval(_shopPollTimer);
        var attempts = 0;
        var maxAttempts = 90; // 3 分钟（2s * 90）
        _shopPollTimer = setInterval(function() {
            attempts++;
            if (attempts > maxAttempts) {
                clearInterval(_shopPollTimer); _shopPollTimer = null;
                showShopStatus('error', '查询超时，请稍后在邮箱或刷新页面后查看订单');
                return;
            }
            fetch('admin/api.php?action=query_shop_order&out_trade_no=' + encodeURIComponent(outTradeNo))
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success) return;
                    if (j.data.status === 1 && j.data.card_content) {
                        clearInterval(_shopPollTimer); _shopPollTimer = null;
                        showCardResult(j.data.card_content, outTradeNo);
                    } else if (j.data.status === 3) {
                        clearInterval(_shopPollTimer); _shopPollTimer = null;
                        showShopStatus('error', '订单异常：卡密库存不足，请联系客服退款');
                    }
                })
                .catch(function() {});
        }, 2000);
    }

    // 监听 shop_return 页面的 postMessage
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'shop_paid' && e.data.out_trade_no) {
            // 支付返回页通知：立刻查询一次
            fetch('admin/api.php?action=query_shop_order&out_trade_no=' + encodeURIComponent(e.data.out_trade_no))
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.success && j.data.status === 1 && j.data.card_content) {
                        if (_shopPollTimer) { clearInterval(_shopPollTimer); _shopPollTimer = null; }
                        showCardResult(j.data.card_content, e.data.out_trade_no);
                    }
                });
        }
    });

    // 暴露给外部
    window.loadShop = function(q) {
        _shopCache = null;
        _shopEtag = '';
        return loadShop(q || '');
    };
})();
