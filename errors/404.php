<?php
// You can include config.php if you need settings
// include_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | Uzima Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 3rem;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 1rem;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: #343a40;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">Page Not Found</div>
            <p class="mb-4">The page you're looking for doesn't exist or has been moved.</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="/index.php" class="btn btn-primary me-md-2">Go to Login</a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
            </div>
        </div>
    </div>
</body>
</html> 