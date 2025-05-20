<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Your Movie Ticket - Tickitz</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); margin: 20px 0;">
          <tr>
            <td style="padding: 40px 40px 20px; text-align: center; background-color: #5f2eea; border-top-left-radius: 16px; border-top-right-radius: 16px;">
              <h1 style="color: #ffffff; font-size: 24px; margin: 0;">🎬 Your Movie Ticket from Tickitz!</h1>
            </td>
          </tr>
          <tr>
            <td style="padding: 40px;">
              <p style="color: #14142b; font-size: 16px; margin: 0 0 20px; line-height: 1.5;">
                Hello, {{ $customerName }}!
              </p>
              <p style="color: #14142b; font-size: 16px; margin: 0 0 20px; line-height: 1.5;">
                Thank you for booking with Tickitz! Below are the details of your movie ticket. We can't wait to see you at the theater!
              </p>
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f9f5ff; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <tr>
                  <td>
                    <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">Booking ID: {{ $bookingId }}</p>
                    <h2 style="color: #5f2eea; font-size: 20px; margin: 0 0 15px;">{{ $movieTitle }}</h2>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px;"><strong>Cinema:</strong> {{ $cinemaName }}</p>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px;"><strong>Room:</strong> {{ $roomName }}</p>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px;"><strong>Showtime:</strong> {{ $showtime }}</p>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px;"><strong>Seats:</strong> {{ $seats }}</p>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px;"><strong>Total Price:</strong> ${{ $totalPrice }}</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="text-align: center; margin-bottom: 20px;">
                <tr>
                  <td>
                    <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">Your Ticket Barcode:</p>
                    <img src="{{ $barcodeUrl }}" alt="Ticket Barcode" style="max-width: 200px; height: auto; margin: 0 auto; display: block;">
                    <h3 style="color: #5f2eea; font-size: 18px; margin: 10px 0 0; letter-spacing: 1px;">{{ $ticketCode }}</h3>
                    <p style="color: #6b7280; font-size: 14px; margin: 10px 0 0;">Present this barcode or code at the cinema for entry.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
                <tr>
                  <td style="text-align: center;">
                    <a href="https://tickitz.com/ticket/{{ $bookingId }}" style="display: inline-block; padding: 14px 30px; background-color: #5f2eea; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 12px; transition: background-color 0.3s;">
                      View Your Ticket
                    </a>
                  </td>
                </tr>
              </table>
              <p style="color: #6b7280; font-size: 14px; margin: 20px 0 0; line-height: 1.5; text-align: center;">
                Please arrive at least 15 minutes before the showtime. Enjoy your movie experience with Tickitz!
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding: 20px 40px; background-color: #f5f5f5; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; text-align: center;">
              <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">
                © 2025 Tickitz. All rights reserved.
              </p>
              <p style="color: #6b7280; font-size: 14px; margin: 0;">
                <a href="https://tickitz.com" style="color: #5f2eea; text-decoration: none;">Visit our website</a> | 
                <a href="mailto:support@tickitz.com" style="color: #5f2eea; text-decoration: none;">Contact Support</a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
