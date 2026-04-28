<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;color:#1D2438;line-height:1.5;">
  <h2 style="margin:0 0 12px;">WorkPulse deletion alert</h2>
  <p>An admin deleted or removed data in WorkPulse.</p>
  <p><strong>Type:</strong> {{ $itemType }}</p>
  <p><strong>Item:</strong> {{ $label }}</p>
  <p><strong>Deleted by:</strong> {{ $deletedBy }}</p>
  <p><strong>Deleted at:</strong> {{ $deletedAt }}</p>
  <p><strong>Super-Admin recovery available until:</strong> {{ $recoverableUntil }}</p>
  <p>Open WorkPulse as Super-Admin and go to Backups & Restore to recover it if needed.</p>
</body>
</html>
