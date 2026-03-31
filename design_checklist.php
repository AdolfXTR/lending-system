<?php
// ============================================================
//  design_checklist.php
//  Interactive modern design migration checklist
//  View: http://localhost/lending_system/design_checklist.php
// ============================================================

// This is a visual tracking tool for your design migration journey
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Migration Checklist | Lending System</title>
    <link rel="stylesheet" href="assets/css/modern.css">
    <style>
        body { background: var(--bg-secondary); }
        .checklist-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .checklist-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 40px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            text-align: center;
        }
        .checklist-header h1 {
            color: white;
            margin: 0;
        }
        .checklist-header p {
            color: rgba(255,255,255,0.9);
            margin: 8px 0 0 0;
        }
        .progress-bar {
            background: rgba(255,255,255,0.3);
            height: 8px;
            border-radius: 4px;
            margin-top: 20px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: white;
            width: 35%;
            transition: width 0.3s ease;
        }
        .progress-text {
            margin-top: 12px;
            font-size: 14px;
        }
        .section {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .section-header {
            background: var(--bg-tertiary);
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        .section-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
        }
        .section-toggle {
            font-size: 20px;
            transition: transform 0.2s;
        }
        .section.open .section-toggle {
            transform: rotate(90deg);
        }
        .section-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .section.open .section-content {
            max-height: 2000px;
        }
        .checklist-item {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .checklist-item:last-child {
            border-bottom: none;
        }
        .checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
            transition: all 0.2s;
        }
        .checkbox:hover {
            border-color: var(--primary);
        }
        .checkbox.checked {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .item-content {
            flex: 1;
        }
        .item-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .item-description {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .item-code {
            background: var(--bg-tertiary);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-family: monospace;
            margin-top: 8px;
            overflow-x: auto;
            border-left: 2px solid var(--primary);
        }
        .checklist-item.completed .item-title,
        .checklist-item.completed .item-description {
            color: var(--success);
            opacity: 0.7;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            text-align: center;
        }
        .stat-box-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .stat-box-label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .encouragement {
            background: var(--primary-lighter);
            border-left: 4px solid var(--primary);
            padding: 16px;
            border-radius: var(--radius-md);
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            display: none;
        }
        .encouragement.show {
            display: block;
        }
        .footer-text {
            text-align: center;
            color: var(--text-tertiary);
            font-size: 13px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <div class="checklist-container">
        <div class="checklist-header">
            <h1>🎨 Design System Migration Checklist</h1>
            <p>Your journey to a modern, professional dashboard</p>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text">
                <span id="completedCount">0</span> of <span id="totalCount">20</span> completed
            </div>
        </div>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-box-number" id="statsCompleted">0%</div>
                <div class="stat-box-label">Completed</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-number" id="statsRemaining">20</div>
                <div class="stat-box-label">Tasks Left</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-number" id="statsHours">2-3</div>
                <div class="stat-box-label">Estimated Time</div>
            </div>
        </div>

        <div class="encouragement" id="encouragement">
            ✨ Great! You're making excellent progress. Keep it up!
        </div>

        <!-- Phase 1: Setup & Learning -->
        <div class="section open">
            <div class="section-header" onclick="toggleSection(this)">
                <span class="section-title">📚 Phase 1: Setup & Learning (30 min)</span>
                <span class="section-toggle">▶</span>
            </div>
            <div class="section-content">
                <div class="checklist-item" data-id="1">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Read README_DESIGN.md</div>
                        <div class="item-description">Quick start guide (5 minutes). Understand what you got and the 3-step process.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="2">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Visit style_guide.php</div>
                        <div class="item-description">Open http://localhost/lending_system/style_guide.php in your browser. See all components live.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="3">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">View example_dashboard_modern.php</div>
                        <div class="item-description">Open the complete template example. Study the structure and HTML patterns.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="4">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Read DESIGN_INTEGRATION_GUIDE.md</div>
                        <div class="item-description">Full component reference (10 minutes). Know what components are available before migrating.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="5">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Read MIGRATION_GUIDE.md</div>
                        <div class="item-description">Step-by-step migration instructions. Shows before/after code and common patterns.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phase 2: First Page Migration -->
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <span class="section-title">⚡ Phase 2: Migrate First Page (30 min)</span>
                <span class="section-toggle">▶</span>
            </div>
            <div class="section-content">
                <div class="checklist-item" data-id="6">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Choose first page (admin/dashboard.php)</div>
                        <div class="item-description">Start with admin dashboard - it showcases all design features and gives confidence.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="7">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Add modern_header.php include</div>
                        <div class="item-description">Replace old header include. Set $pageTitle before including.</div>
                        <div class="item-code">&lt;?php $pageTitle = 'Dashboard'; require_once 'includes/modern_header.php'; ?&gt;</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="8">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Replace footer include</div>
                        <div class="item-description">Replace old footer with modern_footer.php.</div>
                        <div class="item-code">&lt;?php require_once 'includes/modern_footer.php'; ?&gt;</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="9">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Wrap content in page-header div</div>
                        <div class="item-description">Add title section at top of content with .page-header class.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="10">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Convert stat cards to .card.card--stat</div>
                        <div class="item-description">If you have metrics, use stat card component. Copy structure from example.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="11">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Wrap tables in .table-wrapper</div>
                        <div class="item-description">If you have tables, wrap them. Automatic modern striped design applies.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="12">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Test in browser & on mobile</div>
                        <div class="item-description">Open the page. Check desktop styling. Test on mobile device or browser dev tools.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="13">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Verify sidebar navigation works</div>
                        <div class="item-description">Check admin sidebar works correctly. Verify active page is highlighted.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phase 3: Expand Migration -->
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <span class="section-title">🚀 Phase 3: Expand to More Pages (1-2 hours)</span>
                <span class="section-toggle">▶</span>
            </div>
            <div class="section-content">
                <div class="checklist-item" data-id="14">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Migrate user/dashboard.php</div>
                        <div class="item-description">Apply same pattern. This is public-facing, shows off design to users.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="15">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Migrate loan pages</div>
                        <div class="item-description">Apply to user/loan/index.php and admin/loans/index.php. Tables showcase.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="16">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Migrate billing pages</div>
                        <div class="item-description">Apply to user/billing/index.php and admin/billing/index.php.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="17">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Migrate admin management pages</div>
                        <div class="item-description">Apply to users, registrations, savings management pages.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="18">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Update button styles throughout</div>
                        <div class="item-description">Replace .btn-primary with .btn.btn--primary etc. Use button variants.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="19">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Customize colors for your brand</div>
                        <div class="item-description">Edit modern.css primary color to match your branding. Update logo emoji.</div>
                    </div>
                </div>

                <div class="checklist-item" data-id="20">
                    <div class="checkbox" onclick="toggleCheck(this)"></div>
                    <div class="item-content">
                        <div class="item-title">Full system test & launch! 🎉</div>
                        <div class="item-description">Test all pages, all features, on desktop and mobile. Deploy to production!</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-text">
            💡 Estimated total time: 2-3 hours for complete migration
            <br>📱 All responsive design is automatic - no extra work needed
            <br>✨ Your lending system now looks professional and modern!
        </div>
    </div>

    <script>
        // Load saved progress from localStorage
        function loadProgress() {
            const saved = localStorage.getItem('designChecklist');
            if (saved) {
                const checked = JSON.parse(saved);
                checked.forEach(id => {
                    const item = document.querySelector(`[data-id="${id}"]`);
                    if (item) {
                        item.classList.add('completed');
                        item.querySelector('.checkbox').classList.add('checked');
                        item.querySelector('.checkbox').innerHTML = '✓';
                    }
                });
            }
            updateStats();
        }

        // Save progress to localStorage
        function saveProgress() {
            const checked = Array.from(document.querySelectorAll('.checklist-item.completed')).map(el => el.dataset.id);
            localStorage.setItem('designChecklist', JSON.stringify(checked));
            updateStats();
        }

        // Toggle checkbox
        function toggleCheck(el) {
            const item = el.closest('.checklist-item');
            item.classList.toggle('completed');
            el.classList.toggle('checked');
            el.innerHTML = item.classList.contains('completed') ? '✓' : '';
            saveProgress();
        }

        // Toggle section
        function toggleSection(el) {
            el.closest('.section').classList.toggle('open');
        }

        // Update stats
        function updateStats() {
            const total = document.querySelectorAll('.checklist-item').length;
            const completed = document.querySelectorAll('.checklist-item.completed').length;
            const remaining = total - completed;
            const percentage = Math.round((completed / total) * 100);

            document.getElementById('completedCount').textContent = completed;
            document.getElementById('totalCount').textContent = total;
            document.getElementById('statsCompleted').textContent = percentage + '%';
            document.getElementById('statsRemaining').textContent = remaining;
            document.getElementById('progressFill').style.width = percentage + '%';

            if (completed > 5 && completed < total * 0.75) {
                document.getElementById('encouragement').classList.add('show');
            } else if (completed >= total * 0.75) {
                document.getElementById('encouragement').textContent = '🎯 Almost done! You\'re doing amazing!';
            }
        }

        // Initialize
        loadProgress();
    </script>
</body>
</html>
