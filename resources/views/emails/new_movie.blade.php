<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Movie Alert - Tickitz</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
  <!-- Wrapper Table -->
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5;">
    <tr>
      <td align="center">
        <!-- Main Content Container -->
        <table role="presentation" width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); margin: 20px 0;">
          
          <!-- Header -->
          <tr>
            <td style="padding: 40px 40px 20px; text-align: center; background-color: #5f2eea; border-top-left-radius: 16px; border-top-right-radius: 16px;">
              <h1 style="color: #ffffff; font-size: 24px; margin: 0;">🎥 New Movie Alert from Tickitz!</h1>
            </td>
          </tr>

          <!-- Main Content -->
          <tr>
            <td style="padding: 40px;">
              <!-- Greeting -->
              <p style="color: #14142b; font-size: 16px; margin: 0 0 20px; line-height: 1.5;">
                Hello, {{ $customer_name ?? 'Valued Customer' }}!
              </p>
              <p style="color: #14142b; font-size: 16px; margin: 0 0 20px; line-height: 1.5;">
                We're thrilled to announce a new movie coming to theaters! Get ready for an unforgettable cinematic experience with Tickitz.
              </p>

              <!-- Movie Poster -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 20px;">
                <tr>
                  <td style="text-align: center;">
                    <img src="{{ $movie_poster_url ?? 'https://via.placeholder.com/300x450' }}" alt="{{ $movie_title ?? 'Movie' }} Poster" style="max-width: 100%; height: auto; border-radius: 12px;">
                  </td>
                </tr>
              </table>

              <!-- Movie Details -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f9f5ff; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <tr>
                  <td>
                    <h2 style="color: #5f2eea; font-size: 20px; margin: 0 0 15px;">{{ $movie_title ?? 'New Movie' }}</h2>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px; line-height: 1.5;">{{ $movie_description ?? 'No description available.' }}</p>
                    <p style="color: #14142b; font-size: 16px; margin: 0 0 10px;"><strong>Release Date:</strong> {{ $release_date ?? 'TBA' }}</p>
                    <p style="color: #14142b; font-size: 16px; margin: 0;"><strong>Genre:</strong> {{ $movie_genre ?? 'Unknown' }}</p>
                  </td>
                </tr>
              </table>

              <!-- Call to Action Button -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
                <tr>
                  <td style="text-align: center;">
                    <a href="http://localhost:5173/movie/{{ $movie_id ?? '' }}" style="display: inline-block; padding: 14px 30px; background-color: #5f2eea; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 12px; transition: background-color 0.3s;">
                      Book Tickets Now
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Additional Information -->
              <p style="color: #6b7280; font-size: 14px; margin: 20px 0 0; line-height: 1.5; text-align: center;">
                Don’t miss out on this exciting release! Book your tickets today and enjoy the show with Tickitz.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 20px 40px; background-color: #f5f5f5; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; text-align: center;">
              <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">
                © 2025 Tickitz. All rights reserved.
              </p>
              <p style="color: #6b7280; font-size: 14px; margin: 0;">
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