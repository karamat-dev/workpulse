<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;color:#1D2438;line-height:1.5;">
  <h2 style="margin:0 0 12px;">Welcome to WorkPulse</h2>
  <p>Hello {{ $employeeName }},</p>
  <p>Your official WorkPulse attendance account has been created.</p>
  <p><strong>Login email:</strong> {{ $email }}</p>
  <p><strong>Temporary password:</strong> {{ $password }}</p>
  <p>
    <a href="{{ $loginUrl }}" style="display:inline-block;background:#268693;color:#fff;padding:10px 14px;border-radius:6px;text-decoration:none;">Open WorkPulse</a>
  </p>
  <p>Please sign in and change your password from Account Settings.</p>
</body>
</html>
