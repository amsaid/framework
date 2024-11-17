<?php
/**
 * @var Throwable $exception The caught exception
 * @var bool $debugMode Whether debug mode is enabled
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error: <?= htmlspecialchars($exception->getMessage()) ?></title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --text-color: #e1e1e1;
            --border-color: #333;
            --accent-color: #0984e3;
            --error-color: #d63031;
            --success-color: #00b894;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: var(--bg-color);
            color: var(--text-color);
        }
        
        .error-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .error-header {
            background: var(--error-color);
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }
        
        .error-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .error-message {
            margin: 10px 0 0;
            font-size: 18px;
            opacity: 0.9;
        }
        
        .error-details {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stack-trace {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 14px;
            line-height: 1.4;
            white-space: pre-wrap;
        }
        
        .code-preview {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .line-numbers {
            float: left;
            padding-right: 10px;
            border-right: 1px solid var(--border-color);
            margin-right: 10px;
            color: #666;
            user-select: none;
        }
        
        .code-line {
            padding: 0 5px;
            min-height: 1.4em;
        }
        
        .error-line {
            background: rgba(214, 48, 49, 0.2);
            margin: 0 -15px;
            padding: 0 15px;
            border-left: 3px solid var(--error-color);
        }
        
        .code-context {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            cursor: pointer;
            font-size: 14px;
        }
        
        .tab-button.active {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .tab-content {
            display: none;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid var(--border-color);
        }
        
        .data-table th {
            background: rgba(0, 0, 0, 0.2);
        }
        
        .data-key {
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .file-path {
            color: var(--accent-color);
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <h1 class="error-title"><?= get_class($exception) ?></h1>
            <p class="error-message"><?= htmlspecialchars($exception->getMessage()) ?></p>
        </div>

        <div class="error-details">
            <p>
                <strong>File:</strong>
                <span class="file-path"><?= $exception->getFile() ?></span>
                on line <?= $exception->getLine() ?>
            </p>

            <?php
            // Get the file contents around the error line
            $file = file($exception->getFile());
            $line = $exception->getLine();
            $start = max(0, $line - 5);
            $end = min(count($file), $line + 5);
            ?>

            <div class="code-preview">
                <div class="code-context">
                    <div class="line-numbers">
                        <?php for ($i = $start; $i < $end; $i++): ?>
                            <div class="line-number"><?= $i + 1 ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="code-lines">
                        <?php for ($i = $start; $i < $end; $i++): ?>
                            <div class="code-line <?= ($i + 1) == $line ? 'error-line' : '' ?>">
                                <?= htmlspecialchars($file[$i]) ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="stack-trace">Stack Trace</button>
                <button class="tab-button" data-tab="request">Request</button>
                <button class="tab-button" data-tab="server">Server</button>
                <?php if (isset($_SESSION)): ?>
                    <button class="tab-button" data-tab="session">Session</button>
                <?php endif; ?>
            </div>

            <div class="tab-content active" id="stack-trace">
                <div class="stack-trace">
                    <?= nl2br(htmlspecialchars($exception->getTraceAsString())) ?>
                </div>
            </div>

            <div class="tab-content" id="request">
                <table class="data-table">
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td class="data-key">URL</td>
                        <td><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="data-key">Method</td>
                        <td><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="data-key">GET Data</td>
                        <td><pre><?= htmlspecialchars(print_r($_GET, true)) ?></pre></td>
                    </tr>
                    <tr>
                        <td class="data-key">POST Data</td>
                        <td><pre><?= htmlspecialchars(print_r($_POST, true)) ?></pre></td>
                    </tr>
                    <tr>
                        <td class="data-key">Headers</td>
                        <td><pre><?= htmlspecialchars(print_r(getallheaders(), true)) ?></pre></td>
                    </tr>
                </table>
            </div>

            <div class="tab-content" id="server">
                <table class="data-table">
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                    <?php foreach ($_SERVER as $key => $value): ?>
                        <tr>
                            <td class="data-key"><?= htmlspecialchars($key) ?></td>
                            <td><pre><?= htmlspecialchars(print_r($value, true)) ?></pre></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php if (isset($_SESSION)): ?>
                <div class="tab-content" id="session">
                    <table class="data-table">
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                        </tr>
                        <?php foreach ($_SESSION as $key => $value): ?>
                            <tr>
                                <td class="data-key"><?= htmlspecialchars($key) ?></td>
                                <td><pre><?= htmlspecialchars(print_r($value, true)) ?></pre></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                button.classList.add('active');
                document.getElementById(button.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>
