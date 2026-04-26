<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Reset Your GoDone Password</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#1e4fa3 0%,#21569A 100%);padding:36px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:800;letter-spacing:-0.5px;">🔐 GoDone</h1>
              <p style="margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;font-weight:500;">Password Reset Request</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px 44px 32px;">
              <p style="margin:0 0 12px;font-size:15px;color:#374151;font-weight:600;">Hi {{ $firstName }},</p>
              <p style="margin:0 0 28px;font-size:14px;color:#6b7280;line-height:1.7;">
                We received a request to reset the password for your GoDone account.
                Click the button below to create a new password. This link will expire in <strong style="color:#374151;">60 minutes</strong>.
              </p>

              <!-- CTA Button -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding:4px 0 32px;">
                    <a href="{{ $resetUrl }}"
                       style="display:inline-block;background:linear-gradient(135deg,#1e4fa3,#21569A);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:15px 40px;border-radius:12px;letter-spacing:0.3px;">
                      Reset My Password
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Fallback link -->
              <p style="margin:0 0 8px;font-size:12px;color:#9ca3af;">If the button doesn't work, copy and paste this link into your browser:</p>
              <p style="margin:0 0 28px;font-size:11px;color:#21569A;word-break:break-all;">{{ $resetUrl }}</p>

              <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6;">
                If you didn't request a password reset, you can safely ignore this email.
                Your password will remain unchanged.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc;padding:20px 44px;border-top:1px solid #e5e7eb;text-align:center;">
              <p style="margin:0;font-size:11px;color:#9ca3af;">&copy; {{ date('Y') }} GoDone App. All rights reserved.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
