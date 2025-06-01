<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Password Reset - Tickitz</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
  <table role="presentation" width="100%" style="background-color: #f5f5f5;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" style="background-color: #fff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin: 20px 0;">
          <tr>
            <td style="padding: 40px; text-align: center; background-color: #5f2eea; border-radius: 16px 16px 0 0;">
              <h1 style="color: #fff; font-size: 24px; margin: 0;">🔒 Password Reset Request</h1>
            </td>
          </tr>
          <tr>
            <td style="padding: 40px;">
              <p style="color: #14142b; font-size: 16px;">Hello, {{ $customerName }}!</p>
              <p style="color: #14142b; font-size: 16px;">
                Your password has been successfully reset. Below is your new temporary password. Please use it to log in and change your password in your account settings.
              </p>
              <table role="presentation" width="100%" style="background-color: #f9f5ff; border-radius: 12px; padding: 20px; text-align: center;">
                <tr>
                  <td>
                    <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">Your New Password:</p>
                    <h2 style="color: #5f2eea; font-size: 28px; letter-spacing: 2px; margin: 0;">{{ $newPassword }}</h2>
                  </td>
                </tr>
              </table>
              <p style="text-align: center; margin-top: 30px;">
                <a href="http://localhost:5173/login" style="background-color: #5f2eea; color: #fff; padding: 14px 30px; border-radius: 12px; text-decoration: none; font-weight: 600;">Log In Now</a>
              </p>
              <p style="color: #6b7280; font-size: 14px; text-align: center; margin-top: 20px;">
                For security reasons, we recommend changing your password immediately after logging in. If you did not request this reset, please contact our support team.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding: 20px; background-color: #f5f5f5; border-radius: 0 0 16px 16px; text-align: center;">
              <p style="color: #6b7280; font-size: 14px; margin: 0;">© 2025 Tickitz. All rights reserved.</p>
              <p style="color: #6b7280; font-size: 14px;">
                <a href="http://localhost:5173/" style="color: #5f2eea; text-decoration: none;">Visit our website</a> |
                <a href="mailto:ducanhb8a4@gmail.com" style="color: #5f2eea; text-decoration: none;">Contact Support</a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>