$content = Get-Content "index.php" -Raw
$content = $content -replace "<\?= \['api_token'\] \?>", "<?= `$_SESSION['api_token'] ?>"
Set-Content "index.php" -Value $content
