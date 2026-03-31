<?php
// ============================================================
//  style_guide.php
//  Component showcase and style guide
//  Access: http://localhost/lending_system/style_guide.php
// ============================================================

$pageTitle = 'Component Showcase';

// Create minimal header without sidebar for style guide
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Style Guide</title>
    <link rel="stylesheet" href="<?= APP_URL ?? '.' ?>/assets/css/modern.css">
    <style>
        body {
            margin: 0;
            padding: 40px 20px;
        }
        .guide-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .section {
            margin-bottom: 60px;
        }
        .section h2 {
            margin-top: 40px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }
        .section h3 {
            margin-top: 24px;
            margin-bottom: 12px;
            color: var(--text-secondary);
        }
        .demo-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
        }
        .demo-item {
            flex: 1;
            min-width: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        code {
            display: block;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
            overflow-x: auto;
        }
        .header-bar {
            background: var(--primary);
            color: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
        }
        .header-bar h1 {
            margin: 0;
            color: white;
        }
        .header-bar p {
            margin: 8px 0 0 0;
            color: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
    <div class="guide-container">
        <!-- Header -->
        <div class="header-bar">
            <h1>💎 Component & Style Guide</h1>
            <p>All modern UI components for your fintech dashboard</p>
        </div>

        <!-- ========== BUTTONS ========== -->
        <div class="section">
            <h2>Buttons</h2>

            <h3>Button Variants</h3>
            <div class="demo-group">
                <div class="demo-item"><button class="btn btn--primary">Primary</button></div>
                <div class="demo-item"><button class="btn btn--secondary">Secondary</button></div>
                <div class="demo-item"><button class="btn btn--outline">Outline</button></div>
                <div class="demo-item"><button class="btn btn--success">Success</button></div>
                <div class="demo-item"><button class="btn btn--danger">Danger</button></div>
                <div class="demo-item"><button class="btn btn--warning">Warning</button></div>
            </div>

            <h3>Button Sizes</h3>
            <div class="demo-group">
                <div class="demo-item"><button class="btn btn--primary btn--sm">Small</button></div>
                <div class="demo-item"><button class="btn btn--primary">Medium</button></div>
                <div class="demo-item"><button class="btn btn--primary btn--lg">Large</button></div>
            </div>

            <h3>Button States</h3>
            <div class="demo-group">
                <div class="demo-item"><button class="btn btn--primary">Normal</button></div>
                <div class="demo-item"><button class="btn btn--primary" disabled>Disabled</button></div>
            </div>

            <h3>HTML Code</h3>
            <code>&lt;button class="btn btn--primary"&gt;Click Me&lt;/button&gt;
&lt;button class="btn btn--secondary"&gt;Secondary&lt;/button&gt;
&lt;button class="btn btn--danger" disabled&gt;Disabled&lt;/button&gt;</code>
        </div>

        <!-- ========== BADGES ========== -->
        <div class="section">
            <h2>Badges</h2>

            <h3>Badge Variants</h3>
            <div class="demo-group">
                <div class="demo-item"><span class="badge badge--primary">Primary</span></div>
                <div class="demo-item"><span class="badge badge--success">Success</span></div>
                <div class="demo-item"><span class="badge badge--warning">Warning</span></div>
                <div class="demo-item"><span class="badge badge--danger">Danger</span></div>
                <div class="demo-item"><span class="badge badge--secondary">Secondary</span></div>
            </div>

            <h3>With Icons</h3>
            <div class="demo-group">
                <div class="demo-item"><span class="badge badge--success"><span>✓</span> Completed</span></div>
                <div class="demo-item"><span class="badge badge--warning"><span>⏳</span> Pending</span></div>
                <div class="demo-item"><span class="badge badge--danger"><span>✕</span> Failed</span></div>
            </div>

            <h3>HTML Code</h3>
            <code>&lt;span class="badge badge--success"&gt;Completed&lt;/span&gt;
&lt;span class="badge badge--warning"&gt;Pending&lt;/span&gt;
&lt;span class="badge badge--danger"&gt;&lt;span&gt;✕&lt;/span&gt; Failed&lt;/span&gt;</code>
        </div>

        <!-- ========== ALERTS ========== -->
        <div class="section">
            <h2>Alerts</h2>

            <h3>Alert Variants</h3>
            <div class="alert alert--success">
                <span class="alert__icon">✓</span>
                <div class="alert__content">
                    <strong>Success!</strong> Your operation was completed successfully.
                </div>
            </div>

            <div class="alert alert--warning">
                <span class="alert__icon">⚠️</span>
                <div class="alert__content">
                    <strong>Warning:</strong> Please review this before proceeding.
                </div>
            </div>

            <div class="alert alert--danger">
                <span class="alert__icon">✕</span>
                <div class="alert__content">
                    <strong>Error:</strong> Something went wrong. Please try again.
                </div>
            </div>

            <div class="alert alert--info">
                <span class="alert__icon">ℹ️</span>
                <div class="alert__content">
                    <strong>Info:</strong> This is some important information.
                </div>
            </div>

            <h3>HTML Code</h3>
            <code>&lt;div class="alert alert--success"&gt;
    &lt;span class="alert__icon"&gt;✓&lt;/span&gt;
    &lt;div class="alert__content"&gt;Success!&lt;/div&gt;
&lt;/div&gt;</code>
        </div>

        <!-- ========== CARDS ========== -->
        <div class="section">
            <h2>Cards</h2>

            <h3>Basic Card</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="card">
                    <h3 style="margin-top: 0;">Card Title</h3>
                    <p>This is a standard card component with default styling.</p>
                    <button class="btn btn--primary" style="margin-top: 12px;">Learn More</button>
                </div>

                <div class="card card--stat">
                    <div class="stat-value">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-number">₱45,250</div>
                        <div class="stat-change positive">
                            <span>📈</span> +12% this month
                        </div>
                    </div>
                </div>

                <div class="card card--stat">
                    <div class="stat-value">
                        <div class="stat-label">Active Users</div>
                        <div class="stat-number">1,234</div>
                        <div class="stat-change neutral">
                            <span>👥</span> Growing
                        </div>
                    </div>
                </div>
            </div>

            <h3 style="margin-top: 24px;">HTML Code</h3>
            <code>&lt;div class="card card--stat"&gt;
    &lt;div class="stat-value"&gt;
        &lt;div class="stat-label"&gt;Revenue&lt;/div&gt;
        &lt;div class="stat-number"&gt;₱45,250&lt;/div&gt;
        &lt;div class="stat-change positive"&gt;+12%&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</code>
        </div>

        <!-- ========== FORMS ========== -->
        <div class="section">
            <h2>Form Elements</h2>

            <div class="card" style="max-width: 600px;">
                <h3 style="margin-top: 0;">Contact Form</h3>
                
                <form onsubmit="return false;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" placeholder="John Doe">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" placeholder="john@example.com">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" placeholder="+63 912 345 6789">
                    </div>

                    <div class="form-group">
                        <label>Message</label>
                        <textarea placeholder="Enter your message here..."></textarea>
                    </div>

                    <button type="submit" class="btn btn--primary btn--full">Send Message</button>
                </form>
            </div>
        </div>

        <!-- ========== TABLES ========== -->
        <div class="section">
            <h2>Tables</h2>

            <h3>Modern Data Table</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#TRX001</td>
                            <td>₱5,000.00</td>
                            <td>Deposit</td>
                            <td>Mar 25, 2026</td>
                            <td><span class="badge badge--success">Completed</span></td>
                        </tr>
                        <tr>
                            <td>#TRX002</td>
                            <td>₱1,500.00</td>
                            <td>Withdrawal</td>
                            <td>Mar 24, 2026</td>
                            <td><span class="badge badge--warning">Pending</span></td>
                        </tr>
                        <tr>
                            <td>#TRX003</td>
                            <td>₱2,250.00</td>
                            <td>Payment</td>
                            <td>Mar 23, 2026</td>
                            <td><span class="badge badge--success">Completed</span></td>
                        </tr>
                        <tr>
                            <td>#TRX004</td>
                            <td>₱750.00</td>
                            <td>Fee</td>
                            <td>Mar 22, 2026</td>
                            <td><span class="badge badge--danger">Failed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3 style="margin-top: 24px;">HTML Code</h3>
            <code>&lt;div class="table-wrapper"&gt;
    &lt;table&gt;
        &lt;thead&gt;
            &lt;tr&gt;
                &lt;th&gt;Column&lt;/th&gt;
                &lt;th&gt;Column&lt;/th&gt;
            &lt;/tr&gt;
        &lt;/thead&gt;
        &lt;tbody&gt;
            &lt;tr&gt;&lt;td&gt;Data&lt;/td&gt;&lt;/tr&gt;
        &lt;/tbody&gt;
    &lt;/table&gt;
&lt;/div&gt;</code>
        </div>

        <!-- ========== COLORS ========== -->
        <div class="section">
            <h2>Color Palette</h2>

            <h3>Primary Colors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div style="padding: 20px; background: var(--primary); border-radius: var(--radius-lg); color: white; text-align: center;">
                    <div style="font-weight: 600;">Primary</div>
                    <div style="font-size: 12px; opacity: 0.8;">#1e40af</div>
                </div>
                <div style="padding: 20px; background: var(--primary-light); border-radius: var(--radius-lg); color: white; text-align: center;">
                    <div style="font-weight: 600;">Primary Light</div>
                    <div style="font-size: 12px; opacity: 0.8;">#3b82f6</div>
                </div>
                <div style="padding: 20px; background: var(--primary-lighter); border-radius: var(--radius-lg); color: var(--primary); text-align: center;">
                    <div style="font-weight: 600;">Primary Lighter</div>
                    <div style="font-size: 12px;">#dbeafe</div>
                </div>
            </div>

            <h3 style="margin-top: 24px;">Status Colors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                <div style="padding: 20px; background: var(--success); border-radius: var(--radius-lg); color: white; text-align: center; font-weight: 600;">
                    Success<br><span style="font-size: 12px; opacity: 0.8;">#10b981</span>
                </div>
                <div style="padding: 20px; background: var(--warning); border-radius: var(--radius-lg); color: white; text-align: center; font-weight: 600;">
                    Warning<br><span style="font-size: 12px; opacity: 0.8;">#f59e0b</span>
                </div>
                <div style="padding: 20px; background: var(--danger); border-radius: var(--radius-lg); color: white; text-align: center; font-weight: 600;">
                    Danger<br><span style="font-size: 12px; opacity: 0.8;">#ef4444</span>
                </div>
            </div>
        </div>

        <!-- ========== TYPOGRAPHY ========== -->
        <div class="section">
            <h2>Typography</h2>

            <h3>Headings</h3>
            <h1 style="margin: 0;">Heading 1 - 32px</h1>
            <h2 style="margin: 16px 0 0 0;">Heading 2 - 24px</h2>
            <h3 style="margin: 16px 0 0 0;">Heading 3 - 18px</h3>
            <h4 style="margin: 16px 0 0 0;">Heading 4 - 16px</h4>

            <h3 style="margin-top: 24px;">Body Text</h3>
            <p>This is regular paragraph text. It uses a clean system font stack for excellent readability across all devices.</p>
            <p class="text-muted">This is muted text - used for secondary information like timestamps or metadata.</p>
            <p class="text-primary">This is primary text - used for emphasized content.</p>

            <h3>Text Utilities</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                <div><strong class="fw-bold">Bold text</strong></div>
                <div><span class="fw-semibold">Semibold text</span></div>
                <div><span class="fw-normal">Normal text</span></div>
                <div class="text-center">Centered text</div>
                <div class="text-right">Right aligned</div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 60px; padding: 30px; background: var(--bg-secondary); border-radius: var(--radius-lg); text-align: center;">
            <p style="margin: 0; color: var(--text-tertiary);">&copy; 2026 Modern Fintech Dashboard | All Components Ready to Use</p>
        </div>
    </div>
</body>
</html>
