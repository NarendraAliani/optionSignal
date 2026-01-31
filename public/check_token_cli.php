<?php
date_default_timezone_set('Asia/Kolkata');

$token = "eyJhbGciOiJIUzUxMiJ9.eyJ1c2VybmFtZSI6Ik0xNTY1NDgiLCJyb2xlcyI6MCwidXNlcnR5cGUiOiJVU0VSIiwidG9rZW4iOiJleUpoYkdjaU9pSlNVekkxTmlJc0luUjVjQ0k2SWtwWFZDSjkuZXlKMWMyVnlYM1I1Y0dVaU9pSmpiR2xsYm5RaUxDSjBiMnRsYmw5MGVYQmxJam9pZEhKaFpHVmZZV05qWlhOelgzUnZhMlZ1SWl3aVoyMWZhV1FpT2pZc0luTnZkWEpqWlNJNklqTWlMQ0prWlhacFkyVmZhV1FpT2lJNE56ZzVZMlpqWXkxaFkyVTFMVE01T0dJdFlXSTFNaTAzWVRWaVpXTTRZemhpT1RraUxDSnJhV1FpT2lKMGNtRmtaVjlyWlhsZmRqSWlMQ0p2Ylc1bGJXRnVZV2RsY21sa0lqbzJMQ0p3Y205a2RXTjBjeUk2ZXlKa1pXMWhkQ0k2ZXlKemRHRjBkWE1pT2lKaFkzUnBkbVVpZlN3aWJXWWlPbnNpYzNSaGRIVnpJam9pWVdOMGFYWmxJbjBzSW01aWRVeGxibVJwYm1jaU9uc2ljM1JoZEhWeklqb2lZV04wYVhabEluMTlMQ0pwYzNNaU9pSjBjbUZrWlY5c2IyZHBibDl6WlhKMmFXTmxJaXdpYzNWaUlqb2lUVEUxTmpVME9DSXNJbVY0Y0NJNk1UYzJPVGcxTkRBd01pd2libUptSWpveE56WTVOelkzTkRJeUxDSnBZWFFpT2pFM05qazNOamMwTWpJc0ltcDBhU0k2SWprd1kyUTBaRFl4TFdWa1l6a3RORFUxWWkxaE1qVmlMV001WkRJMk9URTJNV05oWlNJc0lsUnZhMlZ1SWpvaUluMC5GQWhLN2ZyQ2VCV1hNV1ZYcVdBamUzdFlncjY3VHdSakpkZ0o3T0RMdGgxR1YwTmE4X1NPZzJva1J5OUNOcEpHQ2lQTWVCVm5kX3VtMTJ6TXo5NWVENi1GbnphdWFDbXlFb1dCemcwYzBhb3ZrR3NHMTU1azdmTXBHZ2hpVV8zV0tySnZXU0ZVRXRfMzBKNUpGNDUzVkdITUN2ZEdWWXc0cUplNWlaY3VNMm8iLCJBUEktS0VZIjoiQlpXN29wYTMiLCJYLU9MRC1BUEktS0VZIjp0cnVlLCJpYXQiOjE3Njk3Njc2MDIsImV4cCI6MTc2OTc5NzgwMH0.HJLDcreNh_7tjGh-Zmgm9EC-y_R9waFRgc_5YrzAxDGQn1p0su2hbH-cwU0wWnRHUF_yw8DjXYIPsbXjJ02DZg";

$parts = explode('.', $token);
if (count($parts) != 3) {
    die("Invalid Token Format");
}

$payload = json_decode(base64_decode($parts[1]), true);

echo "Token Details:\n";
print_r($payload);

if (isset($payload['exp'])) {
    echo "\nExpires At: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
    echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
    
    if ($payload['exp'] < time()) {
        echo "❌ Token is EXPIRED!\n";
    } else {
        echo "✅ Token is VALID.\n";
    }
}
?>
