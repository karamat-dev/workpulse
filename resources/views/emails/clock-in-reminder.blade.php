<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;color:#1D2438;line-height:1.5;">
  <h2 style="margin:0 0 12px;">Clock-in reminder</h2>
  <p>Hello {{ $employeeName }},</p>
  <p>Your shift started at <strong>{{ $shiftStart }}</strong> on {{ $date }}, and muSharp does not show a clock-in yet.</p>
  <p>If you are working today and are not on approved leave, please mark your attendance.</p>
  <p>
    <a href="{{ $loginUrl }}" style="display:inline-block;background:#268693;color:#fff;padding:10px 14px;border-radius:6px;text-decoration:none;">Open muSharp</a>
  </p>
</body>
</html>
