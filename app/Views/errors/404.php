<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 90%;
        }
        .error-code {
            font-size: 72px;
            color: #e74c3c;
            margin: 0;
        }
        .error-message {
            font-size: 24px;
            color: #555;
            margin: 20px 0;
        }
        .error-description {
            color: #666;
            margin: 20px 0;
        }
        .home-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .home-link:hover {
            background-color: #2980b9;
        }
        .debug-info {
            margin-top: 2rem;
            text-align: left;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <p class="error-message">Page Not Found</p>
        <p class="error-description">
            The page you are looking for might have been removed, had its name changed,
            or is temporarily unavailable.
        </p>
        <a href="/" class="home-link">Go to Homepage</a>
        
        <?php if (isset($debug) && $debug): ?>
            <div class="debug-info">
                <h3>Debug Information</h3>
                <pre><?php print_r($debug); ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
