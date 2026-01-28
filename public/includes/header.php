<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - OptionSignal' : 'OptionSignal' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if (isset($extraCss)) echo $extraCss; ?>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
