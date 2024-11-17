<?php
/**
 * @var array $data Debug data array
 */

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}
?>
<style>
    #debug-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #2d3436;
        color: #dfe6e9;
        font-family: monospace;
        font-size: 14px;
        z-index: 10000;
        border-top: 2px solid #0984e3;
        transition: transform 0.3s ease;
    }
    #debug-bar.collapsed {
        transform: translateY(calc(100% - 40px));
    }
    #debug-bar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 15px;
        background: #2d3436;
        cursor: pointer;
        user-select: none;
    }
    #debug-bar-header:hover {
        background: #636e72;
    }
    #debug-bar-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #debug-bar-toggle-icon {
        transition: transform 0.3s ease;
    }
    #debug-bar.collapsed #debug-bar-toggle-icon {
        transform: rotate(180deg);
    }
    #debug-bar-content {
        border-top: 1px solid #636e72;
    }
    #debug-bar-tabs {
        display: flex;
        padding: 10px;
        border-bottom: 1px solid #636e72;
    }
    .debug-tab {
        padding: 8px 15px;
        cursor: pointer;
        margin-right: 5px;
        border-radius: 4px;
    }
    .debug-tab:hover {
        background: #636e72;
    }
    .debug-tab.active {
        background: #0984e3;
    }
    .debug-panel {
        display: none;
        padding: 15px;
        max-height: 300px;
        overflow-y: auto;
    }
    .debug-panel.active {
        display: block;
    }
    .debug-table {
        width: 100%;
        border-collapse: collapse;
    }
    .debug-table th, .debug-table td {
        padding: 8px;
        text-align: left;
        border: 1px solid #636e72;
    }
    .debug-table th {
        background: #636e72;
    }
    .debug-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 5px;
        font-size: 12px;
        background: #0984e3;
    }
    .debug-timeline {
        position: relative;
        height: 30px;
        background: #636e72;
        margin: 10px 0;
        border-radius: 3px;
    }
    .debug-timeline-point {
        position: absolute;
        width: 2px;
        height: 100%;
        background: #00b894;
    }
    .debug-timeline-label {
        position: absolute;
        transform: rotate(-45deg);
        transform-origin: left top;
        font-size: 10px;
        white-space: nowrap;
        color: #dfe6e9;
    }
</style>

<div id="debug-bar">
    <div id="debug-bar-header">
        <div id="debug-bar-toggle">
            <span id="debug-bar-toggle-icon">▼</span>
            <span>Debug Bar</span>
            <span class="debug-badge"><?= number_format($data['performance']['total_time'] * 1000, 2) ?> ms</span>
            <span class="debug-badge"><?= formatBytes($data['performance']['memory_usage']) ?></span>
        </div>
    </div>
    <div id="debug-bar-content">
        <div id="debug-bar-tabs">
            <div class="debug-tab active" data-panel="performance">
                Performance
                <span class="debug-badge"><?= number_format($data['performance']['total_time'] * 1000, 2) ?> ms</span>
            </div>
            <div class="debug-tab" data-panel="database">
                Database
                <span class="debug-badge"><?= count($data['queries']) ?></span>
            </div>
            <div class="debug-tab" data-panel="request">Request</div>
            <div class="debug-tab" data-panel="server">Server</div>
        </div>

        <div class="debug-panel active" id="performance-panel">
            <h3>Timeline</h3>
            <div class="debug-timeline">
                <?php foreach ($data['timeline'] as $point): ?>
                    <div class="debug-timeline-point" style="left: <?= ($point['duration'] / $data['performance']['total_time']) * 100 ?>%">
                        <div class="debug-timeline-label"><?= $point['label'] ?> (<?= number_format($point['duration'] * 1000, 2) ?> ms)</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3>Memory Usage</h3>
            <table class="debug-table">
                <tr>
                    <th>Label</th>
                    <th>Memory</th>
                    <th>Peak</th>
                </tr>
                <?php foreach ($data['memory'] as $point): ?>
                    <tr>
                        <td><?= $point['label'] ?></td>
                        <td><?= formatBytes($point['memory']) ?></td>
                        <td><?= formatBytes($point['peak']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="debug-panel" id="database-panel">
            <table class="debug-table">
                <tr>
                    <th>Query</th>
                    <th>Parameters</th>
                    <th>Time</th>
                </tr>
                <?php foreach ($data['queries'] as $query): ?>
                    <tr>
                        <td><?= htmlspecialchars($query['query']) ?></td>
                        <td><?= $query['params'] ? htmlspecialchars(json_encode($query['params'])) : 'None' ?></td>
                        <td><?= number_format($query['time'] * 1000, 2) ?> ms</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="debug-panel" id="request-panel">
            <h3>Request Details</h3>
            <table class="debug-table">
                <?php foreach ($data['request'] as $key => $value): ?>
                    <tr>
                        <th><?= ucfirst($key) ?></th>
                        <td><pre><?= is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : htmlspecialchars($value) ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="debug-panel" id="server-panel">
            <h3>Server Information</h3>
            <table class="debug-table">
                <?php foreach ($data['server'] as $key => $value): ?>
                    <tr>
                        <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                        <td><pre><?= is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : htmlspecialchars($value) ?></pre></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.debug-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            // Update active tab
            document.querySelectorAll('.debug-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Update active panel
            document.querySelectorAll('.debug-panel').forEach(p => p.classList.remove('active'));
            document.getElementById(tab.dataset.panel + '-panel').classList.add('active');
        });
    });

    // Collapse/Expand functionality
    const debugBar = document.getElementById('debug-bar');
    const debugBarHeader = document.getElementById('debug-bar-header');
    
    debugBarHeader.addEventListener('click', () => {
        debugBar.classList.toggle('collapsed');
        localStorage.setItem('debug-bar-collapsed', debugBar.classList.contains('collapsed'));
    });

    // Restore previous state
    if (localStorage.getItem('debug-bar-collapsed') === 'true') {
        debugBar.classList.add('collapsed');
    }
});
</script>
