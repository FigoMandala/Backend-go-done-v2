<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f5; padding: 20px; color: #333;">
    <div style="max-w-md: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        <h2 style="color: #21569A; margin-top: 0;">Password Reset Request</h2>
        <p>Hello {{ $firstName }},</p>
        <p>You recently requested to reset your password for your GoDone account. Use the OTP code below to proceed.</p>
        
        <div style="background-color: #f3f4f6; text-align: center; padding: 15px; margin: 20px 0; border-radius: 8px;">
            <h1 style="margin: 0; font-size: 36px; letter-spacing: 4px; color: #10b981;">{{ $otp }}</h1>
        </div>

        <p>This code will expire in <strong>15 minutes</strong>.</p>
        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">
        <p style="font-size: 12px; color: #6b7280; text-align: center;">&copy; {{ date('Y') }} GoDone App. All rights reserved.</p>
    </div>
</body>
</html>
