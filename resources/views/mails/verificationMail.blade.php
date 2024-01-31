<!-- resources/views/emails/verification_email.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Email</title>
</head>
<body>
    <h1>Verification Email</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Please click the following link to verify your email:</p>
    <a href="{{ $url }}">Verify Email</a>
</body>
</html>'