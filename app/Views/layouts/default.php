<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'My Application' ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
        }
        p {
            color: #666;
        }
       
    </style>
    <?= $this->section('head') ?? '' ?>
    
</head>
<body>
    <div class="container">
        <?= $content ?? '' ?>
    </div>
    <?= $this->section('scripts') ?? '' ?>
</body>
</html>
