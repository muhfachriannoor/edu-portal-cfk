<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Sarinah - Ecommerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body { width: 100% !important; }
            .footer { width: 100% !important; }
        }

        @media only screen and (max-width: 500px) {
            .button { width: 100% !important; }
        }
    </style>
</head>
<body style="background-color: #ffffff; color: #718096; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; width: 100% !important; line-height: 1.4;">

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #edf2f7; width: 100%; margin: 0; padding: 0;">
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <!-- Header -->
                <tr>
                    <td class="header" style="padding: 25px 0; text-align: center;">
                        <a href="{{ config('app.url') }}" style="font-size: 19px; font-weight: bold; color: #3d4852; text-decoration: none;">
                            Sarinah - Ecommerce
                        </a>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td class="body" width="100%" style="background-color: #edf2f7; padding: 0;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border-radius: 2px; box-shadow: 0 2px 0 rgba(0,0,150,0.025), 2px 4px 0 rgba(0,0,150,0.015); margin: 0 auto; width: 570px;">
                            <tr>
                                <td class="content-cell" style="padding: 32px; max-width: 100vw;">
                                    <h1 style="font-size: 18px; font-weight: bold; margin-top: 0;">Dear {{ $emailAddress }},</h1>
                                    <p style="font-size: 16px; line-height: 1.5em;">{{ $body }}</p>

                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                            <td align="center" style="text-align:center;">
                                                <div style="
                                                    background-color:#2d3748;
                                                    color:#ffffff;
                                                    padding:12px 20px;
                                                    border-radius:4px;
                                                    display:inline-block;
                                                    font-size:18px;
                                                    font-weight:bold;
                                                    text-align:center;
                                                    max-width:100%;
                                                ">
                                                    {{ $token }}
                                                </div>
                                            </td>
                                        </tr>
                                    </table>


                                    <p style="font-size: 16px; line-height: 1.5em;">For security reasons, please do not share this code with anyone.
If you did not create a Sarinah account, please disregard this email.</p>
                                    <p style="font-size: 16px; line-height: 1.5em;">Warm regards, <br> Sarinah E-Commerce Team</p>

                                    {{-- <!-- Subcopy -->
                                    <table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-top: 1px solid #e8e5ef; margin-top: 25px; padding-top: 25px;">
                                        <tr>
                                            <td>
                                                <p style="font-size: 14px; line-height: 1.5em; color: #b0adc5;">
                                                    If you're having trouble clicking the "{{ $token }}" button, copy and paste the URL below into your web browser:
                                                    <a href="#" style="color: #3869d4;">#</a>
                                                </p>
                                            </td>
                                        </tr>
                                    </table> --}}

                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td>
                        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto; text-align: center;">
                            <tr>
                                <td class="content-cell" align="center" style="padding: 32px;">
                                    <p style="font-size: 12px; color: #b0adc5;">© {{ date('Y') }} Sarinah - Ecommerce. All rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
